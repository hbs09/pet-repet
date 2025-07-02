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
        $description = trim($_POST['description'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
        
        // Obter categoria existente para verificar se existe
        $get_category = $db->prepare("SELECT * FROM categories WHERE id = ?");
        $get_category->execute([$category_id]);
        $existing_category = $get_category->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing_category) {
            throw new Exception("Categoria não encontrada.");
        }
        
        // Registrar no log se uma categoria principal do sistema está sendo modificada
        if($category_id <= 3) {
            error_log("Administrador modificou a categoria principal do sistema de ID: " . $category_id);
        }
        
        // Verificar se o nome já existe para evitar duplicações (no mesmo nível)
        $check_name = $db->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND parent_id " . ($parent_id ? "= ?" : "IS NULL") . " AND id != ?");
        $params_check = $parent_id ? [$name, $parent_id, $category_id] : [$name, $category_id];
        $check_name->execute($params_check);
        if($check_name->fetchColumn() > 0) {
            throw new Exception("Já existe uma categoria com este nome neste nível.");
        }
        
        // Verificar se a categoria pai existe, caso esteja definindo uma
        if($parent_id) {
            $check_parent = $db->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
            $check_parent->execute([$parent_id]);
            if($check_parent->fetchColumn() == 0) {
                throw new Exception("A categoria pai selecionada não existe.");
            }
            
            // Verificar se não está tentando definir a própria categoria como pai
            if($parent_id == $category_id) {
                throw new Exception("Uma categoria não pode ser pai de si mesma.");
            }
            
            // Verificar se não está criando um ciclo (categoria pai não pode ser filha da atual)
            $check_cycle = $db->prepare("SELECT parent_id FROM categories WHERE id = ?");
            $check_cycle->execute([$parent_id]);
            $parent_of_parent = $check_cycle->fetchColumn();
            if($parent_of_parent == $category_id) {
                throw new Exception("Não é possível criar dependência circular entre categorias.");
            }
        }
        
        // Se estava como categoria principal e agora vai ter pai, verificar se tem subcategorias
        if($existing_category['parent_id'] === null && $parent_id !== null) {
            $check_subcategories = $db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
            $check_subcategories->execute([$category_id]);
            if($check_subcategories->fetchColumn() > 0) {
                throw new Exception("Esta categoria possui subcategorias e não pode ser transformada em subcategoria. Mova ou exclua as subcategorias primeiro.");
            }
        }
        
        // Atualizar todos os campos da categoria
        $query = "UPDATE categories SET name = ?, description = ?, parent_id = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $params = [$name, $description, $parent_id, $category_id];
        
        if($stmt->execute($params)) {
            // Determinar o tipo de alteração para a mensagem
            $changes = [];
            if($existing_category['name'] !== $name) $changes[] = "nome";
            if($existing_category['description'] !== $description) $changes[] = "descrição";
            if($existing_category['parent_id'] !== $parent_id) {
                if($parent_id === null) {
                    $changes[] = "transformada em categoria principal";
                } elseif($existing_category['parent_id'] === null) {
                    $changes[] = "transformada em subcategoria";
                } else {
                    $changes[] = "categoria pai alterada";
                }
            }
            
            $change_text = !empty($changes) ? " (" . implode(", ", $changes) . ")" : "";
            $message = "Categoria <strong>{$name}</strong> atualizada com sucesso!" . $change_text;
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
    
    <!-- Custom scripts -->
    <script type="text/javascript" src="js/toast-manager.js"></script>
    <script type="text/javascript" src="js/prevent-auto-toasts.js"></script>
    <script type="text/javascript" src="js/content-scroll-manager.js"></script>
    <script type="text/javascript" src="js/categories-script.js"></script>

    
    <script type="text/javascript" src="js/categories-table-loader.js"></script>
    
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
        
        /* Reset básico e configurações globais */
        * {
            box-sizing: border-box;
        }
        
        html {
            height: 100%;
            overflow-x: hidden;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #2c3e50;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .dashboard-container {
            height: 100vh;
            overflow: hidden;
            position: relative;
        }
        
        /* Layout principal */
        .content-wrapper {
            padding: 32px;
            margin-left: 300px; /* Aumentado de 280px para dar mais espaço */
            transition: all 0.3s ease;
            height: 100vh;
            width: calc(100% - 300px); /* Ajustado para corresponder à nova margem */
            box-sizing: border-box;
            position: relative;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 20px; /* Pequena margem em dispositivos móveis */
                padding: 20px;
                width: calc(100% - 20px);
                height: 100vh;
            }
        }
        
        .container-fluid {
            width: 100%;
            max-width: none;
            padding: 0;
            height: 100%;
            overflow: visible;
        }
        
        /* Header principal redesenhado */
        .page-header {
            background: linear-gradient(135deg, #1e88e5 0%, #0d47a1 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 20px 40px rgba(30, 136, 229, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="white" opacity="0.1"/><circle cx="80" cy="40" r="1" fill="white" opacity="0.1"/><circle cx="40" cy="80" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }
        
        .page-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 2;
        }
        
        .page-title h1 {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            letter-spacing: -0.5px;
        }
        
        .page-title h1 i {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            backdrop-filter: blur(10px);
            font-size: 1.5rem;
        }
        
        .page-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            margin-top: 8px;
            font-weight: 400;
        }
        
        /* Botão principal redesenhado */
        .btn-new-category {
            background: rgba(255, 255, 255, 0.95);
            color: #1e88e5;
            border: none;
            border-radius: 16px;
            padding: 16px 32px;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 8px 32px rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(10px);
        }
        
        .btn-new-category:hover {
            background: white;
            color: #0d47a1;
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(255, 255, 255, 0.4);
        }
        
        /* Cards principais */
        .main-card {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            backdrop-filter: blur(20px);
            margin-bottom: 32px;
            overflow: hidden;
        }
        
        .main-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.12);
        }
        
        /* Header dos cards */
        .card-header-custom {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: none;
            padding: 24px 32px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        }
        
        .card-title-custom {
            font-size: 1.25rem;
            font-weight: 600;
            color: #334155;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .card-title-custom i {
            background: linear-gradient(135deg, #1e88e5, #0d47a1);
            color: white;
            border-radius: 12px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            font-size: 1rem;
        }
        
        /* Barra de ferramentas */
        .toolbar {
            display: flex;
            gap: 16px;
            align-items: center;
            padding: 24px 32px 0;
            margin-bottom: 24px;
        }
        
        .search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .search-box input:focus {
            border-color: #1e88e5;
            box-shadow: 0 0 0 4px rgba(30, 136, 229, 0.1);
            outline: none;
        }
        
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
        }
        
        .filter-dropdown {
            position: relative;
        }
        
        .btn-filter {
            background: white;
            border: 2px solid #e2e8f0;
            color: #64748b;
            border-radius: 16px;
            padding: 12px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-filter:hover, .btn-filter:focus {
            border-color: #1e88e5;
            color: #1e88e5;
            background: rgba(30, 136, 229, 0.05);
        }
        
        /* Tabela redesenhada */
        .table-container {
            padding: 0 32px 32px;
            overflow-x: auto;
            overflow-y: auto;
            max-height: calc(100vh - 450px);
            position: relative;
        }
        
        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: auto;
            min-width: 100%;
        }
        
        .custom-table thead th {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #475569;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 20px 24px;
            border: none;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }
        
        .custom-table thead th:first-child {
            border-radius: 16px 0 0 0;
        }
        
        .custom-table thead th:last-child {
            border-radius: 0 16px 0 0;
        }
        
        .custom-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .custom-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.002);
        }
        
        .custom-table tbody td {
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            font-size: 0.95rem;
        }
        
        /* Indicadores de status */
        .status-badge {
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-active {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }
        
        /* Botões de ação */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        
        .btn-action {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
        }
        
        .btn-edit:hover {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
        }
        
        .btn-delete:hover {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }
        
        .btn-toggle {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            color: #16a34a;
        }
        
        .btn-toggle:hover {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
        }
        
        /* Subcategorias */
        .subcategory-row {
            background: linear-gradient(135deg, rgba(30, 136, 229, 0.02) 0%, rgba(13, 71, 161, 0.02) 100%);
            border-left: 4px solid #1e88e5;
        }
        
        .subcategory-name {
            padding-left: 40px;
            position: relative;
        }
        
        .subcategory-name::before {
            content: '└';
            position: absolute;
            left: 24px;
            color: #1e88e5;
            font-weight: bold;
        }
        
        /* Cards de estatísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 252, 0.9) 100%);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.primary {
            background: linear-gradient(135deg, #1e88e5, #0d47a1);
        }
        
        .stat-icon.success {
            background: linear-gradient(135deg, #4ade80, #22c55e);
        }
        
        .stat-icon.info {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        /* Modal redesenhado */
        .modal-content {
            border: none;
            border-radius: 24px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(20px);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
            padding: 24px 32px;
            border-radius: 24px 24px 0 0;
        }
        
        .modal-header .modal-title {
            font-weight: 600;
            color: #334155;
            font-size: 1.25rem;
        }
        
        .modal-body {
            padding: 32px;
        }
        
        .modal-footer {
            border-top: 1px solid rgba(226, 232, 240, 0.6);
            padding: 24px 32px;
            background: #f8fafc;
            border-radius: 0 0 24px 24px;
        }
        
        /* Formulários */
        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.925rem;
        }
        
        .form-control, .form-select {
            padding: 12px 16px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #1e88e5;
            box-shadow: 0 0 0 4px rgba(30, 136, 229, 0.1);
            outline: none;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group-text {
            background: #f3f4f6;
            border: 2px solid #e5e7eb;
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: #6b7280;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }
        
        /* Botões do formulário */
        .btn-primary {
            background: linear-gradient(135deg, #1e88e5, #0d47a1);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1976d2, #0d47a1);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 136, 229, 0.3);
        }
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-outline-secondary {
            border: 2px solid #e5e7eb;
            color: #6b7280;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
            color: #374151;
        }
        
        /* Alertas */
        .alert {
            border: none;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.1) 100%);
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        
        /* Loading states */
        .loading-state {
            padding: 60px 0;
            text-align: center;
        }
        
        .loading-pulse {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .spinner-grow {
            background: linear-gradient(135deg, #1e88e5, #0d47a1);
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            
            .page-title h1 {
                font-size: 2rem;
            }
            
            .toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            
            .search-box {
                max-width: none;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .custom-table {
                font-size: 0.875rem;
            }
            
            .custom-table thead th,
            .custom-table tbody td {
                padding: 12px 16px;
            }
            
            .table-container {
                padding: 0 16px 16px;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 992px) {
            .content-wrapper {
                margin-left: 240px;
                width: calc(100% - 240px);
            }
        }
        
        @media (max-width: 1200px) {
            .content-wrapper {
                margin-left: 280px;
                width: calc(100% - 280px);
            }
        }
        
        /* Ícones da tabela - cores específicas para o novo design */
        .custom-table .fas.fa-folder {
            color: #1e88e5 !important;
            margin-right: 8px;
        }
        
        .custom-table .fas.fa-level-down-alt {
            color: #64748b !important;
            transform: rotate(90deg);
            margin-right: 8px;
        }
        
        .custom-table .fas.fa-box {
            color: #1e88e5 !important;
        }
        
        .custom-table .fas.fa-sitemap {
            color: #0891b2 !important;
        }
        
        /* Forçar cores dos ícones nos action buttons */
        .btn-action .fas {
            color: inherit !important;
        }
        
        /* Garantir scroll correto */
        .dashboard-container {
            overflow: hidden;
        }
        
        .content-wrapper {
            scroll-behavior: smooth;
        }
        
        /* Evitar overflow horizontal */
        .row {
            margin-left: 0;
            margin-right: 0;
        }
        
        .col, .col-* {
            padding-left: 0;
            padding-right: 0;
        }
        
        /* Ajustes finais para mobile */
        @media (max-width: 576px) {
            .content-wrapper {
                padding: 16px;
            }
            
            .page-header {
                padding: 24px;
                margin-bottom: 24px;
            }
            
            .table-container {
                padding: 0 16px 16px;
                max-height: calc(100vh - 350px);
            }
            
            .main-card {
                margin-bottom: 20px;
            }
        }
        
        /* Estilos para o modal de edição de categoria */
        .modal-content.edit-mode .modal-header {
            background: linear-gradient(135deg, #e9f5fe, #d1edff);
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        .modal-content.edit-mode .modal-title i {
            color: #3b82f6;
        }
        
        .edit-category-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 12px;
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .modal-content.edit-mode #btnSaveCategory {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            font-weight: 500;
            padding: 8px 16px;
        }
        
        .modal-content.edit-mode #btnSaveCategory:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
        }
        
        .modal-content.edit-mode .form-label {
            color: #1e40af;
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
                    <div class="page-title">
                        <div>
                            <h1>
                                <i class="fas fa-layer-group"></i>
                                Gestão de Categorias
                            </h1>
                            <p class="page-subtitle">Organize e gerencie todas as categorias da sua loja</p>
                        </div>
                        <button class="btn-new-category" id="btnAddCategory">
                            <i class="fas fa-plus me-2"></i>
                            Nova Categoria
                        </button>
                    </div>
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
                
                <!-- Quick Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div class="stat-number" id="mainCategoryCount">
                            <?php 
                                $mainCategoryCount = 0;
                                foreach($categories as $category) {
                                    if($category['parent_id'] === null) $mainCategoryCount++;
                                }
                                echo $mainCategoryCount;
                            ?>
                        </div>
                        <div class="stat-label">Categorias Principais</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <div class="stat-number" id="subCategoryCount">
                            <?php 
                                $subCategoryCount = 0;
                                foreach($categories as $category) {
                                    if($category['parent_id'] !== null) $subCategoryCount++;
                                }
                                echo $subCategoryCount;
                            ?>
                        </div>
                        <div class="stat-label">Subcategorias</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon info">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-number" id="totalCategoriesCount">
                            <?php echo count($categories); ?>
                        </div>
                        <div class="stat-label">Total de Categorias</div>
                    </div>
                </div>

                <!-- Categories Table -->
                <div class="main-card">
                    <div class="card-header-custom">
                        <h5 class="card-title-custom">
                            <i class="fas fa-list-ul"></i>
                            Lista de Categorias
                        </h5>
                    </div>
                    
                    <!-- Toolbar -->
                    <div class="toolbar">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchCategories" placeholder="Buscar por nome da categoria...">
                        </div>
                        
                        <div class="filter-dropdown">
                            <button class="btn-filter dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-filter me-2"></i>
                                Todos os Filtros
                            </button>
                            <ul class="dropdown-menu shadow-lg border-0" aria-labelledby="filterDropdown">
                                <li><a class="dropdown-item" href="#" data-filter="all">
                                    <i class="fas fa-list me-2"></i>Todas as Categorias
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-filter="main">
                                    <i class="fas fa-folder me-2"></i>Categorias Principais
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-filter="sub">
                                    <i class="fas fa-sitemap me-2"></i>Subcategorias
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-filter="active">
                                    <i class="fas fa-check-circle me-2 text-success"></i>Categorias Ativas
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-filter="inactive">
                                    <i class="fas fa-times-circle me-2 text-danger"></i>Categorias Inativas
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Table Container -->
                    <div class="table-container">
                        <table class="custom-table" id="categoryTable">
                            <thead>
                                <tr>
                                    <th>
                                        <i class="fas fa-tag me-2"></i>
                                        Categoria
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        Produtos
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-toggle-on me-2"></i>
                                        Status
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-tools me-2"></i>
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loading state -->
                                <tr class="loading-state">
                                    <td colspan="4" class="text-center">
                                        <div class="loading-pulse">
                                            <div class="spinner-grow" style="width: 16px; height: 16px;"></div>
                                            <div class="spinner-grow" style="width: 16px; height: 16px; animation-delay: 0.2s;"></div>
                                            <div class="spinner-grow" style="width: 16px; height: 16px; animation-delay: 0.4s;"></div>
                                        </div>
                                        <p class="mt-3 mb-0 text-muted">Carregando categorias...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
               
        
        <!-- Category Modal -->
        <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">
                            <i class="fas fa-plus-circle me-2" style="color: #1e88e5;"></i>
                            <span id="modalActionText">Adicionar Nova Categoria</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="categoryForm" method="POST" action="categories.php" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" id="category_id" name="category_id">
                            
                            <!-- Informações Básicas -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="category_name" class="form-label">
                                            <i class="fas fa-tag me-2 text-primary"></i>
                                            Nome da Categoria <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-edit"></i>
                                            </span>
                                            <input type="text" class="form-control" id="category_name" name="name" 
                                                   placeholder="Ex: Alimentação, Brinquedos..." required>
                                        </div>
                                        <div class="form-text">Escolha um nome claro e descritivo para a categoria.</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="parent_category" class="form-label">
                                            <i class="fas fa-sitemap me-2 text-success"></i>
                                            Categoria Principal
                                        </label>
                                        <select class="form-select" id="parent_category" name="parent_id">
                                            <option value="">Nenhuma (Categoria Principal)</option>
                                            <?php
                                            // Obter todas as categorias possíveis como pais
                                            if (isset($categoryManager)) {
                                                $allCategories = $categoryManager->getAllCategoriesWithDetails();
                                                foreach ($allCategories as $cat) {
                                                    if ($cat['parent_id'] === null) {
                                                        echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['name']) . '</option>';
                                                    }
                                                }
                                            }
                                            ?>
                                        </select>
                                        <div class="form-text">Deixe em branco para criar uma categoria principal.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="category_description" class="form-label">
                                    <i class="fas fa-align-left me-2 text-info"></i>
                                    Descrição
                                </label>
                                <textarea class="form-control" id="category_description" name="description" rows="4" 
                                          placeholder="Descrição detalhada da categoria..."></textarea>
                                <div class="form-text">Uma boa descrição ajuda na organização e SEO da loja.</div>
                            </div>
                            
                            <!-- Informações de Status -->
                            <div class="alert alert-light border">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle text-primary me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Status da Categoria</h6>
                                        <p class="mb-0 small">Após criar a categoria, você pode ativar ou desativar usando o botão na tabela de categorias.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Container para informações específicas do modo de edição -->
                            <div id="editModeInfo" class="alert alert-info border-0" style="background: linear-gradient(135deg, #e9f5fe, #d1edff); display: none;">
                                <div class="d-flex">
                                    <i class="fas fa-lightbulb me-3 align-self-start mt-1" style="color: #3b82f6;"></i>
                                    <div>
                                        <h6 class="mb-1" style="color: #1e40af;">Modo de edição</h6>
                                        <p id="editModeMessage" class="mb-0 small">Atualizando informações da categoria.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary" id="btnSaveCategory">
                                <i class="fas fa-save me-2"></i>
                                <span id="btnSaveCategoryText">Salvar Categoria</span>
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
                    <div class="modal-header" style="background: linear-gradient(135deg, #fee2e2, #fecaca); border-bottom: 1px solid rgba(239, 68, 68, 0.2);">
                        <h5 class="modal-title" id="deleteModalLabel" style="color: #dc2626;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Confirmar Exclusão
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center py-5">
                        <div class="mb-4">
                            <div class="mx-auto" style="width: 80px; height: 80px; background: linear-gradient(135deg, #fee2e2, #fecaca); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-trash-alt text-danger" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        
                        <h4 id="deleteConfirmTitle" class="mb-3">Tem certeza que deseja excluir esta categoria?</h4>
                        <p id="deleteConfirmMessage" class="text-muted mb-4">Esta ação não pode ser desfeita.</p>
                        
                        <div class="alert alert-warning border-0" style="background: linear-gradient(135deg, #fef3c7, #fde68a);">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="deleteWarningMessage">Certifique-se de que não há produtos associados a esta categoria.</span>
                        </div>
                        
                        <input type="hidden" id="categoryToDeleteId" value="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>
                            Cancelar
                        </button>
                        <button type="button" id="confirmDeleteBtn" class="btn" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white;">
                            <i class="fas fa-trash-alt me-2"></i>
                            Confirmar Exclusão
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Edit Category Modal -->
        <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content edit-mode">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalTitle">
                            <i class="fas fa-edit me-2" style="color: #1e88e5;"></i>
                            <span id="editModalActionText">Editar Categoria</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editCategoryForm" method="POST" action="categories.php" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" id="edit_category_id" name="category_id">
                            <input type="hidden" name="action" value="edit">
                            
                            <!-- Informações Básicas -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="edit_category_name" class="form-label">
                                            <i class="fas fa-tag me-2 text-primary"></i>
                                            Nome da Categoria <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-edit"></i>
                                            </span>
                                            <input type="text" class="form-control" id="edit_category_name" name="name" 
                                                   placeholder="Ex: Alimentação, Brinquedos..." required>
                                        </div>
                                        <div class="form-text">Escolha um nome claro e descritivo para a categoria.</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="edit_parent_category" class="form-label">
                                            <i class="fas fa-sitemap me-2 text-success"></i>
                                            Categoria Principal
                                        </label>
                                        <select class="form-select" id="edit_parent_category" name="parent_id">
                                            <option value="">Nenhuma (Categoria Principal)</option>
                                            <?php
                                            // Obter todas as categorias possíveis como pais
                                            if (isset($categoryManager)) {
                                                $allCategories = $categoryManager->getAllCategoriesWithDetails();
                                                foreach ($allCategories as $cat) {
                                                    if ($cat['parent_id'] === null) {
                                                        echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['name']) . '</option>';
                                                    }
                                                }
                                            }
                                            ?>
                                        </select>
                                        <div class="form-text">Deixe em branco para manter como categoria principal.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="edit_category_description" class="form-label">
                                    <i class="fas fa-align-left me-2 text-info"></i>
                                    Descrição
                                </label>
                                <textarea class="form-control" id="edit_category_description" name="description" rows="4" 
                                          placeholder="Descrição detalhada da categoria..."></textarea>
                                <div class="form-text">Uma boa descrição ajuda na organização e SEO da loja.</div>
                            </div>
                            
                            <!-- Container para informações específicas do modo de edição -->
                            <div class="alert alert-info border-0" style="background: linear-gradient(135deg, #e9f5fe, #d1edff);">
                                <div class="d-flex">
                                    <i class="fas fa-lightbulb me-3 align-self-start mt-1" style="color: #3b82f6;"></i>
                                    <div>
                                        <h6 class="mb-1" style="color: #1e40af;">Modo de edição</h6>
                                        <p id="editModeMessage" class="mb-0 small">Editando informações da categoria selecionada.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary" id="btnUpdateCategory">
                                <i class="fas fa-save me-2"></i>
                                <span>Atualizar Categoria</span>
                            </button>
                        </div>
                    </form>
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
                        contentWrapper.style.marginLeft = '20px';
                        contentWrapper.style.width = 'calc(100% - 20px)';
                    } else {
                        sidebar.classList.remove('collapsed');
                        contentWrapper.style.marginLeft = '300px';
                        contentWrapper.style.width = 'calc(100% - 300px)';
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
                    const rows = document.querySelectorAll('#categoryTable tbody tr:not(.loading-state)');
                    
                    rows.forEach(row => {
                        const text = row.innerText.toLowerCase();
                        if (text.includes(searchTerm)) {
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
                    const rows = document.querySelectorAll('#categoryTable tbody tr:not(.loading-state)');
                    
                    rows.forEach(row => {
                        // Reset search first
                        if (searchInput) searchInput.value = '';
                        
                        switch(filter) {
                            case 'all':
                                row.style.display = '';
                                break;
                            case 'main':
                                if (!row.classList.contains('subcategory-row')) {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                                break;
                            case 'sub':
                                if (row.classList.contains('subcategory-row')) {
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
                    const dropdownBtn = document.getElementById('filterDropdown');
                    if (dropdownBtn) {
                        dropdownBtn.innerHTML = `
                            <i class="fas fa-filter me-2"></i>
                            ${this.innerText}
                        `;
                    }
                });
            });
            
            // Função para atualizar a contagem de categorias visíveis
            function updateVisibleCount() {
                const visibleRows = document.querySelectorAll('#categoryTable tbody tr:not(.loading-state)[style=""], #categoryTable tbody tr:not(.loading-state):not([style])').length;
                const totalElement = document.getElementById('totalCategories');
                if (totalElement) {
                    totalElement.textContent = visibleRows;
                }
            }
            
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
                        modalTitle.textContent = 'Adicionar Nova Categoria';
                        
                        // Reset form
                        const form = document.getElementById('categoryForm');
                        if (form) form.reset();
                        if (categoryIdField) categoryIdField.value = '';
                    }
                });
            }
            
            // Event listener para o botão de nova categoria
            const btnAddCategory = document.getElementById('btnAddCategory');
            if (btnAddCategory) {
                btnAddCategory.addEventListener('click', function() {
                    const modal = document.getElementById('categoryModal');
                    if (modal) {
                        const bootstrapModal = new bootstrap.Modal(modal);
                        bootstrapModal.show();
                    }
                });
            }
            
            // Event listener para confirmação de exclusão
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function() {
                    // Chamar a função de confirmação de exclusão implementada no arquivo categories-table-loader.js
                    if (typeof confirmDeleteCategory === 'function') {
                        confirmDeleteCategory();
                    } else {
                        console.error('Função confirmDeleteCategory não encontrada');
                        showGlobalToast('Erro ao processar a exclusão. A função não está disponível.', 'error');
                    }
                });
            }
        });
    </script>
</body>
</html>
