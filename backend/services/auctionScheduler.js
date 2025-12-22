// =====================================================
// AUCTION SCHEDULER - Handles Auction End Events
// =====================================================
// This scheduler runs periodically to:
// - Check for auctions that have ended
// - Update their status to 'ended'
// - Send notifications to winners and sellers
// =====================================================

const db = require('../config/database');
const mailService = require('../services/mailService');

// How often to check for ended auctions (in milliseconds)
const CHECK_INTERVAL = 60 * 1000; // Every 1 minute

// -----------------------------------------------------
// Process Ended Auctions
// -----------------------------------------------------
// Finds auctions where end_time has passed but status is still 'active'

async function processEndedAuctions() {
    try {
        // Find auctions that should have ended
        const [endedAuctions] = await db.query(`
            SELECT id, title, seller_id, current_price
            FROM auctions
            WHERE status = 'active'
              AND end_time <= NOW()
        `);
        
        if (endedAuctions.length === 0) {
            return; // No auctions to process
        }
        
        console.log(`📋 Found ${endedAuctions.length} ended auctions to process`);
        
        for (const auction of endedAuctions) {
            await processAuctionEnd(auction);
        }
        
    } catch (error) {
        console.error('Error in processEndedAuctions:', error);
    }
}


// -----------------------------------------------------
// Process Single Auction End
// -----------------------------------------------------

async function processAuctionEnd(auction) {
    try {
        console.log(`⏰ Processing ended auction: ${auction.title} (ID: ${auction.id})`);
        
        // Get the winning bid (highest bid)
        const [winningBid] = await db.query(`
            SELECT b.*, u.id AS winner_id, u.username, u.email, u.full_name
            FROM bids b
            JOIN users u ON b.user_id = u.id
            WHERE b.auction_id = ?
            ORDER BY b.amount DESC
            LIMIT 1
        `, [auction.id]);
        
        const hasWinner = winningBid.length > 0;
        const winner = hasWinner ? winningBid[0] : null;
        
        // -------------------------------------------------
        // Update auction status and winner
        // -------------------------------------------------
        
        if (hasWinner) {
            // Update auction status AND winner_id
            await db.query(
                'UPDATE auctions SET status = ?, winner_id = ? WHERE id = ?',
                ['ended', winner.winner_id, auction.id]
            );

            // Reset all bids for this auction
            await db.query(
                'UPDATE bids SET is_winning_bid = FALSE WHERE auction_id = ?',
                [auction.id]
            );
            
            // Mark the winning bid
            await db.query(
                'UPDATE bids SET is_winning_bid = TRUE WHERE id = ?',
                [winner.id]
            );
            
            console.log(`🏆 Winner: ${winner.full_name} with bid $${winner.amount}`);
        } else {
            // Just update status
            await db.query(
                'UPDATE auctions SET status = ? WHERE id = ?',
                ['ended', auction.id]
            );
            console.log(`❌ No bids received for auction ${auction.id}`);
        }
        
        // -------------------------------------------------
        // Deactivate all auto-bids for this auction
        // -------------------------------------------------
        
        await db.query(
            'UPDATE auto_bids SET is_active = FALSE WHERE auction_id = ?',
            [auction.id]
        );
        
        // -------------------------------------------------
        // 📧 SEND EMAIL NOTIFICATIONS
        // -------------------------------------------------
        
        // 1. Notify seller that auction ended
        await mailService.notifySellerAuctionEnded(auction.id);
        
        // 2. If there's a winner, notify them
        if (hasWinner) {
            await mailService.notifyAuctionWon(auction.id, winner.winner_id, winner.amount);
        }
        
        console.log(`✅ Auction ${auction.id} processed successfully`);
        
    } catch (error) {
        console.error(`Error processing auction ${auction.id}:`, error);
    }
}


// -----------------------------------------------------
// Start Scheduler
// -----------------------------------------------------

function startScheduler() {
    console.log('🕐 Auction scheduler started');
    console.log(`   Checking every ${CHECK_INTERVAL / 1000} seconds`);
    
    // Run immediately on start
    processEndedAuctions();
    
    // Then run periodically
    setInterval(processEndedAuctions, CHECK_INTERVAL);
}


// -----------------------------------------------------
// Manual trigger (for testing)
// -----------------------------------------------------

async function checkNow() {
    console.log('🔄 Manual auction check triggered');
    await processEndedAuctions();
}


// -----------------------------------------------------
// Export
// -----------------------------------------------------

module.exports = {
    startScheduler,
    processEndedAuctions,
    checkNow
};
