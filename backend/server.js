require('dotenv').config();

const express = require('express');
const cors = require('cors');
const multer = require('multer');
const path = require('path');
const config = require('./appsettings.json');

// Routes
const authRoutes = require('./routes/auth');
const userRoutes = require('./routes/users');
const auctionRoutes = require('./routes/auction');
const bidRoutes = require('./routes/bids');
const profileRoutes = require('./routes/profile');
const adminRoutes = require('./routes/admin');

// Middleware
const { authenticate, isAdmin } = require('./middleware/auth');

const app = express();
const PORT = config.server.port || 3000;

//for mail-notification
require('dotenv').config();
const auctionScheduler = require('./services/auctionScheduler');


const fs = require('fs');

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static files from frontend/uploads
app.use('/uploads', express.static(path.join(__dirname, '../frontend/uploads')));

// Configure multer for auction images (Memory Storage for DB)
const uploadAuction = multer({ 
    storage: multer.memoryStorage(),
    limits: { fileSize: 5 * 1024 * 1024 }, // 5MB limit
    fileFilter: function (req, file, cb) {
        const allowedTypes = /jpeg|jpg|png|gif|webp/;
        const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
        const mimetype = allowedTypes.test(file.mimetype);
        if (extname && mimetype) {
            return cb(null, true);
        } else {
            cb(new Error('Only images are allowed!'));
        }
    }
});

// Auth & User Routes
app.use('/api/auth', authRoutes);
app.use('/api/users', authenticate, userRoutes);

// Auction Routes
app.get('/api/auctions', auctionRoutes.getAllAuctions);
app.post('/api/auctions', authenticate, uploadAuction.single('image'), auctionRoutes.createAuction);
app.get('/api/auctions/:id', auctionRoutes.getAuctionById);
app.get('/api/auctions/:id/image', auctionRoutes.getAuctionImage);
app.put('/api/auctions/:id', authenticate, auctionRoutes.updateAuction);
app.put('/api/auctions/:id', authenticate, auctionRoutes.updateAuction);
app.delete('/api/auctions/:id', authenticate, auctionRoutes.deleteAuction);

// Bid Routes
app.get('/api/auctions/:id/bids', bidRoutes.getBidsByAuction);
app.post('/api/auctions/:id/bids', authenticate, bidRoutes.placeBid);
app.post('/api/auctions/:id/auto-bid', authenticate, bidRoutes.setAutoBid);

// Profile Routes
app.get('/api/profile', authenticate, profileRoutes.getProfile);
app.put('/api/profile', authenticate, profileRoutes.updateProfile);
app.get('/api/my/auctions', authenticate, profileRoutes.getMyAuctions);
app.get('/api/my/bids', authenticate, profileRoutes.getMyBids);

// Admin Routes
app.get('/api/admin/stats', authenticate, isAdmin, adminRoutes.getStats);
app.get('/api/admin/users', authenticate, isAdmin, adminRoutes.getAllUsers);
app.put('/api/admin/users/:id/ban', authenticate, isAdmin, adminRoutes.banUser);
app.delete('/api/admin/auctions/:id', authenticate, isAdmin, adminRoutes.deleteAuction);

// Health check
app.get('/api/health', (req, res) => {
    res.json({ status: 'ok', message: 'Server is running' });
});

// Error handling middleware
app.use((err, req, res, next) => {
    if (err instanceof multer.MulterError) {
        if (err.code === 'LIMIT_FILE_SIZE') {
            return res.status(400).json({ success: false, message: 'File size exceeds 2MB limit' });
        }
        return res.status(400).json({ success: false, message: err.message || 'File upload error' });
    }
    if (err.message) {
        return res.status(400).json({ success: false, message: err.message });
    }
    console.error('Error:', err);
    res.status(500).json({ success: false, message: 'Internal server error' });
});

// Start server
app.listen(PORT, () => {
    console.log(`Server is running on http://localhost:${PORT}`);
    auctionScheduler.startScheduler();
});
