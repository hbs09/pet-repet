<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Cart.php';

header('Content-Type: application/json');

if ($_POST && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $database = new Database();
    $db = $database->getConnection();
    $cart = new Cart($db);
    
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $session_id = isset($_SESSION['session_id']) ? $_SESSION['session_id'] : session_id();
    
    if (!isset($_SESSION['session_id'])) {
        $_SESSION['session_id'] = $session_id;
    }
    
    if ($cart->addItem($user_id, $session_id, $product_id, $quantity)) {
        $cart_count = $cart->getItemCount($user_id, $session_id);
        echo json_encode([
            'success' => true,
            'cart_count' => $cart_count,
            'message' => 'Produto adicionado ao carrinho'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao adicionar produto ao carrinho'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Dados inválidos'
    ]);
}
?>
