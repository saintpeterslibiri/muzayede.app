// =====================================================
// BIDS.JS - Bid Route Handlers (with Email Notifications)
// =====================================================
// Handles all bid-related operations:
// - Get bids for an auction
// - Place a new bid (+ email notifications)
// - Set auto-bid
// - Process auto-bid logic (+ email notifications)
// =====================================================


// -----------------------------------------------------
// Import dependencies
// -----------------------------------------------------

const db = require('../config/database');
const response = require('../utils/response');
const validation = require('../utils/validation');

// Mail Service for notifications
const mailService = require('../services/mailService');


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
        // Order by bid_amount DESC to show highest bids first (correct order even if timestamps match)
        
        const sql = `
            SELECT 
                b.id,
                b.bid_amount as amount,
                b.is_auto_bid,
                b.created_at,
                u.id AS user_id,
                u.username,
                u.full_name
            FROM bids b
            JOIN users u ON b.user_id = u.id
            WHERE b.auction_id = ?
            ORDER BY b.bid_amount DESC
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
//
// Email notifications:
//   - Notifies seller about new bid
//   - Notifies previous highest bidder that they were outbid

async function placeBid(req, res) {
    try {
        const auctionId = req.params.id;
        const { amount } = req.body;
        
        // Get actual user ID from auth
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

        // Check if there is an auto-bid from another user that is higher than this bid
        // If so, we should reject this bid or immediately outbid it?
        // Standard behavior: Accept the bid, then let auto-bid outbid it immediately.
        // BUT, if the user has an auto-bid themselves, we should check that too.
        
        // -------------------------------------------------
        // Get previous highest bidder (for outbid notification)
        // -------------------------------------------------
        
        const [previousHighestBid] = await db.query(`
            SELECT user_id, bid_amount as amount 
            FROM bids 
            WHERE auction_id = ? 
            ORDER BY bid_amount DESC 
            LIMIT 1
        `, [auctionId]);
        
        const previousHighestBidder = previousHighestBid.length > 0 ? previousHighestBid[0] : null;
        
        // -------------------------------------------------
        // Insert the bid
        // -------------------------------------------------
        
        const insertSql = `
            INSERT INTO bids (auction_id, user_id, bid_amount, is_auto_bid)
            VALUES (?, ?, ?, FALSE)
        `;
        
        const [result] = await db.query(insertSql, [auctionId, userId, bidAmount]);
        
        // -------------------------------------------------
        // Update auction's current price
        // -------------------------------------------------
        
        // Try updating current_price, if it fails (column doesn't exist), try current_highest_bid
        try {
            await db.query(
                'UPDATE auctions SET current_price = ? WHERE id = ?',
                [bidAmount, auctionId]
            );
        } catch (err) {
            if (err.code === 'ER_BAD_FIELD_ERROR') {
                 await db.query(
                    'UPDATE auctions SET current_highest_bid = ? WHERE id = ?',
                    [bidAmount, auctionId]
                );
            } else {
                throw err;
            }
        }
        
        // -------------------------------------------------
        // 📧 SEND EMAIL NOTIFICATIONS (async, don't await)
        // -------------------------------------------------
        
        // 1. Notify seller about new bid
        mailService.notifySellerNewBid(auctionId, bidAmount, userId)
            .catch(err => console.error('Failed to send seller notification:', err));
        
        // 2. Notify previous highest bidder that they were outbid
        if (previousHighestBidder && previousHighestBidder.user_id !== userId) {
            mailService.notifyOutbid(auctionId, previousHighestBidder.user_id, bidAmount)
                .catch(err => console.error('Failed to send outbid notification:', err));
        }
        
        // -------------------------------------------------
        // Process auto-bids from other users
        // -------------------------------------------------
        // Check if any other user has auto-bid set higher than this bid
        
        // We need to check if the current user has an auto-bid that is lower than the new bid
        // If so, we should disable it or update it? 
        // Usually, manual bid overrides auto-bid if manual is higher.
        
        // Also check if there is a higher auto-bid from another user
        // If so, that auto-bid should immediately trigger and outbid this new bid
        
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
            currentPrice: updatedAuction[0].current_price
        }, 'Bid placed successfully', 201);
        
    } catch (error) {
        console.error('Error in placeBid:', error);
        response.serverError(res, 'Failed to place bid');
    }
}


// -----------------------------------------------------
// POST /api/auctions/:id/auto-bid - Set auto-bid
// -----------------------------------------------------
// Sets maximum bid amount for automatic bidding
//
// Required in request body:
//   - max_amount: Maximum amount user is willing to bid

async function setAutoBid(req, res) {
    try {
        const auctionId = req.params.id;
        const { max_amount } = req.body;
        
        // Get actual user ID from auth
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
        // Get previous highest bidder (for outbid notification)
        // -------------------------------------------------
        
        const [previousHighestBid] = await db.query(`
            SELECT user_id, bid_amount as amount 
            FROM bids 
            WHERE auction_id = ? 
            ORDER BY bid_amount DESC 
            LIMIT 1
        `, [auctionId]);
        
        const previousHighestBidder = previousHighestBid.length > 0 ? previousHighestBid[0] : null;
        
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
        let initialBid = currentPrice + bidIncrement;
        
        // If current highest bid is from another user, we need to beat it
        // If current highest bid is already from this user, we don't need to increase unless outbid
        if (previousHighestBidder && previousHighestBidder.user_id === userId) {
             // User is already winning, no need to place new bid immediately
             // Just updating max_amount is enough
             initialBid = currentPrice; 
        } else {
             // User is not winning, place bid to take lead
             if (initialBid <= maxAmount) {
                // Insert auto-bid
                await db.query(
                    `INSERT INTO bids (auction_id, user_id, bid_amount, is_auto_bid) VALUES (?, ?, ?, TRUE)`,
                    [auctionId, userId, initialBid]
                );
                
                // Update auction price
            await db.query(`
                UPDATE auctions
                SET current_price = (
                    SELECT MAX(bid_amount)
                    FROM bids
                    WHERE auction_id = ?
                )
                WHERE id = ?
            `, [auctionId, auctionId]);
                
                // -------------------------------------------------
                // 📧 SEND EMAIL NOTIFICATIONS
                // -------------------------------------------------
                
                // Notify seller about new bid
                mailService.notifySellerNewBid(auctionId, initialBid, userId)
                    .catch(err => console.error('Failed to send seller notification:', err));
                
                // Notify previous highest bidder
                if (previousHighestBidder && previousHighestBidder.user_id !== userId) {
                    mailService.notifyOutbid(auctionId, previousHighestBidder.user_id, initialBid)
                        .catch(err => console.error('Failed to send outbid notification:', err));
                }
             }
        }
        
        // Process other auto-bids (may trigger bidding war)
        // We pass initialBid (or currentPrice if no new bid) as the amount to beat
        await processAutoBids(auctionId, userId, (initialBid > currentPrice ? initialBid : currentPrice));
        
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
        const [autoBids] = await db.query(`
            SELECT *
            FROM auto_bids
            WHERE auction_id = ?
              AND is_active = TRUE
              AND user_id != ?
              AND max_amount > ?
            ORDER BY max_amount DESC
            LIMIT 1
        `, [auctionId, excludeUserId, currentBidAmount]);

        if (autoBids.length === 0) return;

        const autoBid = autoBids[0];

        // 🔥 TEK HAMLE
        const bidAmount = autoBid.max_amount;

        await db.query(
            `INSERT INTO bids (auction_id, user_id, bid_amount, is_auto_bid)
             VALUES (?, ?, ?, TRUE)`,
            [auctionId, autoBid.user_id, bidAmount]
        );

        try {
            await db.query(
                `UPDATE auctions SET current_price = ? WHERE id = ?`,
                [bidAmount, auctionId]
            );
        } catch (err) {
             if (err.code === 'ER_BAD_FIELD_ERROR') {
                 await db.query(
                    'UPDATE auctions SET current_highest_bid = ? WHERE id = ?',
                    [bidAmount, auctionId]
                );
            } else {
                console.error('Error updating auction price in auto-bid:', err);
            }
        }

        // 📧 Bildirimler
        mailService.notifyOutbid(auctionId, excludeUserId, bidAmount)
            .catch(console.error);

        mailService.notifySellerNewBid(auctionId, bidAmount, autoBid.user_id)
            .catch(console.error);

    } catch (err) {
        console.error('Auto-bid error:', err);
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
