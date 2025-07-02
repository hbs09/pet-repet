<?php
session_start();
require_once '../../config/database.php';

// Verificar se é um administrador
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Acesso não autorizado.']));
}

// Verificar se o ID da categoria foi fornecido
if(!isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'ID de categoria não fornecido ou inválido.']));
}

$category_id = intval($_GET['category_id']);

try {
    // Conectar ao banco de dados
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar informações da categoria
    $category_query = "SELECT id, name, parent_id FROM categories WHERE id = ?";
    $stmt_category = $db->prepare($category_query);
    $stmt_category->execute([$category_id]);
    $category = $stmt_category->fetch(PDO::FETCH_ASSOC);
    
    if(!$category) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Categoria não encontrada.']));
    }
    
    $is_main_category = ($category['parent_id'] === null);
    $products = [];
    $associated_count = 0;
    $direct_count = 0;
    
    // Verificar produtos com category_id direto (campo na tabela products)
    $direct_query = "SELECT p.id, p.name, p.image_url, p.price FROM products p WHERE p.category_id = ?";
    $stmt_direct = $db->prepare($direct_query);
    $stmt_direct->execute([$category_id]);
    $direct_products = $stmt_direct->fetchAll(PDO::FETCH_ASSOC);
    $direct_count = count($direct_products);
    $products = array_merge($products, $direct_products);
    
    // Verificar produtos associados via product_categories
    $assoc_query = "SELECT p.id, p.name, p.image_url, p.price 
                    FROM products p 
                    JOIN product_categories pc ON p.id = pc.product_id 
                    WHERE pc.category_id = ?";
    $stmt_assoc = $db->prepare($assoc_query);
    $stmt_assoc->execute([$category_id]);
    $associated_products = $stmt_assoc->fetchAll(PDO::FETCH_ASSOC);
    $associated_count = count($associated_products);
    
    // Mesclar os resultados, removendo duplicatas
    foreach($associated_products as $prod) {
        $exists = false;
        foreach($products as $existing) {
            if($existing['id'] == $prod['id']) {
                $exists = true;
                break;
            }
        }
        if(!$exists) {
            $products[] = $prod;
        }
    }
    
    // Preparar resposta
    $response = [
        'success' => true,
        'category' => $category,
        'products' => $products,
        'counts' => [
            'direct' => $direct_count,
            'associated' => $associated_count,
            'total' => count($products)
        ],
        'is_main_category' => $is_main_category
    ];
    
    echo json_encode($response);
    
} catch(PDOException $e) {
    error_log('Erro na consulta de produtos por categoria: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao buscar produtos associados à categoria.']);
}
?>
