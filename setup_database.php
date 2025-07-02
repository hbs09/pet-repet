<?php
// Este script ajuda a criar o banco de dados automaticamente
// NOTA: Este arquivo deve ser removido em ambiente de produção!
session_start();

// Verificar se o utilizador está logado e é administrador
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die(json_encode(['success' => false, 'message' => 'Acesso restrito. Este arquivo só pode ser acessado por administradores.']));
}

header('Content-Type: application/json');

require_once 'config/database.php';

try {
    // Configurações do banco de dados do arquivo de configuração
    $dbConfig = new Database();
    $host = $dbConfig->host ?? 'localhost';
    $dbName = $dbConfig->db_name ?? 'pet_repet';
    $username = $dbConfig->username ?? 'root';
    $password = $dbConfig->password ?? '';
    
    // Conectar ao MySQL sem especificar o banco de dados
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Criar o banco de dados se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Selecionar o banco de dados
    $pdo->exec("USE `$dbName`");
    
    // Caminho para o arquivo SQL
    $sqlFile = 'database/pet_repet.sql';
    
    // Verificar se o arquivo SQL existe
    if (!file_exists($sqlFile)) {
        die(json_encode([
            'success' => false, 
            'message' => "Arquivo SQL não encontrado: $sqlFile"
        ]));
    }
    
    // Ler o conteúdo do arquivo SQL
    $sql = file_get_contents($sqlFile);
    
    // Executar o SQL (separando os comandos por ponto e vírgula)
    $pdo->exec($sql);
    
    echo json_encode([
        'success' => true,
        'message' => "Banco de dados '$dbName' criado com sucesso e estrutura importada."
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => "Erro ao configurar o banco de dados: " . $e->getMessage()
    ]);
}
?>
