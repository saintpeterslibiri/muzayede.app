const jwt = require('jsonwebtoken');
const config = require('../appsettings.json');

// Verify JWT token
const authenticate = (req, res, next) => {
    try {
        const token = req.headers.authorization?.split(' ')[1] || req.headers['x-auth-token'];
        
        if (!token) {
            return res.status(401).json({ 
                success: false, 
                message: 'No token provided. Access denied.' 
            });
        }

        const decoded = jwt.verify(token, config.jwt.secret);
        req.user = decoded;
        next();
    } catch (error) {
        return res.status(401).json({ 
            success: false, 
            message: 'Invalid or expired token.' 
        });
    }
};

// Check if user is admin
const isAdmin = (req, res, next) => {
    if (req.user && req.user.role === 'admin') {
        next();
    } else {
        return res.status(403).json({ 
            success: false, 
            message: 'Access denied. Admin privileges required.' 
        });
    }
};

// Check if user is regular user or admin
const isUser = (req, res, next) => {
    if (req.user && (req.user.role === 'user' || req.user.role === 'admin')) {
        next();
    } else {
        return res.status(403).json({ 
            success: false, 
            message: 'Access denied. Authentication required.' 
        });
    }
};

module.exports = {
    authenticate,
    isAdmin,
    isUser
};

