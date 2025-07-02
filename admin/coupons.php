<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';

// Verificar se o utilizador está logado e é administrador
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit;
}

// Conectar ao banco de dados
$database = new Database();
$db = $database->getConnection();

// Verificar se a conexão foi bem-sucedida
if (!$db) {
    die("Erro: Não foi possível conectar ao banco de dados MySQL.");
}

// Mensagens de feedback
$message = "";
$message_type = "";

// Buscar cupões do banco de dados
$coupons = [];
if ($db) {
    try {
        $query = "SELECT * FROM coupons ORDER BY id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Se a tabela não existir, não exibe erro, apenas mostra uma mensagem
        if ($e->getCode() == '42S02') { // Código de erro MySQL para tabela inexistente
            $message = "A tabela de cupões ainda não foi criada. Configure-a para começar a usar cupões.";
            $message_type = "info";
        } else {
            // Outros erros
            error_log("Erro na consulta de cupões: " . $e->getMessage());
            $message = "Erro ao carregar cupões: " . $e->getMessage();
            $message_type = "error";
        }
    }
} else {
    // Se $db for nulo, exibir uma mensagem de erro
    $message = "Não foi possível conectar ao banco de dados. Verifique se o serviço MySQL está ativo no XAMPP.";
    $message_type = "error";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Cupões - Pet & Repet</title>
    
    <!-- Common Styles -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Toastify for notifications -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js"></script>
    
    <!-- Custom scripts -->
    <script type="text/javascript" src="js/toast-manager.js"></script>
    <script type="text/javascript" src="js/prevent-auto-toasts.js"></script>
    <script type="text/javascript" src="js/content-scroll-manager.js"></script>
    
    <!-- Sidebar CSS -->
    <link rel="stylesheet" type="text/css" href="css/sidebar.css">
    
    <meta name="description" content="Gestão de Cupões do Pet & Repet">
    <meta name="theme-color" content="#8c52ff">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="Pet & Repet Admin">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <style>
        /* CSS para garantir que os toasts sejam visíveis */
        .toastify {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 9999 !important;
        }
        
        /* Esconder mensagens HTML tradicionais */
        .message {
            display: none !important;
        }
        
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
        
        /* Estilos específicos da página de cupões */
        .main-content {
            padding: 20px 30px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
        }
        
        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            color: #333;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
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
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8c52ff, #6a3fc3);
            color: white;
        }
        
        .btn-primary:hover {
            box-shadow: 0 5px 15px rgba(140, 82, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .coupons-container {
            max-height: calc(100vh - 150px);
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .setup-message {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin: 40px auto;
            max-width: 600px;
        }
        
        .setup-message h2 {
            color: #6a3fc3;
            margin-bottom: 20px;
        }
        
        .setup-message p {
            color: #666;
            margin-bottom: 25px;
        }
        
        .coupon-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .coupon-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .coupon-code {
            display: inline-block;
            background: #f1f3f9;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: monospace;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .coupon-details {
            margin-top: 10px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .coupon-actions {
            display: flex;
            gap: 8px;
        }
    </style>
</head>
<body>
    <script>
        // Definindo variável para guardar mensagens PHP importantes
        window.phpMessages = {
            message: <?php echo !empty($message) ? json_encode($message) : 'null'; ?>,
            type: <?php echo !empty($message_type) ? json_encode($message_type) : 'null'; ?>
        };
    </script>
    
    <div class="dashboard-container">
        <?php
        // Define this constant to allow the sidebar include
        define('INCLUDE_PERMITTED', true);
        include_once 'templates/sidebar.php';
        ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-percent"></i> Gestão de Cupões</h1>
                <div class="header-actions">
                    <a href="add_coupon.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Adicionar Cupão
                    </a>
                </div>
            </div>
            
            <div class="coupons-container">
                <?php if (empty($coupons) && $message_type == 'info'): ?>
                    <!-- Mensagem quando a tabela de cupões não existe -->
                    <div class="setup-message">
                        <h2>Configuração de Cupões</h2>
                        <p>O sistema de cupões ainda não está configurado. Para começar a usar cupões promocionais, você precisa criar a tabela necessária no banco de dados.</p>
                        <a href="setup_coupons.php" class="btn btn-primary">
                            <i class="fas fa-cog"></i> Configurar Sistema de Cupões
                        </a>
                    </div>
                <?php elseif (empty($coupons)): ?>
                    <!-- Mensagem quando não há cupões cadastrados -->
                    <div class="alert alert-info">
                        Nenhum cupão encontrado. Comece adicionando um novo cupão promocional!
                    </div>
                <?php else: ?>
                    <!-- Lista de cupões -->
                    <?php foreach($coupons as $coupon): ?>
                        <div class="coupon-card">
                            <div class="coupon-info">
                                <h3><?php echo $coupon['name']; ?></h3>
                                <span class="coupon-code"><?php echo $coupon['code']; ?></span>
                                <div class="coupon-details">
                                    <?php if($coupon['discount_type'] == 'percentage'): ?>
                                        <span><?php echo $coupon['discount_value']; ?>% de desconto</span>
                                    <?php else: ?>
                                        <span><?php echo number_format($coupon['discount_value'], 2, ',', ' '); ?>€ de desconto</span>
                                    <?php endif; ?>
                                    
                                    <span> • Válido até: <?php echo date('d/m/Y', strtotime($coupon['expiry_date'])); ?></span>
                                    
                                    <?php if($coupon['is_active']): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inativo</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="coupon-actions">
                                <a href="edit_coupon.php?id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmDelete(<?php echo $coupon['id']; ?>)" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Adaptação para dispositivos móveis
            function adjustLayout() {
                try {
                    const windowWidth = window.innerWidth;
                    const sidebar = document.querySelector('.sidebar');
                    const mainContent = document.querySelector('.main-content');
                    
                    if (!sidebar || !mainContent) return;
                    
                    if (windowWidth <= 576) {
                        sidebar.style.display = 'none';
                        mainContent.style.marginLeft = '0';
                        mainContent.style.width = '100%';
                    } else {
                        sidebar.style.display = 'flex';
                        if (windowWidth <= 768) {
                            mainContent.style.marginLeft = '180px';
                            mainContent.style.width = 'calc(100% - 180px)';
                        } else if (windowWidth <= 992) {
                            mainContent.style.marginLeft = '220px';
                            mainContent.style.width = 'calc(100% - 220px)';
                        } else {
                            mainContent.style.marginLeft = '280px';
                            mainContent.style.width = 'calc(100% - 280px)';
                        }
                    }
                } catch (err) {
                    console.error('Erro ao ajustar layout:', err);
                }
            }
            
            // Executar no carregamento da página e depois de qualquer redimensionamento
            window.addEventListener('load', adjustLayout);
            window.addEventListener('resize', adjustLayout);
        });
        
        // Função para confirmar exclusão de cupão
        function confirmDelete(couponId) {
            if (confirm('Tem certeza que deseja excluir este cupão? Esta ação não pode ser desfeita.')) {
                window.location.href = `coupons.php?delete=${couponId}`;
            }
        }
    </script>
</body>
</html>
