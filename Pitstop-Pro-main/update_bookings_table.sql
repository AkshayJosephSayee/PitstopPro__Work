-- Add status column to bookings table if it doesn't exist
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS status ENUM('pending', 'confirmed', 'completed', 'cancelled') 
DEFAULT 'pending' NOT NULL,
ADD COLUMN IF NOT EXISTS customer_id INT NOT NULL,
ADD CONSTRAINT fk_bookings_customer 
FOREIGN KEY (customer_id) REFERENCES tbl_customer(id);

-- Update any existing bookings to link to customers if needed
-- You may need to adjust this based on your existing data
UPDATE bookings SET status = 'pending' WHERE status IS NULL;