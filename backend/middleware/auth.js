// =====================================================
// AUTH.JS - Authentication Middleware
// =====================================================
// This middleware verifies user authentication.
// It checks the session token and attaches user info to request.
//
// NOTE: This is a placeholder implementation.
// Your friend will implement the full auth system.
// =====================================================


// -----------------------------------------------------
// Import dependencies
// -----------------------------------------------------

const db = require('../config/database');
const response = require('../utils/response');


// -----------------------------------------------------
// Verify Token Middleware
// -----------------------------------------------------
// Checks if request has valid authentication token.
// If valid, attaches user info to req.user
// If invalid, returns 401 Unauthorized
//
// Usage in routes:
//   Before calling route handler, call this middleware
//   const user = await authMiddleware.verifyToken(req, res);
//   if (!user) return; // Response already sent
//
// Token is sent in Authorization header:
//   Authorization: Bearer <token>

async function verifyToken(req, res) {
    try {
        // -------------------------------------------------
        // Extract token from Authorization header
        // -------------------------------------------------
        // Format: "Bearer <token>"
        
        const authHeader = req.headers['authorization'];
        
        // Check if Authorization header exists
        if (!authHeader) {
            response.unauthorized(res, 'No authorization token provided');
            return null;
        }
        
        // Check if it starts with "Bearer "
        if (!authHeader.startsWith('Bearer ')) {
            response.unauthorized(res, 'Invalid authorization format. Use: Bearer <token>');
            return null;
        }
        
        // Extract the token (remove "Bearer " prefix)
        const token = authHeader.slice(7); // "Bearer ".length = 7
        
        // Check if token is not empty
        if (!token || token.trim() === '') {
            response.unauthorized(res, 'Empty token provided');
            return null;
        }
        
        // -------------------------------------------------
        // Verify token in database
        // -------------------------------------------------
        // Check sessions table for valid, non-expired token
        
        const sql = `
            SELECT 
                s.id AS session_id,
                s.user_id,
                s.expires_at,
                u.id,
                u.username,
                u.email,
                u.full_name,
                u.role,
                u.status
            FROM sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.token = ?
        `;
        
        const [rows] = await db.pool.query(sql, [token]);
        
        // Check if token exists
        if (rows.length === 0) {
            response.unauthorized(res, 'Invalid or expired token');
            return null;
        }
        
        const session = rows[0];
        
        // -------------------------------------------------
        // Check if token is expired
        // -------------------------------------------------
        
        const now = new Date();
        const expiresAt = new Date(session.expires_at);
        
        if (now > expiresAt) {
            // Token expired - delete it from database
            await db.pool.query('DELETE FROM sessions WHERE token = ?', [token]);
            response.unauthorized(res, 'Token has expired. Please login again');
            return null;
        }
        
        // -------------------------------------------------
        // Check if user is banned
        // -------------------------------------------------
        
        if (session.status === 'banned') {
            response.forbidden(res, 'Your account has been banned');
            return null;
        }
        
        if (session.status === 'suspended') {
            response.forbidden(res, 'Your account has been suspended');
            return null;
        }
        
        // -------------------------------------------------
        // Return user object
        // -------------------------------------------------
        // This will be attached to req.user
        
        const user = {
            id: session.user_id,
            username: session.username,
            email: session.email,
            full_name: session.full_name,
            role: session.role,
            status: session.status
        };
        
        return user;
        
    } catch (error) {
        console.error('Error in verifyToken:', error);
        response.serverError(res, 'Authentication error');
        return null;
    }
}


// -----------------------------------------------------
// Optional Auth Middleware
// -----------------------------------------------------
// Same as verifyToken but doesn't return error if no token.
// Used for routes that work both with and without auth.
// Example: View auction (anyone can view, but logged in users see more)

async function optionalAuth(req, res) {
    try {
        const authHeader = req.headers['authorization'];
        
        // If no auth header, just return null (no user)
        if (!authHeader || !authHeader.startsWith('Bearer ')) {
            return null;
        }
        
        const token = authHeader.slice(7);
        
        if (!token || token.trim() === '') {
            return null;
        }
        
        // Try to verify token
        const sql = `
            SELECT 
                s.user_id,
                s.expires_at,
                u.username,
                u.email,
                u.full_name,
                u.role,
                u.status
            FROM sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.token = ? AND s.expires_at > NOW() AND u.status = 'active'
        `;
        
        const [rows] = await db.pool.query(sql, [token]);
        
        if (rows.length === 0) {
            return null;
        }
        
        return {
            id: rows[0].user_id,
            username: rows[0].username,
            email: rows[0].email,
            full_name: rows[0].full_name,
            role: rows[0].role,
            status: rows[0].status
        };
        
    } catch (error) {
        console.error('Error in optionalAuth:', error);
        return null;
    }
}


// -----------------------------------------------------
// Require Role Middleware
// -----------------------------------------------------
// Checks if user has required role (seller, admin, etc.)
// Must be called AFTER verifyToken
//
// Usage:
//   const user = await authMiddleware.verifyToken(req, res);
//   if (!user) return;
//   if (!authMiddleware.requireRole(user, 'seller', res)) return;

function requireRole(user, requiredRole, res) {
    // Admin can do everything
    if (user.role === 'admin') {
        return true;
    }
    
    // Check if user has required role
    if (user.role !== requiredRole) {
        response.forbidden(res, `This action requires ${requiredRole} role`);
        return false;
    }
    
    return true;
}


// -----------------------------------------------------
// Require Seller Middleware
// -----------------------------------------------------
// Shorthand for requireRole(user, 'seller', res)

function requireSeller(user, res) {
    if (user.role === 'admin' || user.role === 'seller') {
        return true;
    }
    
    response.forbidden(res, 'Only sellers can perform this action');
    return false;
}


// -----------------------------------------------------
// Require Admin Middleware
// -----------------------------------------------------
// Shorthand for requireRole(user, 'admin', res)

function requireAdmin(user, res) {
    if (user.role !== 'admin') {
        response.forbidden(res, 'Admin access required');
        return false;
    }
    
    return true;
}


// -----------------------------------------------------
// TEMPORARY: Get user for testing
// -----------------------------------------------------
// This function is for TESTING ONLY while auth is not implemented.
// It returns a fake user based on a test user ID.
// REMOVE THIS when your friend implements real auth.

async function getTestUser(testUserId) {
    try {
        const [rows] = await db.pool.query(
            `SELECT id, username, email, full_name, role, status 
             FROM users WHERE id = ?`,
            [testUserId]
        );
        
        if (rows.length === 0) {
            return null;
        }
        
        return rows[0];
    } catch (error) {
        console.error('Error getting test user:', error);
        return null;
    }
}


// -----------------------------------------------------
// Export all functions
// -----------------------------------------------------

module.exports = {
    verifyToken,
    optionalAuth,
    requireRole,
    requireSeller,
    requireAdmin,
    getTestUser  // REMOVE when real auth is implemented
};