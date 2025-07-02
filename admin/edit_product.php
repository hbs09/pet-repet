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

// Verificar se o ID do produto foi fornecido
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "ID do produto inválido.";
    $_SESSION['message_type'] = "error";
    header("Location: products.php");
    exit;
}

$product_id = $_GET['id'];

// Buscar dados do produto
$query = "SELECT * FROM products WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $product_id);
$stmt->execute();

if($stmt->rowCount() == 0) {
    $_SESSION['message'] = "Produto não encontrado.";
    $_SESSION['message_type'] = "error";
    header("Location: products.php");
    exit;
}

$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar categorias adicionais do produto
$query = "SELECT category_id FROM product_categories WHERE product_id = :product_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':product_id', $product_id);
$stmt->execute();
$additional_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Buscar imagens do produto
$query = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, sort_order ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':product_id', $product_id);
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as categorias para o formulário
$query = "SELECT id, name, parent_id FROM categories ORDER BY parent_id, name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar categorias por hierarquia
$categorized = [];
foreach($categories as $category) {
    if($category['parent_id'] === null) {
        $categorized[$category['id']] = [
            'name' => $category['name'],
            'children' => []
        ];
    }
}

foreach($categories as $category) {
    if($category['parent_id'] !== null && isset($categorized[$category['parent_id']])) {
        $categorized[$category['parent_id']]['children'][] = $category;
    }
}

// Processar formulário de atualização
$message = "";
$message_type = "";

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Iniciar transação
        $db->beginTransaction();
        
        // Validar dados obrigatórios
        $required_fields = ['name', 'price', 'category_id', 'stock_quantity'];
        foreach($required_fields as $field) {
            if(empty($_POST[$field])) {
                throw new Exception("O campo " . $field . " é obrigatório.");
            }
        }
        
        // Processar imagem se foi enviada
        $image_url = $product['image_url'];
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if(!in_array($_FILES['image']['type'], $allowed_types)) {
                throw new Exception("Tipo de arquivo não permitido. Use JPG, PNG ou WEBP.");
            }
            
            if($_FILES['image']['size'] > $max_size) {
                throw new Exception("A imagem deve ter no máximo 2MB.");
            }
            
            // Criar diretório se não existir
            $upload_dir = '../media/produtos/';
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Gerar nome único para o arquivo
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('product_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = './media/produtos/' . $new_filename;
            } else {
                throw new Exception("Falha ao fazer upload da imagem.");
            }
        }
        
        // Atualizar produto
        $query = "UPDATE products SET 
                  name = :name, 
                  description = :description, 
                  short_description = :short_description, 
                  price = :price, 
                  sale_price = :sale_price, 
                  sku = :sku, 
                  image_url = :image_url, 
                  category_id = :category_id, 
                  brand = :brand, 
                  stock_quantity = :stock_quantity, 
                  is_active = :is_active, 
                  is_featured = :is_featured 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        // Bind dos parâmetros
        $stmt->bindParam(':id', $product_id);
        $stmt->bindParam(':name', $_POST['name']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':short_description', $_POST['short_description']);
        $stmt->bindParam(':price', $_POST['price']);
        
        $sale_price = !empty($_POST['sale_price']) ? $_POST['sale_price'] : null;
        $stmt->bindParam(':sale_price', $sale_price);
        
        $stmt->bindParam(':sku', $_POST['sku']);
        $stmt->bindParam(':image_url', $image_url);
        $stmt->bindParam(':category_id', $_POST['category_id']);
        $stmt->bindParam(':brand', $_POST['brand']);
        $stmt->bindParam(':stock_quantity', $_POST['stock_quantity']);
        $stmt->bindParam(':is_active', $_POST['is_active']);
        $stmt->bindParam(':is_featured', $_POST['is_featured']);
        
        if($stmt->execute()) {
            // Remover categorias adicionais existentes
            $delete_query = "DELETE FROM product_categories WHERE product_id = :product_id";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->bindParam(':product_id', $product_id);
            $delete_stmt->execute();
            
            // Processar categorias adicionais
            if(isset($_POST['additional_categories']) && is_array($_POST['additional_categories'])) {
                $categories_query = "INSERT INTO product_categories (product_id, category_id) VALUES (:product_id, :category_id)";
                $cat_stmt = $db->prepare($categories_query);
                
                foreach($_POST['additional_categories'] as $category_id) {
                    $cat_stmt->bindParam(':product_id', $product_id);
                    $cat_stmt->bindParam(':category_id', $category_id);
                    $cat_stmt->execute();
                }
            }
            
            // Adicionar categoria principal também (se não estiver já nas adicionais)
            $check_query = "SELECT COUNT(*) FROM product_categories WHERE product_id = :product_id AND category_id = :category_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':product_id', $product_id);
            $check_stmt->bindParam(':category_id', $_POST['category_id']);
            $check_stmt->execute();
            
            if($check_stmt->fetchColumn() == 0) {
                $main_cat_query = "INSERT INTO product_categories (product_id, category_id) VALUES (:product_id, :category_id)";
                $main_cat_stmt = $db->prepare($main_cat_query);
                $main_cat_stmt->bindParam(':product_id', $product_id);
                $main_cat_stmt->bindParam(':category_id', $_POST['category_id']);
                $main_cat_stmt->execute();
            }
            
            // Se a imagem foi atualizada, atualizar também na tabela product_images
            if($image_url != $product['image_url'] && $image_url != null) {
                // Verificar se já existe uma imagem principal
                $check_image_query = "SELECT COUNT(*) FROM product_images WHERE product_id = :product_id AND is_primary = 1";
                $check_img_stmt = $db->prepare($check_image_query);
                $check_img_stmt->bindParam(':product_id', $product_id);
                $check_img_stmt->execute();
                
                if($check_img_stmt->fetchColumn() > 0) {
                    // Atualizar imagem principal existente
                    $update_img_query = "UPDATE product_images SET image_url = :image_url, alt_text = :alt_text 
                                        WHERE product_id = :product_id AND is_primary = 1";
                    $update_img_stmt = $db->prepare($update_img_query);
                    $update_img_stmt->bindParam(':product_id', $product_id);
                    $update_img_stmt->bindParam(':image_url', $image_url);
                    $update_img_stmt->bindParam(':alt_text', $_POST['name']);
                    $update_img_stmt->execute();
                } else {
                    // Adicionar nova imagem principal
                    $insert_img_query = "INSERT INTO product_images (product_id, image_url, alt_text, is_primary) 
                                       VALUES (:product_id, :image_url, :alt_text, 1)";
                    $insert_img_stmt = $db->prepare($insert_img_query);
                    $insert_img_stmt->bindParam(':product_id', $product_id);
                    $insert_img_stmt->bindParam(':image_url', $image_url);
                    $insert_img_stmt->bindParam(':alt_text', $_POST['name']);
                    $insert_img_stmt->execute();
                }
            }
            
            $db->commit();
            $message = "Produto atualizado com sucesso.";
            $message_type = "success";
        } else {
            throw new Exception("Erro ao atualizar produto.");
        }
        
    } catch(Exception $e) {
        $db->rollBack();
        $message = "Erro: " . $e->getMessage();
        $message_type = "error";
    }
}

// Recarregar dados do produto após atualização
$query = "SELECT * FROM products WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $product_id);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Recarregar categorias adicionais
$query = "SELECT category_id FROM product_categories WHERE product_id = :product_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':product_id', $product_id);
$stmt->execute();
$additional_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../scr/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- Custom Cat Paw Loader -->
    <link rel="stylesheet" type="text/css" href="../scr/custom-loader.css">
    <script type="text/javascript" src="../scr/custom-loader.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js"></script>
    <script>
    // Função global para exibir toasts
    function showGlobalToast(message, type = 'info') {
        console.log("Exibindo toast global:", message, type);
        
        var background = "#3498db"; // azul para info
        var duration = 3000;
        
        if (type === 'success') {
            background = "#2ecc71"; // verde
            duration = 4000;
        } else if (type === 'error') {
            background = "#e74c3c"; // vermelho
            duration = 6000;
        } else if (type === 'warning') {
            background = "linear-gradient(to right, #ff7e5f, #feb47b)"; // laranja
            duration = 5000;
        }
        
        // Remover ícones HTML (<i>) e emojis do texto da mensagem
        let cleanMessage = message;
        try {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = message;
            cleanMessage = tempDiv.textContent || tempDiv.innerText || message;
            
            // Remover emojis comuns usados em notificações (✅, ❌, etc.)
            cleanMessage = cleanMessage.replace(/[\u{1F300}-\u{1F5FF}\u{1F900}-\u{1F9FF}\u{1F600}-\u{1F64F}\u{1F680}-\u{1F6FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}✅❌]/ug, '');
            
            // Remover espaços extras que possam ter ficado
            cleanMessage = cleanMessage.replace(/\s+/g, ' ').trim();
        } catch (err) {
            console.error("Erro ao limpar mensagem:", err);
        }
        
        try {
            Toastify({
                text: cleanMessage,
                duration: duration,
                gravity: "top",
                position: "center",
                style: {
                    background: background,
                    borderRadius: "8px",
                    boxShadow: "0 3px 10px rgba(0,0,0,0.2)",
                    fontSize: "14px",
                    padding: "12px 20px"
                }
            }).showToast();
            return true;
        } catch (e) {
            console.error("Erro ao exibir toast:", e);
            alert(message);
            return false;
        }
    }
    </script>
    <title>Editar Produto - Pet & Repet</title>
    <meta name="description" content="Editar Produto do Pet & Repet">
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
        
        .dashboard-header .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn i {
            margin-right: 6px;
        }
        
        /* Formulário */
        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .category-group {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .category-parent {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .category-children {
            margin-left: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .category-item {
            display: inline-block;
            margin-right: 10px;
        }
        
        /* Preview imagem */
        .image-preview {
            width: 100px;
            height: 100px;
            border-radius: 4px;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Mensagens */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .message-success {
            background-color: #e3fcef;
            color: #2ecc71;
            border-left: 4px solid #2ecc71;
        }
        
        .message-error {
            background-color: #fde8e8;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
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
                    <li><a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h3 class="sidebar-section-title">Gestão de Produtos</h3>
                <ul class="sidebar-menu">
                    <li><a href="products.php" class="active"><i class="fas fa-box"></i> Produtos</a></li>
                    <li><a href="categories.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="coupons.php"><i class="fas fa-percent"></i> Cupões</a></li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h3 class="sidebar-section-title">Vendas & Clientes</h3>
                <ul class="sidebar-menu">
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Pedidos</a></li>
                    <li><a href="customers.php"><i class="fas fa-users"></i> Clientes</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-line"></i> Relatórios</a></li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h3 class="sidebar-section-title">Sistema</h3>
                <ul class="sidebar-menu">
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Configurações</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </div>
            
            <div class="sidebar-footer">
                <span><i class="fas fa-circle status-online"></i> Online</span>
                <span class="version">v1.0</span>
            </div>
        </div>
        
        <div class="main-content">
            <div class="dashboard-header">
                <h1>Editar Produto</h1>
                <div class="actions">
                    <a href="products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
                </div>
            </div>
            
            <?php if(!empty($message)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showPawLoader('Processando...');
                    setTimeout(function() {
                        hidePawLoader();
                        showGlobalToast('<?php echo addslashes($message); ?>', '<?php echo $message_type; ?>');
                    }, 500);
                });
            </script>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form action="edit_product.php?id=<?php echo $product_id; ?>" method="post" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="product_name">Nome do Produto*</label>
                                <input type="text" id="product_name" name="name" class="form-control" required value="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="sku">SKU</label>
                                <input type="text" id="sku" name="sku" class="form-control" value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="short_description">Descrição Curta</label>
                            <input type="text" id="short_description" name="short_description" class="form-control" value="<?php echo htmlspecialchars($product['short_description'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Descrição Completa</label>
                            <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Preço*</label>
                                <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required value="<?php echo $product['price']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="sale_price">Preço Promocional</label>
                                <input type="number" id="sale_price" name="sale_price" class="form-control" step="0.01" min="0" value="<?php echo $product['sale_price'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="stock_quantity">Quantidade em Estoque*</label>
                                <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0" required value="<?php echo $product['stock_quantity']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="brand">Marca</label>
                                <input type="text" id="brand" name="brand" class="form-control" value="<?php echo htmlspecialchars($product['brand'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="main_category">Categoria Principal*</label>
                            <select id="main_category" name="category_id" class="form-control" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach($categorized as $id => $parent): ?>
                                    <option value="<?php echo $id; ?>" <?php echo $product['category_id'] == $id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($parent['name']); ?>
                                    </option>
                                    <?php foreach($parent['children'] as $child): ?>
                                        <option value="<?php echo $child['id']; ?>" <?php echo $product['category_id'] == $child['id'] ? 'selected' : ''; ?>>
                                            &nbsp;&nbsp;- <?php echo htmlspecialchars($child['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Categorias Adicionais</label>
                            <?php foreach($categorized as $id => $parent): ?>
                                <div class="category-group">
                                    <div class="category-parent"><?php echo htmlspecialchars($parent['name']); ?></div>
                                    <div class="category-children">
                                        <?php foreach($parent['children'] as $child): ?>
                                            <label class="category-item">
                                                <input type="checkbox" name="additional_categories[]" value="<?php echo $child['id']; ?>" 
                                                       <?php echo in_array($child['id'], $additional_categories) ? 'checked' : ''; ?>>
                                                <?php echo htmlspecialchars($child['name']); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_image">Imagem Principal</label>
                            <input type="file" id="product_image" name="image" class="form-control">
                            <?php if(!empty($product['image_url'])): ?>
                                <div class="image-preview">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                                <small class="form-text text-muted">Atual: <?php echo htmlspecialchars($product['image_url']); ?></small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="is_active">Status</label>
                                <select id="is_active" name="is_active" class="form-control">
                                    <option value="1" <?php echo $product['is_active'] == 1 ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="0" <?php echo $product['is_active'] == 0 ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="is_featured">Destaque</label>
                                <select id="is_featured" name="is_featured" class="form-control">
                                    <option value="0" <?php echo $product['is_featured'] == 0 ? 'selected' : ''; ?>>Não</option>
                                    <option value="1" <?php echo $product['is_featured'] == 1 ? 'selected' : ''; ?>>Sim</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Already included the Toastify and showGlobalToast functionality in the head section -->
</body>
</html>
