// =====================================================
// BIDS.JS - Bid Route Handlers
// =====================================================
// Handles all bid-related operations:
// - Get bids for an auction
// - Place a new bid
// - Set auto-bid
// - Process auto-bid logic
// =====================================================


// -----------------------------------------------------
// Import dependencies
// -----------------------------------------------------

const db = require('../config/database');
const response = require('../utils/response');
const validation = require('../utils/validation');


// -----------------------------------------------------
// GET /api/auctions/:id/bids - Get all bids for an auction
// -----------------------------------------------------
// Returns bid history for a specific auction
// Sorted by amount (highest first) or time (newest first)

async function getBidsByAuction(req, res) {
    try {
        // Get auction ID from URL parameters
        const auctionId = req.params.id;
        
        // Validate auction ID
        if (!validation.isValidNumber(auctionId)) {
            response.sendError(res, 'Invalid auction ID', 400);
            return;
        }
        
        // Check if auction exists
        const [auctionRows] = await db.query(
            'SELECT id, title, status FROM auctions WHERE id = ?',
            [auctionId]
        );
        
        if (auctionRows.length === 0) {
            response.notFound(res, 'Auction not found');
            return;
        }
        
        // -------------------------------------------------
        // Get all bids for this auction
        // -------------------------------------------------
        // Join with users table to get bidder information
        // Order by created_at DESC to show newest bids first
        
        const sql = `
            SELECT 
                b.id,
                b.amount,
                b.is_auto_bid,
                b.created_at,
                u.id AS user_id,
                u.username,
                u.full_name
            FROM bids b
            JOIN users u ON b.user_id = u.id
            WHERE b.auction_id = ?
            ORDER BY b.created_at DESC
        `;
        
        const [bids] = await db.query(sql, [auctionId]);
        
        // -------------------------------------------------
        // Get highest bid info
        // -------------------------------------------------
        
        const highestBid = bids.length > 0 ? bids.reduce((max, bid) => 
            parseFloat(bid.amount) > parseFloat(max.amount) ? bid : max
        ) : null;
        
        response.sendSuccess(res, {
            auction: auctionRows[0],
            bids: bids,
            totalBids: bids.length,
            highestBid: highestBid
        }, 'Bids retrieved successfully');
        
    } catch (error) {
        console.error('Error in getBidsByAuction:', error);
        response.serverError(res, 'Failed to retrieve bids');
    }
}


// -----------------------------------------------------
// POST /api/auctions/:id/bids - Place a new bid
// -----------------------------------------------------
// Required in request body:
//   - amount: Bid amount (must be higher than current price)
//
// Business rules:
//   - Auction must be active
//   - Bid must be higher than current price
//   - User cannot bid on their own auction
//   - Updates auction's current_price

async function placeBid(req, res) {
    try {
        const auctionId = req.params.id;
        const { amount } = req.body;
        
        // TODO: Get actual user ID from auth
        // For testing, use buyer1 (id: 3)
        const userId = req.user?.id || 3;
        
        // -------------------------------------------------
        // Validate input
        // -------------------------------------------------
        
        if (!validation.isValidNumber(auctionId)) {
            response.sendError(res, 'Invalid auction ID', 400);
            return;
        }
        
        if (!validation.isPositiveNumber(amount)) {
            response.sendError(res, 'Bid amount must be a positive number', 400);
            return;
        }
        
        // -------------------------------------------------
        // Get auction details
        // -------------------------------------------------
        
        const [auctionRows] = await db.query(
            'SELECT * FROM auctions WHERE id = ?',
            [auctionId]
        );
        
        if (auctionRows.length === 0) {
            response.notFound(res, 'Auction not found');
            return;
        }
        
        const auction = auctionRows[0];
        
        // -------------------------------------------------
        // Business rule validations
        // -------------------------------------------------
        
        // Check if auction is active
        if (auction.status !== 'active') {
            response.sendError(res, 'This auction is not active', 400);
            return;
        }
        
        // Check if auction has ended (time-based)
        const now = new Date();
        const endTime = new Date(auction.end_time);
        if (now > endTime) {
            response.sendError(res, 'This auction has ended', 400);
            return;
        }
        
        // Check if user is the seller (cannot bid on own auction)
        if (auction.seller_id === userId) {
            response.sendError(res, 'You cannot bid on your own auction', 400);
            return;
        }
        
        // Check if bid is higher than current price
        const bidAmount = parseFloat(amount);
        const currentPrice = parseFloat(auction.current_price);
        
        if (bidAmount <= currentPrice) {
            response.sendError(res, `Bid must be higher than current price ($${currentPrice})`, 400);
            return;
        }
        
        // -------------------------------------------------
        // Insert the bid
        // -------------------------------------------------
        
        const insertSql = `
            INSERT INTO bids (auction_id, user_id, amount, is_auto_bid)
            VALUES (?, ?, ?, FALSE)
        `;
        
        const [result] = await db.query(insertSql, [auctionId, userId, bidAmount]);
        
        // -------------------------------------------------
        // Update auction's current price
        // -------------------------------------------------
        
        await db.query(
            'UPDATE auctions SET current_price = ? WHERE id = ?',
            [bidAmount, auctionId]
        );
        
        // -------------------------------------------------
        // Process auto-bids from other users
        // -------------------------------------------------
        // Check if any other user has auto-bid set higher than this bid
        
        await processAutoBids(auctionId, userId, bidAmount);
        
        // -------------------------------------------------
        // Get updated auction info
        // -------------------------------------------------
        
        const [updatedAuction] = await db.query(
            'SELECT current_price FROM auctions WHERE id = ?',
            [auctionId]
        );
        
        // Get the newly created bid
        const [newBid] = await db.query(
            `SELECT b.*, u.username 
             FROM bids b 
             JOIN users u ON b.user_id = u.id 
             WHERE b.id = ?`,
            [result.insertId]
        );
        
        response.sendSuccess(res, {
            bid: newBid[0],
            newCurrentPrice: updatedAuction[0].current_price,
            message: 'Bid placed successfully'
        }, 'Bid placed successfully', 201);
        
    } catch (error) {
        console.error('Error in placeBid:', error);
        response.serverError(res, 'Failed to place bid');
    }
}


// -----------------------------------------------------
// POST /api/auctions/:id/auto-bid - Set auto-bid
// -----------------------------------------------------
// Required in request body:
//   - max_amount: Maximum amount for auto-bidding
//
// Auto-bid automatically places bids on behalf of user
// whenever someone else bids, up to max_amount

async function setAutoBid(req, res) {
    try {
        const auctionId = req.params.id;
        const { max_amount } = req.body;
        
        // TODO: Get actual user ID from auth
        const userId = req.user?.id || 3;
        
        // -------------------------------------------------
        // Validate input
        // -------------------------------------------------
        
        if (!validation.isValidNumber(auctionId)) {
            response.sendError(res, 'Invalid auction ID', 400);
            return;
        }
        
        if (!validation.isPositiveNumber(max_amount)) {
            response.sendError(res, 'Max amount must be a positive number', 400);
            return;
        }
        
        // -------------------------------------------------
        // Get auction details
        // -------------------------------------------------
        
        const [auctionRows] = await db.query(
            'SELECT * FROM auctions WHERE id = ?',
            [auctionId]
        );
        
        if (auctionRows.length === 0) {
            response.notFound(res, 'Auction not found');
            return;
        }
        
        const auction = auctionRows[0];
        
        // -------------------------------------------------
        // Business rule validations
        // -------------------------------------------------
        
        // Check if auction is active
        if (auction.status !== 'active') {
            response.sendError(res, 'This auction is not active', 400);
            return;
        }
        
        // Check if user is the seller
        if (auction.seller_id === userId) {
            response.sendError(res, 'You cannot set auto-bid on your own auction', 400);
            return;
        }
        
        // Check if max_amount is higher than current price
        const maxAmount = parseFloat(max_amount);
        const currentPrice = parseFloat(auction.current_price);
        
        if (maxAmount <= currentPrice) {
            response.sendError(res, `Max amount must be higher than current price ($${currentPrice})`, 400);
            return;
        }
        
        // -------------------------------------------------
        // Insert or update auto-bid
        // -------------------------------------------------
        // UNIQUE constraint on (user_id, auction_id) ensures one auto-bid per user per auction
        // ON DUPLICATE KEY UPDATE: If exists, update; if not, insert
        
        const upsertSql = `
            INSERT INTO auto_bids (auction_id, user_id, max_amount, is_active)
            VALUES (?, ?, ?, TRUE)
            ON DUPLICATE KEY UPDATE max_amount = ?, is_active = TRUE, updated_at = CURRENT_TIMESTAMP
        `;
        
        await db.query(upsertSql, [auctionId, userId, maxAmount, maxAmount]);
        
        // -------------------------------------------------
        // Immediately place a bid at current_price + increment
        // -------------------------------------------------
        // When setting auto-bid, place an initial bid to take the lead
        
        const bidIncrement = 1.00; // $1 increment, can be made configurable
        const initialBid = currentPrice + bidIncrement;
        
        // Only place bid if it's within max_amount
        if (initialBid <= maxAmount) {
            // Insert auto-bid
            await db.query(
                `INSERT INTO bids (auction_id, user_id, amount, is_auto_bid) VALUES (?, ?, ?, TRUE)`,
                [auctionId, userId, initialBid]
            );
            
            // Update auction price
            await db.query(
                'UPDATE auctions SET current_price = ? WHERE id = ?',
                [initialBid, auctionId]
            );
            
            // Process other auto-bids (may trigger bidding war)
            await processAutoBids(auctionId, userId, initialBid);
        }
        
        // Get updated info
        const [updatedAuction] = await db.query(
            'SELECT current_price FROM auctions WHERE id = ?',
            [auctionId]
        );
        
        response.sendSuccess(res, {
            autoBid: {
                auction_id: parseInt(auctionId),
                user_id: userId,
                max_amount: maxAmount,
                is_active: true
            },
            currentPrice: updatedAuction[0].current_price
        }, 'Auto-bid set successfully', 201);
        
    } catch (error) {
        console.error('Error in setAutoBid:', error);
        response.serverError(res, 'Failed to set auto-bid');
    }
}


// -----------------------------------------------------
// Process Auto-Bids (Internal function)
// -----------------------------------------------------
// Called after a new bid is placed
// Checks if any other user's auto-bid should counter-bid
//
// Parameters:
//   - auctionId: The auction being bid on
//   - excludeUserId: User who just bid (don't auto-bid against yourself)
//   - currentBidAmount: The amount of the bid just placed

async function processAutoBids(auctionId, excludeUserId, currentBidAmount) {
    try {
        // Bid increment amount
        const bidIncrement = 1.00;
        
        // -------------------------------------------------
        // Find active auto-bids that can counter
        // -------------------------------------------------
        // Must be:
        //   - For this auction
        //   - Not from the user who just bid
        //   - Active
        //   - max_amount > current bid
        
        const sql = `
            SELECT * FROM auto_bids
            WHERE auction_id = ?
              AND user_id != ?
              AND is_active = TRUE
              AND max_amount > ?
            ORDER BY max_amount DESC
        `;
        
        const [autoBids] = await db.query(sql, [auctionId, excludeUserId, currentBidAmount]);
        
        // If no auto-bids can counter, we're done
        if (autoBids.length === 0) {
            return;
        }
        
        // Get the highest auto-bid
        const highestAutoBid = autoBids[0];
        
        // Calculate new bid amount
        let newBidAmount = parseFloat(currentBidAmount) + bidIncrement;
        
        // Cap at max_amount
        if (newBidAmount > parseFloat(highestAutoBid.max_amount)) {
            newBidAmount = parseFloat(highestAutoBid.max_amount);
        }
        
        // -------------------------------------------------
        // Place the auto-bid
        // -------------------------------------------------
        
        await db.query(
            `INSERT INTO bids (auction_id, user_id, amount, is_auto_bid) VALUES (?, ?, ?, TRUE)`,
            [auctionId, highestAutoBid.user_id, newBidAmount]
        );
        
        // Update auction price
        await db.query(
            'UPDATE auctions SET current_price = ? WHERE id = ?',
            [newBidAmount, auctionId]
        );
        
        console.log(`Auto-bid placed: User ${highestAutoBid.user_id} bid $${newBidAmount} on auction ${auctionId}`);
        
        // -------------------------------------------------
        // Check if original bidder has auto-bid too
        // -------------------------------------------------
        // This could trigger a "bidding war" between auto-bidders
        // Recursively process until no more auto-bids can counter
        
        // To prevent infinite loops, we check if there are other auto-bids
        // that can counter this new bid
        const [remainingAutoBids] = await db.query(
            `SELECT COUNT(*) as count FROM auto_bids
             WHERE auction_id = ?
               AND user_id != ?
               AND is_active = TRUE
               AND max_amount > ?`,
            [auctionId, highestAutoBid.user_id, newBidAmount]
        );
        
        if (remainingAutoBids[0].count > 0) {
            // Recursively process (with a small delay to prevent stack overflow)
            // In production, you might want to use a queue system
            await processAutoBids(auctionId, highestAutoBid.user_id, newBidAmount);
        }
        
    } catch (error) {
        console.error('Error in processAutoBids:', error);
        // Don't throw - auto-bid failure shouldn't fail the main bid
    }
}


// -----------------------------------------------------
// Export all functions
// -----------------------------------------------------

module.exports = {
    getBidsByAuction,
    placeBid,
    setAutoBid
};