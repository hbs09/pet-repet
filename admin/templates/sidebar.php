<?php
// Ensure this file is only included in other pages and not accessed directly
if (!defined('INCLUDE_PERMITTED')) {
    header("HTTP/1.1 403 Forbidden");
    exit('Forbidden access');
}

// Get the current file name to highlight the active menu item
$current_file = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Pet&Repet</h2>
        <p>Bem-vindo, <?php echo $_SESSION['user_first_name']; ?></p>
        <div class="admin-badge">
            <span>Administrador</span>
        </div>
    </div>
    
    <div class="sidebar-section">
        <h3 class="sidebar-section-title">Principal</h3>
        <ul class="sidebar-menu">
            <li><a href="../dashboard.php" <?php echo ($current_file == 'dashboard.php') ? 'class="active"' : ''; ?>><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        </ul>
    </div>
    
    <div class="sidebar-section">
        <h3 class="sidebar-section-title">Gestão de Produtos</h3>
        <ul class="sidebar-menu">
            <li><a href="products.php" <?php echo ($current_file == 'products.php') ? 'class="active"' : ''; ?>><i class="fas fa-box"></i> Produtos</a></li>
            <li><a href="categories.php" <?php echo ($current_file == 'categories.php') ? 'class="active"' : ''; ?>><i class="fas fa-tags"></i> Categorias</a></li>
            <li><a href="coupons.php" <?php echo ($current_file == 'coupons.php') ? 'class="active"' : ''; ?>><i class="fas fa-percent"></i> Cupões</a></li>
        </ul>
    </div>
    
    <div class="sidebar-section">
        <h3 class="sidebar-section-title">Vendas & Clientes</h3>
        <ul class="sidebar-menu">
            <li><a href="orders.php" <?php echo ($current_file == 'orders.php') ? 'class="active"' : ''; ?>><i class="fas fa-shopping-cart"></i> Pedidos</a></li>
            <li><a href="customers.php" <?php echo ($current_file == 'customers.php') ? 'class="active"' : ''; ?>><i class="fas fa-users"></i> Clientes</a></li>
            <li><a href="reports.php" <?php echo ($current_file == 'reports.php') ? 'class="active"' : ''; ?>><i class="fas fa-chart-line"></i> Relatórios</a></li>
        </ul>
    </div>
    
    <div class="sidebar-section">
        <h3 class="sidebar-section-title">Sistema</h3>
        <ul class="sidebar-menu">
            <li><a href="settings.php" <?php echo ($current_file == 'settings.php') ? 'class="active"' : ''; ?>><i class="fas fa-cog"></i> Configurações</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
        </ul>
    </div>
    
    <div class="sidebar-footer">
        <span><i class="fas fa-circle status-online"></i> Online</span>
        <span class="version">v1.0</span>
    </div>
</div>

<script>
// Function to adjust sidebar display based on screen size
function adjustSidebar() {
    try {
        const windowWidth = window.innerWidth;
        const sidebar = document.querySelector('.sidebar');
        
        if (!sidebar) return;
        
        if (windowWidth <= 576) {
            sidebar.style.display = 'none';
        } else {
            sidebar.style.display = 'flex';
        }
    } catch (err) {
        console.error('Erro ao ajustar layout da sidebar:', err);
    }
}

// Run on page load and window resize
window.addEventListener('load', adjustSidebar);
window.addEventListener('resize', adjustSidebar);

// Toggle sidebar for mobile view
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar.style.display === 'none' || sidebar.style.display === '') {
        sidebar.style.display = 'flex';
    } else {
        sidebar.style.display = 'none';
    }
}
</script>
