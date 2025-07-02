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
if(!isset($_POST['parent_id']) || !is_numeric($_POST['parent_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de categoria pai inválido']);
    exit;
}

$parent_id = (int)$_POST['parent_id'];

try {
    // Conectar ao banco de dados
    $database = new Database();
    $db = $database->getConnection();
    
    // Instanciar gerenciador de categorias
    $categoryManager = new CategoryManager($db);
    
    // Buscar subcategorias
    $subcategories = $categoryManager->getSubcategoriesByParentId($parent_id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'subcategories' => $subcategories,
        'count' => count($subcategories)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
