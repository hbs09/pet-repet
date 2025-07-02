<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/CategoryManager.php';

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
    die("Erro: Não foi possível conectar ao banco de dados MySQL. Verifique se o servidor MySQL está em execução e se as configurações de conexão estão corretas.");
}

// Instância do gerenciador de categorias
$categoryManager = new CategoryManager($db);

// Mensagens de feedback
$message = "";
$message_type = "";

// Verificar se há mensagens temporárias na sessão
if(isset($_SESSION['temp_message']) && !empty($_SESSION['temp_message'])) {
    $message = $_SESSION['temp_message'];
    $message_type = $_SESSION['temp_message_type'] ?? 'error';
    // Limpar as mensagens da sessão após utilizá-las
    unset($_SESSION['temp_message']);
    unset($_SESSION['temp_message_type']);
}

// Manter compatibilidade com o método GET para exclusão (será redirecionado para POST)
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    // Redirecionar para a mesma página sem o parâmetro, para evitar reprocessamento
    // Isso irá evitar que o URL continue com ?delete=id após a operação
    header("Location: " . $_SERVER['PHP_SELF'] . "?delete_redirect=" . $category_id);
    exit;
}

// Processar exclusão via GET redirecionado, mas usando POST
if(isset($_GET['delete_redirect']) && is_numeric($_GET['delete_redirect'])) {
    $category_id = $_GET['delete_redirect'];
    
    // Incluir código JavaScript para enviar um formulário POST automaticamente
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // Criar e enviar um formulário para fazer a exclusão via POST
            const form = document.createElement("form");
            form.method = "POST";
            form.action = "' . $_SERVER['PHP_SELF'] . '";
            form.style.display = "none";
            
            // Adicionar campo para identificar a ação como delete
            const actionInput = document.createElement("input");
            actionInput.type = "hidden";
            actionInput.name = "action";
            actionInput.value = "delete";
            form.appendChild(actionInput);
            
            // Adicionar o ID da categoria a ser excluída
            const categoryIdInput = document.createElement("input");
            categoryIdInput.type = "hidden";
            categoryIdInput.name = "category_id";
            categoryIdInput.value = "' . $category_id . '";
            form.appendChild(categoryIdInput);
            
            // Adicionar o formulário ao documento e enviá-lo
            document.body.appendChild(form);
            form.submit();
        });
    </script>';
}

// Processar adição de categoria
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    try {
        // Verificar campos obrigatórios
        if(empty($_POST['name'])) {
            throw new Exception("O nome da categoria é obrigatório.");
        }
        
        $name = trim($_POST['name']);
        
        // Verificar se o nome já existe para evitar duplicações
        $check_name = $db->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND parent_id = ?");
        $check_name->execute([$name, $_POST['parent_id']]);
        if($check_name->fetchColumn() > 0) {
            throw new Exception("Já existe uma categoria com este nome neste nível.");
        }
        
        $description = trim($_POST['description']);
        $parent_id = $_POST['parent_id'] ? $_POST['parent_id'] : null;
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        
        // Verificar se a categoria pai existe, caso esteja criando uma subcategoria
        if($parent_id) {
            $check_parent = $db->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
            $check_parent->execute([$parent_id]);
            if($check_parent->fetchColumn() == 0) {
                throw new Exception("A categoria pai selecionada não existe.");
            }
        }
        
        // Upload de imagem se fornecida
        $image_url = null;
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
            $upload_dir = '../media/categorias/';
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Gerar nome único para o arquivo baseado no nome da categoria
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $base_filename = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $name));
            $new_filename = $base_filename . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = './media/categorias/' . $new_filename;
            } else {
                throw new Exception("Falha ao fazer upload da imagem.");
            }
        }
        
        // Inserir a categoria
        try {
            $db->beginTransaction();
            
            $query = "INSERT INTO categories (name, description, image_url, parent_id, is_active) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([$name, $description, $image_url, $parent_id, $is_active])) {
                $category_id = $db->lastInsertId();
                
                // Se for uma categoria animal (parent_id = 2), criar algumas relações padrão
                if($parent_id == 2) {
                    // Verificar se a tabela category_relations existe
                    $check_table = $db->query("SHOW TABLES LIKE 'category_relations'");
                    if($check_table->rowCount() > 0) {
                        // Relacionar com alguns tipos de produtos populares (exemplo: Alimentos)
                        $get_food = $db->prepare("SELECT id FROM categories WHERE parent_id = 1 AND name LIKE '%Alimento%' LIMIT 1");
                        $get_food->execute();
                        $food_id = $get_food->fetchColumn();
                        
                        if($food_id) {
                            $insert_relation = $db->prepare("INSERT INTO category_relations (animal_category_id, product_type_id) VALUES (?, ?)");
                            $insert_relation->execute([$category_id, $food_id]);
                        }
                    }
                }
                
                $db->commit();
                $message = "Categoria <strong>{$name}</strong> adicionada com sucesso!";
                $message_type = "success";
            } else {
                throw new Exception("Erro ao adicionar a categoria.");
            }
        } catch(Exception $e) {
            $db->rollBack();
            throw $e;
        }
    } catch(Exception $e) {
        $message = $e->getMessage();
        $message_type = "error";
    }
}

// Processar edição de categoria
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') {
    try {
        if(!isset($_POST['category_id']) || empty($_POST['name'])) {
            throw new Exception("ID da categoria e nome são obrigatórios.");
        }
        
        $category_id = $_POST['category_id'];
        $name = trim($_POST['name']);
        
        // Obter valores existentes da categoria para manter inalterados
        $get_category = $db->prepare("SELECT description, parent_id, is_active FROM categories WHERE id = ?");
        $get_category->execute([$category_id]);
        $existing_category = $get_category->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing_category) {
            throw new Exception("Categoria não encontrada.");
        }
        
        // Manter os valores existentes dos outros campos
        $description = $existing_category['description'];
        $parent_id = $existing_category['parent_id'];
        $is_active = $existing_category['is_active'];
        
        // Registrar no log se uma categoria principal do sistema está sendo modificada
        if($category_id <= 3) {
            error_log("Administrador modificou o nome da categoria principal do sistema de ID: " . $category_id);
        }
        
        // Verificar se o nome já existe para evitar duplicações
        $check_name = $db->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND parent_id = ? AND id != ?");
        $check_name->execute([$name, $parent_id, $category_id]);
        if($check_name->fetchColumn() > 0) {
            throw new Exception("Já existe uma categoria com este nome neste nível.");
        }
        
        // Atualizar apenas o nome da categoria
        $query = "UPDATE categories SET name = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $params = [$name, $category_id];
        
        if($stmt->execute($params)) {
            $message = "Categoria atualizada com sucesso!";
            $message_type = "success";
        } else {
            throw new Exception("Erro ao atualizar a categoria.");
        }
    } catch(Exception $e) {
        $message = $e->getMessage();
        $message_type = "error";
    }
}

// Processar exclusão de categoria via POST (nova implementação)
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['category_id']) && is_numeric($_POST['category_id'])) {
    $category_id = $_POST['category_id'];
    
    try {
        $db->beginTransaction();
        
        // Verificar se a categoria tem subcategorias (apenas para categorias principais)
        $check_parent = $db->prepare("SELECT parent_id FROM categories WHERE id = ?");
        $check_parent->execute([$category_id]);
        $parent_id = $check_parent->fetchColumn();
        
        // Só verifica subcategorias se for uma categoria principal
        if($parent_id === null) {
            $check_subcategories = $db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
            $check_subcategories->execute([$category_id]);
            $has_subcategories = ($check_subcategories->fetchColumn() > 0);
            
            if($has_subcategories) {
                throw new Exception("Não é possível excluir a categoria principal pois ela possui subcategorias.");
            }
            
            // Para categorias principais, verificar se há produtos associados e impedir a exclusão se houver
            $check_direct_products = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $check_direct_products->execute([$category_id]);
            $has_direct_products = ($check_direct_products->fetchColumn() > 0);
            
            if($has_direct_products) {
                throw new Exception("Não é possível excluir a categoria principal pois existem produtos associados a ela. Remova ou realoque os produtos primeiro.");
            }
            
            // Verificar associações secundárias na tabela product_categories
            $check_secondary_products = $db->prepare("SELECT COUNT(*) FROM product_categories WHERE category_id = ?");
            $check_secondary_products->execute([$category_id]);
            $has_secondary_products = ($check_secondary_products->fetchColumn() > 0);
            
            if($has_secondary_products) {
                throw new Exception("Não é possível excluir a categoria principal pois existem produtos com associações secundárias a ela. Remova estas associações primeiro.");
            }
        } else {
            // Para subcategorias, verificar produtos associados
            $check_direct_products = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $check_direct_products->execute([$category_id]);
            $has_direct_products = ($check_direct_products->fetchColumn() > 0);
            
            // Para subcategorias, permitimos realocar produtos automaticamente
            if($has_direct_products) {
                // Verificar se temos uma categoria padrão para realocar os produtos
                $default_category = null;
                
                // Para subcategorias, usar outra subcategoria da mesma categoria pai
                $get_alt_category = $db->prepare("SELECT id FROM categories WHERE parent_id = ? AND id != ? LIMIT 1");
                $get_alt_category->execute([$parent_id, $category_id]);
                $default_category = $get_alt_category->fetchColumn();
                
                if(!$default_category) {
                    // Se não houver outra subcategoria, usar a categoria pai
                    $default_category = $parent_id;
                }
                
                // Atualizar os produtos para a categoria alternativa
                $update_products = $db->prepare("UPDATE products SET category_id = ? WHERE category_id = ?");
                $update_products->execute([$default_category, $category_id]);
                $moved_count = $update_products->rowCount();
                error_log("Movidos $moved_count produtos da categoria ID: $category_id para categoria ID: $default_category");
            }
        }
        
        // Remover associações na tabela product_categories (apenas para subcategorias)
        if ($parent_id !== null) {
            $check_product_categories = $db->prepare("SELECT COUNT(*) FROM product_categories WHERE category_id = ?");
            $check_product_categories->execute([$category_id]);
            $has_product_categories = ($check_product_categories->fetchColumn() > 0);
            
            if($has_product_categories) {
                // Remover associações de produtos com esta categoria
                $delete_assoc = $db->prepare("DELETE FROM product_categories WHERE category_id = ?");
                $delete_assoc->execute([$category_id]);
                $count = $delete_assoc->rowCount();
                error_log("Removidas $count associações de produtos com a categoria ID: $category_id na tabela product_categories");
            }
        }
        
        // 3. Remover relações de categorias se existirem
        // Verificar primeiro se a tabela category_relations existe
        $check_table = $db->query("SHOW TABLES LIKE 'category_relations'");
        if ($check_table->rowCount() > 0) {
            try {
                $delete_relations = $db->prepare("DELETE FROM category_relations WHERE animal_category_id = ? OR product_type_id = ?");
                $delete_relations->execute([$category_id, $category_id]);
                $rel_count = $delete_relations->rowCount();
                if($rel_count > 0) {
                    error_log("Removidas $rel_count relações de categorias associadas à categoria ID: $category_id");
                }
            } catch (PDOException $e) {
                // Apenas logar o erro, não impedir a exclusão da categoria
                error_log("Erro ao excluir relações de categoria: " . $e->getMessage());
            }
        }
        
        // Mostrar alerta especial se for uma categoria do sistema (1, 2 ou 3)
        if($category_id <= 3) {
            // Apenas registrar a ação para fins de log e adicionar aviso
            error_log("Administrador tentou remover a categoria do sistema de ID: " . $category_id);
            throw new Exception("Não é possível excluir categorias do sistema (IDs 1, 2 e 3) pois são fundamentais para o funcionamento da loja.");
        }
        
        // Verificação adicional para categorias que são utilizadas como destino de realocação
        if($parent_id !== null && isset($default_category) && $category_id == $default_category) {
            error_log("Tentativa de excluir a categoria ID: $category_id que seria usada como destino de realocação");
            throw new Exception("Esta categoria não pode ser excluída porque é usada como destino de realocação para outras categorias.");
        }
        
        // Tentar excluir a categoria com tratamento robusto de erros
        try {
            $query = "DELETE FROM categories WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$category_id]);
            
            if($result && $stmt->rowCount() > 0) {
                $db->commit();
                
                // Personalizar a mensagem com detalhes sobre a realocação
                if ($parent_id === null) {
                    $message = "Categoria principal excluída com sucesso!";
                } else {
                    if (isset($has_direct_products) && $has_direct_products) {
                        // Buscar o nome da categoria de destino para exibir na mensagem
                        $get_dest_name = $db->prepare("SELECT name FROM categories WHERE id = ?");
                        $get_dest_name->execute([$default_category]);
                        $dest_name = $get_dest_name->fetchColumn();
                        
                        $message = "Subcategoria excluída com sucesso! {$moved_count} produtos foram realocados para \"{$dest_name}\".";
                    } else {
                        $message = "Subcategoria excluída com sucesso!";
                    }
                    
                    if (isset($has_product_categories) && $has_product_categories) {
                        $message .= " Foram removidas {$count} associações de produtos com esta categoria.";
                    }
                }
                
                $message_type = "success";
                
                // Redirecionar após exclusão bem-sucedida para limpar parâmetros e evitar mensagens duplicadas
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                // Se não houve erro mas também nenhuma linha foi afetada
                throw new Exception("Erro ao excluir a categoria. A operação não afetou nenhum registro.");
            }
        } catch (PDOException $pe) {
            // Capturar erro específico de banco de dados
            throw new Exception("Erro de banco de dados ao excluir categoria: " . $pe->getMessage());
        }
    } catch(Exception $e) {
        if($db->inTransaction()) {
            $db->rollBack();
        }
        
        // Verificar se é um erro de integridade referencial
        if($e instanceof PDOException && $e->getCode() == '23000') {
            // Extrair informações do erro
            preg_match('/FOREIGN KEY \(`([^`]+)`\) REFERENCES `([^`]+)`/', $e->getMessage(), $matches);
            $message = "Não foi possível excluir esta categoria pois ela ainda está sendo referenciada na tabela de produtos.";
            if (count($matches) >= 3) {
                $message .= " Campo: {$matches[1]}, Tabela: {$matches[2]}.";
                $message .= " Por favor, remova ou realoque os produtos associados antes de excluir a categoria.";
            }
        } else {
            $message = "Erro ao excluir a categoria: " . $e->getMessage();
        }
        
        $message_type = "error";
        // Registrar o erro completo no log do sistema
        error_log("Erro ao excluir categoria ID: $category_id - " . $e->getMessage());
        
        // Salvar mensagem na sessão e redirecionar para limpar parâmetros
        $_SESSION['temp_message'] = $message;
        $_SESSION['temp_message_type'] = $message_type;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Limpar mensagem de erro caso não seja uma operação POST com action=delete
// Isso evita que o erro apareça na atualização da página
if(!($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete')) {
    $message = "";
    $message_type = "";
}

// Buscar todas as categorias
$categories = [];
if ($db) {
    try {
        $query = "SELECT c.*, p.name as parent_name, 
                (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as subcategory_count,
                (SELECT COUNT(*) FROM product_categories WHERE category_id = c.id) as product_count
                FROM categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                ORDER BY COALESCE(c.parent_id, c.id), c.name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Registrar erro e definir mensagem
        error_log("Erro na consulta: " . $e->getMessage());
        $message = "Erro ao carregar categorias: " . $e->getMessage();
        $message_type = "error";
    }
} else {
    // Se $db for nulo, exibir uma mensagem de erro
    $message = "Não foi possível conectar ao banco de dados. Verifique se o serviço MySQL está ativo no XAMPP.";
    $message_type = "error";
}

// Organizar categorias para apresentação em árvore
$categoryTree = [];
$mainCategories = [];

// Primeiro, separar todas as categorias principais
foreach($categories as $category) {
    if($category['parent_id'] === null) {
        $mainCategories[$category['id']] = $category;
        $mainCategories[$category['id']]['children'] = [];
    }
}

// Adicionar as subcategorias
foreach($categories as $category) {
    if($category['parent_id'] !== null && isset($mainCategories[$category['parent_id']])) {
        $mainCategories[$category['parent_id']]['children'][] = $category;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Categorias - Pet & Repet</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
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
    
    <!-- Minimalist Category Styles -->
    <link rel="stylesheet" type="text/css" href="css/minimalist-categories.css">
    
    <!-- Custom scripts -->
    <script type="text/javascript" src="js/toast-manager.js"></script>
    <script type="text/javascript" src="js/prevent-auto-toasts.js"></script>
    <script type="text/javascript" src="js/content-scroll-manager.js"></script>
    <script type="text/javascript" src="js/categories-script.js"></script>
    
    <!-- Sidebar CSS -->
    <link rel="stylesheet" type="text/css" href="css/sidebar.css">
    
    <meta name="description" content="Gestão de Categorias do Pet & Repet">
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
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .content-wrapper {
            padding: 28px;
            margin-left: 280px; /* Ajustado para a largura exata da sidebar (280px) */
            transition: all 0.3s;
            min-height: 100vh;
            width: calc(100% - 280px); /* Garante que ocupe todo o espaço restante */
            box-sizing: border-box; /* Garante que o padding não aumente a largura */
            position: relative; /* Contexto para posicionamento absoluto */
            flex-grow: 1; /* Para garantir que cresça e ocupe todo espaço disponível */
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
                padding: 20px;
                width: 100%; /* Em telas menores, ocupa 100% da largura */
            }
        }
        
        /* Garantir que o container-fluid utilize toda a largura disponível */
        .container-fluid {
            width: 100%;
            max-width: none;
            padding-left: 0;
            padding-right: 0;
        }
        
        /* Ajustes adicionais para manter espaço apenas no conteúdo interno */
        .card {
            margin-left: 0;
            margin-right: 0;
        }
        
        /* Design moderno para o cabeçalho da página */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #8c52ff, #5e17eb);
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(140, 82, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(-15deg);
            pointer-events: none;
        }
        
        .page-header h2 {
            font-weight: 800;
            font-size: 1.8rem;
            color: #ffffff;
            margin: 0;
            display: flex;
            align-items: center;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .page-header h2 i {
            margin-right: 15px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        
        /* Estilizar o botão de nova categoria */
        .btn-primary {
            background-color: #ffffff;
            color: #5e17eb;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background-color: #f0f0f0;
            color: #5e17eb;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(94, 23, 235, 0.2);
        }
        
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 4px 8px rgba(94, 23, 235, 0.2);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background-color: #0d47a1;
            border-color: #0d47a1;
            box-shadow: 0 6px 12px rgba(13, 71, 161, 0.2);
            transform: translateY(-2px);
        }
        
        /* Melhorar estilo do card principal */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #f0f0f0;
            padding: 16px 20px;
        }
        
        .card-header h5 {
            font-weight: 600;
            font-size: 1.15rem;
            margin: 0;
            color: #333;
        }
        
        .card-body {
            padding: 0;
        }
        
        /* Melhorar estilo da tabela */
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: #f9f9f9;
            color: #666;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 20px;
            border-top: none;
            border-bottom: 1px solid #eee;
        }
        
        .table tbody td {
            padding: 15px 20px;
            vertical-align: middle;
            border-color: #f5f5f5;
            font-size: 0.95rem;
        }
        
        .table tbody tr:hover {
            background-color: rgba(30, 136, 229, 0.03);
        }
        
        /* Estilizar categorias filhas */
        .category-child {
            background-color: rgba(30, 136, 229, 0.04);
            border-left: 4px solid #1e88e5;
        }
        
        .category-child td:first-child {
            padding-left: 30px;
        }
        
        /* Melhorar estilo para o indicador de status */
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }
        
        .status-active {
            background-color: #2ecc71;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
        }
        
        .status-inactive {
            background-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2);
        }
        
        /* CSS para imagem removido */
        
        /* Estilizar botões de ações na tabela */
        .btn-action {
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin: 0 3px;
            transition: all 0.2s;
        }
        
        .btn-edit {
            background-color: rgba(30, 136, 229, 0.1);
            color: #1e88e5;
            border: none;
        }
        
        .btn-edit:hover {
            background-color: rgba(30, 136, 229, 0.2);
            color: #1565c0;
        }
        
        .btn-delete {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: none;
        }
        
        .btn-delete:hover {
            background-color: rgba(231, 76, 60, 0.2);
            color: #c0392b;
        }
        
        /* Estilo para botões de toggle status */
        .btn-toggle-status.btn-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            border: none;
        }
        
        .btn-toggle-status.btn-success:hover {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
        }
        
        .btn-toggle-status.btn-warning {
            background-color: rgba(241, 196, 15, 0.1);
            color: #f39c12;
            border: none;
        }
        
        .btn-toggle-status.btn-warning:hover {
            background-color: rgba(241, 196, 15, 0.2);
            color: #d35400;
        }
        
        /* Estilizar alerta de informações */
        .alert-info {
            background-color: rgba(30, 136, 229, 0.1);
            border: 1px solid rgba(30, 136, 229, 0.2);
            color: #0d47a1;
            border-radius: 10px;
            padding: 15px 20px;
            font-size: 0.9rem;
        }
        
        /* Melhorar estilo do modal */
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .modal-header {
            background-color: #f9f9f9;
            border-bottom: 1px solid #f0f0f0;
            padding: 20px 25px;
        }
        
        .modal-header .modal-title {
            font-weight: 700;
            color: #333;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            border-top: 1px solid #f0f0f0;
            padding: 20px 25px;
        }
        
        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #1e88e5;
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.15);
        }
        
        .form-text {
            color: #888;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        /* Estilizar badges para contadores */
        .category-badge {
            background: rgba(30, 136, 229, 0.1);
            color: #1e88e5;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-block;
        }
    </style>
</head>
<body>
    <script>
        // Definindo uma variável no contexto global para guardar mensagens PHP importantes
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
        
        <!-- Page Content -->
        <div class="content-wrapper">
            <div class="container-fluid px-0"> <!-- Removendo padding horizontal -->
                <!-- Page Header -->
                <div class="page-header">
                    <h2><i class="fas fa-tags"></i> Gestão de Categorias</h2>
                    <button class="btn btn-primary" id="btnAddCategory">
                        <i class="fas fa-plus me-2"></i> Nova Categoria
                    </button>
                </div>
                
                <!-- PHP messages will now use our Toastify notifications -->
                <?php if (!empty($message)): ?>
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
                
                <!-- Categories Table -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <h5 class="mb-0 d-flex align-items-center">
                                <span class="icon-circle me-2">
                                    <i class="fas fa-list-ul"></i>
                                </span>
                                Todas as Categorias
                            </h5>
                            <div class="d-flex align-items-center mt-3 mt-md-0">
                                <div class="input-group me-2" style="width: 250px;">
                                    <input type="text" class="form-control" id="searchCategories" placeholder="Buscar categorias...">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-filter me-1"></i> Filtrar
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="filterDropdown">
                                        <li><a class="dropdown-item" href="#" data-filter="all">Todas as Categorias</a></li>
                                        <li><a class="dropdown-item" href="#" data-filter="main">Categorias Principais</a></li>
                                        <li><a class="dropdown-item" href="#" data-filter="sub">Subcategorias</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" data-filter="active">Categorias Ativas</a></li>
                                        <li><a class="dropdown-item" href="#" data-filter="inactive">Categorias Inativas</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table" id="categoryTable">
                                    <thead>
                                        <tr>
                                            <th>Categoria</th>
                                            <th class="text-center">Contagem</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded via AJAX -->
                                        <tr>
                                            <td colspan="6" class="text-center py-5 loading-state">
                                                <div class="loading-pulse">
                                                    <div class="spinner-grow me-2" role="status" style="width: 12px; height: 12px;"></div>
                                                    <div class="spinner-grow me-2" role="status" style="width: 12px; height: 12px; animation-delay: 0.2s;"></div>
                                                    <div class="spinner-grow" role="status" style="width: 12px; height: 12px; animation-delay: 0.4s;"></div>
                                                </div>
                                                <p class="mt-3 mb-0 fw-medium" style="color: #8c52ff;">Carregando categorias...</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                        <div class="total-counter">
                            <span class="badge rounded-pill">Total: <strong id="totalCategories" class="ms-1">0</strong></span>
                        </div>
                        <div>
                            <nav aria-label="Navegação de página" class="d-inline-block">
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item disabled"><a class="page-link" href="#">Anterior</a></li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item"><a class="page-link" href="#">Próximo</a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats Row -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="stat-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-folder text-primary"></i>
                                </div>
                                <div>
                                    <div class="small text-muted">Principais</div>
                                    <h5 class="mb-0 fw-bold" id="mainCategoryCount">
                                        <?php 
                                            $mainCategoryCount = 0;
                                            foreach($categories as $category) {
                                                if($category['parent_id'] === null) $mainCategoryCount++;
                                            }
                                            echo $mainCategoryCount;
                                        ?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="stat-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-sitemap text-success"></i>
                                </div>
                                <div>
                                    <div class="small text-muted">Subcategorias</div>
                                    <h5 class="mb-0 fw-bold" id="subCategoryCount">
                                        <?php 
                                            $subCategoryCount = 0;
                                            foreach($categories as $category) {
                                                if($category['parent_id'] !== null) $subCategoryCount++;
                                            }
                                            echo $subCategoryCount;
                                        ?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="stat-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-tags text-info"></i>
                                </div>
                                <div>
                                    <div class="small text-muted">Total</div>
                                    <h5 class="mb-0 fw-bold" id="totalCategoriesCount">
                                        <?php echo count($categories); ?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Categories Note - Minimalist version -->
                <div class="alert alert-info alert-dismissible fade show p-2">
                    <div class="d-flex align-items-center">
                        <div class="info-icon me-3">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Dica</h6>
                            <p class="mb-0 small">Crie categorias principais (Tipo, Animal, Marca) e depois adicione subcategorias para melhor organização.</p>
                        </div>
                        <button type="button" class="btn-close ms-auto btn-sm" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Category Modal -->
        <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">
                            <i class="fas fa-plus-circle me-2 text-primary"></i>
                            <span id="modalActionText">Adicionar Categoria</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="categoryForm" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" id="category_id" name="category_id">
                            
                            <!-- Tabs para organizar o formulário -->
                            <ul class="nav nav-tabs mb-3" id="categoryModalTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab" aria-controls="basic-info" aria-selected="true">Básico</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced-info" type="button" role="tab" aria-controls="advanced-info" aria-selected="false">Avançado</button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="categoryModalTabContent">
                                <!-- Informações Básicas -->
                                <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-tab">
                                    <div class="mb-3">
                                        <label for="category_name" class="form-label">Nome <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                            <input type="text" class="form-control" id="category_name" name="name" placeholder="Nome da categoria" required>
                                        </div>
                                        <div class="form-text">Escolha um nome claro e descritivo.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="category_description" class="form-label">Descrição</label>
                                        <textarea class="form-control" id="category_description" name="description" rows="3" placeholder="Descrição detalhada da categoria..."></textarea>
                                        <div class="form-text">Uma boa descrição ajuda na organização e SEO da loja.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="parent_category" class="form-label">Categoria Principal</label>
                                        <select class="form-select" id="parent_category" name="parent_id">
                                            <option value="">Nenhuma (Categoria Principal)</option>
                                            <?php
                                            // Obter todas as categorias possíveis como pais
                                            $allCategories = $categoryManager->getAllCategoriesWithDetails();
                                            foreach ($allCategories as $cat) {
                                                if ($cat['parent_id'] === null) { // Mostrar todas as categorias principais como possíveis pais
                                                    echo '<option value="' . $cat['id'] . '">' . $cat['name'] . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                        <div class="form-text">Deixe em branco para criar uma categoria principal ou selecione uma categoria pai.</div>
                                    </div>
                                </div>
                                
                                <!-- Configurações Avançadas -->
                                <div class="tab-pane fade" id="advanced-info" role="tabpanel" aria-labelledby="advanced-tab">
                                    <div class="mb-4 mt-2">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Informações avançadas:</strong> Após criar a categoria, você pode ativar ou desativar usando o botão na tabela de categorias.
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-light border mb-0">
                                        <div class="d-flex">
                                            <div class="me-2">
                                                <i class="fas fa-info-circle text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1">Dica de Organização</h6>
                                                <p class="mb-0 small">Mantenha suas categorias organizadas para facilitar a navegação dos clientes e a gestão de produtos.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary" id="btnSaveCategory">
                                <i class="fas fa-save me-1"></i> Salvar Categoria
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteModalLabel">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Confirmar Exclusão
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <div class="display-1 text-danger mb-4">
                                <i class="fas fa-trash-alt"></i>
                            </div>
                            
                            <h4 id="deleteConfirmTitle">Tem certeza que deseja excluir esta categoria?</h4>
                            <p id="deleteConfirmMessage" class="text-muted"></p>
                            
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <span id="deleteWarningMessage">Esta ação não pode ser desfeita.</span>
                            </div>
                        </div>
                        
                        <input type="hidden" id="categoryToDeleteId" value="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> Confirmar Exclusão
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Category Modal Script -->
    <script src="js/category-modal.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check for PHP messages
            if (window.phpMessages && window.phpMessages.message) {
                showGlobalToast(window.phpMessages.message, window.phpMessages.type || 'info');
                // Limpar as mensagens após exibi-las para evitar que apareçam novamente no refresh
                window.phpMessages.message = null;
                window.phpMessages.type = null;
            }
            
            // Mobile layout adjustments
            function adjustLayout() {
                try {
                    const windowWidth = window.innerWidth;
                    const sidebar = document.querySelector('.sidebar');
                    const contentWrapper = document.querySelector('.content-wrapper');
                    
                    if (!sidebar || !contentWrapper) return;
                    
                    if (windowWidth <= 768) {
                        sidebar.classList.add('collapsed');
                        contentWrapper.style.marginLeft = '0';
                    } else {
                        sidebar.classList.remove('collapsed');
                        contentWrapper.style.marginLeft = '250px';
                    }
                } catch (err) {
                    console.error('Erro ao ajustar layout:', err);
                }
            }
            
            // Execute on page load and resize
            window.addEventListener('load', adjustLayout);
            window.addEventListener('resize', adjustLayout);
            
            // Inicializar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Funcionalidade para o campo de busca
            const searchInput = document.getElementById('searchCategories');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#categoryTable tbody tr');
                    
                    rows.forEach(row => {
                        if (row.innerHTML.toLowerCase().includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    // Atualizar contagem visível
                    updateVisibleCount();
                });
            }
            
            // Funcionalidade para filtros do dropdown
            const filterLinks = document.querySelectorAll('[data-filter]');
            filterLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const filter = this.getAttribute('data-filter');
                    const rows = document.querySelectorAll('#categoryTable tbody tr');
                    
                    rows.forEach(row => {
                        // Reset search first
                        if (searchInput) searchInput.value = '';
                        
                        switch(filter) {
                            case 'all':
                                row.style.display = '';
                                break;
                            case 'main':
                                if (!row.classList.contains('category-child')) {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                                break;
                            case 'sub':
                                if (row.classList.contains('category-child')) {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                                break;
                            case 'active':
                                if (row.querySelector('.status-active')) {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                                break;
                            case 'inactive':
                                if (row.querySelector('.status-inactive')) {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                                break;
                        }
                    });
                    
                    // Atualizar contagem visível
                    updateVisibleCount();
                    
                    // Atualizar texto do botão dropdown
                    document.getElementById('filterDropdown').innerHTML = `
                        <i class="fas fa-filter me-1"></i> Filtro: ${this.innerText}
                    `;
                });
            });
            
            // Função para atualizar a contagem de categorias visíveis
            function updateVisibleCount() {
                const visibleRows = document.querySelectorAll('#categoryTable tbody tr[style=""]').length;
                document.getElementById('totalCategories').textContent = visibleRows;
            }
            
            // Código de preview de imagem removido
            
            // Toggles de status no formulário
            // Código para alternar status removido - agora usamos o botão de toggle na tabela
            
            // Atualizar título do modal baseado na ação
            const categoryModal = document.getElementById('categoryModal');
            if (categoryModal) {
                categoryModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const actionType = button?.getAttribute('data-action') || 'add';
                    const modalTitle = document.getElementById('modalActionText');
                    const categoryIdField = document.getElementById('category_id');
                    
                    if (actionType === 'edit') {
                        modalTitle.textContent = 'Editar Categoria';
                        // Aqui você pode carregar os dados da categoria para edição
                    } else {
                        modalTitle.textContent = 'Adicionar Categoria';
                        
                        // Reset form
                        document.getElementById('categoryForm').reset();
                        if (categoryIdField) categoryIdField.value = '';
                        // Código de reset de imagem removido
                        
                        // Reset status toggle
                        if (statusToggle) statusToggle.checked = true;
                        if (statusActiveText && statusInactiveText) {
                            statusActiveText.classList.remove('d-none');
                            statusInactiveText.classList.add('d-none');
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
