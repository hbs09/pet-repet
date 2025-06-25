<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize database connection for navbar functionality
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Cart.php';

$database = new Database();
$db = $database->getConnection();
$cart = new Cart($db);

// Get session ID for cart
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$session_id = $_SESSION['session_id'];

// Get cart item count
$cart_count = $cart->getItemCount($user_id, $session_id);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./scr/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title><?php echo $page_title ?? 'Pet & Repet'; ?> - Pet & Repet</title>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="top-nav">
        <div class="container">
            <a href="index.php" class="nav-brand">Pet & Repet</a>
            <div class="nav-tools">
                <div class="search-bar">
                    <form action="search.php" method="GET">
                        <input type="text" name="q" placeholder="Procurar produtos..." required>
                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="tool-icons">
                    <a href="favoritos.php" class="tool-icon" title="Favoritos">
                        <i class="fas fa-heart"></i>
                    </a>
                    <a href="carrinho.php" class="tool-icon" title="Carrinho">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    </a>
                    <div class="tool-icon account-menu" id="account-menu-icon">
                        <i class="far fa-user"></i>
                        <div class="account-dropdown">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <div class="dropdown-header">
                                    <p class="user-name"><?php echo htmlspecialchars($_SESSION['user_first_name'] ?? 'Utilizador'); ?></p>
                                    <p class="user-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                                </div>
                                <a href="account.php" class="account-dropdown-item">
                                    <i class="fas fa-user-circle"></i>
                                    <span>A Minha Conta</span>
                                </a>
                                <a href="orders.php" class="account-dropdown-item">
                                    <i class="fas fa-box"></i>
                                    <span>As Minhas Encomendas</span>
                                </a>
                                <a href="wishlist.php" class="account-dropdown-item">
                                    <i class="fas fa-heart"></i>
                                    <span>Lista de Desejos</span>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="logout.php" class="account-dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Terminar Sessão</span>
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="account-dropdown-item">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span>Entrar</span>
                                </a>
                                <a href="registo.php" class="account-dropdown-item">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Criar Conta</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Menu -->
    <div class="main-menu">
        <div class="container">
            <ul class="nav-menu">
                <li class="nav-item"><a href="categoria.php?id=6" class="nav-link"> CÃES</a></li>
                <li class="nav-item"><a href="categoria.php?id=7" class="nav-link"> GATOS</a></li>
                <li class="nav-item dropdown-item">
                    <a href="produtos.php" class="nav-link">PRODUTOS</a>
                    <div class="mega-dropdown">
                        <div class="dropdown-columns">
                            <!-- Seção: Tipo de produto -->
                            <div class="dropdown-col category-section">
                                <div class="col-header">
                                    <div class="main-category">
                                        <a href="categoria.php?parent=1" class="category-title"><i class="fas fa-box-open"></i> Tipo de produto</a>
                                    </div>
                                </div>
                                <div class="col-content">
                                    <ul class="dropdown-list">
                                        <?php
                                        // Obter subcategorias de tipo de produto
                                        $product_stmt = $db->prepare("SELECT id, name FROM categories WHERE parent_id = 1 AND is_active = TRUE ORDER BY name");
                                        $product_stmt->execute();
                                        while ($product_cat = $product_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                            <li>
                                                <a href="categoria.php?id=<?php echo $product_cat['id']; ?>" class="dropdown-link">
                                                    <?php echo htmlspecialchars($product_cat['name']); ?>
                                                </a>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>
                            </div>

                            <!-- Seção: Animal -->
                            <div class="dropdown-col category-section">
                                <div class="col-header">
                                    <div class="main-category">
                                        <a href="categoria.php?parent=2" class="category-title"><i class="fas fa-paw"></i> Animal</a>
                                    </div>
                                </div>
                                <div class="col-content">
                                    <ul class="dropdown-list">
                                        <?php
                                        // Obter subcategorias de animais
                                        $animal_stmt = $db->prepare("SELECT id, name FROM categories WHERE parent_id = 2 AND is_active = TRUE ORDER BY name");
                                        $animal_stmt->execute();
                                        while ($animal_cat = $animal_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                            <li>
                                                <a href="categoria.php?id=<?php echo $animal_cat['id']; ?>" class="dropdown-link">
                                                    <?php echo htmlspecialchars($animal_cat['name']); ?>
                                                </a>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>
                            </div>

                            <!-- Seção: Marca -->
                            <div class="dropdown-col category-section">
                                <div class="col-header">
                                    <div class="main-category">
                                        <a href="categoria.php?parent=3" class="category-title"><i class="fas fa-tags"></i> Marca</a>
                                    </div>
                                </div>
                                <div class="col-content">
                                    <ul class="dropdown-list">
                                        <?php
                                        // Obter subcategorias de marcas
                                        $brand_stmt = $db->prepare("SELECT id, name FROM categories WHERE parent_id = 3 AND is_active = TRUE ORDER BY name");
                                        $brand_stmt->execute();
                                        while ($brand_cat = $brand_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                            <li>
                                                <a href="categoria.php?id=<?php echo $brand_cat['id']; ?>" class="dropdown-link">
                                                    <?php echo htmlspecialchars($brand_cat['name']); ?>
                                                </a>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-footer">
                            <a href="produtos.php" class="view-all-link">
                                Ver todos os produtos
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </li>
                <li class="nav-item"><a href="servicos.php" class="nav-link">SERVIÇOS</a></li>
                <li class="nav-item"><a href="sobre.php" class="nav-link">SOBRE NÓS</a></li>
                <li class="nav-item"><a href="contacto.php" class="nav-link">CONTACTO</a></li>
            </ul>
        </div>
    </div>

    <script>
        // Add scroll effect to navbar
        let lastScrollTop = 0;
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('.top-nav');
            const mainMenu = document.querySelector('.main-menu');
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }

            // Auto-hide main menu on scroll
            if (scrollTop > lastScrollTop && scrollTop > nav.offsetHeight) {
                // Scroll Down
                mainMenu.classList.add('hidden');
            } else {
                // Scroll Up
                mainMenu.classList.remove('hidden');
            }
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; // For Mobile or negative scrolling
            
            // Close dropdown on scroll
            const dropdownItems = document.querySelectorAll('.dropdown-item');
            dropdownItems.forEach(item => {
                item.classList.remove('dropdown-open');
            });
        });

        // Dropdown management
        const dropdownItems = document.querySelectorAll('.dropdown-item');
        
        dropdownItems.forEach(item => {
            const dropdown = item.querySelector('.mega-dropdown');
            
            item.addEventListener('mouseenter', function() {
                this.classList.add('dropdown-open');
            });
            
            item.addEventListener('mouseleave', function() {
                this.classList.remove('dropdown-open');
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown-item')) {
                dropdownItems.forEach(item => {
                    item.classList.remove('dropdown-open');
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Account dropdown functionality
            const accountMenuIcon = document.getElementById('account-menu-icon');
            let accountMenuTimeout;
            let isClicked = false;

            if (accountMenuIcon) {
                const showMenu = () => {
                    clearTimeout(accountMenuTimeout);
                    accountMenuIcon.classList.add('active');
                };

                const hideMenu = () => {
                    if (!isClicked) { // Only hide on mouseleave if not opened by click
                        accountMenuTimeout = setTimeout(() => {
                            accountMenuIcon.classList.remove('active');
                        }, 250);
                    }
                };

                accountMenuIcon.addEventListener('mouseenter', showMenu);
                accountMenuIcon.addEventListener('mouseleave', hideMenu);

                accountMenuIcon.addEventListener('click', function(event) {
                    event.stopPropagation();
                    const isActive = this.classList.toggle('active');
                    isClicked = isActive;
                });

                // Close when clicking outside
                document.addEventListener('click', function(event) {
                    if (accountMenuIcon.classList.contains('active') && !accountMenuIcon.contains(event.target)) {
                        accountMenuIcon.classList.remove('active');
                        isClicked = false;
                    }
                });
            }
        });
    </script>
