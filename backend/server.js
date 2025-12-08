const express = require('express');
const cors = require('cors');
const multer = require('multer');
const config = require('./appsettings.json');
const authRoutes = require('./routes/auth');
const userRoutes = require('./routes/users');

const app = express();
const PORT = config.server.port || 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static files from frontend/uploads
const path = require('path');
app.use('/uploads', express.static(path.join(__dirname, '../frontend/uploads')));

// Routes
app.use('/api/auth', authRoutes);
app.use('/api/users', userRoutes);

// Health check
app.get('/api/health', (req, res) => {
    res.json({ status: 'ok', message: 'Server is running' });
});

// Error handling middleware
app.use((err, req, res, next) => {
    // Multer errors
    if (err instanceof multer.MulterError) {
        if (err.code === 'LIMIT_FILE_SIZE') {
            return res.status(400).json({
                success: false,
                message: 'File size exceeds 2MB limit'
            });
        }
        return res.status(400).json({
            success: false,
            message: err.message || 'File upload error'
        });
    }
    
    // Other errors
    if (err.message) {
        return res.status(400).json({
            success: false,
            message: err.message
        });
    }
    
    // Unknown errors
    console.error('Error:', err);
    res.status(500).json({
        success: false,
        message: 'Internal server error'
    });
});

// Start server
app.listen(PORT, () => {
    console.log(`Server is running on http://localhost:${PORT}`);
});

