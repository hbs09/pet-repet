<?php
/**
 * Get Category Details
 * This endpoint retrieves detailed information about a specific category
 * for use in the edit modal
 */
session_start();
require_once '../../config/database.php';
require_once '../../classes/CategoryManager.php';

// Verificar se o utilizador está logado e é administrador
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

// Verificar se o ID da categoria foi fornecido
if (!isset($_POST['category_id']) || empty($_POST['category_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID da categoria é obrigatório']);
    exit;
}

$categoryId = intval($_POST['category_id']);

// Conectar ao banco de dados
$database = new Database();
$db = $database->getConnection();

// Instanciar o gerenciador de categorias
$categoryManager = new CategoryManager($db);

try {
    // Obter detalhes da categoria
    $category = $categoryManager->getCategoryById($categoryId);
    
    if ($category) {
        // Adicionar informações extras se necessário
        if ($category['parent_id'] !== null) {
            $parentCategory = $categoryManager->getCategoryById($category['parent_id']);
            if ($parentCategory) {
                $category['parent_name'] = $parentCategory['name'];
            }
        }
        
        // Verificar se há subcategorias
        $subcategories = $categoryManager->getSubcategoriesByParentId($categoryId);
        $category['has_subcategories'] = !empty($subcategories);
        $category['subcategory_count'] = count($subcategories);
        
        // Verificar se há produtos associados
        $category['has_products'] = $categoryManager->categoryHasProducts($categoryId);
        
        echo json_encode([
            'success' => true,
            'category' => $category
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Categoria não encontrada'
        ]);
    }
} catch (Exception $e) {
    error_log('Erro ao buscar categoria: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar detalhes da categoria: ' . $e->getMessage()
    ]);
}
?>
