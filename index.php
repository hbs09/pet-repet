<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Product.php';
require_once 'classes/Category.php';
require_once 'classes/Cart.php';
require_once 'classes/CategoryManager.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize classes
$product = new Product($db);
$category = new Category($db);
$cart = new Cart($db);
$categoryManager = new CategoryManager($db);

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

// Get categories for menu
$product_types = $categoryManager->getProductTypes();
$animal_categories = $categoryManager->getAnimalCategories();
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

    <!-- Hero Section -->
    <section class="hero-carousel">
        <div class="carousel-inner">
            <!-- Slide 1 -->
            <div class="carousel-slide active" style="background-image:url(./media/index/promo1.png);">
            </div>
            <!-- Slide 2 -->
            <div class="carousel-slide" style="background-image:url(../media/index/hero-wallpaper-2.jpg);">
            </div>
            <!-- Slide 3 -->
            <div class="carousel-slide" style="background-image:url(../media/index/hero-wallpaper-3.jpg);">
            </div>
        </div>
        <button class="carousel-control prev"><i class="fas fa-chevron-left"></i></button>
        <button class="carousel-control next"><i class="fas fa-chevron-right"></i></button>
        <div class="carousel-indicators"></div>
    </section>

    <!-- Featured Products Section -->
    <section id="produtos" class="section animate-on-scroll">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Produtos em Destaque</h2>
                <p class="section-subtitle">Selecionamos os melhores produtos para o bem-estar do seu animal de estimação</p>
            </div>
            <div class="product-grid">
                <?php 
                $product_count = 0;
                while ($row = $featured_products->fetch(PDO::FETCH_ASSOC)): 
                    if ($product_count >= 3) break;
                ?>
                    <div class="product-card">
                        <a href="produto.php?id=<?php echo $row['id']; ?>">
                            <div class="product-image">
                                <?php if($row['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                <?php endif; ?>
                            </div>
                        </a>
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
                            <div class="product-actions">
                                <button class="product-button add-to-cart" data-product-id="<?php echo $row['id']; ?>">
                                    Adicionar ao Carrinho
                                </button>
                                <a href="#" class="product-button-icon add-to-wishlist" data-product-id="<?php echo $row['id']; ?>" title="Adicionar aos Favoritos"><i class="far fa-heart"></i></a>
                            </div>
                        </div>
                    </div>
                <?php 
                    $product_count++;
                endwhile; 
                ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section features animate-on-scroll">
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
    <section class="section testimonials animate-on-scroll">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">O Que Dizem os Nossos Clientes</h2>
                <p class="section-subtitle">Milhares de clientes satisfeitos confiam em nós</p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <p class="testimonial-text">"Excelente atendimento e produtos de qualidade. O meu Golden Retriever adora a ração que comprei aqui!"</p>
                    <div class="testimonial-author-info">
                        <div class="testimonial-author">Maria Silva</div>
                        <div class="testimonial-role">Cliente desde 2022</div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"Entrega super rápida e o apoio veterinário ajudou-me muito a escolher os produtos certos para o meu gato."</p>
                    <div class="testimonial-author-info">
                        <div class="testimonial-author">João Santos</div>
                        <div class="testimonial-role">Cliente desde 2021</div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"Preços competitivos e variedade incrível. Recomendo a todos os donos de animais!"</p>
                    <div class="testimonial-author-info">
                        <div class="testimonial-author">Ana Costa</div>
                        <div class="testimonial-role">Cliente desde 2023</div>
                    </div>
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
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
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
                        <li><i class="fas fa-map-marker-alt"></i>Rua das Flores, 123, Lisboa</li>
                        <li><i class="fas fa-phone"></i>+351 213 456 789</li>
                        <li><i class="fas fa-envelope"></i>info@petrepet.pt</li>
                        <li><i class="fas fa-clock"></i>Seg-Sex: 9h-18h</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Pet & Repet. Todos os direitos reservados. | <a href="privacidade.php">Política de Privacidade</a> | <a href="termos.php">Termos de Uso</a></p>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="./scr/categories.js"></script>
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

        // Add to wishlist functionality
        $(document).on('click', '.add-to-wishlist', function(e) {
            e.preventDefault();
            const button = $(this);
            const icon = button.find('i');
            
            button.toggleClass('active');
            
            if (button.hasClass('active')) {
                icon.removeClass('far').addClass('fas');
                // Opcional: Aqui pode adicionar uma chamada AJAX para guardar x    nos favoritos no servidor
            } else {
                icon.removeClass('fas').addClass('far');
                // Opcional: Aqui pode adicionar uma chamada AJAX para remover dos favoritos no servidor
            }
        });

        // Animações ao rolar a página
        document.addEventListener("DOMContentLoaded", function() {
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

            const animatedElements = document.querySelectorAll('.animate-on-scroll');

            if ("IntersectionObserver" in window) {
                const observer = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    rootMargin: '0px 0px -100px 0px' // Aciona a animação um pouco antes do elemento estar totalmente visível
                });

                animatedElements.forEach(el => observer.observe(el));
            } else {
                // Fallback para browsers antigos: mostra todos os elementos de uma vez
                animatedElements.forEach(el => el.classList.add('is-visible'));
            }

            // Carousel functionality
            const carouselInner = document.querySelector('.carousel-inner');
            const slides = document.querySelectorAll('.carousel-slide');
            const prevBtn = document.querySelector('.carousel-control.prev');
            const nextBtn = document.querySelector('.carousel-control.next');
            const indicatorsContainer = document.querySelector('.carousel-indicators');
            
            if (carouselInner) {
                const slideCount = slides.length;
                let currentIndex = 0;
                let autoPlayInterval;

                // Create indicators
                for (let i = 0; i < slideCount; i++) {
                    const indicator = document.createElement('button');
                    indicator.classList.add('indicator');
                    indicator.dataset.index = i;
                    if (i === 0) indicator.classList.add('active');
                    indicatorsContainer.appendChild(indicator);
                }
                const indicators = document.querySelectorAll('.indicator');

                function goToSlide(index) {
                    if (index < 0) {
                        index = slideCount - 1;
                    } else if (index >= slideCount) {
                        index = 0;
                    }

                    carouselInner.style.transform = `translateX(-${index * 100}%)`;
                    
                    slides.forEach(slide => slide.classList.remove('active'));
                    slides[index].classList.add('active');
                    
                    indicators.forEach(indicator => indicator.classList.remove('active'));
                    indicators[index].classList.add('active');
                    
                    currentIndex = index;
                }

                function nextSlide() {
                    goToSlide(currentIndex + 1);
                }

                function prevSlide() {
                    goToSlide(currentIndex - 1);
                }

                function startAutoPlay() {
                    stopAutoPlay();
                    autoPlayInterval = setInterval(nextSlide, 5000);
                }

                function stopAutoPlay() {
                    clearInterval(autoPlayInterval);
                }

                nextBtn.addEventListener('click', () => {
                    nextSlide();
                    stopAutoPlay();
                    startAutoPlay();
                });

                prevBtn.addEventListener('click', () => {
                    prevSlide();
                    stopAutoPlay();
                    startAutoPlay();
                });

                indicators.forEach(indicator => {
                    indicator.addEventListener('click', (e) => {
                        const index = parseInt(e.target.dataset.index, 10);
                        goToSlide(index);
                        stopAutoPlay();
                        startAutoPlay();
                    });
                });

                carouselInner.addEventListener('mouseenter', stopAutoPlay);
                carouselInner.addEventListener('mouseleave', startAutoPlay);

                goToSlide(0);
                startAutoPlay();
            }
        });
    </script>
</body>
</html>