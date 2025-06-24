<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Product.php';
require_once 'classes/Category.php';
require_once 'classes/Cart.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize classes
$product = new Product($db);
$category = new Category($db);
$cart = new Cart($db);

// Get session ID for cart
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$session_id = $_SESSION['session_id'];

// Get cart item count
$cart_count = $cart->getItemCount($user_id, $session_id);

// Get featured products
$featured_products = $product->readFeatured();

// Get categories
$categories = $category->readAll();
?>


<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/scr/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Pet & Repet - A sua loja de animais de estimação</title>
    <meta name="description" content="Descubra a melhor seleção de produtos para o seu animal de estimação. Alimentação, brinquedos, acessórios e muito mais.">
</head>
<body>
    <!-- Top Navbar -->
    <nav class="top-nav">
        <div class="container">
            <div class="nav-brand">Pet & Repet</div>
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
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="conta.php" class="tool-icon" title="Minha Conta">
                            <i class="fas fa-user"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="tool-icon" title="Entrar">
                            <i class="fas fa-sign-in-alt"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Menu -->
    <div class="main-menu">
        <div class="container">
            <ul class="nav-menu">
                <li><a href="categoria.php?id=1">CÃO</a></li>
                <li><a href="categoria.php?id=2">GATO</a></li>
                <li class="dropdown-item">
                    PRODUTOS
                    <div class="mega-dropdown">
                        <div class="dropdown-columns">
                            <?php while ($cat_row = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="dropdown-col">
                                    <div class="col-header">
                                        <span class="dropdown-title"><?php echo htmlspecialchars($cat_row['name']); ?></span>
                                    </div>
                                    <div class="col-content">
                                        <p><a href="categoria.php?id=<?php echo $cat_row['id']; ?>"><?php echo htmlspecialchars($cat_row['description']); ?></a></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <div class="dropdown-footer">
                            <a href="produtos.php" class="view-all-link">
                                Ver todos os produtos
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </li>
                <li><a href="servicos.php">SERVIÇOS</a></li>
                <li><a href="sobre.php">SOBRE NÓS</a></li>
                <li><a href="contacto.php">CONTACTO</a></li>
            </ul>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content fade-in-up">
                <h1>O Melhor para o Seu Melhor Amigo</h1>
                <p>Descubra uma seleção premium de produtos para cães e gatos. Alimentação, brinquedos, acessórios e muito mais para manter o seu animal feliz e saudável.</p>
                <div class="hero-buttons">
                    <a href="#produtos" class="btn btn-primary">Ver Produtos</a>
                    <a href="servicos.php" class="btn btn-secondary">Nossos Serviços</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section id="produtos" class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Produtos em Destaque</h2>
                <p class="section-subtitle">Selecionamos os melhores produtos para o bem-estar do seu animal de estimação</p>
            </div>
            <div class="product-grid">
                <?php while ($row = $featured_products->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if($row['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="product-content">
                            <h3 class="product-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <div class="product-price">
                                <?php if($row['sale_price']): ?>
                                    <span class="sale-price">€<?php echo number_format($row['sale_price'], 2); ?></span>
                                    <span class="original-price">€<?php echo number_format($row['price'], 2); ?></span>
                                <?php else: ?>
                                    €<?php echo number_format($row['price'], 2); ?>
                                <?php endif; ?>
                            </div>
                            <p class="product-description"><?php echo htmlspecialchars(substr($row['description'], 0, 100)) . '...'; ?></p>
                            <div class="product-actions">
                                <button class="product-button add-to-cart" data-product-id="<?php echo $row['id']; ?>">
                                    Adicionar ao Carrinho
                                </button>
                                <a href="produto.php?id=<?php echo $row['id']; ?>" class="product-link">Ver Detalhes</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Porquê Escolher-nos?</h2>
                <p class="section-subtitle">Oferecemos os melhores produtos e serviços para o seu animal de estimação</p>
            </div>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">🚚</div>
                    <h3 class="feature-title">Entrega Rápida</h3>
                    <p class="feature-description">Entregas em 24-48h em todo o país. Portes grátis em compras superiores a €50.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">💎</div>
                    <h3 class="feature-title">Qualidade Premium</h3>
                    <p class="feature-description">Selecionamos apenas as melhores marcas e produtos de qualidade superior.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🏥</div>
                    <h3 class="feature-title">Apoio Veterinário</h3>
                    <p class="feature-description">Equipa de veterinários disponível para esclarecer todas as suas dúvidas.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="section testimonials">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">O Que Dizem os Nossos Clientes</h2>
                <p class="section-subtitle">Milhares de clientes satisfeitos confiam em nós</p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <p class="testimonial-text">"Excelente atendimento e produtos de qualidade. O meu Golden Retriever adora a ração que comprei aqui!"</p>
                    <div class="testimonial-author">Maria Silva</div>
                    <div class="testimonial-role">Cliente desde 2022</div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"Entrega super rápida e o apoio veterinário ajudou-me muito a escolher os produtos certos para o meu gato."</p>
                    <div class="testimonial-author">João Santos</div>
                    <div class="testimonial-role">Cliente desde 2021</div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"Preços competitivos e variedade incrível. Recomendo a todos os donos de animais!"</p>
                    <div class="testimonial-author">Ana Costa</div>
                    <div class="testimonial-role">Cliente desde 2023</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Pet & Repet</h3>
                    <p>A sua loja de confiança para produtos de animais de estimação. Cuidamos do seu melhor amigo como se fosse nosso.</p>
                    <div style="margin-top: 20px;">
                        <a href="#" style="color: #3498db; margin-right: 15px; font-size: 20px;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: #3498db; margin-right: 15px; font-size: 20px;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: #3498db; margin-right: 15px; font-size: 20px;"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Produtos</h3>
                    <ul>
                        <li><a href="categoria.php?id=1">Produtos para Cães</a></li>
                        <li><a href="categoria.php?id=2">Produtos para Gatos</a></li>
                        <li><a href="alimentacao.php">Alimentação</a></li>
                        <li><a href="brinquedos.php">Brinquedos</a></li>
                        <li><a href="acessorios.php">Acessórios</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Apoio ao Cliente</h3>
                    <ul>
                        <li><a href="ajuda.php">Central de Ajuda</a></li>
                        <li><a href="entrega.php">Entregas</a></li>
                        <li><a href="devolucoes.php">Devoluções</a></li>
                        <li><a href="garantia.php">Garantias</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contacto</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt" style="color: #3498db; margin-right: 8px;"></i>Rua das Flores, 123, Lisboa</li>
                        <li><i class="fas fa-phone" style="color: #3498db; margin-right: 8px;"></i>+351 213 456 789</li>
                        <li><i class="fas fa-envelope" style="color: #3498db; margin-right: 8px;"></i>info@petrepet.pt</li>
                        <li><i class="fas fa-clock" style="color: #3498db; margin-right: 8px;"></i>Seg-Sex: 9h-18h</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Pet & Repet. Todos os direitos reservados. | <a href="privacidade.php" style="color: #3498db;">Política de Privacidade</a> | <a href="termos.php" style="color: #3498db;">Termos de Uso</a></p>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Add scroll effect to navbar
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('.top-nav');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
            
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

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add to cart functionality with AJAX
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
                        // Update cart count
                        $('.cart-count').text(response.cart_count);
                        
                        // Update button
                        button.text('Adicionado!');
                        button.css('background', '#1abc9c');
                        setTimeout(() => {
                            button.text('Adicionar ao Carrinho');
                            button.css('background', '#3498db');
                        }, 2000);
                    } else {
                        alert('Erro ao adicionar produto ao carrinho');
                    }
                },
                error: function() {
                    alert('Erro ao adicionar produto ao carrinho');
                }
            });
        });
    </script>
</body>
</html>