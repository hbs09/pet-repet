<?php
require_once 'config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare('SELECT id, name, description, parent_id, is_active FROM categories ORDER BY parent_id IS NULL DESC, parent_id, name');
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== CATEGORIAS NA BASE DE DADOS ===\n";
    foreach($categories as $cat) {
        $parent = $cat['parent_id'] ? 'SubCat (Parent: '.$cat['parent_id'].')' : 'Principal';
        $status = $cat['is_active'] ? 'Ativa' : 'Inativa';
        echo "ID: {$cat['id']} | {$cat['name']} | {$parent} | {$status}\n";
    }
    echo "\nTotal: ".count($categories)." categorias\n";
} catch(Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}
?>
