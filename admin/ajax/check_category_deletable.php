<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/CategoryManager.php';

// Verificar se o utilizador está logado e é administrador
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Verificar se recebemos um ID válido
if(!isset($_POST['category_id']) || !is_numeric($_POST['category_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de categoria inválido']);
    exit;
}

$category_id = (int)$_POST['category_id'];

try {
    // Conectar ao banco de dados
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar se a categoria existe
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        throw new Exception("Categoria não encontrada.");
    }
    
    // Verificar se é uma categoria principal
    $isMainCategory = ($category['parent_id'] === null);
    
    // Se for categoria principal, verificar se tem subcategorias
    $hasSubcategories = false;
    if ($isMainCategory) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt->execute([$category_id]);
        $hasSubcategories = ($stmt->fetchColumn() > 0);
    }
    
    // Verificar se tem produtos diretamente associados
    $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $hasProducts = ($stmt->fetchColumn() > 0);
    
    // Preparar resposta
    $response = [
        'success' => true,
        'category' => [
            'id' => $category['id'],
            'name' => $category['name'],
            'is_main_category' => $isMainCategory,
            'has_subcategories' => $hasSubcategories,
            'has_products' => $hasProducts,
            'is_deletable' => !($isMainCategory && $hasSubcategories)
        ]
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
