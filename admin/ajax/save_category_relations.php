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

// Verificar se é uma requisição POST
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Verificar se foi fornecido um ID de categoria
if(!isset($_POST['category_id']) || !is_numeric($_POST['category_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de categoria inválido']);
    exit;
}

$category_id = intval($_POST['category_id']);

// Conectar ao banco de dados
$database = new Database();
$db = $database->getConnection();

// Verificar se a categoria existe e é do tipo animal
$stmt = $db->prepare("SELECT id, name, parent_id FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$category) {
    echo json_encode(['success' => false, 'error' => 'Categoria não encontrada']);
    exit;
}

if($category['parent_id'] != 2) { // Não é subcategoria de Animal
    echo json_encode(['success' => false, 'error' => 'Esta categoria não suporta relações']);
    exit;
}

try {
    // Verificar se a tabela category_relations existe
    $check_table = $db->query("SHOW TABLES LIKE 'category_relations'");
    $table_exists = $check_table->rowCount() > 0;
    
    if (!$table_exists) {
        echo json_encode([
            'success' => false, 
            'error' => 'Tabela de relações não encontrada. Execute o script SQL para criar a tabela.',
            'setup_url' => '../setup_category_relations.php'
        ]);
        exit;
    }
    
    // Iniciar transação
    $db->beginTransaction();
    
    // Remover todas as relações existentes para esta categoria
    $stmt = $db->prepare("DELETE FROM category_relations WHERE animal_category_id = ?");
    $stmt->execute([$category_id]);
    
    // Se foram enviadas relações, adicioná-las
    if(isset($_POST['type_relations']) && is_array($_POST['type_relations'])) {
        $insert_stmt = $db->prepare("INSERT INTO category_relations (animal_category_id, product_type_id) VALUES (?, ?)");
        
        foreach($_POST['type_relations'] as $type_id) {
            if(is_numeric($type_id)) {
                $insert_stmt->execute([$category_id, intval($type_id)]);
            }
        }
    }
    
    // Confirmar a transação
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Relações atualizadas com sucesso']);
} catch(PDOException $e) {
    // Reverter em caso de erro
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao atualizar relações: ' . $e->getMessage(),
        'details' => [
            'sql_state' => $e->errorInfo[0],
            'code' => $e->getCode()
        ],
        'setup_url' => '../setup_category_relations.php'
    ]);
} catch(Exception $e) {
    // Reverter em caso de erro
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao atualizar relações: ' . $e->getMessage(),
        'setup_url' => '../setup_category_relations.php'
    ]);
}
