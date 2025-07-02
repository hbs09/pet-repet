<?php
/**
 * AJAX Category Management
 * Handles AJAX requests for category management
 */
session_start();
require_once '../../config/database.php';
require_once '../../classes/CategoryManager.php';

// Verificar se o utilizador está logado e é administrador
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

// Conectar ao banco de dados
$database = new Database();
$db = $database->getConnection();

// Instância do gerenciador de categorias
$categoryManager = new CategoryManager($db);

// Obter o tipo de ação da requisição
$action = isset($_POST['action']) ? $_POST['action'] : '';
$response = ['success' => false];

try {
    switch ($action) {
        case 'add':
            // Validar dados
            if (empty($_POST['name'])) {
                throw new Exception('O nome da categoria é obrigatório');
            }
            
            $name = trim($_POST['name']);
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $parentId = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
            
            // Categorias são sempre criadas como ativas
            $isActive = true;
            
            // Criar categoria
            $categoryId = $categoryManager->createCategory($name, $description, $parentId, $isActive);
            
            if ($categoryId) {
                $response['success'] = true;
                $response['message'] = 'Categoria adicionada com sucesso!';
                $response['category_id'] = $categoryId;
            } else {
                throw new Exception('Erro ao adicionar categoria');
            }
            break;
            
        case 'edit':
            // Validar dados
            if (empty($_POST['category_id']) || empty($_POST['name'])) {
                throw new Exception('ID da categoria e nome são obrigatórios');
            }
            
            $categoryId = $_POST['category_id'];
            $name = trim($_POST['name']);
            $description = isset($_POST['description']) ? trim($_POST['description']) : null;
            
            // Tratar parent_id como NULL se for string vazia, 'null', ou não existir
            $parentId = isset($_POST['parent_id']) ? $_POST['parent_id'] : null;
            if ($parentId === '' || $parentId === 'null' || strtolower($parentId) === 'null') {
                $parentId = null;
            }
            
            // Não alteramos mais o status da categoria durante a edição
            $isActive = null;
            
            // Registrar informações para diagnóstico
            error_log("Atualizando categoria - ID: $categoryId, Nome: $name, Parent ID: " . (is_null($parentId) ? "NULL" : $parentId));
            
            try {
                // Atualizar categoria
                $result = $categoryManager->updateCategory($categoryId, $name, $description, $parentId, $isActive);
                
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Categoria atualizada com sucesso!';
                } else {
                    throw new Exception('Erro ao atualizar categoria');
                }
            } catch (Exception $e) {
                error_log("Erro detalhado ao atualizar categoria: " . $e->getMessage());
                throw $e;
            }
            break;
            
        case 'check_products':
            // Verificar se uma categoria tem produtos associados
            if (empty($_POST['category_id'])) {
                throw new Exception('ID da categoria é obrigatório');
            }
            
            $categoryId = $_POST['category_id'];
            
            // Verificar se é categoria principal
            $stmt = $db->prepare("SELECT parent_id FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $parentId = $stmt->fetchColumn();
            
            if ($parentId !== null) {
                // Se não for categoria principal, retornar erro
                throw new Exception('Apenas categorias principais podem ser verificadas');
            }
            
            // Verificar produtos diretamente associados
            $hasProducts = $categoryManager->categoryHasProducts($categoryId);
            
            $response['success'] = true;
            $response['has_products'] = $hasProducts;
            $response['category_id'] = $categoryId;
            break;
            
        case 'delete':
            // Validar dados
            if (empty($_POST['category_id'])) {
                throw new Exception('ID da categoria é obrigatório');
            }
            
            $categoryId = $_POST['category_id'];
            
            // Excluir categoria
            $result = $categoryManager->deleteCategory($categoryId);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Categoria excluída com sucesso!';
            } else {
                throw new Exception('Erro ao excluir categoria');
            }
            break;
        
        case 'toggle_status':
            // Validar dados
            if (empty($_POST['category_id'])) {
                throw new Exception('ID da categoria é obrigatório');
            }
            
            $categoryId = $_POST['category_id'];
            
            // Obter status atual da categoria
            $category = $categoryManager->getCategoryById($categoryId);
            if (!$category) {
                throw new Exception('Categoria não encontrada');
            }
            
            // Inverter o status (ativar/desativar)
            $newStatus = $category['is_active'] == 1 ? 0 : 1;
            
            // Atualizar o status
            $result = $categoryManager->toggleCategoryStatus($categoryId, $newStatus);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = $newStatus == 1 ? 'Categoria ativada com sucesso!' : 'Categoria desativada com sucesso!';
                $response['new_status'] = $newStatus;
                $response['category_id'] = $categoryId;
            } else {
                throw new Exception('Erro ao alterar o status da categoria');
            }
            break;
            
        case 'get_categories':
            // Obter todas as categorias
            $categories = $categoryManager->getAllCategoriesWithDetails();
            $response['success'] = true;
            $response['categories'] = $categories;
            break;
            
        case 'get_category':
            // Validar dados
            if (empty($_POST['category_id'])) {
                throw new Exception('ID da categoria é obrigatório');
            }
            
            $categoryId = $_POST['category_id'];
            
            // Obter categoria
            $category = $categoryManager->getCategoryById($categoryId);
            
            if ($category) {
                $response['success'] = true;
                $response['category'] = $category;
            } else {
                throw new Exception('Categoria não encontrada');
            }
            break;
            
        case 'get_subcategories':
            // Validar dados
            if (!isset($_POST['parent_id'])) {
                throw new Exception('ID da categoria pai é obrigatório');
            }
            
            $parentId = $_POST['parent_id'];
            
            // Obter subcategorias
            $subcategories = $categoryManager->getSubcategoriesByParentId($parentId);
            $response['success'] = true;
            $response['subcategories'] = $subcategories;
            break;
            
        default:
            throw new Exception('Ação desconhecida');
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    http_response_code(400);
}

// Enviar resposta
header('Content-Type: application/json');
echo json_encode($response);
