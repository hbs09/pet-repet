-- Create database
CREATE DATABASE IF NOT EXISTS pet_repet;
USE pet_repet;

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    parent_id INT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    sku VARCHAR(100) UNIQUE,
    image_url VARCHAR(255),
    category_id INT NOT NULL,
    brand VARCHAR(100),
    stock_quantity INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_category (category_id),
    INDEX idx_featured (is_featured),
    INDEX idx_price (price),
    INDEX idx_stock (stock_quantity)
);

-- Product images table
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    postal_code VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    customer_name VARCHAR(200) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20),
    shipping_address TEXT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_date (created_at)
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Shopping cart table
CREATE TABLE shopping_cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(255) DEFAULT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id)
);

-- Wishlist table
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

-- Product reviews table
CREATE TABLE product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    comment TEXT,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_rating (rating),
    INDEX idx_approved (is_approved)
);

-- Coupons table
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('percentage', 'fixed') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    minimum_amount DECIMAL(10,2) DEFAULT 0,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    valid_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valid_until TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active)
);

-- Newsletter subscriptions
CREATE TABLE newsletter_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL,
    INDEX idx_email (email)
);

-- Insert sample categories
INSERT INTO categories (name, description, image_url) VALUES
('Cães', 'Produtos para cães de todas as raças e idades', '/images/categories/dogs.jpg'),
('Gatos', 'Produtos para gatos de todas as raças e idades', '/images/categories/cats.jpg'),
('Alimentação', 'Ração, snacks e suplementos', '/images/categories/food.jpg'),
('Brinquedos', 'Brinquedos interativos e de entretenimento', '/images/categories/toys.jpg'),
('Acessórios', 'Coleiras, camas, transportadoras e mais', '/images/categories/accessories.jpg'),
('Higiene', 'Produtos de limpeza e cuidados', '/images/categories/hygiene.jpg');

-- Insert subcategories
INSERT INTO categories (name, description, parent_id) VALUES
('Ração Seca', 'Ração seca para cães e gatos', 3),
('Ração Húmida', 'Ração húmida e patês', 3),
('Snacks', 'Petiscos e treats', 3),
('Brinquedos Interativos', 'Brinquedos que estimulam a mente', 4),
('Brinquedos de Pelúcia', 'Brinquedos macios e confortáveis', 4),
('Coleiras e Trelas', 'Coleiras, trelas e arneses', 5),
('Camas e Almofadas', 'Camas confortáveis para descanso', 5),
('Shampoos', 'Produtos para banho e higiene', 6);

-- Insert sample products
INSERT INTO products (name, description, short_description, price, sale_price, sku, category_id, brand, stock_quantity, is_featured) VALUES
('Ração Premium Cão Adulto', 'Ração completa e equilibrada para cães adultos de todas as raças. Rico em proteínas de alta qualidade.', 'Ração completa e equilibrada para cães adultos', 29.99, NULL, 'DOG-FOOD-001', 7, 'Royal Canin', 50, TRUE),
('Brinquedo Interativo Kong', 'Brinquedo resistente que mantém o cão entretido. Pode ser recheado com petiscos.', 'Brinquedo estimulante que mantém o seu cão ativo', 15.99, 12.99, 'TOY-KONG-001', 10, 'Kong', 30, TRUE),
('Cama Ortopédica Grande', 'Cama ortopédica super confortável, ideal para cães grandes. Espuma memory foam.', 'Cama ortopédica super confortável', 49.99, NULL, 'BED-ORTHO-001', 13, 'Trixie', 15, TRUE),
('Ração Gato Esterilizado', 'Ração especial para gatos esterilizados. Controla o peso e previne problemas urinários.', 'Ração especial para gatos esterilizados', 24.99, NULL, 'CAT-FOOD-001', 7, 'Hill\'s', 40, FALSE),
('Arranhador Torre', 'Arranhador alto com várias plataformas. Ideal para gatos ativos.', 'Arranhador com múltiplas plataformas', 79.99, 69.99, 'CAT-SCRATCH-001', 2, 'Catit', 10, TRUE),
('Shampoo Cão Pele Sensível', 'Shampoo hipoalergênico para cães com pele sensível. Fórmula suave e natural.', 'Shampoo hipoalergênico para pele sensível', 12.99, NULL, 'SHAMP-SENS-001', 14, 'Virbac', 25, FALSE),
('Snacks Naturais Cão', 'Petiscos naturais sem conservantes. Ideais para treino e recompensa.', 'Petiscos naturais para treino', 6.99, 5.99, 'TREATS-001', 9, 'Zuke', 80, FALSE);

-- Insert sample product images
INSERT INTO product_images (product_id, image_url, alt_text, is_primary) VALUES
(1, '/images/products/dog-food-premium.jpg', 'Ração Premium Cão Adulto', TRUE),
(2, '/images/products/kong-toy.jpg', 'Brinquedo Kong Interativo', TRUE),
(3, '/images/products/orthopedic-bed.jpg', 'Cama Ortopédica Grande', TRUE),
(4, '/images/products/cat-food-sterilized.jpg', 'Ração Gato Esterilizado', TRUE),
(5, '/images/products/cat-tower.jpg', 'Arranhador Torre', TRUE),
(6, '/images/products/sensitive-shampoo.jpg', 'Shampoo Pele Sensível', TRUE),
(7, '/images/products/natural-treats.jpg', 'Snacks Naturais Cão', TRUE);

-- Create admin user (password: admin123 - should be properly hashed in production)
INSERT INTO users (email, password_hash, first_name, last_name, is_active, email_verified) VALUES
('admin@petrepet.pt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Pet&Repet', TRUE, TRUE);



-- Insert sample orders
INSERT INTO orders (user_id, order_number, status, total_amount, shipping_amount, customer_name, customer_email, customer_phone, shipping_address) VALUES
(2, 'PR-2024-001', 'delivered', 45.98, 5.00, 'Maria Silva', 'maria.silva@email.com', '+351 912 345 678', 'Avenida da Liberdade, 456, 1250-096 Lisboa'),
(3, 'PR-2024-002', 'processing', 29.99, 0.00, 'João Santos', 'joao.santos@email.com', '+351 923 456 789', 'Rua do Comércio, 789, 4000-001 Porto');

-- Insert sample order items
INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price) VALUES
(1, 1, 'Ração Premium Cão Adulto', 1, 29.99, 29.99),
(1, 2, 'Brinquedo Interativo Kong', 1, 12.99, 12.99),
(1, 7, 'Snacks Naturais Cão', 1, 5.99, 5.99),
(2, 1, 'Ração Premium Cão Adulto', 1, 29.99, 29.99);

-- Insert sample coupons
INSERT INTO coupons (code, type, value, minimum_amount, usage_limit, valid_until) VALUES
('WELCOME10', 'percentage', 10.00, 30.00, 100, '2024-12-31 23:59:59'),
('FRETE50', 'fixed', 5.00, 50.00, NULL, '2024-12-31 23:59:59');

-- Create some sample customers
INSERT INTO users (email, password_hash, first_name, last_name, phone, is_active, email_verified) VALUES
('maria.silva@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Silva', '+351 912 345 678', TRUE, TRUE),
('joao.santos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'João', 'Santos', '+351 923 456 789', TRUE, TRUE);

-- Insert sample coupons
INSERT INTO coupons (code, type, value, minimum_amount, usage_limit, valid_until) VALUES
('WELCOME10', 'percentage', 10.00, 30.00, 100, '2024-12-31 23:59:59'),
('FRETE50', 'fixed', 5.00, 50.00, NULL, '2024-12-31 23:59:59');

-- Create indexes for better performance
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_stock ON products(stock_quantity);
CREATE INDEX idx_orders_date ON orders(created_at);
CREATE INDEX idx_product_reviews_approved ON product_reviews(is_approved);
