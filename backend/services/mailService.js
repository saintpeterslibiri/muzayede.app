// =====================================================
// MAIL SERVICE - Email Notification Service
// =====================================================
// Handles all email notifications:
// - New bid notifications (to seller)
// - Outbid notifications (to previous highest bidder)
// - Auction won notifications (to winner)
// - Auction ended notifications (to seller)
// =====================================================

const nodemailer = require('nodemailer');
const mailConfig = require('../config/mail');
const db = require('../config/database');

// -----------------------------------------------------
// Create Nodemailer Transporter
// -----------------------------------------------------

const transporter = nodemailer.createTransport({
    host: mailConfig.smtp.host,
    port: mailConfig.smtp.port,
    secure: mailConfig.smtp.secure,
    auth: mailConfig.smtp.auth
});

// Verify connection on startup
transporter.verify((error, success) => {
    if (error) {
        console.error('❌ Mail server connection failed:', error.message);
    } else {
        console.log('✅ Mail server is ready to send emails');
    }
});


// -----------------------------------------------------
// Helper: Send Email
// -----------------------------------------------------

async function sendEmail(to, subject, html) {
    // Check if mail is enabled
    if (!mailConfig.enabled) {
        console.log(`📧 [Mail Disabled] Would send to ${to}: ${subject}`);
        return { success: true, disabled: true };
    }
    
    try {
        const info = await transporter.sendMail({
            from: `"${mailConfig.from.name}" <${mailConfig.from.email}>`,
            to: to,
            subject: subject,
            html: html
        });
        
        console.log(`📧 Email sent to ${to}: ${info.messageId}`);
        return { success: true, messageId: info.messageId };
        
    } catch (error) {
        console.error(`❌ Failed to send email to ${to}:`, error.message);
        return { success: false, error: error.message };
    }
}


// -----------------------------------------------------
// Helper: Get User by ID
// -----------------------------------------------------

async function getUserById(userId) {
    const [rows] = await db.query(
        'SELECT id, username, email, full_name FROM users WHERE id = ?',
        [userId]
    );
    return rows.length > 0 ? rows[0] : null;
}


// -----------------------------------------------------
// Helper: Format Currency
// -----------------------------------------------------

function formatCurrency(amount) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(amount);
}


// -----------------------------------------------------
// 1. NEW BID NOTIFICATION (to Seller)
// -----------------------------------------------------
// Called when someone places a bid on an auction
// Notifies the seller that they received a new bid

async function notifySellerNewBid(auctionId, bidAmount, bidderId) {
    try {
        // Get auction details with seller info
        const [auctionRows] = await db.query(`
            SELECT a.*, u.email AS seller_email, u.full_name AS seller_name, u.username AS seller_username
            FROM auctions a
            JOIN users u ON a.seller_id = u.id
            WHERE a.id = ?
        `, [auctionId]);
        
        if (auctionRows.length === 0) return;
        
        const auction = auctionRows[0];
        
        // Get bidder info
        const bidder = await getUserById(bidderId);
        if (!bidder) return;
        
        // Build email HTML
        const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; }
                .highlight { background: #dbeafe; padding: 15px; border-radius: 8px; margin: 15px 0; }
                .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
                .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎉 Yeni Teklif Aldınız!</h1>
                </div>
                <div class="content">
                    <p>Merhaba <strong>${auction.seller_name}</strong>,</p>
                    
                    <p>"<strong>${auction.title}</strong>" ilanınıza yeni bir teklif geldi!</p>
                    
                    <div class="highlight">
                        <p><strong>Teklif Veren:</strong> ${bidder.full_name} (@${bidder.username})</p>
                        <p><strong>Teklif Tutarı:</strong> ${formatCurrency(bidAmount)}</p>
                        <p><strong>Güncel Fiyat:</strong> ${formatCurrency(bidAmount)}</p>
                    </div>
                    
                    <a href="${mailConfig.appUrl}/auction_detail.php?id=${auctionId}" class="btn">
                        İlanı Görüntüle
                    </a>
                </div>
                <div class="footer">
                    <p>Bu email ${mailConfig.from.name} tarafından gönderilmiştir.</p>
                    <p>© ${new Date().getFullYear()} Müzayede.app</p>
                </div>
            </div>
        </body>
        </html>
        `;
        
        await sendEmail(
            auction.seller_email,
            `🎉 Yeni Teklif: "${auction.title}" - ${formatCurrency(bidAmount)}`,
            html
        );
        
    } catch (error) {
        console.error('Error in notifySellerNewBid:', error);
    }
}


// -----------------------------------------------------
// 2. OUTBID NOTIFICATION (to Previous Highest Bidder)
// -----------------------------------------------------
// Called when someone's bid is surpassed
// Notifies them to place a higher bid

async function notifyOutbid(auctionId, outbidUserId, newBidAmount) {
    try {
        // Get auction details
        const [auctionRows] = await db.query(
            'SELECT id, title, end_time FROM auctions WHERE id = ?',
            [auctionId]
        );
        
        if (auctionRows.length === 0) return;
        
        const auction = auctionRows[0];
        
        // Get outbid user info
        const user = await getUserById(outbidUserId);
        if (!user) return;
        
        // Calculate time remaining
        const endTime = new Date(auction.end_time);
        const now = new Date();
        const hoursLeft = Math.max(0, Math.floor((endTime - now) / (1000 * 60 * 60)));
        
        const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc2626; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; }
                .highlight { background: #fee2e2; padding: 15px; border-radius: 8px; margin: 15px 0; }
                .btn { display: inline-block; background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
                .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
                .urgent { color: #dc2626; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>⚠️ Teklifiniz Geçildi!</h1>
                </div>
                <div class="content">
                    <p>Merhaba <strong>${user.full_name}</strong>,</p>
                    
                    <p>"<strong>${auction.title}</strong>" ilanındaki teklifiniz geçildi!</p>
                    
                    <div class="highlight">
                        <p><strong>Yeni En Yüksek Teklif:</strong> ${formatCurrency(newBidAmount)}</p>
                        <p class="urgent">⏰ Kalan Süre: ${hoursLeft} saat</p>
                    </div>
                    
                    <p>Bu ürünü hala kazanmak istiyorsanız, hemen yeni bir teklif verin!</p>
                    
                    <a href="${mailConfig.appUrl}/auction_detail.php?id=${auctionId}" class="btn">
                        Yeni Teklif Ver
                    </a>
                </div>
                <div class="footer">
                    <p>Bu email ${mailConfig.from.name} tarafından gönderilmiştir.</p>
                    <p>© ${new Date().getFullYear()} Müzayede.app</p>
                </div>
            </div>
        </body>
        </html>
        `;
        
        await sendEmail(
            user.email,
            `⚠️ Teklifiniz Geçildi: "${auction.title}"`,
            html
        );
        
    } catch (error) {
        console.error('Error in notifyOutbid:', error);
    }
}


// -----------------------------------------------------
// 3. AUCTION WON NOTIFICATION (to Winner)
// -----------------------------------------------------
// Called when auction ends and there's a winner

async function notifyAuctionWon(auctionId, winnerId, winningAmount) {
    try {
        // Get auction details
        const [auctionRows] = await db.query(`
            SELECT a.*, u.full_name AS seller_name, u.email AS seller_email
            FROM auctions a
            JOIN users u ON a.seller_id = u.id
            WHERE a.id = ?
        `, [auctionId]);
        
        if (auctionRows.length === 0) return;
        
        const auction = auctionRows[0];
        
        // Get winner info
        const winner = await getUserById(winnerId);
        if (!winner) return;
        
        const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #16a34a; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; }
                .highlight { background: #dcfce7; padding: 15px; border-radius: 8px; margin: 15px 0; }
                .btn { display: inline-block; background: #16a34a; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
                .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🏆 Tebrikler, Kazandınız!</h1>
                </div>
                <div class="content">
                    <p>Merhaba <strong>${winner.full_name}</strong>,</p>
                    
                    <p>"<strong>${auction.title}</strong>" açık artırmasını kazandınız!</p>
                    
                    <div class="highlight">
                        <p><strong>Kazanan Teklif:</strong> ${formatCurrency(winningAmount)}</p>
                        <p><strong>Satıcı:</strong> ${auction.seller_name}</p>
                    </div>
                    
                    <p>Satıcı sizinle iletişime geçecektir. Siparişleriniz sayfasından detayları görebilirsiniz.</p>
                    
                    <a href="${mailConfig.appUrl}/my_orders.php" class="btn">
                        Siparişlerimi Gör
                    </a>
                </div>
                <div class="footer">
                    <p>Bu email ${mailConfig.from.name} tarafından gönderilmiştir.</p>
                    <p>© ${new Date().getFullYear()} Müzayede.app</p>
                </div>
            </div>
        </body>
        </html>
        `;
        
        await sendEmail(
            winner.email,
            `🏆 Tebrikler! "${auction.title}" Açık Artırmasını Kazandınız!`,
            html
        );
        
    } catch (error) {
        console.error('Error in notifyAuctionWon:', error);
    }
}


// -----------------------------------------------------
// 4. AUCTION ENDED NOTIFICATION (to Seller)
// -----------------------------------------------------
// Notifies seller when their auction ends

async function notifySellerAuctionEnded(auctionId) {
    try {
        // Get auction with seller and winner info
        const [auctionRows] = await db.query(`
            SELECT a.*, 
                   u.email AS seller_email, 
                   u.full_name AS seller_name
            FROM auctions a
            JOIN users u ON a.seller_id = u.id
            WHERE a.id = ?
        `, [auctionId]);
        
        if (auctionRows.length === 0) return;
        
        const auction = auctionRows[0];
        
        // Get winning bid info
        const [winningBid] = await db.query(`
            SELECT b.*, u.full_name AS winner_name, u.email AS winner_email, u.username AS winner_username
            FROM bids b
            JOIN users u ON b.user_id = u.id
            WHERE b.auction_id = ?
            ORDER BY b.amount DESC
            LIMIT 1
        `, [auctionId]);
        
        const hasWinner = winningBid.length > 0;
        const winner = hasWinner ? winningBid[0] : null;
        
        const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: ${hasWinner ? '#2563eb' : '#64748b'}; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; }
                .highlight { background: ${hasWinner ? '#dbeafe' : '#f1f5f9'}; padding: 15px; border-radius: 8px; margin: 15px 0; }
                .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
                .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>${hasWinner ? '🎊 Açık Artırma Tamamlandı!' : '⏰ Açık Artırma Sona Erdi'}</h1>
                </div>
                <div class="content">
                    <p>Merhaba <strong>${auction.seller_name}</strong>,</p>
                    
                    <p>"<strong>${auction.title}</strong>" açık artırmanız sona erdi.</p>
                    
                    ${hasWinner ? `
                    <div class="highlight">
                        <p><strong>🏆 Kazanan:</strong> ${winner.winner_name} (@${winner.winner_username})</p>
                        <p><strong>💰 Kazanan Teklif:</strong> ${formatCurrency(winner.amount)}</p>
                        <p><strong>📧 Email:</strong> ${winner.winner_email}</p>
                    </div>
                    <p>Kazanan ile iletişime geçerek ürün teslimatını ayarlayabilirsiniz.</p>
                    ` : `
                    <div class="highlight">
                        <p>Maalesef bu açık artırmaya teklif gelmedi.</p>
                        <p>Ürünü yeniden listeleyebilirsiniz.</p>
                    </div>
                    `}
                    
                    <a href="${mailConfig.appUrl}/my_auctions.php" class="btn">
                        İlanlarımı Gör
                    </a>
                </div>
                <div class="footer">
                    <p>Bu email ${mailConfig.from.name} tarafından gönderilmiştir.</p>
                    <p>© ${new Date().getFullYear()} Müzayede.app</p>
                </div>
            </div>
        </body>
        </html>
        `;
        
        await sendEmail(
            auction.seller_email,
            hasWinner 
                ? `🎊 "${auction.title}" Satıldı! - ${formatCurrency(winner.amount)}`
                : `⏰ "${auction.title}" Açık Artırması Sona Erdi`,
            html
        );
        
    } catch (error) {
        console.error('Error in notifySellerAuctionEnded:', error);
    }
}


// -----------------------------------------------------
// Export all functions
// -----------------------------------------------------

module.exports = {
    sendEmail,
    notifySellerNewBid,
    notifyOutbid,
    notifyAuctionWon,
    notifySellerAuctionEnded
};
