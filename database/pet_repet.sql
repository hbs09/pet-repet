-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 24-Jun-2025 às 18:38
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `pet_repet`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image_url`, `parent_id`, `is_active`, `created_at`) VALUES
(1, 'Tipo de produto', 'Tipo de produto', '', NULL, 1, '2025-06-24 14:27:09'),
(2, 'Animal', 'Por Animal', '', NULL, 1, '2025-06-24 14:27:09'),
(3, 'Marca', 'Por Marca', '', NULL, 1, '2025-06-24 14:27:09'),
(4, 'Alimentos', 'Todos os tipos de alimentos para animais', NULL, 1, 1, '2025-06-24 14:27:09'),
(5, 'Brinquedos', 'Brinquedos para diversão e estímulo', NULL, 1, 1, '2025-06-24 14:27:09'),
(6, 'Acessórios', 'Acessórios essenciais para pets', NULL, 1, 1, '2025-06-24 14:27:09'),
(7, 'Saúde', 'Produtos para saúde e bem-estar', NULL, 1, 1, '2025-06-24 14:27:09'),
(8, 'Camas & Casinhas', 'Conforto para o descanso do seu pet', NULL, 1, 1, '2025-06-24 14:27:09'),
(9, 'Cão', 'Produtos para cães', NULL, 2, 1, '2025-06-24 14:27:09'),
(10, 'Gato', 'Produtos para gatos', NULL, 2, 1, '2025-06-24 14:27:09'),
(11, 'Pássaro', 'Produtos para pássaros', NULL, 2, 1, '2025-06-24 14:27:09'),
(12, 'Peixe', 'Produtos para peixes', NULL, 2, 1, '2025-06-24 14:27:09'),
(13, 'Marca A', 'Marca A', NULL, 3, 1, '2025-06-24 14:27:09'),
(14, 'Marca B', 'Marca B', NULL, 3, 1, '2025-06-24 14:27:09'),
(15, 'Marca C', 'Marca C', NULL, 3, 1, '2025-06-24 14:27:09'),
(16, 'Marca D', 'Marca D', NULL, 3, 1, '2025-06-24 14:27:09'),
(17, 'Marca E', 'Marca E', NULL, 3, 1, '2025-06-24 14:27:09'),
(18, 'Marca F', 'Marca F', NULL, 3, 1, '2025-06-24 14:27:09');

-- --------------------------------------------------------

--
-- Estrutura da tabela `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT 0.00,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `valid_from` timestamp NOT NULL DEFAULT current_timestamp(),
  `valid_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `type`, `value`, `minimum_amount`, `usage_limit`, `used_count`, `is_active`, `valid_from`, `valid_until`, `created_at`) VALUES
(1, 'WELCOME10', 'percentage', 10.00, 30.00, 100, 0, 1, '2025-06-24 14:27:10', '2024-12-31 23:59:59', '2025-06-24 14:27:10'),
(2, 'FRETE50', 'fixed', 5.00, 50.00, NULL, 0, 1, '2025-06-24 14:27:10', '2024-12-31 23:59:59', '2025-06-24 14:27:10');

-- --------------------------------------------------------

--
-- Estrutura da tabela `newsletter_subscriptions`
--

CREATE TABLE `newsletter_subscriptions` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unsubscribed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_amount` decimal(10,2) DEFAULT 0.00,
  `customer_name` varchar(200) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `status`, `total_amount`, `shipping_amount`, `customer_name`, `customer_email`, `customer_phone`, `shipping_address`, `notes`, `created_at`) VALUES
(1, 2, 'PR-2024-001', 'delivered', 45.98, 5.00, 'Maria Silva', 'maria.silva@email.com', '+351 912 345 678', 'Avenida da Liberdade, 456, 1250-096 Lisboa', NULL, '2025-06-24 14:27:10'),
(2, 3, 'PR-2024-002', 'processing', 29.99, 0.00, 'João Santos', 'joao.santos@email.com', '+351 923 456 789', 'Rua do Comércio, 789, 4000-001 Porto', NULL, '2025-06-24 14:27:10');

-- --------------------------------------------------------

--
-- Estrutura da tabela `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 1, 'Ração Premium Cão Adulto', 1, 29.99, 29.99),
(2, 1, 2, 'Brinquedo Interativo Kong', 1, 12.99, 12.99),
(3, 1, 7, 'Snacks Naturais Cão', 1, 5.99, 5.99),
(4, 2, 1, 'Ração Premium Cão Adulto', 1, 29.99, 29.99);

-- --------------------------------------------------------

--
-- Estrutura da tabela `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `short_description`, `price`, `sale_price`, `sku`, `image_url`, `category_id`, `brand`, `stock_quantity`, `is_active`, `is_featured`, `created_at`) VALUES
(1, 'Ração Premium Cão Adulto', 'Ração completa e equilibrada para cães adultos de todas as raças. Rico em proteínas de alta qualidade.', 'Ração completa e equilibrada para cães adultos', 29.99, NULL, 'DOG-FOOD-001', './media/produtos/produto1.jpg', 7, 'Royal Canin', 50, 1, 1, '2025-06-24 14:27:09'),
(2, 'Brinquedo Interativo Kong', 'Brinquedo resistente que mantém o cão entretido. Pode ser recheado com petiscos.', 'Brinquedo estimulante que mantém o seu cão ativo', 15.99, 12.99, 'TOY-KONG-001', 'media/produtos/produto2.png', 10, 'Kong', 30, 1, 1, '2025-06-24 14:27:09'),
(3, 'Cama Ortopédica Grande', 'Cama ortopédica super confortável, ideal para cães grandes. Espuma memory foam.', 'Cama ortopédica super confortável', 49.99, NULL, 'BED-ORTHO-001', './media/produtos/cama.png', 13, 'Trixie', 15, 1, 1, '2025-06-24 14:27:09'),
(4, 'Ração Gato Esterilizado', 'Ração especial para gatos esterilizados. Controla o peso e previne problemas urinários.', 'Ração especial para gatos esterilizados', 24.99, NULL, 'CAT-FOOD-001', NULL, 7, 'Hill\'s', 40, 1, 0, '2025-06-24 14:27:09'),
(5, 'Arranhador Torre', 'Arranhador alto com várias plataformas. Ideal para gatos ativos.', 'Arranhador com múltiplas plataformas', 79.99, 69.99, 'CAT-SCRATCH-001', NULL, 2, 'Catit', 10, 1, 1, '2025-06-24 14:27:09'),
(6, 'Shampoo Cão Pele Sensível', 'Shampoo hipoalergênico para cães com pele sensível. Fórmula suave e natural.', 'Shampoo hipoalergênico para pele sensível', 12.99, NULL, 'SHAMP-SENS-001', NULL, 14, 'Virbac', 25, 1, 0, '2025-06-24 14:27:09'),
(7, 'Snacks Naturais Cão', 'Petiscos naturais sem conservantes. Ideais para treino e recompensa.', 'Petiscos naturais para treino', 6.99, 5.99, 'TREATS-001', NULL, 9, 'Zuke', 80, 1, 0, '2025-06-24 14:27:09'),
(8, 'Ração Cão Premium', 'Ração premium para cãos de todas as idades', 'Nutrição completa para cãos', 35.99, NULL, 'FOOD-CÃO-9', NULL, 9, 'Marca D', 50, 1, 0, '2025-06-24 14:37:33'),
(9, 'Ração Gato Premium', 'Ração premium para gatos de todas as idades', 'Nutrição completa para gatos', 29.99, NULL, 'FOOD-GAT-10', NULL, 10, 'Marca E', 50, 1, 0, '2025-06-24 14:37:33'),
(10, 'Ração Pássaro Premium', 'Ração premium para pássaros de todas as idades', 'Nutrição completa para pássaros', 19.99, NULL, 'FOOD-PÁS-11', NULL, 11, 'Marca F', 50, 1, 0, '2025-06-24 14:37:33'),
(11, 'Ração Peixe Premium', 'Ração premium para peixes de todas as idades', 'Nutrição completa para peixes', 15.99, NULL, 'FOOD-PEI-12', NULL, 12, 'Marca A', 50, 1, 0, '2025-06-24 14:37:33');

-- --------------------------------------------------------

--
-- Estrutura da tabela `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `product_categories`
--

INSERT INTO `product_categories` (`id`, `product_id`, `category_id`, `created_at`) VALUES
(1, 5, 2, '2025-06-24 14:37:42'),
(2, 1, 7, '2025-06-24 14:37:42'),
(3, 4, 7, '2025-06-24 14:37:42'),
(4, 7, 9, '2025-06-24 14:37:42'),
(5, 8, 9, '2025-06-24 14:37:42'),
(6, 2, 10, '2025-06-24 14:37:42'),
(7, 9, 10, '2025-06-24 14:37:42'),
(8, 10, 11, '2025-06-24 14:37:42'),
(9, 11, 12, '2025-06-24 14:37:42'),
(10, 3, 13, '2025-06-24 14:37:42'),
(11, 6, 14, '2025-06-24 14:37:42'),
(30, 1, 4, '2025-06-24 14:39:14'),
(31, 1, 6, '2025-06-24 14:39:14'),
(32, 2, 5, '2025-06-24 14:39:14'),
(33, 2, 6, '2025-06-24 14:39:14'),
(34, 3, 8, '2025-06-24 14:39:14'),
(35, 3, 6, '2025-06-24 14:39:14'),
(36, 4, 4, '2025-06-24 14:39:14'),
(37, 5, 6, '2025-06-24 14:39:14'),
(38, 5, 7, '2025-06-24 14:39:14'),
(39, 6, 7, '2025-06-24 14:39:14'),
(40, 6, 6, '2025-06-24 14:39:14'),
(41, 7, 4, '2025-06-24 14:39:14'),
(42, 7, 6, '2025-06-24 14:39:14');

-- --------------------------------------------------------

--
-- Estrutura da tabela `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `alt_text`, `is_primary`, `sort_order`, `created_at`) VALUES
(1, 1, '/images/products/dog-food-premium.jpg', 'Ração Premium Cão Adulto', 1, 0, '2025-06-24 14:27:09'),
(2, 2, '/images/products/kong-toy.jpg', 'Brinquedo Kong Interativo', 1, 0, '2025-06-24 14:27:09'),
(3, 3, '/images/products/orthopedic-bed.jpg', 'Cama Ortopédica Grande', 1, 0, '2025-06-24 14:27:09'),
(4, 4, '/images/products/cat-food-sterilized.jpg', 'Ração Gato Esterilizado', 1, 0, '2025-06-24 14:27:09'),
(5, 5, '/images/products/cat-tower.jpg', 'Arranhador Torre', 1, 0, '2025-06-24 14:27:09'),
(6, 6, '/images/products/sensitive-shampoo.jpg', 'Shampoo Pele Sensível', 1, 0, '2025-06-24 14:27:09'),
(7, 7, '/images/products/natural-treats.jpg', 'Snacks Naturais Cão', 1, 0, '2025-06-24 14:27:09');

-- --------------------------------------------------------

--
-- Estrutura da tabela `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `title` varchar(200) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `shopping_cart`
--

CREATE TABLE `shopping_cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `shopping_cart`
--

INSERT INTO `shopping_cart` (`id`, `user_id`, `session_id`, `product_id`, `quantity`, `created_at`, `updated_at`) VALUES
(1, NULL, '183ji5p17apriv0jfl005pf2e7', 1, 7, '2025-06-24 15:58:16', '2025-06-24 16:37:05'),
(2, NULL, '183ji5p17apriv0jfl005pf2e7', 2, 4, '2025-06-24 15:58:17', '2025-06-24 16:37:05'),
(3, NULL, '183ji5p17apriv0jfl005pf2e7', 3, 3, '2025-06-24 15:58:18', '2025-06-24 16:37:04'),
(4, NULL, '183ji5p17apriv0jfl005pf2e7', 5, 1, '2025-06-24 16:19:30', '2025-06-24 16:19:30');

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `address`, `city`, `postal_code`, `is_active`, `email_verified`, `created_at`) VALUES
(1, 'admin@petrepet.pt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Pet&Repet', NULL, NULL, NULL, NULL, 1, 1, '2025-06-24 14:27:10'),
(2, 'maria.silva@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Silva', '+351 912 345 678', NULL, NULL, NULL, 1, 1, '2025-06-24 14:27:10'),
(3, 'joao.santos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'João', 'Santos', '+351 923 456 789', NULL, NULL, NULL, 1, 1, '2025-06-24 14:27:10');

-- --------------------------------------------------------

--
-- Estrutura da tabela `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Índices para tabela `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Índices para tabela `newsletter_subscriptions`
--
ALTER TABLE `newsletter_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_orders_date` (`created_at`);

--
-- Índices para tabela `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Índices para tabela `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_products_price` (`price`),
  ADD KEY `idx_products_stock` (`stock_quantity`);

--
-- Índices para tabela `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_category` (`product_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Índices para tabela `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Índices para tabela `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_product_reviews_approved` (`is_approved`);

--
-- Índices para tabela `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `newsletter_subscriptions`
--
ALTER TABLE `newsletter_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de tabela `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Limitadores para a tabela `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Limitadores para a tabela `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Limitadores para a tabela `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
