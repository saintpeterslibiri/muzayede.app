// =====================================================
// MAIL.JS - Email Configuration
// =====================================================
// Gmail SMTP settings for sending notifications
// 
// IMPORTANT: Gmail requires "App Password" if 2FA is enabled
// Go to: Google Account > Security > 2-Step Verification > App passwords
// Generate a new app password for "Mail" and use it here
// =====================================================

const config = {
    // SMTP Settings
    smtp: {
        host: process.env.SMTP_HOST || 'smtp.gmail.com',
        port: parseInt(process.env.SMTP_PORT) || 587,
        secure: false, // true for 465, false for 587
        auth: {
            user: process.env.SMTP_USER || 'your-email@gmail.com',
            pass: process.env.SMTP_PASS || 'your-app-password'
        }
    },
    
    // Default sender info
    from: {
        name: process.env.MAIL_FROM_NAME || 'Müzayede.app',
        email: process.env.MAIL_FROM_EMAIL || 'noreply@muzayede.app'
    },
    
    // App URL for links in emails
    appUrl: process.env.APP_URL || 'http://localhost:8080',
    
    // Enable/disable email sending (useful for development)
    enabled: process.env.MAIL_ENABLED !== 'false'
};

module.exports = config;
