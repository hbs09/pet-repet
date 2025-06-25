<?php
session_start();
require_once '../config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilizador não autenticado']);
    exit;
}

// Verificar se os dados foram enviados
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de pedido inválido']);
    exit;
}

// Obter os dados do formulário
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

// Validação básica
if (empty($first_name) || empty($last_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, preencha os campos obrigatórios']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

try {
    // Conectar ao banco de dados
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar se o email já está em uso por outro usuário
    $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check_stmt->execute([$email, $_SESSION['user_id']]);
    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Este email já está a ser utilizado por outra conta']);
        exit;
    }
    
    // Atualizar os dados do usuário
    $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
    $result = $stmt->execute([$first_name, $last_name, $email, $phone, $_SESSION['user_id']]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Perfil atualizado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o perfil']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
