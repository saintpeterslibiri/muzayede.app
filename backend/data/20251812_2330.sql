-- ============================================
-- Migration: Align Database Schema with Code
-- ============================================

USE muzayede_db;

-- ============================================
-- AUCTIONS TABLE CHANGES
-- ============================================
-- 1. Make 'category_id' nullable so it doesn't block inserts when using 'category' string
-- 2. Make 'description' nullable as the code allows null
-- 3. Update 'status' ENUM to include 'pending'
-- 4. Rename 'current_highest_bid' to 'current_price' to match code
-- 5. Add 'category' string column

ALTER TABLE auctions 
    MODIFY COLUMN category_id INT NULL,
    MODIFY COLUMN description TEXT NULL,
    MODIFY COLUMN status ENUM('draft', 'pending', 'active', 'ended', 'closed', 'cancelled') DEFAULT 'draft';


-- Add category string column (if it doesn't exist, this will fail - ignore if already exists)
ALTER TABLE auctions 
    ADD COLUMN category VARCHAR(50) NULL AFTER category_id;

-- Update existing rows to have a category string if possible (optional)
UPDATE auctions a 
JOIN categories c ON a.category_id = c.id 
SET a.category = c.slug 
WHERE a.category IS NULL AND a.category_id IS NOT NULL;

-- ============================================
-- BIDS TABLE CHANGES
-- ============================================
-- Rename 'bid_amount' to 'amount' to match code
-- Note: If 'amount' column already exists, comment out or skip this line

ALTER TABLE bids 
    CHANGE COLUMN bid_amount amount DECIMAL(10, 2) NOT NULL;

-- ============================================
-- AUCTIONS TABLE - Add winner_id column
-- ============================================
-- Add winner_id to track who won the auction when it ends

ALTER TABLE auctions 
    ADD COLUMN winner_id INT NULL AFTER status,
    ADD FOREIGN KEY (winner_id) REFERENCES users(id) ON DELETE SET NULL,
    ADD INDEX idx_winner_id (winner_id);
