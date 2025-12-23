// =====================================================
// AUCTION.JS - Auction Route Handlers
// =====================================================
// Handles all auction-related operations:
// - List all auctions (with filtering & pagination)
// - Get single auction by ID
// - Create new auction
// - Update auction
// - Delete auction
// =====================================================


// -----------------------------------------------------
// Import dependencies
// -----------------------------------------------------

// Database connection pool
const db = require('../config/database');

// Response helpers
const response = require('../utils/response');

// Validation helpers
const validation = require('../utils/validation');


// -----------------------------------------------------
// GET /api/auctions - List all auctions
// -----------------------------------------------------
// Query parameters:
//   - category: Filter by category (electronics, collectibles, etc.)
//   - status: Filter by status (active, ended, pending)
//   - sort: Sort order (ending_soon, newest, highest_bid)
//   - page: Page number for pagination (default: 1)
//   - limit: Items per page (default: 10)
//   - q: Search query for title
//
// Example: GET /api/auctions?category=electronics&sort=ending_soon&page=1

async function getAllAuctions(req, res) {
    console.log('GET /api/auctions - Request received');
    try {
        // Extract query parameters from request
        // req.query was set in server.js by parseQueryParams()
        const { category, status, sort, page, limit, q } = req.query;
        
        // -------------------------------------------------
        // Build SQL query dynamically
        // -------------------------------------------------
        // We start with base query and add conditions based on filters
        
        // Base SELECT query
        // We join with users table to get seller information
        // COALESCE(a.current_price, a.current_highest_bid) handles both column names
        let sql = `
            SELECT 
                a.id,
                a.title,
                a.description,
                a.category,
                a.image_path,
                a.starting_price,
                COALESCE(
                    (SELECT MAX(bid_amount) FROM bids WHERE auction_id = a.id),
                    a.starting_price
                ) as current_price,
                a.start_time,
                a.end_time,
                a.status,
                a.created_at,
                u.id AS seller_id,
                u.username AS seller_username,
                u.full_name AS seller_name
            FROM auctions a
            JOIN users u ON a.seller_id = u.id
            WHERE 1=1
        `;
        
        // Array to hold parameter values (for prepared statement)
        // Prepared statements prevent SQL injection attacks
        const params = [];
        
        // -------------------------------------------------
        // Add filters to query
        // -------------------------------------------------
        
        // Filter by category
        if (category && category !== '') {
            // ? is a placeholder, actual value goes in params array
            sql += ` AND a.category = ?`;
            params.push(category);
        }
        
        // Filter by status
        // If no status specified, default to showing only active auctions
        if (status && status !== '') {
            sql += ` AND a.status = ?`;
            params.push(status);
            // If status is 'active', also filter out expired auctions
            if (status === 'active') {
                sql += ` AND a.start_time <= NOW() AND a.end_time > NOW()`;
            }
        } else {
            // By default, show only active auctions that have started and haven't ended yet
            sql += ` AND a.status = 'active' AND a.start_time <= NOW() AND a.end_time > NOW()`;
        }
        
        // Search by title (if q parameter provided)
        if (q && q !== '') {
            // LIKE with % for partial matching
            // %laptop% matches "Gaming Laptop", "laptop case", etc.
            sql += ` AND a.title LIKE ?`;
            params.push(`%${q}%`);
        }
        
        // -------------------------------------------------
        // Add sorting
        // -------------------------------------------------
        
        // Determine sort order based on 'sort' parameter
        switch (sort) {
            case 'ending_soon':
                // Auctions ending soonest first
                sql += ` ORDER BY a.end_time ASC`;
                break;
            case 'newest':
                // Most recently created first
                sql += ` ORDER BY a.created_at DESC`;
                break;
            case 'highest_bid':
                // Highest current price first
                sql += ` ORDER BY a.current_price DESC`;
                break;
            case 'lowest_bid':
                // Lowest current price first
                sql += ` ORDER BY a.current_price ASC`;
                break;
            default:
                // Default: ending soon
                sql += ` ORDER BY a.end_time ASC`;
        }
        
        // -------------------------------------------------
        // Add pagination
        // -------------------------------------------------
        
        // Parse page and limit, with defaults
        // parseInt(): Convert string to integer
        // || : If NaN or 0, use default value
        const pageNum = parseInt(page) || 1;
        const limitNum = parseInt(limit) || 10;
        
        // Calculate offset (how many rows to skip)
        // Page 1: offset 0 (skip 0, get items 1-10)
        // Page 2: offset 10 (skip 10, get items 11-20)
        const offset = (pageNum - 1) * limitNum;
        
        // LIMIT: Maximum number of rows to return
        // OFFSET: Number of rows to skip
        sql += ` LIMIT ? OFFSET ?`;
        params.push(limitNum, offset);
        
        // -------------------------------------------------
        // Execute query
        // -------------------------------------------------
        
        // db.query() returns array: [rows, fields]
        // We only need rows, so we destructure with [rows]
        const [rows] = await db.query(sql, params);

        // Map rows to include image URL
        const auctions = rows.map(auction => ({
            ...auction,
            image_path: auction.image_path || (auction.id ? `/api/auctions/${auction.id}/image` : null)
        }));
        
        // -------------------------------------------------
        // Get total count for pagination info
        // -------------------------------------------------
        
        // We need to know total number of matching auctions
        // to calculate total pages
        let countSql = `
            SELECT COUNT(*) as total 
            FROM auctions a 
            WHERE 1=1
        `;
        const countParams = [];
        
        // Apply same filters (but not pagination)
        if (category && category !== '') {
            countSql += ` AND a.category = ?`;
            countParams.push(category);
        }
        
        if (status && status !== '') {
            countSql += ` AND a.status = ?`;
            countParams.push(status);
            // If status is 'active', also filter out expired auctions
            if (status === 'active') {
                countSql += ` AND a.start_time <= NOW() AND a.end_time > NOW()`;
            }
        } else {
            countSql += ` AND a.status = 'active' AND a.start_time <= NOW() AND a.end_time > NOW()`;
        }
        
        if (q && q !== '') {
            countSql += ` AND a.title LIKE ?`;
            countParams.push(`%${q}%`);
        }
        
        const [countResult] = await db.query(countSql, countParams);
        const totalItems = countResult[0].total;
        const totalPages = Math.ceil(totalItems / limitNum);
        
        // -------------------------------------------------
        // Send response
        // -------------------------------------------------
        
        response.sendSuccess(res, {
            auctions: auctions,
            pagination: {
                currentPage: pageNum,
                totalPages: totalPages,
                totalItems: totalItems,
                itemsPerPage: limitNum,
                hasNextPage: pageNum < totalPages,
                hasPrevPage: pageNum > 1
            }
        }, 'Auctions retrieved successfully');
        
    } catch (error) {
        console.error('Error in getAllAuctions:', error);
        response.serverError(res, 'Failed to retrieve auctions');
    }
}


// -----------------------------------------------------
// GET /api/auctions/:id - Get single auction by ID
// -----------------------------------------------------
// Returns detailed information about one auction,
// including seller info and bid count

async function getAuctionById(req, res) {
    try {
        // Get auction ID from URL parameters
        // req.params was set in server.js by matchRoute()
        const auctionId = req.params.id;
        
        // Validate ID is a number
        if (!validation.isValidNumber(auctionId)) {
            response.sendError(res, 'Invalid auction ID', 400);
            return;
        }
        
        // Query to get auction with seller info
        // We also check for the highest auto-bid to display the true "current highest bid"
        // If there is an active auto-bid higher than current_price, that should be considered?
        // Actually, current_price in auctions table SHOULD reflect the current winning price.
        // But if the user wants to see the max_amount of the winning auto-bid, that's private info usually.
        // However, if the request implies that current_price is not updating correctly, 
        // we can fetch the max bid from bids table to be sure.
        
        const sql = `
            SELECT 
                a.*,
                COALESCE(
                    (SELECT MAX(bid_amount) FROM bids WHERE auction_id = a.id),
                    a.starting_price
                ) as current_price,
                u.username AS seller_username,
                u.full_name AS seller_name,
                u.avatar_path AS seller_avatar,
                (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) AS bid_count
            FROM auctions a
            JOIN users u ON a.seller_id = u.id
            WHERE a.id = ?
        `;
        
        const [rows] = await db.query(sql, [auctionId]);
        
        // Check if auction exists
        if (rows.length === 0) {
            response.notFound(res, 'Auction not found');
            return;
        }

        const auction = rows[0];
        // If image_path is null (meaning image is in DB), construct URL
        if (!auction.image_path) {
            auction.image_path = `/api/auctions/${auction.id}/image`;
        }
        
        // -------------------------------------------------
        // Get user's auto-bid if authenticated
        // -------------------------------------------------
        let userAutoBid = null;
        if (req.user && req.user.id) {
            const userId = req.user.id;
            const [autoBidRows] = await db.query(
                'SELECT max_amount, is_active FROM auto_bids WHERE auction_id = ? AND user_id = ?',
                [auctionId, userId]
            );
            
            if (autoBidRows.length > 0 && autoBidRows[0].is_active) {
                userAutoBid = {
                    max_amount: parseFloat(autoBidRows[0].max_amount),
                    is_active: true
                };
            }
        }
        
        // Add user's auto-bid to auction object if exists
        if (userAutoBid) {
            auction.user_auto_bid = userAutoBid;
        }
        
        // Return the auction
        response.sendSuccess(res, { auction: auction }, 'Auction retrieved successfully');
        
    } catch (error) {
        console.error('Error in getAuctionById:', error);
        response.serverError(res, 'Failed to retrieve auction');
    }
}

// -----------------------------------------------------
// GET /api/auctions/:id/image - Get auction image
// -----------------------------------------------------
async function getAuctionImage(req, res) {
    try {
        const auctionId = req.params.id;
        
        const [rows] = await db.query(
            'SELECT image_data, image_mime_type FROM auctions WHERE id = ?',
            [auctionId]
        );
        
        if (rows.length === 0 || !rows[0].image_data) {
            // Return placeholder or 404
            // For now, 404
            return res.status(404).send('Image not found');
        }
        
        const img = rows[0];
        res.setHeader('Content-Type', img.image_mime_type || 'image/jpeg');
        res.send(img.image_data);
        
    } catch (error) {
        console.error('Error in getAuctionImage:', error);
        res.status(500).send('Error retrieving image');
    }
}


// -----------------------------------------------------
// POST /api/auctions - Create new auction
// -----------------------------------------------------
// Required fields in request body:
//   - title: Auction title
//   - category: Category (electronics, collectibles, etc.)
//   - starting_price: Starting price
//   - start_time: When auction starts
//   - end_time: When auction ends
// Optional:
//   - description: Item description
//   - image_path: Path to item image

async function createAuction(req, res) {
    console.log('POST /api/auctions - Request received');
    try {
        // Get data from request body
        // req.body was set in server.js by parseRequestBody()
        const { title, description, category, starting_price, start_time, end_time } = req.body;
        
        // Handle image upload
        let imageData = null;
        let imageMimeType = null;
        
        if (req.file) {
            console.log(`Image uploaded: ${req.file.originalname}, size: ${req.file.size}, mimetype: ${req.file.mimetype}`);
            imageData = req.file.buffer;
            imageMimeType = req.file.mimetype;
        } else {
            console.log('No image file received in request');
        }
        
        // -------------------------------------------------
        // Authentication check
        // -------------------------------------------------
        // For now, we'll use a hardcoded user ID (seller1 = id 2)
        // Later, this will come from auth middleware
        // TODO: Replace with actual authenticated user
        const sellerId = req.user?.id || 2; // Default to seller1 for testing
        
        // -------------------------------------------------
        // Validate input
        // -------------------------------------------------
        
        const validationErrors = validation.validateAuction(req.body);
        
        if (validationErrors) {
            // Join all error messages into one string
            response.sendError(res, validationErrors.join(', '), 400);
            return;
        }
        
        // -------------------------------------------------
        // Check if user is a seller
        // -------------------------------------------------
        
        const [userRows] = await db.query(
            'SELECT role FROM users WHERE id = ?',
            [sellerId]
        );
        
        if (userRows.length === 0) {
            response.sendError(res, 'User not found', 404);
            return;
        }
        
        // Only sellers and admins can create auctions
        if (userRows[0].role === 'buyer') {
            response.forbidden(res, 'Only sellers can create auctions');
            return;
        }
        
        // -------------------------------------------------
        // Determine initial status
        // -------------------------------------------------
        
        // If start_time is in the future, status is 'pending'
        // If start_time is now or past, status is 'active'
        const now = new Date();
        const startDate = new Date(start_time);
        const initialStatus = startDate > now ? 'pending' : 'active';
        
        // -------------------------------------------------
        // Insert auction into database
        // -------------------------------------------------
        
        const insertSql = `
            INSERT INTO auctions 
            (seller_id, title, description, category, image_data, image_mime_type, starting_price, current_price, start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `;
        
        // current_price starts equal to starting_price
        const insertParams = [
            sellerId,
            title,
            description || null,
            category,
            imageData,
            imageMimeType,
            starting_price,
            starting_price, // current_price = starting_price initially
            start_time,
            end_time,
            initialStatus
        ];
        
        const [result] = await db.query(insertSql, insertParams);
        
        // result.insertId contains the auto-generated ID
        const newAuctionId = result.insertId;
        
        // -------------------------------------------------
        // Fetch and return the created auction
        // -------------------------------------------------
        
        const [newAuction] = await db.query(
            'SELECT * FROM auctions WHERE id = ?',
            [newAuctionId]
        );
        
        // 201 = Created (HTTP status for successful resource creation)
        response.sendSuccess(res, { auction: newAuction[0] }, 'Auction created successfully', 201);
        
    } catch (error) {
        console.error('Error in createAuction:', error);
        response.serverError(res, 'Failed to create auction');
    }
}


// -----------------------------------------------------
// PUT /api/auctions/:id - Update auction
// -----------------------------------------------------
// Only the seller who created the auction can update it.
// Can only update if auction is still pending (not started).

async function updateAuction(req, res) {
    try {
        const auctionId = req.params.id;
        const { title, description, category, starting_price, start_time, end_time, image_path } = req.body;
        
        // TODO: Get actual user ID from auth
        const userId = req.user?.id || 2;
        
        // Validate auction ID
        if (!validation.isValidNumber(auctionId)) {
            response.sendError(res, 'Invalid auction ID', 400);
            return;
        }
        
        // -------------------------------------------------
        // Check if auction exists and user owns it
        // -------------------------------------------------
        
        const [auctionRows] = await db.query(
            'SELECT * FROM auctions WHERE id = ?',
            [auctionId]
        );
        
        if (auctionRows.length === 0) {
            response.notFound(res, 'Auction not found');
            return;
        }
        
        const auction = auctionRows[0];
        
        // Check ownership
        if (auction.seller_id !== userId) {
            response.forbidden(res, 'You can only update your own auctions');
            return;
        }
        
        // Check if auction can be updated
        // Can only update pending auctions (not yet started)
        if (auction.status !== 'pending') {
            response.sendError(res, 'Can only update pending auctions', 400);
            return;
        }
        
        // -------------------------------------------------
        // Build UPDATE query dynamically
        // -------------------------------------------------
        // Only update fields that were provided
        
        const updates = [];
        const updateParams = [];
        
        if (title !== undefined) {
            updates.push('title = ?');
            updateParams.push(title);
        }
        
        if (description !== undefined) {
            updates.push('description = ?');
            updateParams.push(description);
        }
        
        if (category !== undefined) {
            updates.push('category = ?');
            updateParams.push(category);
        }
        
        if (starting_price !== undefined) {
            updates.push('starting_price = ?');
            updates.push('current_price = ?'); // Also update current_price
            updateParams.push(starting_price, starting_price);
        }
        
        if (start_time !== undefined) {
            updates.push('start_time = ?');
            updateParams.push(start_time);
        }
        
        if (end_time !== undefined) {
            updates.push('end_time = ?');
            updateParams.push(end_time);
        }
        
        if (image_path !== undefined) {
            updates.push('image_path = ?');
            updateParams.push(image_path);
        }
        
        // If nothing to update
        if (updates.length === 0) {
            response.sendError(res, 'No fields to update', 400);
            return;
        }
        
        // Add auction ID to params
        updateParams.push(auctionId);
        
        // Execute UPDATE
        const updateSql = `UPDATE auctions SET ${updates.join(', ')} WHERE id = ?`;
        await db.query(updateSql, updateParams);
        
        // Fetch updated auction
        const [updatedAuction] = await db.query(
            'SELECT * FROM auctions WHERE id = ?',
            [auctionId]
        );
        
        response.sendSuccess(res, { auction: updatedAuction[0] }, 'Auction updated successfully');
        
    } catch (error) {
        console.error('Error in updateAuction:', error);
        response.serverError(res, 'Failed to update auction');
    }
}


// -----------------------------------------------------
// DELETE /api/auctions/:id - Delete auction
// -----------------------------------------------------
// Only the seller who created it can delete.
// Can only delete pending auctions (no bids yet).

async function deleteAuction(req, res) {
    try {
        const auctionId = req.params.id;
        
        // TODO: Get actual user ID from auth
        const userId = req.user?.id || 2;
        
        // Validate auction ID
        if (!validation.isValidNumber(auctionId)) {
            response.sendError(res, 'Invalid auction ID', 400);
            return;
        }
        
        // Check if auction exists
        const [auctionRows] = await db.query(
            'SELECT * FROM auctions WHERE id = ?',
            [auctionId]
        );
        
        if (auctionRows.length === 0) {
            response.notFound(res, 'Auction not found');
            return;
        }
        
        const auction = auctionRows[0];
        
        // Check ownership
        if (auction.seller_id !== userId) {
            response.forbidden(res, 'You can only delete your own auctions');
            return;
        }
        
        // Check if auction has bids
        const [bidRows] = await db.query(
            'SELECT COUNT(*) as count FROM bids WHERE auction_id = ?',
            [auctionId]
        );
        
        if (bidRows[0].count > 0) {
            response.sendError(res, 'Cannot delete auction with existing bids', 400);
            return;
        }
        
        // Delete the auction
        await db.query('DELETE FROM auctions WHERE id = ?', [auctionId]);
        
        response.sendSuccess(res, null, 'Auction deleted successfully');
        
    } catch (error) {
        console.error('Error in deleteAuction:', error);
        response.serverError(res, 'Failed to delete auction');
    }
}


// -----------------------------------------------------
// Export all functions
// -----------------------------------------------------

module.exports = {
    getAllAuctions,
    getAuctionById,
    createAuction,
    updateAuction,
    deleteAuction,
    getAuctionImage
};