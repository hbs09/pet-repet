<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/CategoryManager.php';

// Verificar se o utilizador está logado e é administrador
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

try {
    // Conectar ao banco de dados
    $database = new Database();
    $db = $database->getConnection();
    
    // Instância do gerenciador de categorias
    $categoryManager = new CategoryManager($db);
    
    // Buscar todas as categorias com detalhes
    $allCategories = $categoryManager->getAllCategoriesWithDetails();
    
    // Organizar categorias em estrutura hierárquica
    $organizedCategories = [];
    $categoriesById = [];
    
    // Primeiro, criar um array indexado por ID
    foreach ($allCategories as $category) {
        $categoriesById[$category['id']] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'description' => $category['description'],
            'parent_id' => $category['parent_id'],
            'product_count' => $category['product_count'],
            'is_active' => $category['is_active'],
            'subcategories' => []
        ];
    }
    
    // Organizar em hierarquia (principais com suas subcategorias)
    foreach ($categoriesById as $category) {
        if ($category['parent_id'] === null) {
            // É uma categoria principal
            $organizedCategories[] = $category;
        } else {
            // É uma subcategoria, adicionar à categoria pai
            if (isset($categoriesById[$category['parent_id']])) {
                $categoriesById[$category['parent_id']]['subcategories'][] = $category;
            }
        }
    }
    
    // Atualizar as categorias principais com suas subcategorias
    for ($i = 0; $i < count($organizedCategories); $i++) {
        $organizedCategories[$i]['subcategories'] = $categoriesById[$organizedCategories[$i]['id']]['subcategories'];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'categories' => $organizedCategories
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar categorias: ' . $e->getMessage()
    ]);
}
?>
