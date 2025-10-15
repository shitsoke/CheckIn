CREATE DATABASE IF NOT EXISTS checkin;
USE checkin;

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
);
INSERT INTO roles (name) VALUES ('admin'), ('customer');

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(50) NOT NULL,
  middle_name VARCHAR(50),
  last_name VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role_id INT NOT NULL DEFAULT 2,
  is_banned TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE profiles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  phone VARCHAR(30),
  address VARCHAR(255),
  avatar VARCHAR(255),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE room_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50),
  hourly_rate DECIMAL(10,2),
  description TEXT
);
INSERT INTO room_types (name, hourly_rate, description) VALUES
('Standard',150.00,'Basic single bed room'),
('Deluxe',250.00,'Comfortable room with double bed'),
('Suite',400.00,'Luxury suite with amenities');

CREATE TABLE rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_number VARCHAR(20),
  room_type_id INT,
  status ENUM('available','reserved','occupied','maintenance') DEFAULT 'available',
  FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  room_id INT,
  start_time DATETIME,
  end_time DATETIME,
  hours INT,
  total_amount DECIMAL(10,2),
  payment_method ENUM('cash','online'),
  status ENUM('reserved','confirmed','ongoing','checked_out','canceled') DEFAULT 'reserved',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (room_id) REFERENCES rooms(id)
);

CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  room_type_id INT,
  rating INT,
  comment TEXT,
  is_visible TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);



-- Create admin profile (avoids foreign key issues)
INSERT INTO profiles (user_id, phone, address, avatar)
SELECT id, '', '', NULL
FROM users
WHERE email = 'admin@checkin.com'
LIMIT 1;

INSERT INTO profiles (user_id, phone, address, avatar)
SELECT id, '', '', NULL FROM users WHERE email='admin@checkin.com' LIMIT 1;

-- Table to store room images. filepath should be a path relative to webroot (e.g. uploads/rooms/<file>)
CREATE TABLE IF NOT EXISTS room_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  filepath VARCHAR(255) NOT NULL,
  alt_text VARCHAR(255),
  is_primary TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Migration note:
-- After adding this table, upload images into `uploads/rooms/` and insert rows like:
-- INSERT INTO room_images (room_id, filepath, alt_text) VALUES (1, 'uploads/rooms/room1-1.jpg', 'Room 1 - main');

-- Add columns for email verification and password reset to users table
-- Run these if the users table exists already in a live DB
ALTER TABLE users
  ADD COLUMN email_verified TINYINT(1) DEFAULT 0,
  ADD COLUMN verification_token VARCHAR(128) NULL,
  ADD COLUMN reset_token VARCHAR(128) NULL,
  ADD COLUMN reset_expires DATETIME NULL;

-- Add room_id to reviews so reviews can be tied to specific rooms (nullable for old rows)
ALTER TABLE reviews
  ADD COLUMN room_id INT NULL,
  ADD INDEX idx_room_id (room_id),
  ADD CONSTRAINT fk_reviews_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL;

-- Note: After running the ALTER statements, you can migrate existing reviews by mapping room_type_id to a representative room id, or leave room_id NULL.
USE checkin;
INSERT IGNORE INTO roles (id, name) VALUES (1, 'admin'), (2, 'customer');

-- Sql command to add admin password is admin123

INSERT INTO users (
  first_name,
  middle_name,
  last_name,
  email,
  password,
  role_id,
  is_banned,
  email_verified,
  verification_token,
  reset_token,
  reset_expires
) VALUES (
  'System',
  NULL,
  'Admin',
  'admin@checkin.com',
  '$2y$10$pAJwojMIsBblORfMBgXxG.5zJ6sD.9IyNFGqdP8Mg3fcZm2YGaXKG',
  1,             
  0,            
  1,              
  NULL,
  NULL,
  NULL
);
