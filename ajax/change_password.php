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
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validação básica
if (empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos']);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'As passwords não coincidem']);
    exit;
}

// Validar a força da password (você pode ajustar conforme necessário)
if (strlen($new_password) < 8) {
    echo json_encode(['success' => false, 'message' => 'A password deve ter pelo menos 8 caracteres']);
    exit;
}

try {
    // Conectar ao banco de dados
    $database = new Database();
    $db = $database->getConnection();
    
    // Hash da nova password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Atualizar a password do usuário - a coluna na base de dados é password_hash
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $result = $stmt->execute([$hashed_password, $_SESSION['user_id']]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Password alterada com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao alterar a password']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
?>
