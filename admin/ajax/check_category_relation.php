<?php
// Desativar exibição de erros para evitar corromper o JSON
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../../config/database.php';
require_once '../../classes/User.php';

// Definir cabeçalho de resposta como JSON
header('Content-Type: application/json');

// Verificar se o utilizador está logado e é administrador
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

// Verificar se foram fornecidos os parâmetros necessários
if(!isset($_GET['product_type']) || !isset($_GET['animal']) || !is_numeric($_GET['product_type']) || !is_numeric($_GET['animal'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit;
}

$productTypeId = intval($_GET['product_type']);
$animalId = intval($_GET['animal']);

// Conectar ao banco de dados
$database = new Database();
$db = $database->getConnection();

try {
    // Verificar se a tabela category_relations existe
    $check_table = $db->query("SHOW TABLES LIKE 'category_relations'");
    $table_exists = $check_table->rowCount() > 0;
    
    if ($table_exists) {
        // Verificar se a relação existe
        $stmt = $db->prepare("SELECT COUNT(*) FROM category_relations WHERE product_type_id = ? AND animal_category_id = ?");
        $stmt->execute([$productTypeId, $animalId]);
        $relationExists = $stmt->fetchColumn() > 0;
        
        echo json_encode(['exists' => $relationExists]);
    } else {
        // Se a tabela não existir, sempre retornar que a relação não existe
        echo json_encode([
            'exists' => false,
            'warning' => 'Tabela de relações não encontrada',
            'setup_url' => '../setup_category_relations.php'
        ]);
    }
} catch (PDOException $e) {
    // Em caso de erro no banco de dados
    echo json_encode([
        'exists' => false,
        'error' => 'Erro no banco de dados: ' . $e->getMessage(),
        'setup_url' => '../setup_category_relations.php'
    ]);
}
