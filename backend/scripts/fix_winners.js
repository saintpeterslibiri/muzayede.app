const db = require('../config/database');

async function fixWinners() {
    try {
        console.log('Starting winner fix script...');

        // Find all ended auctions with no winner_id
        const [endedAuctions] = await db.query(`
            SELECT id, title 
            FROM auctions 
            WHERE status = 'ended' AND winner_id IS NULL
        `);

        console.log(`Found ${endedAuctions.length} ended auctions without winner_id.`);

        for (const auction of endedAuctions) {
            // Find the highest bid for this auction
            const [winningBid] = await db.query(`
                SELECT user_id, amount 
                FROM bids 
                WHERE auction_id = ? 
                ORDER BY amount DESC 
                LIMIT 1
            `, [auction.id]);

            if (winningBid.length > 0) {
                const winnerId = winningBid[0].user_id;
                const amount = winningBid[0].amount;

                // Update auction with winner_id
                await db.query(`
                    UPDATE auctions 
                    SET winner_id = ? 
                    WHERE id = ?
                `, [winnerId, auction.id]);

                // Update bids table to mark winning bid
                await db.query(`
                    UPDATE bids 
                    SET is_winning_bid = (user_id = ?) 
                    WHERE auction_id = ?
                `, [winnerId, auction.id]);

                console.log(`Fixed auction "${auction.title}" (ID: ${auction.id}): Winner ID ${winnerId} with bid $${amount}`);
            } else {
                console.log(`Auction "${auction.title}" (ID: ${auction.id}) has no bids.`);
            }
        }

        console.log('Winner fix script completed.');
        process.exit(0);
    } catch (error) {
        console.error('Error running fix script:', error);
        process.exit(1);
    }
}

fixWinners();
