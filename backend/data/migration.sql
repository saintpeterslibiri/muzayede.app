-- ============================================
-- Migration: Align Auctions Table with Code
-- ============================================

USE muzayede_db;

-- 1. Make 'category_id' nullable so it doesn't block inserts when using 'category' string
-- 2. Make 'description' nullable as the code allows null
-- 3. Update 'status' ENUM to include 'pending'

ALTER TABLE auctions 
    MODIFY COLUMN category_id INT NULL,
    MODIFY COLUMN description TEXT NULL,
    MODIFY COLUMN status ENUM('draft', 'pending', 'active', 'ended', 'closed', 'cancelled') DEFAULT 'draft';

-- Update existing rows to have a category string if possible (optional)
UPDATE auctions a 
JOIN categories c ON a.category_id = c.id 
SET a.category = c.slug 
WHERE a.category IS NULL AND a.category_id IS NOT NULL;
