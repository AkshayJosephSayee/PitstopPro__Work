-- Run this query to add customer information columns to tbl_bookings table
-- This allows storing booking information without requiring user login

ALTER TABLE tbl_bookings 
ADD COLUMN customer_name VARCHAR(100) AFTER booking_id,
ADD COLUMN customer_email VARCHAR(100) AFTER customer_name,
ADD COLUMN customer_mobile VARCHAR(20) AFTER customer_email,
ADD COLUMN service_type VARCHAR(100) AFTER customer_mobile;

-- Now the tbl_bookings table will have these columns:
-- booking_id, customer_name, customer_email, customer_mobile, service_type, 
-- User_id_, vehicle_id, Service_id, booking_date, booking_time, 
-- b_status, special_request, created_at

-- The User_id_, vehicle_id, and Service_id can remain NULL for guest bookings
