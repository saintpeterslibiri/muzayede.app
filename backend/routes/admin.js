// =====================================================
// ADMIN.JS - Admin Route Handlers
// =====================================================
// Handles admin-only operations:
// - Get dashboard statistics
// - Get all users
// - Ban/unban users
// - Delete any auction
// =====================================================


// -----------------------------------------------------
// Import dependencies
// -----------------------------------------------------

const db = require('../config/database');
const response = require('../utils/response');
const validation = require('../utils/validation');


// -----------------------------------------------------
// Admin check helper function
// -----------------------------------------------------
// Verifies if the current user is an admin
// Returns true if admin, false otherwise

async function isAdmin(userId) {
    try {
        const [rows] = await db.pool.query(
            'SELECT role FROM users WHERE id = ?',
            [userId]
        );
        
        if (rows.length === 0) {
            return false;
        }
        
        return rows[0].role === 'admin';
    } catch (error) {
        console.error('Error checking admin status:', error);
        return false;
    }
}


// -----------------------------------------------------
// GET /api/admin/stats - Get dashboard statistics
// -----------------------------------------------------
// Returns overall platform statistics:
// - Total users (by role)
// - Total auctions (by status)
// - Total bids
// - Revenue stats

async function getStats(req, res) {
    try {
        // TODO: Get actual user ID from auth
        const userId = req.user?.id || 1; // Default to admin for testing
        
        // -------------------------------------------------
        // Verify admin access
        // -------------------------------------------------
        
        const adminCheck = await isAdmin(userId);
        if (!adminCheck) {
            response.forbidden(res, 'Admin access required');
            return;
        }
        
        // -------------------------------------------------
        // Get user statistics
        // -------------------------------------------------
        
        // Total users
        const [totalUsers] = await db.pool.query(
            'SELECT COUNT(*) as count FROM users'
        );
        
        // Users by role
        const [usersByRole] = await db.pool.query(
            `SELECT role, COUNT(*) as count 
             FROM users 
             GROUP BY role`
        );
        
        // Users by status
        const [usersByStatus] = await db.pool.query(
            `SELECT status, COUNT(*) as count 
             FROM users 
             GROUP BY status`
        );
        
        // New users this month
        const [newUsersThisMonth] = await db.pool.query(
            `SELECT COUNT(*) as count 
             FROM users 
             WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')`
        );
        
        // -------------------------------------------------
        // Get auction statistics
        // -------------------------------------------------
        
        // Total auctions
        const [totalAuctions] = await db.pool.query(
            'SELECT COUNT(*) as count FROM auctions'
        );
        
        // Auctions by status
        const [auctionsByStatus] = await db.pool.query(
            `SELECT status, COUNT(*) as count 
             FROM auctions 
             GROUP BY status`
        );
        
        // Auctions by category
        const [auctionsByCategory] = await db.pool.query(
            `SELECT category, COUNT(*) as count 
             FROM auctions 
             GROUP BY category`
        );
        
        // Auctions created this month
        const [auctionsThisMonth] = await db.pool.query(
            `SELECT COUNT(*) as count 
             FROM auctions 
             WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')`
        );
        
        // -------------------------------------------------
        // Get bid statistics
        // -------------------------------------------------
        
        // Total bids
        const [totalBids] = await db.pool.query(
            'SELECT COUNT(*) as count FROM bids'
        );
        
        // Bids this month
        const [bidsThisMonth] = await db.pool.query(
            `SELECT COUNT(*) as count 
             FROM bids 
             WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')`
        );
        
        // Average bids per auction
        const [avgBidsPerAuction] = await db.pool.query(
            `SELECT AVG(bid_count) as average FROM (
                SELECT COUNT(*) as bid_count 
                FROM bids 
                GROUP BY auction_id
             ) as bid_counts`
        );
        
        // -------------------------------------------------
        // Get financial statistics
        // -------------------------------------------------
        
        // Total value of all current prices (active auctions)
        const [totalActiveValue] = await db.pool.query(
            `SELECT SUM(current_price) as total 
             FROM auctions 
             WHERE status = 'active'`
        );
        
        // Total value of ended auctions (completed sales)
        const [totalSalesValue] = await db.pool.query(
            `SELECT SUM(current_price) as total 
             FROM auctions 
             WHERE status = 'ended' AND winner_id IS NOT NULL`
        );
        
        // Highest bid ever
        const [highestBid] = await db.pool.query(
            'SELECT MAX(amount) as amount FROM bids'
        );
        
        // -------------------------------------------------
        // Get recent activity
        // -------------------------------------------------
        
        // Recent auctions (last 5)
        const [recentAuctions] = await db.pool.query(
            `SELECT a.id, a.title, a.status, a.created_at, u.username as seller
             FROM auctions a
             JOIN users u ON a.seller_id = u.id
             ORDER BY a.created_at DESC
             LIMIT 5`
        );
        
        // Recent bids (last 5)
        const [recentBids] = await db.pool.query(
            `SELECT b.amount, b.created_at, u.username, a.title as auction_title
             FROM bids b
             JOIN users u ON b.user_id = u.id
             JOIN auctions a ON b.auction_id = a.id
             ORDER BY b.created_at DESC
             LIMIT 5`
        );
        
        // -------------------------------------------------
        // Build response
        // -------------------------------------------------
        
        // Convert arrays to objects for easier access
        const roleStats = {};
        usersByRole.forEach(row => {
            roleStats[row.role] = row.count;
        });
        
        const statusStats = {};
        usersByStatus.forEach(row => {
            statusStats[row.status] = row.count;
        });
        
        const auctionStatusStats = {};
        auctionsByStatus.forEach(row => {
            auctionStatusStats[row.status] = row.count;
        });
        
        const categoryStats = {};
        auctionsByCategory.forEach(row => {
            categoryStats[row.category] = row.count;
        });
        
        response.sendSuccess(res, {
            users: {
                total: totalUsers[0].count,
                byRole: roleStats,
                byStatus: statusStats,
                newThisMonth: newUsersThisMonth[0].count
            },
            auctions: {
                total: totalAuctions[0].count,
                byStatus: auctionStatusStats,
                byCategory: categoryStats,
                createdThisMonth: auctionsThisMonth[0].count
            },
            bids: {
                total: totalBids[0].count,
                thisMonth: bidsThisMonth[0].count,
                averagePerAuction: parseFloat(avgBidsPerAuction[0].average) || 0
            },
            financial: {
                activeAuctionsValue: parseFloat(totalActiveValue[0].total) || 0,
                completedSalesValue: parseFloat(totalSalesValue[0].total) || 0,
                highestBidEver: parseFloat(highestBid[0].amount) || 0
            },
            recentActivity: {
                auctions: recentAuctions,
                bids: recentBids
            }
        }, 'Dashboard statistics retrieved successfully');
        
    } catch (error) {
        console.error('Error in getStats:', error);
        response.serverError(res, 'Failed to retrieve statistics');
    }
}


// -----------------------------------------------------
// GET /api/admin/users - Get all users
// -----------------------------------------------------
// Returns list of all users with their stats
// Supports pagination and filtering

async function getAllUsers(req, res) {
    try {
        // TODO: Get actual user ID from auth
        const userId = req.user?.id || 1;
        
        // Verify admin access
        const adminCheck = await isAdmin(userId);
        if (!adminCheck) {
            response.forbidden(res, 'Admin access required');
            return;
        }
        
        // Get query parameters
        const { role, status, search, page, limit } = req.query;
        
        // -------------------------------------------------
        // Build query
        // -------------------------------------------------
        
        let sql = `
            SELECT 
                u.id,
                u.username,
                u.email,
                u.full_name,
                u.role,
                u.status,
                u.created_at,
                u.updated_at,
                (SELECT COUNT(*) FROM auctions WHERE seller_id = u.id) AS auction_count,
                (SELECT COUNT(*) FROM bids WHERE user_id = u.id) AS bid_count
            FROM users u
            WHERE 1=1
        `;
        
        const params = [];
        
        // Filter by role
        if (role && role !== '') {
            sql += ` AND u.role = ?`;
            params.push(role);
        }
        
        // Filter by status
        if (status && status !== '') {
            sql += ` AND u.status = ?`;
            params.push(status);
        }
        
        // Search by username or email
        if (search && search !== '') {
            sql += ` AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)`;
            const searchPattern = `%${search}%`;
            params.push(searchPattern, searchPattern, searchPattern);
        }
        
        // Order by newest first
        sql += ` ORDER BY u.created_at DESC`;
        
        // -------------------------------------------------
        // Pagination
        // -------------------------------------------------
        
        const pageNum = parseInt(page) || 1;
        const limitNum = parseInt(limit) || 20;
        const offset = (pageNum - 1) * limitNum;
        
        sql += ` LIMIT ? OFFSET ?`;
        params.push(limitNum, offset);
        
        // -------------------------------------------------
        // Execute query
        // -------------------------------------------------
        
        const [users] = await db.pool.query(sql, params);
        
        // Get total count for pagination
        let countSql = 'SELECT COUNT(*) as total FROM users u WHERE 1=1';
        const countParams = [];
        
        if (role && role !== '') {
            countSql += ` AND u.role = ?`;
            countParams.push(role);
        }
        
        if (status && status !== '') {
            countSql += ` AND u.status = ?`;
            countParams.push(status);
        }
        
        if (search && search !== '') {
            countSql += ` AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)`;
            const searchPattern = `%${search}%`;
            countParams.push(searchPattern, searchPattern, searchPattern);
        }
        
        const [countResult] = await db.pool.query(countSql, countParams);
        const totalItems = countResult[0].total;
        const totalPages = Math.ceil(totalItems / limitNum);
        
        // -------------------------------------------------
        // Response
        // -------------------------------------------------
        
        response.sendSuccess(res, {
            users: users,
            pagination: {
                currentPage: pageNum,
                totalPages: totalPages,
                totalItems: totalItems,
                itemsPerPage: limitNum
            }
        }, 'Users retrieved successfully');
        
    } catch (error) {
        console.error('Error in getAllUsers:', error);
        response.serverError(res, 'Failed to retrieve users');
    }
}


// -----------------------------------------------------
// PUT /api/admin/users/:id/ban - Ban or unban a user
// -----------------------------------------------------
// Toggles user status between 'active' and 'banned'
// Banned users cannot log in or perform actions

async function banUser(req, res) {
    try {
        // TODO: Get actual user ID from auth
        const adminId = req.user?.id || 1;
        const targetUserId = req.params.id;
        
        // Verify admin access
        const adminCheck = await isAdmin(adminId);
        if (!adminCheck) {
            response.forbidden(res, 'Admin access required');
            return;
        }
        
        // Validate target user ID
        if (!validation.isValidNumber(targetUserId)) {
            response.sendError(res, 'Invalid user ID', 400);
            return;
        }
        
        // -------------------------------------------------
        // Check if target user exists
        // -------------------------------------------------
        
        const [userRows] = await db.pool.query(
            'SELECT * FROM users WHERE id = ?',
            [targetUserId]
        );
        
        if (userRows.length === 0) {
            response.notFound(res, 'User not found');
            return;
        }
        
        const targetUser = userRows[0];
        
        // -------------------------------------------------
        // Prevent admin from banning themselves or other admins
        // -------------------------------------------------
        
        if (parseInt(targetUserId) === adminId) {
            response.sendError(res, 'You cannot ban yourself', 400);
            return;
        }
        
        if (targetUser.role === 'admin') {
            response.sendError(res, 'Cannot ban other admins', 400);
            return;
        }
        
        // -------------------------------------------------
        // Toggle ban status
        // -------------------------------------------------
        
        // If currently active, ban them
        // If currently banned, unban them
        const newStatus = targetUser.status === 'banned' ? 'active' : 'banned';
        
        await db.pool.query(
            'UPDATE users SET status = ? WHERE id = ?',
            [newStatus, targetUserId]
        );
        
        // -------------------------------------------------
        // If banning, also cancel their active auctions
        // -------------------------------------------------
        
        if (newStatus === 'banned') {
            await db.pool.query(
                `UPDATE auctions SET status = 'cancelled' 
                 WHERE seller_id = ? AND status IN ('pending', 'active')`,
                [targetUserId]
            );
        }
        
        // Get updated user
        const [updatedUser] = await db.pool.query(
            `SELECT id, username, email, full_name, role, status, created_at 
             FROM users WHERE id = ?`,
            [targetUserId]
        );
        
        const actionText = newStatus === 'banned' ? 'banned' : 'unbanned';
        
        response.sendSuccess(res, {
            user: updatedUser[0],
            action: actionText
        }, `User ${actionText} successfully`);
        
    } catch (error) {
        console.error('Error in banUser:', error);
        response.serverError(res, 'Failed to update user status');
    }
}


// -----------------------------------------------------
// DELETE /api/admin/auctions/:id - Delete any auction
// -----------------------------------------------------
// Admin can delete any auction regardless of ownership
// Also deletes associated bids

async function deleteAuction(req, res) {
    try {
        // TODO: Get actual user ID from auth
        const adminId = req.user?.id || 1;
        const auctionId = req.params.id;
        
        // Verify admin access
        const adminCheck = await isAdmin(adminId);
        if (!adminCheck) {
            response.forbidden(res, 'Admin access required');
            return;
        }
        
        // Validate auction ID
        if (!validation.isValidNumber(auctionId)) {
            response.sendError(res, 'Invalid auction ID', 400);
            return;
        }
        
        // -------------------------------------------------
        // Check if auction exists
        // -------------------------------------------------
        
        const [auctionRows] = await db.pool.query(
            'SELECT * FROM auctions WHERE id = ?',
            [auctionId]
        );
        
        if (auctionRows.length === 0) {
            response.notFound(res, 'Auction not found');
            return;
        }
        
        const auction = auctionRows[0];
        
        // -------------------------------------------------
        // Delete auction
        // -------------------------------------------------
        // Foreign key with ON DELETE CASCADE will automatically
        // delete associated bids and auto_bids
        
        await db.pool.query('DELETE FROM auctions WHERE id = ?', [auctionId]);
        
        response.sendSuccess(res, {
            deletedAuction: {
                id: auction.id,
                title: auction.title
            }
        }, 'Auction deleted successfully');
        
    } catch (error) {
        console.error('Error in deleteAuction:', error);
        response.serverError(res, 'Failed to delete auction');
    }
}


// -----------------------------------------------------
// Export all functions
// -----------------------------------------------------

module.exports = {
    getStats,
    getAllUsers,
    banUser,
    deleteAuction
};