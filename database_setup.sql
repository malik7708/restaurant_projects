-- Active: 1771688979278@@127.0.0.1@3306@mysql
-- Database setup for restaurant project
-- Run this SQL script in phpMyAdmin or MySQL command line

CREATE DATABASE IF NOT EXISTS restaurant_db;

USE restaurant_db;

-- Admin users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO
    users (username, password)
VALUES (
        'admin',
        '$2y$10$NcOrEQhMkljjDXE02RYmJeDCE1bDhO7qQYKwOvj/t7nOFBnf0JHdi'
    );
-- password_hash('admin123', PASSWORD_DEFAULT)

-- Tables for QR Dining System
CREATE TABLE IF NOT EXISTS tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(10) UNIQUE NOT NULL,
    qr_code VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample tables
INSERT INTO
    tables (table_number, status)
VALUES ('T01', 'active'),
    ('T02', 'active'),
    ('T03', 'active'),
    ('T04', 'active'),
    ('T05', 'active'),
    ('T06', 'active'),
    ('T07', 'active'),
    ('T08', 'active'),
    ('T09', 'active'),
    ('T10', 'active');

-- Waiter calls table for QR Dining System
CREATE TABLE IF NOT EXISTS waiter_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (table_id) REFERENCES tables (id) ON DELETE CASCADE
);

-- Menu items table
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    discount DECIMAL(5, 2) DEFAULT 0, -- Discount percentage (0-100)
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample menu items
INSERT INTO
    menu_items (
        name,
        description,
        price,
        discount,
        image
    )
VALUES (
        'Margherita Pizza',
        'Fresh tomatoes, mozzarella, basil, and olive oil',
        1299,
        0,
        'pizza1.jpg'
    ),
    (
        'Chicken Burger',
        'Grilled chicken breast with lettuce, tomato, and mayo',
        999,
        10,
        'burger1.jpg'
    ),
    (
        'Caesar Salad',
        'Romaine lettuce, croutons, parmesan, caesar dressing',
        899,
        0,
        'salad1.jpg'
    ),
    (
        'Pasta Carbonara',
        'Spaghetti with bacon, eggs, cheese, and black pepper',
        1499,
        5,
        'pasta1.jpg'
    ),
    (
        'Chocolate Cake',
        'Rich chocolate cake with vanilla frosting',
        699,
        0,
        'cake1.jpg'
    );

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    items TEXT NOT NULL, -- JSON encoded cart items
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM(
        'pending',
        'confirmed',
        'preparing',
        'ready',
        'delivered',
        'cancelled'
    ) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES tables (id) ON DELETE SET NULL
);

-- Reservations table
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    guests INT NOT NULL,
    special_requests TEXT,
    status ENUM(
        'pending',
        'confirmed',
        'cancelled'
    ) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Banners table for home page slider
CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    image VARCHAR(255) NOT NULL,
    link VARCHAR(255),
    is_active BOOLEAN DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sample banners
INSERT INTO
    banners (
        title,
        description,
        image,
        link,
        is_active,
        display_order
    )
VALUES (
        'Delicious Burgers',
        'Try our signature burgers made with premium beef',
        'banner-burger.jpg.svg',
        'menu.php',
        1,
        1
    ),
    (
        'Fresh Pizzas',
        'Authentic Italian pizzas with fresh ingredients',
        'banner-pizza.jpg.svg',
        'menu.php',
        1,
        2
    ),
    (
        'Sweet Desserts',
        'Indulge in our mouth-watering desserts',
        'banner-dessert.jpg.svg',
        'menu.php',
        1,
        3
    );