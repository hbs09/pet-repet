<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/pet-repet/scr/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <title><?php echo $page_title ?? 'Pet & Repet'; ?></title>
</head>
<body>
    <nav class="top-nav">
        <div class="container">
            <a href="/pet-repet/index.php" class="nav-brand">Pet & Repet</a>
            <div class="nav-tools">
                <div class="search-bar">
                    <input type="text" placeholder="Procurar produtos...">
                    <button><i class="fas fa-search"></i></button>
                </div>
                <div class="tool-icons">
                    <a href="#" class="tool-icon"><i class="far fa-heart"></i></a>
                    <a href="#" class="tool-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">3</span>
                    </a>
                    <div class="tool-icon account-menu" id="account-menu-icon">
                        <i class="far fa-user"></i>
                        <div class="account-dropdown">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <div class="dropdown-header">
                                    <p class="user-name"><?php echo htmlspecialchars($_SESSION['user_first_name']); ?></p>
                                    <p class="user-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                                </div>
                                <a href="/pet-repet/account.php" class="account-dropdown-item">
                                    <i class="fas fa-user-circle"></i>
                                    <span>A Minha Conta</span>
                                </a>
                                <a href="/pet-repet/orders.php" class="account-dropdown-item">
                                    <i class="fas fa-box"></i>
                                    <span>As Minhas Encomendas</span>
                                </a>
                                <a href="/pet-repet/wishlist.php" class="account-dropdown-item">
                                    <i class="fas fa-heart"></i>
                                    <span>Lista de Desejos</span>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="/pet-repet/logout.php" class="account-dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Terminar Sessão</span>
                                </a>
                            <?php else: ?>
                                <a href="/pet-repet/login.php" class="account-dropdown-item">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span>Entrar</span>
                                </a>
                                <a href="/pet-repet/registo.php" class="account-dropdown-item">
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const accountMenuIcon = document.getElementById('account-menu-icon');

        if (accountMenuIcon) {
            accountMenuIcon.addEventListener('click', function(event) {
                event.stopPropagation(); 
                this.classList.toggle('active');
            });

            document.addEventListener('click', function(event) {
                if (accountMenuIcon.classList.contains('active') && !accountMenuIcon.contains(event.target)) {
                    accountMenuIcon.classList.remove('active');
                }
            });
        }
    });
    </script>
</body>
</html>
