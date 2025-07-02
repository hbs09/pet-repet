<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';

// Verificar se o utilizador está logado e é administrador
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./scr/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <title>Painel de Administração - Pet & Repet</title>
    <meta name="description" content="Painel de Administração Pet & Repet">
    <style>
        :root {
            --primary-color: #8c52ff;
            --secondary-color: #6a3fc3;
            --sidebar-bg: #2b2d42;
            --sidebar-hover: #3c3f5c;
            --sidebar-active: #8c52ff;
            --text-light: #f8f9fa;
            --text-muted: #adb5bd;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            margin-top: 0;
        }
        
        .sidebar {
            width: 280px;
            background-color: var(--sidebar-bg);
            color: var(--text-light);
            padding: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            z-index: 100;
            position: relative;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: 25px;
            background-color: rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 15px;
            text-align: center;
        }
        
        .sidebar-header h2 {
            margin: 0 0 10px 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            letter-spacing: 0.5px;
        }
        
        .sidebar-header p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0 10px;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 8px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .sidebar-menu a:hover {
            background-color: var(--sidebar-hover);
            color: var(--text-light);
            transform: translateX(5px);
        }
        
        .sidebar-menu a.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 8px rgba(140, 82, 255, 0.3);
        }
        
        .sidebar-menu i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover i, 
        .sidebar-menu a.active i {
            transform: scale(1.2);
        }
        
        /* Admin badge */
        .admin-badge {
            margin-top: 12px;
        }
        
        .admin-badge span {
            background-color: var(--primary-color);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Sidebar sections */
        .sidebar-section {
            margin-bottom: 15px;
            padding: 0 10px;
        }
        
        .sidebar-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin: 20px 15px 10px;
            font-weight: 600;
        }
        
        /* Sidebar footer */
        .sidebar-footer {
            margin-top: auto;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: var(--text-muted);
            border-top: 1px solid rgba(255,255,255,0.05);
            position: absolute;
            bottom: 0;
            width: 100%;
            box-sizing: border-box;
        }
        
        .status-online {
            color: #2ecc71;
            font-size: 0.65rem;
            margin-right: 5px;
        }
        
        .version {
            background-color: rgba(255,255,255,0.1);
            padding: 2px 5px;
            border-radius: 3px;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .dashboard-header h1 {
            font-size: 1.8rem;
            color: #333;
            margin: 0;
            font-weight: 600;
        }
        
        .dashboard-header .date {
            background-color: var(--sidebar-bg);
            color: #fff;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        
        .dashboard-header .date i {
            margin-right: 8px;
            color: var(--primary-color);
        }
        
        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            margin: 0;
            font-size: 16px;
            color: #7f8c8d;
        }
        
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 600;
            margin: 10px 0 0;
            color: #2c3e50;
        }
        
        .stat-card .trend {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .trend-up {
            color: #2ecc71;
        }
        
        .trend-down {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
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
                    <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h3 class="sidebar-section-title">Gestão de Produtos</h3>
                <ul class="sidebar-menu">
                    <li><a href="admin/products.php"><i class="fas fa-box"></i> Produtos</a></li>
                    <li><a href="admin/categories.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="#"><i class="fas fa-percent"></i> Cupões</a></li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h3 class="sidebar-section-title">Vendas & Clientes</h3>
                <ul class="sidebar-menu">
                    <li><a href="#"><i class="fas fa-shopping-cart"></i> Pedidos</a></li>
                    <li><a href="#"><i class="fas fa-users"></i> Clientes</a></li>
                    <li><a href="#"><i class="fas fa-chart-line"></i> Relatórios</a></li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h3 class="sidebar-section-title">Sistema</h3>
                <ul class="sidebar-menu">
                    <li><a href="#"><i class="fas fa-cog"></i> Configurações</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </div>
            
            <div class="sidebar-footer">
                <span><i class="fas fa-circle status-online"></i> Online</span>
                <span class="version">v1.0</span>
            </div>
        </div>
        
        <div class="main-content">
            <div class="dashboard-header">
                <h1>Painel de Controle</h1>
                <div class="date"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y'); ?></div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Vendas Totais</h3>
                    <div class="stat-value">€2,450.32</div>
                    <div class="trend"><i class="fas fa-arrow-up trend-up"></i> 12.5% desde o mês passado</div>
                </div>
                
                <div class="stat-card">
                    <h3>Pedidos</h3>
                    <div class="stat-value">15</div>
                    <div class="trend"><i class="fas fa-arrow-up trend-up"></i> 5 novos hoje</div>
                </div>
                
                <div class="stat-card">
                    <h3>Clientes</h3>
                    <div class="stat-value">237</div>
                    <div class="trend"><i class="fas fa-arrow-up trend-up"></i> 18 novos esta semana</div>
                </div>
                
                <div class="stat-card">
                    <h3>Produtos</h3>
                    <div class="stat-value">89</div>
                    <div class="trend"><i class="fas fa-arrow-down trend-down"></i> 3 com estoque baixo</div>
                </div>
            </div>
            
            <div class="card">
                <h2>Pedidos Recentes</h2>
                <p>Em construção - Aqui irá aparecer uma lista dos pedidos mais recentes.</p>
            </div>
            
            <div class="card">
                <h2>Produtos Populares</h2>
                <p>Em construção - Aqui irá aparecer uma lista dos produtos mais vendidos.</p>
            </div>
        </div>
    </div>
    
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Aqui você pode adicionar JavaScript para funcionalidades do painel
        });
    </script>
</body>
</html>
