const express = require('express');
const bcrypt = require('bcryptjs');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const pool = require('../config/database');
const { authenticate } = require('../middleware/auth');

// Multer instance for error handling
const multerInstance = multer;

const router = express.Router();

// Configure multer for avatar uploads
const storage = multer.diskStorage({
    destination: function (req, file, cb) {
        const uploadPath = path.join(__dirname, '../../frontend/uploads/avatars');
        // Create directory if it doesn't exist
        if (!fs.existsSync(uploadPath)) {
            fs.mkdirSync(uploadPath, { recursive: true });
        }
        cb(null, uploadPath);
    },
    filename: function (req, file, cb) {
        const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
        cb(null, 'avatar-' + req.user.id + '-' + uniqueSuffix + path.extname(file.originalname));
    }
});

const upload = multer({
    storage: storage,
    limits: { fileSize: 2 * 1024 * 1024 }, // 2MB limit
    fileFilter: function (req, file, cb) {
        // Allowed file extensions
        const allowedExtensions = ['.jpg', '.jpeg', '.png', '.gif'];
        // Allowed MIME types
        const allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/pjpeg', // Some browsers send this for JPEG
            'image/x-png'  // Some browsers send this for PNG
        ];
        
        const fileExt = path.extname(file.originalname).toLowerCase();
        const isValidExt = allowedExtensions.includes(fileExt);
        const isValidMime = allowedMimeTypes.includes(file.mimetype.toLowerCase());
        
        // Accept if either extension or MIME type is valid (more flexible)
        if (isValidExt || isValidMime) {
            return cb(null, true);
        } else {
            cb(new Error('Only image files are allowed (jpeg, jpg, png, gif)'));
        }
    }
});

// Get user profile
router.get('/profile', authenticate, async (req, res) => {
    try {
        const [users] = await pool.execute(
            `SELECT id, username, email, full_name, role, status, avatar_path, created_at 
             FROM users WHERE id = ?`,
            [req.user.id]
        );

        if (users.length === 0) {
            return res.status(404).json({
                success: false,
                message: 'User not found'
            });
        }

        const user = users[0];

        // Get user statistics
        const [auctionStats] = await pool.execute(
            `SELECT 
                COUNT(*) as total_auctions,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_auctions
             FROM auctions WHERE seller_id = ?`,
            [req.user.id]
        );

        const [bidStats] = await pool.execute(
            `SELECT 
                COUNT(*) as total_bids,
                SUM(CASE WHEN is_winning_bid = TRUE AND a.status = 'ended' THEN 1 ELSE 0 END) as won_auctions
             FROM bids b
             INNER JOIN auctions a ON b.auction_id = a.id
             WHERE b.user_id = ?`,
            [req.user.id]
        );

        res.json({
            success: true,
            data: {
                user: user,
                stats: {
                    auctions: auctionStats[0].total_auctions || 0,
                    active_auctions: auctionStats[0].active_auctions || 0,
                    bids: bidStats[0].total_bids || 0,
                    won: bidStats[0].won_auctions || 0
                }
            }
        });
    } catch (error) {
        console.error('Get profile error:', error);
        res.status(500).json({
            success: false,
            message: 'Server error'
        });
    }
});

// Update user profile
router.put('/profile', authenticate, async (req, res) => {
    try {
        const { full_name, email } = req.body;
        const userId = req.user.id;

        // Validation
        if (!full_name || !email) {
            return res.status(400).json({
                success: false,
                message: 'Full name and email are required'
            });
        }

        // Check if email is already taken by another user
        const [existingUsers] = await pool.execute(
            'SELECT id FROM users WHERE email = ? AND id != ?',
            [email, userId]
        );

        if (existingUsers.length > 0) {
            return res.status(400).json({
                success: false,
                message: 'Email is already taken'
            });
        }

        // Update user
        await pool.execute(
            'UPDATE users SET full_name = ?, email = ? WHERE id = ?',
            [full_name, email, userId]
        );

        // Get updated user
        const [users] = await pool.execute(
            `SELECT id, username, email, full_name, role, status, avatar_path, created_at 
             FROM users WHERE id = ?`,
            [userId]
        );

        res.json({
            success: true,
            message: 'Profile updated successfully',
            data: {
                user: users[0]
            }
        });
    } catch (error) {
        console.error('Update profile error:', error);
        res.status(500).json({
            success: false,
            message: 'Server error'
        });
    }
});

// Change password
router.put('/password', authenticate, async (req, res) => {
    try {
        const { current_password, new_password } = req.body;
        const userId = req.user.id;

        // Validation
        if (!current_password || !new_password) {
            return res.status(400).json({
                success: false,
                message: 'Current password and new password are required'
            });
        }

        if (new_password.length < 6) {
            return res.status(400).json({
                success: false,
                message: 'New password must be at least 6 characters long'
            });
        }

        // Get current user password
        const [users] = await pool.execute(
            'SELECT password_hash FROM users WHERE id = ?',
            [userId]
        );

        if (users.length === 0) {
            return res.status(404).json({
                success: false,
                message: 'User not found'
            });
        }

        // Verify current password
        const isPasswordValid = await bcrypt.compare(current_password, users[0].password_hash);

        if (!isPasswordValid) {
            return res.status(401).json({
                success: false,
                message: 'Current password is incorrect'
            });
        }

        // Hash new password
        const salt = await bcrypt.genSalt(10);
        const newPasswordHash = await bcrypt.hash(new_password, salt);

        // Update password
        await pool.execute(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [newPasswordHash, userId]
        );

        res.json({
            success: true,
            message: 'Password changed successfully'
        });
    } catch (error) {
        console.error('Change password error:', error);
        res.status(500).json({
            success: false,
            message: 'Server error'
        });
    }
});

// Upload avatar with error handling
router.post('/avatar', authenticate, (req, res, next) => {
    upload.single('avatar')(req, res, (err) => {
        if (err) {
            // Multer error handling
            if (err.code === 'LIMIT_FILE_SIZE') {
                return res.status(400).json({
                    success: false,
                    message: 'File size exceeds 2MB limit'
                });
            }
            // File filter error or other multer errors
            const errorMessage = err.message || 'File upload error';
            return res.status(400).json({
                success: false,
                message: errorMessage
            });
        }
        next();
    });
}, async (req, res) => {
    try {
        if (!req.file) {
            return res.status(400).json({
                success: false,
                message: 'No file uploaded'
            });
        }

        const userId = req.user.id;
        
        // Get current avatar path from database
        const [currentUsers] = await pool.execute(
            'SELECT avatar_path FROM users WHERE id = ?',
            [userId]
        );
        const oldAvatarPath = currentUsers.length > 0 ? currentUsers[0].avatar_path : null;

        // Save relative path (from frontend root)
        const avatarPath = 'uploads/avatars/' + req.file.filename;

        // Update user avatar
        await pool.execute(
            'UPDATE users SET avatar_path = ? WHERE id = ?',
            [avatarPath, userId]
        );

        // Delete old avatar if exists
        if (oldAvatarPath && oldAvatarPath.startsWith('uploads/avatars/')) {
            const oldFilePath = path.join(__dirname, '../../frontend', oldAvatarPath);
            if (fs.existsSync(oldFilePath)) {
                try {
                    fs.unlinkSync(oldFilePath);
                } catch (err) {
                    console.error('Error deleting old avatar:', err);
                }
            }
        }

        // Get updated user
        const [users] = await pool.execute(
            `SELECT id, username, email, full_name, role, status, avatar_path, created_at 
             FROM users WHERE id = ?`,
            [userId]
        );

        res.json({
            success: true,
            message: 'Avatar uploaded successfully',
            data: {
                user: users[0],
                avatar_path: avatarPath
            }
        });
    } catch (error) {
        console.error('Upload avatar error:', error);
        res.status(500).json({
            success: false,
            message: error.message || 'Server error'
        });
    }
});

module.exports = router;

