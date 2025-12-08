-- ============================================
-- MUZAYEDE.APP - Initial Database Schema
-- MySQL Database Structure
-- ============================================

-- Create database
DROP DATABASE IF EXISTS muzayede_db;
CREATE DATABASE muzayede_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE muzayede_db;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE  users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'banned', 'suspended') DEFAULT 'active',
    avatar_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CATEGORIES TABLE
-- ============================================
CREATE TABLE  categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO categories (name, slug, description) VALUES
('Electronics', 'electronics', 'Electronic devices and gadgets'),
('Collectibles', 'collectibles', 'Rare and collectible items'),
('Other', 'other', 'Other miscellaneous items')
ON DUPLICATE KEY UPDATE name=name;

-- ============================================
-- AUCTIONS TABLE
-- ============================================
CREATE TABLE  auctions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category_id INT NOT NULL,
    starting_price DECIMAL(10, 2) NOT NULL,
    current_highest_bid DECIMAL(10, 2) DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('draft', 'active', 'ended', 'closed', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_seller_id (seller_id),
    INDEX idx_category_id (category_id),
    INDEX idx_status (status),
    INDEX idx_end_time (end_time),
    INDEX idx_start_time (start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BIDS TABLE
-- ============================================
CREATE TABLE  bids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auction_id INT NOT NULL,
    user_id INT NOT NULL,
    bid_amount DECIMAL(10, 2) NOT NULL,
    bid_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_auto_bid BOOLEAN DEFAULT FALSE,
    is_winning_bid BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_auction_id (auction_id),
    INDEX idx_user_id (user_id),
    INDEX idx_bid_time (bid_time),
    INDEX idx_is_winning_bid (is_winning_bid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AUTO_BIDS TABLE
-- ============================================
CREATE TABLE  auto_bids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auction_id INT NOT NULL,
    user_id INT NOT NULL,
    max_amount DECIMAL(10, 2) NOT NULL,
    current_bid_amount DECIMAL(10, 2) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_auction (auction_id, user_id),
    INDEX idx_auction_id (auction_id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PASSWORD_RESETS TABLE
-- ============================================
CREATE TABLE  password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SESSIONS TABLE (Optional - for session management)
-- ============================================
CREATE TABLE  sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    data TEXT DEFAULT NULL,
    last_activity INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VIEWS FOR COMMON QUERIES
-- ============================================

-- View: Active Auctions with Details
DROP VIEW IF EXISTS v_active_auctions;
CREATE VIEW v_active_auctions AS
SELECT 
    a.id,
    a.title,
    a.description,
    a.starting_price,
    a.current_highest_bid,
    a.image_path,
    a.start_time,
    a.end_time,
    a.status,
    a.created_at,
    u.id AS seller_id,
    u.username AS seller_username,
    u.full_name AS seller_name,
    c.name AS category_name,
    c.slug AS category_slug,
    (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) AS bid_count
FROM auctions a
INNER JOIN users u ON a.seller_id = u.id
INNER JOIN categories c ON a.category_id = c.id
WHERE a.status = 'active'
ORDER BY a.end_time ASC;

-- View: User Bid Summary
DROP VIEW IF EXISTS v_user_bid_summary;
CREATE VIEW v_user_bid_summary AS
SELECT 
    b.user_id,
    b.auction_id,
    a.title AS auction_title,
    b.bid_amount,
    b.bid_time,
    b.is_winning_bid,
    a.status AS auction_status,
    a.end_time,
    CASE 
        WHEN a.status = 'ended' AND b.is_winning_bid = TRUE THEN 'won'
        WHEN a.status = 'ended' AND b.is_winning_bid = FALSE THEN 'lost'
        WHEN a.status = 'active' AND b.is_winning_bid = TRUE THEN 'leading'
        WHEN a.status = 'active' AND b.is_winning_bid = FALSE THEN 'outbid'
        ELSE 'pending'
    END AS bid_status
FROM bids b
INNER JOIN auctions a ON b.auction_id = a.id
ORDER BY b.bid_time DESC;

-- ============================================
-- STORED PROCEDURES (Optional - for complex operations)
-- ============================================

DELIMITER //

-- Procedure: Place a bid
DROP PROCEDURE IF EXISTS sp_place_bid;
CREATE PROCEDURE sp_place_bid(
    IN p_auction_id INT,
    IN p_user_id INT,
    IN p_bid_amount DECIMAL(10, 2),
    IN p_is_auto_bid BOOLEAN
)
BEGIN
    DECLARE v_current_highest DECIMAL(10, 2);
    DECLARE v_auction_status VARCHAR(20);
    DECLARE v_seller_id INT;
    
    -- Get auction details
    SELECT current_highest_bid, status, seller_id INTO v_current_highest, v_auction_status, v_seller_id
    FROM auctions WHERE id = p_auction_id;
    
    -- Check if auction is active
    IF v_auction_status != 'active' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Auction is not active';
    END IF;
    
    -- Check if user is not the seller
    IF p_user_id = v_seller_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Seller cannot bid on their own auction';
    END IF;
    
    -- Check if bid is higher than current highest
    IF p_bid_amount <= COALESCE(v_current_highest, 0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bid must be higher than current highest bid';
    END IF;
    
    -- Update previous winning bids
    UPDATE bids SET is_winning_bid = FALSE WHERE auction_id = p_auction_id;
    
    -- Insert new bid
    INSERT INTO bids (auction_id, user_id, bid_amount, is_auto_bid)
    VALUES (p_auction_id, p_user_id, p_bid_amount, p_is_auto_bid);
    
    -- Update auction current highest bid
    UPDATE auctions 
    SET current_highest_bid = p_bid_amount,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_auction_id;
    
    -- Mark new bid as winning
    UPDATE bids 
    SET is_winning_bid = TRUE 
    WHERE id = LAST_INSERT_ID();
END //

DELIMITER ;

-- ============================================
-- TRIGGERS
-- ============================================

DELIMITER //

-- Trigger: Update auction status when end_time is reached
-- Note: This would typically be handled by a cron job or scheduled task
-- This trigger is for reference

-- Trigger: Set initial current_highest_bid to starting_price when auction is activated
DROP TRIGGER IF EXISTS trg_auction_activate;
CREATE TRIGGER trg_auction_activate
BEFORE UPDATE ON auctions
FOR EACH ROW
BEGIN
    IF NEW.status = 'active' AND OLD.status != 'active' AND NEW.current_highest_bid IS NULL THEN
        SET NEW.current_highest_bid = NEW.starting_price;
    END IF;
END //

DELIMITER ;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Additional composite indexes for common query patterns

-- ============================================
-- SAMPLE DATA (Optional - for testing)
-- ============================================

-- Insert a test admin user (password: admin123 - should be hashed in real app)
-- Password hash for 'admin123' using bcrypt (you should use proper password hashing)
INSERT INTO users (username, email, password_hash, full_name, role, status) VALUES
('admin', 'admin@muzayede.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin', 'active')
ON DUPLICATE KEY UPDATE username=username;

-- ============================================
-- END OF SCHEMA
-- ============================================

