// =====================================================
// PROFILE.JS - Profile Route Handlers
// =====================================================
// Handles user profile operations:
// - Get user profile
// - Update profile info
// - Get user's auctions (my auctions)
// - Get user's bids (my bids)
// =====================================================


// -----------------------------------------------------
// Import dependencies
// -----------------------------------------------------

const db = require('../config/database');
const response = require('../utils/response');
const validation = require('../utils/validation');


// -----------------------------------------------------
// GET /api/profile - Get current user's profile
// -----------------------------------------------------
// Returns user information including stats
// (auction count, bid count, won auctions)

async function getProfile(req, res) {
    try {
        // TODO: Get actual user ID from auth middleware
        // For testing, use buyer1 (id: 3)
        const userId = req.user?.id || 3;
        
        // -------------------------------------------------
        // Get user basic info
        // -------------------------------------------------
        // Don't return password_hash for security
        
        const userSql = `
            SELECT 
                id,
                username,
                email,
                full_name,
                role,
                avatar_path,
                status,
                created_at,
                updated_at
            FROM users
            WHERE id = ?
        `;
        
        const [userRows] = await db.query(userSql, [userId]);
        
        if (userRows.length === 0) {
            response.notFound(res, 'User not found');
            return;
        }
        
        const user = userRows[0];
        
        // -------------------------------------------------
        // Get user statistics
        // -------------------------------------------------
        
        // Count auctions created by user (if seller)
        const [auctionCount] = await db.query(
            'SELECT COUNT(*) as count FROM auctions WHERE seller_id = ?',
            [userId]
        );
        
        // Count bids placed by user
        const [bidCount] = await db.query(
            'SELECT COUNT(*) as count FROM bids WHERE user_id = ?',
            [userId]
        );
        
        // Count auctions won by user
        const [wonCount] = await db.query(
            'SELECT COUNT(*) as count FROM auctions WHERE winner_id = ?',
            [userId]
        );
        
        // Count active bids (auctions user is participating in that are still active)
        const [activeBidCount] = await db.query(
            `SELECT COUNT(DISTINCT b.auction_id) as count 
             FROM bids b
             JOIN auctions a ON b.auction_id = a.id
             WHERE b.user_id = ? AND a.status = 'active'`,
            [userId]
        );
        
        // -------------------------------------------------
        // Build response
        // -------------------------------------------------
        
        response.sendSuccess(res, {
            user: user,
            stats: {
                auctionsCreated: auctionCount[0].count,
                totalBids: bidCount[0].count,
                auctionsWon: wonCount[0].count,
                activeBids: activeBidCount[0].count
            }
        }, 'Profile retrieved successfully');
        
    } catch (error) {
        console.error('Error in getProfile:', error);
        response.serverError(res, 'Failed to retrieve profile');
    }
}


// -----------------------------------------------------
// PUT /api/profile - Update user profile
// -----------------------------------------------------
// Updatable fields:
//   - full_name
//   - email
//   - avatar_path
//
// Password change is handled separately for security

async function updateProfile(req, res) {
    try {
        // TODO: Get actual user ID from auth
        const userId = req.user?.id || 3;
        
        const { full_name, email, avatar_path } = req.body;
        
        // -------------------------------------------------
        // Check if user exists
        // -------------------------------------------------
        
        const [userRows] = await db.query(
            'SELECT * FROM users WHERE id = ?',
            [userId]
        );
        
        if (userRows.length === 0) {
            response.notFound(res, 'User not found');
            return;
        }
        
        // -------------------------------------------------
        // Validate email if provided
        // -------------------------------------------------
        
        if (email !== undefined && !validation.isEmpty(email)) {
            if (!validation.isValidEmail(email)) {
                response.sendError(res, 'Invalid email format', 400);
                return;
            }
            
            // Check if email is already used by another user
            const [existingEmail] = await db.query(
                'SELECT id FROM users WHERE email = ? AND id != ?',
                [email, userId]
            );
            
            if (existingEmail.length > 0) {
                response.sendError(res, 'Email is already in use', 400);
                return;
            }
        }
        
        // -------------------------------------------------
        // Build UPDATE query dynamically
        // -------------------------------------------------
        
        const updates = [];
        const updateParams = [];
        
        if (full_name !== undefined) {
            updates.push('full_name = ?');
            updateParams.push(full_name);
        }
        
        if (email !== undefined && !validation.isEmpty(email)) {
            updates.push('email = ?');
            updateParams.push(email);
        }
        
        if (avatar_path !== undefined) {
            updates.push('avatar_path = ?');
            updateParams.push(avatar_path);
        }
        
        // If nothing to update
        if (updates.length === 0) {
            response.sendError(res, 'No fields to update', 400);
            return;
        }
        
        // Add user ID to params
        updateParams.push(userId);
        
        // -------------------------------------------------
        // Execute UPDATE
        // -------------------------------------------------
        
        const updateSql = `UPDATE users SET ${updates.join(', ')} WHERE id = ?`;
        await db.query(updateSql, updateParams);
        
        // -------------------------------------------------
        // Return updated user
        // -------------------------------------------------
        
        const [updatedUser] = await db.query(
            `SELECT id, username, email, full_name, role, avatar_path, status, created_at, updated_at
             FROM users WHERE id = ?`,
            [userId]
        );
        
        response.sendSuccess(res, { user: updatedUser[0] }, 'Profile updated successfully');
        
    } catch (error) {
        console.error('Error in updateProfile:', error);
        response.serverError(res, 'Failed to update profile');
    }
}


// -----------------------------------------------------
// GET /api/my/auctions - Get user's auctions
// -----------------------------------------------------
// Returns all auctions created by the current user
// Used in "My Auctions" page

async function getMyAuctions(req, res) {
    try {
        // TODO: Get actual user ID from auth
        const userId = req.user?.id || 2; // Default to seller1 for testing
        
        // Get query parameters for filtering
        const { status, page, limit } = req.query;
        
        // -------------------------------------------------
        // Build query
        // -------------------------------------------------
        
        let sql = `
            SELECT 
                a.*,
                (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) AS bid_count,
                (SELECT MAX(amount) FROM bids WHERE auction_id = a.id) AS highest_bid
            FROM auctions a
            WHERE a.seller_id = ?
        `;
        
        const params = [userId];
        
        // Filter by status if provided
        if (status && status !== '') {
            sql += ` AND a.status = ?`;
            params.push(status);
        }
        
        // Order by newest first
        sql += ` ORDER BY a.created_at DESC`;
        
        // -------------------------------------------------
        // Pagination
        // -------------------------------------------------
        
        const pageNum = parseInt(page) || 1;
        const limitNum = parseInt(limit) || 10;
        const offset = (pageNum - 1) * limitNum;
        
        sql += ` LIMIT ? OFFSET ?`;
        params.push(limitNum, offset);
        
        // -------------------------------------------------
        // Execute query
        // -------------------------------------------------
        
        const [auctions] = await db.query(sql, params);
        
        // Get total count
        let countSql = `SELECT COUNT(*) as total FROM auctions WHERE seller_id = ?`;
        const countParams = [userId];
        
        if (status && status !== '') {
            countSql += ` AND status = ?`;
            countParams.push(status);
        }
        
        const [countResult] = await db.query(countSql, countParams);
        const totalItems = countResult[0].total;
        const totalPages = Math.ceil(totalItems / limitNum);
        
        // -------------------------------------------------
        // Response
        // -------------------------------------------------
        
        response.sendSuccess(res, {
            auctions: auctions,
            pagination: {
                currentPage: pageNum,
                totalPages: totalPages,
                totalItems: totalItems,
                itemsPerPage: limitNum
            }
        }, 'Your auctions retrieved successfully');
        
    } catch (error) {
        console.error('Error in getMyAuctions:', error);
        response.serverError(res, 'Failed to retrieve your auctions');
    }
}


// -----------------------------------------------------
// GET /api/my/bids - Get user's bids
// -----------------------------------------------------
// Returns all bids placed by the current user
// Grouped into: active, won, lost
// Used in "My Bids" page

async function getMyBids(req, res) {
    try {
        // TODO: Get actual user ID from auth
        const userId = req.user?.id || 3; // Default to buyer1 for testing
        
        // -------------------------------------------------
        // Get all auctions user has bid on
        // -------------------------------------------------
        // We need to get unique auctions, user's highest bid,
        // and current auction status
        
        const sql = `
            SELECT 
                a.id AS auction_id,
                a.title,
                a.image_path,
                a.current_price,
                a.end_time,
                a.status AS auction_status,
                a.winner_id,
                MAX(b.amount) AS my_highest_bid,
                MAX(b.created_at) AS last_bid_time
            FROM bids b
            JOIN auctions a ON b.auction_id = a.id
            WHERE b.user_id = ?
            GROUP BY a.id
            ORDER BY b.created_at DESC
        `;
        
        const [bidRecords] = await db.query(sql, [userId]);
        
        // -------------------------------------------------
        // Categorize bids
        // -------------------------------------------------
        // active: Auction is still active
        // won: Auction ended and user is winner
        // lost: Auction ended and user is not winner
        // outbid: Active but user is not highest bidder
        
        const activeBids = [];
        const wonBids = [];
        const lostBids = [];
        
        for (const record of bidRecords) {
            // Determine if user is currently leading
            const isLeading = parseFloat(record.my_highest_bid) >= parseFloat(record.current_price);
            
            // Add status info to record
            const bidInfo = {
                auction_id: record.auction_id,
                title: record.title,
                image_path: record.image_path,
                current_price: record.current_price,
                my_highest_bid: record.my_highest_bid,
                end_time: record.end_time,
                last_bid_time: record.last_bid_time,
                is_leading: isLeading
            };
            
            // Categorize based on auction status
            if (record.auction_status === 'active') {
                // Active auction
                bidInfo.bid_status = isLeading ? 'leading' : 'outbid';
                activeBids.push(bidInfo);
            } else if (record.auction_status === 'ended') {
                // Ended auction
                if (record.winner_id === userId) {
                    bidInfo.bid_status = 'won';
                    wonBids.push(bidInfo);
                } else {
                    bidInfo.bid_status = 'lost';
                    lostBids.push(bidInfo);
                }
            }
        }
        
        // -------------------------------------------------
        // Get summary stats
        // -------------------------------------------------
        
        const stats = {
            totalBidsPlaced: bidRecords.length,
            activeBids: activeBids.length,
            leading: activeBids.filter(b => b.bid_status === 'leading').length,
            outbid: activeBids.filter(b => b.bid_status === 'outbid').length,
            won: wonBids.length,
            lost: lostBids.length
        };
        
        // -------------------------------------------------
        // Response
        // -------------------------------------------------
        
        response.sendSuccess(res, {
            active: activeBids,
            won: wonBids,
            lost: lostBids,
            stats: stats
        }, 'Your bids retrieved successfully');
        
    } catch (error) {
        console.error('Error in getMyBids:', error);
        response.serverError(res, 'Failed to retrieve your bids');
    }
}


// -----------------------------------------------------
// Export all functions
// -----------------------------------------------------

module.exports = {
    getProfile,
    updateProfile,
    getMyAuctions,
    getMyBids
};