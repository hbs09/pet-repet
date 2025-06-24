<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Product.php';
require_once 'classes/Category.php';
require_once 'classes/Cart.php';

$database = new Database();
$db = $database->getConnection();

$product = new Product($db);
$category = new Category($db);
$cart = new Cart($db);

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$session_id = $_SESSION['session_id'] ?? session_id();
$cart_count = $cart->getItemCount($user_id, $session_id);

// Get all products
$products = $product->readAll();
$categories = $category->readAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/scr/style.css">
    <title>Produtos - Pet & Repet</title>
</head>
<body>
    <!-- Include header navigation here -->
    
    <!-- Products Section -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h1 class="section-title">Todos os Produtos</h1>
                <p class="section-subtitle">Encontre tudo o que precisa para o seu animal de estimação</p>
            </div>
            
            <!-- Category Filter -->
            <div class="category-filter">
                <a href="produtos.php" class="filter-btn <?php echo !isset($_GET['categoria']) ? 'active' : ''; ?>">Todos</a>
                <?php 
                $categories->execute(); // Reset the result set
                while ($cat_row = $categories->fetch(PDO::FETCH_ASSOC)): 
                ?>
                    <a href="produtos.php?categoria=<?php echo $cat_row['id']; ?>" 
                       class="filter-btn <?php echo (isset($_GET['categoria']) && $_GET['categoria'] == $cat_row['id']) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat_row['name']); ?>
                    </a>
                <?php endwhile; ?>
            </div>

            <div class="product-grid">
                <?php while ($row = $products->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if($row['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="product-content">
                            <h3 class="product-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <div class="product-brand"><?php echo htmlspecialchars($row['brand']); ?></div>
                            <div class="product-price">
                                <?php if($row['sale_price']): ?>
                                    <span class="sale-price">€<?php echo number_format($row['sale_price'], 2); ?></span>
                                    <span class="original-price">€<?php echo number_format($row['price'], 2); ?></span>
                                <?php else: ?>
                                    €<?php echo number_format($row['price'], 2); ?>
                                <?php endif; ?>
                            </div>
                            <p class="product-description"><?php echo htmlspecialchars(substr($row['description'], 0, 100)) . '...'; ?></p>
                            <div class="product-stock">
                                <?php if($row['stock_quantity'] > 0): ?>
                                    <span class="in-stock">Em stock (<?php echo $row['stock_quantity']; ?>)</span>
                                <?php else: ?>
                                    <span class="out-of-stock">Sem stock</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions">
                                <?php if($row['stock_quantity'] > 0): ?>
                                    <button class="product-button add-to-cart" data-product-id="<?php echo $row['id']; ?>">
                                        Adicionar ao Carrinho
                                    </button>
                                <?php else: ?>
                                    <button class="product-button" disabled>Sem Stock</button>
                                <?php endif; ?>
                                <a href="produto.php?id=<?php echo $row['id']; ?>" class="product-link">Ver Detalhes</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Add to cart functionality
        $(document).on('click', '.add-to-cart', function() {
            const productId = $(this).data('product-id');
            const button = $(this);
            
            $.ajax({
                url: 'ajax/add_to_cart.php',
                method: 'POST',
                data: {
                    product_id: productId,
                    quantity: 1
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        $('.cart-count').text(response.cart_count);
                        button.text('Adicionado!');
                        button.css('background', '#1abc9c');
                        setTimeout(() => {
                            button.text('Adicionar ao Carrinho');
                            button.css('background', '#3498db');
                        }, 2000);
                    } else {
                        alert('Erro ao adicionar produto ao carrinho');
                    }
                }
            });
        });
    </script>
</body>
</html>
