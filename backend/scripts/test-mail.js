// =====================================================
// TEST MAIL SCRIPT
// =====================================================
// Run this to test your email configuration
// Usage: node scripts/test-mail.js your-email@example.com
// =====================================================

require('dotenv').config();

const nodemailer = require('nodemailer');
const mailConfig = require('../config/mail');

// Get test email from command line argument
const testEmail = process.argv[2];

if (!testEmail) {
    console.log('❌ Please provide a test email address');
    console.log('Usage: node scripts/test-mail.js your-email@example.com');
    process.exit(1);
}

async function testMail() {
    console.log('🔧 Testing mail configuration...\n');
    
    console.log('SMTP Settings:');
    console.log(`  Host: ${mailConfig.smtp.host}`);
    console.log(`  Port: ${mailConfig.smtp.port}`);
    console.log(`  User: ${mailConfig.smtp.auth.user}`);
    console.log(`  From: ${mailConfig.from.name} <${mailConfig.from.email}>`);
    console.log('');
    
    // Create transporter
    const transporter = nodemailer.createTransport({
        host: mailConfig.smtp.host,
        port: mailConfig.smtp.port,
        secure: mailConfig.smtp.secure,
        auth: mailConfig.smtp.auth
    });
    
    // Verify connection
    console.log('📡 Verifying connection...');
    try {
        await transporter.verify();
        console.log('✅ Connection successful!\n');
    } catch (error) {
        console.log('❌ Connection failed:', error.message);
        console.log('\nPossible issues:');
        console.log('  1. Wrong SMTP credentials');
        console.log('  2. Need to enable "Less secure apps" or use App Password');
        console.log('  3. Firewall blocking port 587');
        process.exit(1);
    }
    
    // Send test email
    console.log(`📧 Sending test email to ${testEmail}...`);
    
    try {
        const info = await transporter.sendMail({
            from: `"${mailConfig.from.name}" <${mailConfig.from.email}>`,
            to: testEmail,
            subject: '🧪 Müzayede.app - Test Email',
            html: `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #16a34a; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; border-radius: 0 0 8px 8px; }
                    .success { background: #dcfce7; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>✅ Email Kurulumu Başarılı!</h1>
                    </div>
                    <div class="content">
                        <div class="success">
                            <h2>🎉 Tebrikler!</h2>
                            <p>Müzayede.app mail sistemi başarıyla çalışıyor.</p>
                        </div>
                        
                        <p>Bu test emaili başarıyla gönderildi. Artık şu bildirimleri alabilirsiniz:</p>
                        <ul>
                            <li>✉️ Yeni teklif bildirimleri</li>
                            <li>⚠️ Teklifiniz geçildi bildirimleri</li>
                            <li>🏆 Açık artırma kazandınız bildirimleri</li>
                            <li>⏰ Açık artırma sona erdi bildirimleri</li>
                        </ul>
                        
                        <p style="color: #64748b; font-size: 12px; margin-top: 20px;">
                            Test zamanı: ${new Date().toLocaleString('tr-TR')}
                        </p>
                    </div>
                </div>
            </body>
            </html>
            `
        });
        
        console.log('✅ Test email sent successfully!');
        console.log(`   Message ID: ${info.messageId}`);
        console.log(`\n📬 Check ${testEmail} for the test email.`);
        
    } catch (error) {
        console.log('❌ Failed to send email:', error.message);
        process.exit(1);
    }
}

testMail();
