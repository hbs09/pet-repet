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
    
    // Instanciar gerenciador de categorias
    $categoryManager = new CategoryManager($db);
    
    // Verificar se a categoria existe e obter informações antes da exclusão
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        throw new Exception("Categoria não encontrada.");
    }
    
    // Armazenar se é subcategoria antes da exclusão
    $isSubcategory = ($category['parent_id'] !== null);
    
    // Executar a exclusão da categoria usando o método existente
    // O método interno já verifica subcategorias, produtos associados e
    // faz a realocação quando necessário
    $result = $categoryManager->deleteCategory($category_id);
    
    if ($result) {
        // Construir mensagem de sucesso
        if ($isSubcategory) {
            $message = "Subcategoria excluída com sucesso!";
        } else {
            $message = "Categoria principal excluída com sucesso!";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'category_id' => $category_id
        ]);
    } else {
        throw new Exception("Erro ao excluir a categoria.");
    }
} catch (Exception $e) {
    // O tratamento de erro já está dentro do método deleteCategory
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
