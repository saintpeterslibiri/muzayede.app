-- Increase precision for price/amount columns to support larger values
-- Changing from DECIMAL(10, 2) to DECIMAL(20, 2)

ALTER TABLE auctions MODIFY COLUMN starting_price DECIMAL(20, 2) NOT NULL;
ALTER TABLE auctions MODIFY COLUMN current_price DECIMAL(20, 2) DEFAULT NULL;

ALTER TABLE bids MODIFY COLUMN bid_amount DECIMAL(20, 2) NOT NULL;

ALTER TABLE auto_bids MODIFY COLUMN max_amount DECIMAL(20, 2) NOT NULL;
ALTER TABLE auto_bids MODIFY COLUMN current_bid_amount DECIMAL(20, 2) DEFAULT NULL;
