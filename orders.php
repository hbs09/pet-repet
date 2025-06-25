<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Cart.php';

// Initialize database connection for cart count
$database = new Database();
$db = $database->getConnection();
$cart = new Cart($db);

// Get session ID for cart
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$session_id = $_SESSION['session_id'];

// Get cart item count
$cart_count = $cart->getItemCount($user_id, $session_id);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./scr/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>As Minhas Encomendas - Pet & Repet</title>
</head>
<body>
    <?php
    $page_title = "As Minhas Encomendas";
    include 'templates/header.php';
    ?>

    <main style="padding-top: 120px; text-align: center;">
        <h1>As Minhas Encomendas</h1>
        <p>Histórico de encomendas do utilizador.</p>
    </main>

   
</body>
</html>
                                      

