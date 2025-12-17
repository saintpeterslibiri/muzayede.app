// This is the entry point of our backend application.
// All incoming requests are routed to appropriate handlers.


//Importing parts

const http = require('http');
const url = require('url');

const db = require('./config/database');

const response = require('./utils/response');

const auctionRoutes = require('./routes/auction');
const bidRoutes = require('./routes/bids');
const profileRoutes = require('./routes/profile');
const adminRoutes = require('./routes/admin');

//server configuration
//chechk if port is set in env var if not set , use 3000 as default

const PORT = process.env.PORT || 3000;

function parseRequestBody(req){
    return  new Promise((resolve,reject) =>{
        //array to collect data chunks
        const chunks = [];

        req.on('data', (chunk)=> {
            chunks.push(chunk);
        });

        req.on('end',() =>{
            if(chunks.length === 0){
                resolve({});
                return;
            }

            try{
                //buffer.concat() = combine all chunks into single string
                //toString() = convert vuffer to string
                const bodyString = Buffer.concat(chunks).toString();

                //converts json string to JavaScript object
                const bodyObject = JSON.parse(bodyString);

                resolve(bodyObject);
            }catch (error){
                // if json is invalid  reject with error
                reject(new Error ('Invalid JSON format'));
            }

        });
        //fired if somethings goes wrong
        req.on('error',(error) => {
            reject(error);
        });
    });
}



// Parse query parameters from URL

// URL: /api/auctions?category=electronics&sort=newest
// Returns: { category: 'electronics', sort: 'newest' }

function parseQueryParams(urlString){
    // URL constructor: Parses URL string into components
    // 'http://localhost' is base URL (required but we only need query)
    const parseUrl = new URL(urlString, 'http://localhost');
    // searchParams: URLSearchParams object containing query params 
    return Object.fromEntries(parseUrl.searchParams);
}

//Extract route parameters from Url
function matchRoute(urlPath,pattern){
    //split path by /
    const urlParts = urlPath.split('/');
    const patternParts = pattern.split('/');

    if(urlParts.length !== patternParts.length){
        return null;
    }

    //object to store extracted parameters
    const params = {};

    for(let i = 0; i < patternParts.length; i++){
        const patternPart = patternParts[i];
        const urlPart = urlParts[i];

        //If pattern part starts with ':' its a parameter
        if(patternPart.startsWith(':')){
            //remove it and use as key
            const paramName = patternPart.slice(1);
            params[paramName]=urlPart;
        }
        //otherwise, parts must match exactly
        else if(patternPart !== urlPart){
            return null;
        }
    
    }
    return params;
}

function handleCORS(req, res) {
    // Set CORS headers
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    
    // If OPTIONS request (preflight), respond immediately
    if (req.method === 'OPTIONS') {
        res.writeHead(204); // 204 = No Content
        res.end();
        return true; // Signal that request was handled
    }
    
    return false; // Continue processing request
}



// Main request handler

// This function is called for every incoming HTTP request.
// It determines which route handler should process the request.


async function handleRequest(req, res) {
    // Handle CORS preflight requests
    if (handleCORS(req, res)) {
        return;
    }
    
    // Parse the URL to get pathname
    // '/api/auctions?category=electronics' → '/api/auctions'
    const parsedUrl = url.parse(req.url);
    const pathname = parsedUrl.pathname;
    
    // Get HTTP method (GET, POST, PUT, DELETE)
    const method = req.method;
    
    // Log incoming request (helpful for debugging)
    console.log(`[${new Date().toISOString()}] ${method} ${pathname}`);
    
    try {
        // Parse query parameters
        const query = parseQueryParams(req.url);
        
        // Attach query to request object for easy access
        req.query = query;
        
        // -------------------------------------------------
        // Route matching
        // -------------------------------------------------
        // Check URL and method, call appropriate handler
        
        // -----------------------------
        // AUCTION ROUTES
        // -----------------------------
        
        // GET /api/auctions - List all auctions
        if (pathname === '/api/auctions' && method === 'GET') {
            await auctionRoutes.getAllAuctions(req, res);
            return;
        }
        
        // POST /api/auctions - Create new auction
        if (pathname === '/api/auctions' && method === 'POST') {
            req.body = await parseRequestBody(req);
            await auctionRoutes.createAuction(req, res);
            return;
        }
        
        // GET /api/auctions/:id - Get single auction
        let params = matchRoute(pathname, '/api/auctions/:id');
        if (params && method === 'GET') {
            req.params = params;
            await auctionRoutes.getAuctionById(req, res);
            return;
        }
        
        // PUT /api/auctions/:id - Update auction
        params = matchRoute(pathname, '/api/auctions/:id');
        if (params && method === 'PUT') {
            req.params = params;
            req.body = await parseRequestBody(req);
            await auctionRoutes.updateAuction(req, res);
            return;
        }
        
        // DELETE /api/auctions/:id - Delete auction
        params = matchRoute(pathname, '/api/auctions/:id');
        if (params && method === 'DELETE') {
            req.params = params;
            await auctionRoutes.deleteAuction(req, res);
            return;
        }
        
        // -----------------------------
        // BID ROUTES
        // -----------------------------
        
        // GET /api/auctions/:id/bids - Get bids for auction
        params = matchRoute(pathname, '/api/auctions/:id/bids');
        if (params && method === 'GET') {
            req.params = params;
            await bidRoutes.getBidsByAuction(req, res);
            return;
        }
        
        // POST /api/auctions/:id/bids - Place a bid
        params = matchRoute(pathname, '/api/auctions/:id/bids');
        if (params && method === 'POST') {
            req.params = params;
            req.body = await parseRequestBody(req);
            await bidRoutes.placeBid(req, res);
            return;
        }
        
        // POST /api/auctions/:id/auto-bid - Set auto-bid
        params = matchRoute(pathname, '/api/auctions/:id/auto-bid');
        if (params && method === 'POST') {
            req.params = params;
            req.body = await parseRequestBody(req);
            await bidRoutes.setAutoBid(req, res);
            return;
        }
        
        // -----------------------------
        // PROFILE ROUTES
        // -----------------------------
        
        // GET /api/profile - Get current user profile
        if (pathname === '/api/profile' && method === 'GET') {
            await profileRoutes.getProfile(req, res);
            return;
        }
        
        // PUT /api/profile - Update profile
        if (pathname === '/api/profile' && method === 'PUT') {
            req.body = await parseRequestBody(req);
            await profileRoutes.updateProfile(req, res);
            return;
        }
        
        // GET /api/my/auctions - Get user's auctions
        if (pathname === '/api/my/auctions' && method === 'GET') {
            await profileRoutes.getMyAuctions(req, res);
            return;
        }
        
        // GET /api/my/bids - Get user's bids
        if (pathname === '/api/my/bids' && method === 'GET') {
            await profileRoutes.getMyBids(req, res);
            return;
        }
        
        // -----------------------------
        // ADMIN ROUTES
        // -----------------------------
        
        // GET /api/admin/stats - Get dashboard statistics
        if (pathname === '/api/admin/stats' && method === 'GET') {
            await adminRoutes.getStats(req, res);
            return;
        }
        
        // GET /api/admin/users - Get all users
        if (pathname === '/api/admin/users' && method === 'GET') {
            await adminRoutes.getAllUsers(req, res);
            return;
        }
        
        // PUT /api/admin/users/:id/ban - Ban a user
        params = matchRoute(pathname, '/api/admin/users/:id/ban');
        if (params && method === 'PUT') {
            req.params = params;
            await adminRoutes.banUser(req, res);
            return;
        }
        
        // DELETE /api/admin/auctions/:id - Delete auction (admin)
        params = matchRoute(pathname, '/api/admin/auctions/:id');
        if (params && method === 'DELETE') {
            req.params = params;
            await adminRoutes.deleteAuction(req, res);
            return;
        }
        
        // -----------------------------
        // Health check endpoint
        // -----------------------------
        // Simple endpoint to check if server is running
        if (pathname === '/api/health' && method === 'GET') {
            response.sendSuccess(res, { status: 'ok', timestamp: new Date().toISOString() }, 'Server is running');
            return;
        }
        
        // -----------------------------
        // 404 - Route not found
        // -----------------------------
        // If no route matched, return 404
        response.notFound(res, `Route ${method} ${pathname} not found`);
        
    } catch (error) {
        // Log error for debugging
        console.error('Server error:', error);
        
        // Send error response
        if (error.message === 'Invalid JSON format') {
            response.sendError(res, 'Invalid JSON format in request body', 400);
        } else {
            response.serverError(res, 'Internal server error');
        }
    }
}


// -----------------------------------------------------
// Create and start HTTP server
// -----------------------------------------------------

// createServer(): Creates HTTP server instance
// Takes a callback function that handles all requests
const server = http.createServer(handleRequest);

// server.listen(): Start listening for connections
// PORT: Port number to listen on
// Callback: Called when server starts successfully
server.listen(PORT, async () => {
    console.log('=====================================================');
    console.log('   MUZAYEDE.APP Backend Server');
    console.log('=====================================================');
    console.log(`   Server running at: http://localhost:${PORT}`);
    console.log(`   Started at: ${new Date().toLocaleString()}`);
    console.log('=====================================================');
    
    // Test database connection on startup
    await db.testConnection();
    
    console.log('=====================================================');
    console.log('   Available endpoints:');
    console.log('   - GET  /api/health          (Health check)');
    console.log('   - GET  /api/auctions        (List auctions)');
    console.log('   - POST /api/auctions        (Create auction)');
    console.log('   - GET  /api/auctions/:id    (Get auction)');
    console.log('   - And more...');
    console.log('=====================================================');
});


// -----------------------------------------------------
// Handle server errors
// -----------------------------------------------------

// 'error' event: Fired when server encounters an error
server.on('error', (error) => {
    if (error.code === 'EADDRINUSE') {
        console.error(`❌ Port ${PORT} is already in use.`);
        console.error('   Try a different port or stop the other process.');
    } else {
        console.error('❌ Server error:', error.message);
    }
    process.exit(1);
});


// -----------------------------------------------------
// Graceful shutdown
// -----------------------------------------------------
// Handle process termination signals (Ctrl+C, etc.)
// Close server and database connections properly

process.on('SIGINT', () => {
    console.log('\n🛑 Shutting down server...');
    server.close(() => {
        console.log('   Server closed.');
        process.exit(0);
    });
});