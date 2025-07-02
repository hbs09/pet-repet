<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';

// Security check - only admins can access this page
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit;
}

// Define this constant to allow the sidebar include
define('INCLUDE_PERMITTED', true);

// Database connection
$database = new Database();
$db = $database->getConnection();

// Page-specific variables
$page_title = "Título da Página";
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Pet & Repet Admin</title>
    
    <!-- Common Styles -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Toastify for notifications -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js"></script>
    
    <!-- Custom Cat Paw Loader -->
    <link rel="stylesheet" type="text/css" href="../scr/custom-loader.css">
    <script type="text/javascript" src="../scr/custom-loader.js"></script>
    
    <!-- Custom scripts -->
    <script type="text/javascript" src="js/toast-manager.js"></script>
    <script type="text/javascript" src="js/prevent-auto-toasts.js"></script>
    <script type="text/javascript" src="js/content-scroll-manager.js"></script>
    
    <!-- Sidebar CSS -->
    <link rel="stylesheet" type="text/css" href="css/sidebar.css">
    
    <!-- Page-specific CSS -->
    <style>
        /* Reset básico para evitar conflitos */
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff;
            color: #333;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Page header styling */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .page-header h1 {
            font-size: 1.8rem;
            color: #333;
            margin: 0;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        
        .page-header h1::before {
            content: '';
            display: inline-block;
            width: 3px;
            height: 22px;
            background: linear-gradient(to bottom, #8c52ff, #6a3fc3);
            margin-right: 12px;
            border-radius: 6px;
        }
        
        .page-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn {
            padding: 9px 18px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 6px rgba(0,0,0,0.05);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8c52ff, #6a3fc3);
            color: white;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            box-shadow: 0 5px 15px rgba(140, 82, 255, 0.3);
            transform: translateY(-2px);
        }
        
        /* Page content container */
        .page-content {
            padding: 20px;
            overflow-y: auto;
            height: calc(100vh - 100px);
        }
    </style>
</head>
<body>
    <script>
        // Global PHP messages for toast notifications
        window.phpMessages = {
            message: <?php echo !empty($message) ? json_encode($message) : 'null'; ?>,
            type: <?php echo !empty($message_type) ? json_encode($message_type) : 'null'; ?>
        };
        
        // Assegurar que todas as notificações usem a mesma função centralizada
        document.addEventListener('DOMContentLoaded', function() {
            // Garantir que showGlobalToast sempre use o ToastManager
            if (typeof window.showGlobalToast !== 'function') {
                window.showGlobalToast = function(message, type) {
                    return ToastManager.showToast(message, type);
                };
            }
            
            // Exibir mensagem PHP se existir (usando o ToastManager centralizado)
            if (window.phpMessages && window.phpMessages.message) {
                ToastManager.showToast(window.phpMessages.message, window.phpMessages.type || 'info');
            }
        });
    </script>
    
    <div class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include_once 'templates/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <div class="page-header">
                <h1><?php echo $page_title; ?></h1>
                <div class="page-actions">
                    <button class="btn btn-primary"><i class="fas fa-plus"></i> Nova Ação</button>
                </div>
            </div>
            
            <div class="page-content">
                <!-- Your page content goes here -->
                <p>Conteúdo da página vai aqui. Este é um template para páginas admin com sidebar.</p>
            </div>
        </div>
    </div>
    
    <!-- Sidebar functionality -->
    <script src="js/sidebar.js"></script>
    
    <!-- Page-specific scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Your page-specific code goes here
            console.log('Page loaded');
        });
    </script>
</body>
</html>
