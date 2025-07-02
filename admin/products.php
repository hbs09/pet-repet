<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Product.php';

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

// Buscar produtos do banco de dados
$products = [];
if ($db) {
    try {
        $query = "SELECT p.*, c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Registrar erro e definir mensagem
        error_log("Erro na consulta: " . $e->getMessage());
        $message = "Erro ao carregar produtos: " . $e->getMessage();
        $message_type = "error";
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
    <title>Gestão de Produtos - Pet & Repet</title>
    
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
    
    <meta name="description" content="Gestão de Produtos do Pet & Repet">
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
        
        /* Estilos específicos da página de produtos */
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
        
        .products-container {
            max-height: calc(100vh - 150px);
            overflow-y: auto;
            padding-right: 10px;
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
                <h1><i class="fas fa-box"></i> Gestão de Produtos</h1>
                <div class="header-actions">
                    <a href="add_product.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Adicionar Produto
                    </a>
                </div>
            </div>
            
            <div class="products-container">
                <?php if (empty($products)): ?>
                    <div class="alert alert-info">
                        Nenhum produto encontrado. Comece adicionando um novo produto!
                    </div>
                <?php else: ?>
                    <div class="products-table-container">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Imagem</th>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Preço</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td>
                                        <?php if($product['image_url']): ?>
                                            <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" width="50">
                                        <?php else: ?>
                                            <div class="no-image">Sem imagem</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $product['name']; ?></td>
                                    <td><?php echo $product['category_name']; ?></td>
                                    <td><?php echo number_format($product['price'], 2, ',', ' '); ?> €</td>
                                    <td><?php echo $product['stock_quantity']; ?></td>
                                    <td>
                                        <?php if($product['is_active']): ?>
                                            <span class="status-badge status-active">Ativo</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $product['id']; ?>)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
        
        // Função para confirmar exclusão de produto
        function confirmDelete(productId) {
            if (confirm('Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.')) {
                window.location.href = `products.php?delete=${productId}`;
            }
        }
    </script>
</body>
</html>
