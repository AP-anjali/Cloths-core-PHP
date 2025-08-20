-- ✅ Full MySQL Database Schema for Real-Life Clothing eCommerce Website (Updated with More Details)

-- 1. Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    phone VARCHAR(15),
    address TEXT,
    city VARCHAR(50),
    pincode VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100)
);

-- 3. Subcategories Table
CREATE TABLE subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    subcategory_name VARCHAR(100),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- 4. Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    category_id INT,
    subcategory_id INT,
    price DECIMAL(10,2),
    discount_type VARCHAR(10), -- 'flat' or 'percent'
    discount_value DECIMAL(10,2),
    description TEXT,
    stock INT,
    brand VARCHAR(100),
    material VARCHAR(100),
    gender VARCHAR(20),
    main_image VARCHAR(255),
    sku_code VARCHAR(100),
    fabric VARCHAR(100),
    fit VARCHAR(50),
    sleeve_type VARCHAR(50),
    occasion VARCHAR(100),
    pattern VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id)
);

-- 5. Product Images Table (Multiple images per product)
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    image_url VARCHAR(255),
    is_default BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 6. Product Variants (Size, Color)
CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    size VARCHAR(10),
    color VARCHAR(50),
    quantity INT,
    image VARCHAR(255),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 7. Cart Table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_variant_id INT,
    quantity INT,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
);

-- 8. Orders Table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10,2),
    payment_method VARCHAR(20), -- 'COD', 'Online'
    payment_status VARCHAR(20), -- 'Pending', 'Success', 'Failed'
    order_status VARCHAR(20),   -- 'Pending', 'Shipped', 'Delivered'
    tracking_number VARCHAR(100),
    courier_service VARCHAR(100),
    estimated_delivery_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 9. Order Items
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_variant_id INT,
    quantity INT,
    price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
);

-- 10. Admin Table (optional)
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255)
);

CREATE TABLE sliders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  main_image VARCHAR(255) NOT NULL,
  background_image VARCHAR(255) NOT NULL,
  subtitle VARCHAR(255),
  title VARCHAR(255),
  offer_text VARCHAR(255),
  link TEXT,
  status ENUM('active', 'inactive') DEFAULT 'active'
);

-- 🔗 Relationships Summary:
-- users → orders, cart
-- categories → subcategories, products
-- subcategories → products
-- products → product_variants, product_images
-- product_variants → cart, order_items
-- orders → order_items
-- admin manages products/orders manually (via dashboard)

-- ✅ Real-life ready. Just connect to your PHP site.
-- Want PHP code for this database? Just ask!
