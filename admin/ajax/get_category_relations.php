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

// Verificar se foi fornecido um ID de categoria
if(!isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'ID de categoria inválido']);
    exit;
}

$category_id = intval($_GET['category_id']);

// Conectar ao banco de dados
$database = new Database();
$db = $database->getConnection();

// Obter informações da categoria atual para verificar o tipo
$stmt = $db->prepare("SELECT id, name, parent_id FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$category) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Categoria não encontrada']);
    exit;
}

// Se for uma subcategoria de Animal (parent_id = 2),
// queremos obter todos os tipos de produto (parent_id = 1) e ver se há relação
if($category['parent_id'] == 2) {
    try {
        // Verificar se a tabela category_relations existe
        $check_table = $db->query("SHOW TABLES LIKE 'category_relations'");
        $table_exists = $check_table->rowCount() > 0;
        
        if ($table_exists) {
            // Obter todos os tipos de produto (subcategorias de 'Tipo de produto')
            $stmt = $db->prepare("
                SELECT c.id, c.name,
                (SELECT COUNT(*) FROM category_relations WHERE animal_category_id = ? AND product_type_id = c.id) > 0 AS related
                FROM categories c 
                WHERE c.parent_id = 1 AND c.is_active = 1
                ORDER BY c.name
            ");
            $stmt->execute([$category_id]);
            $type_relations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['type_relations' => $type_relations]);
        } else {
            // Se a tabela não existe, retornar apenas tipos de produtos sem relações
            $stmt = $db->prepare("
                SELECT c.id, c.name, FALSE AS related
                FROM categories c 
                WHERE c.parent_id = 1 AND c.is_active = 1
                ORDER BY c.name
            ");
            $stmt->execute();
            $type_relations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'type_relations' => $type_relations,
                'warning' => 'Tabela de relações não encontrada. Execute o script SQL para criar a tabela category_relations.'
            ]);
        }
    } catch (PDOException $e) {
        // Em caso de erro no banco de dados
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode([
            'error' => 'Erro no banco de dados', 
            'details' => $e->getMessage(),
            'sql_state' => $e->errorInfo[0]
        ]);
        exit;
    }
} else {
    echo json_encode(['error' => 'Esta categoria não suporta relações com outras categorias']);
}
