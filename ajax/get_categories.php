<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    // Inicializar conexão com o banco de dados
    $database = new Database();
    $db = $database->getConnection();
    
    // Obter parâmetro parent (ID da categoria pai)
    $parent = isset($_GET['parent']) ? intval($_GET['parent']) : null;
    
    if ($parent !== null) {
        // Buscar subcategorias
        $stmt = $db->prepare("SELECT id, name FROM categories WHERE parent_id = ? AND is_active = TRUE ORDER BY name");
        $stmt->execute([$parent]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Retornar como JSON
        echo json_encode(['categories' => $categories]);
    } else {
        // Se não for fornecido ID da categoria pai
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Parâmetro parent é necessário']);
    }
} catch (Exception $e) {
    // Em caso de erro
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Erro ao processar requisição: ' . $e->getMessage()]);
}
?>
?>
