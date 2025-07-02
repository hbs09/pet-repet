<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';

// Verificar se o utilizador está logado e é administrador
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo "Acesso negado. Você deve estar logado como administrador.";
    exit;
}

// Conectar ao banco de dados
$database = new Database();
$db = $database->getConnection();

$success = true;
$messages = [];

try {
    // Verificar se a tabela já existe
    $check_table = $db->query("SHOW TABLES LIKE 'category_relations'");
    $table_exists = $check_table->rowCount() > 0;
    
    if (!$table_exists) {
        // Ler o arquivo SQL
        $sql_file = file_get_contents('../database/category_relations.sql');
        if ($sql_file === false) {
            throw new Exception("Não foi possível ler o arquivo SQL!");
        }
        
        // Executar o SQL - precisamos extrair e executar o CREATE TABLE separadamente
        $createTableQuery = "CREATE TABLE `category_relations` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `animal_category_id` int(11) NOT NULL,
          `product_type_id` int(11) NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_relation` (`animal_category_id`,`product_type_id`),
          KEY `product_type_id` (`product_type_id`),
          CONSTRAINT `category_relations_ibfk_1` FOREIGN KEY (`animal_category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
          CONSTRAINT `category_relations_ibfk_2` FOREIGN KEY (`product_type_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $result = $db->exec($createTableQuery);
        if ($result === false) {
            throw new Exception("Erro ao criar a tabela: " . implode(" ", $db->errorInfo()));
        }
        
        $messages[] = "Tabela 'category_relations' criada com sucesso!";
    } else {
        $messages[] = "A tabela 'category_relations' já existe no banco de dados.";
    }
    
    // Verificar se há categorias de exemplo para criar algumas relações
    $animal_stmt = $db->query("SELECT id FROM categories WHERE parent_id = 2 LIMIT 5");
    $type_stmt = $db->query("SELECT id FROM categories WHERE parent_id = 1 LIMIT 5");
    
    $animals = $animal_stmt->fetchAll(PDO::FETCH_COLUMN);
    $types = $type_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($animals) && !empty($types)) {
        // Criar algumas relações de exemplo
        $db->beginTransaction();
        
        $check_stmt = $db->prepare("SELECT COUNT(*) FROM category_relations WHERE animal_category_id = ? AND product_type_id = ?");
        $insert_stmt = $db->prepare("INSERT INTO category_relations (animal_category_id, product_type_id) VALUES (?, ?)");
        
        $examples_created = 0;
        
        foreach ($animals as $animal_id) {
            foreach ($types as $type_id) {
                $check_stmt->execute([$animal_id, $type_id]);
                $relation_exists = $check_stmt->fetchColumn() > 0;
                
                if (!$relation_exists) {
                    // Criar apenas algumas combinações para demonstração
                    if (rand(0, 100) > 50) {
                        $insert_stmt->execute([$animal_id, $type_id]);
                        $examples_created++;
                    }
                }
            }
        }
        
        $db->commit();
        $messages[] = "Foram criadas {$examples_created} relações de exemplo entre categorias de animais e tipos de produtos.";
    }
    
} catch (Exception $e) {
    $success = false;
    $messages[] = "Erro: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração de Relações entre Categorias - Pet & Repet</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        h1 {
            color: #8c52ff;
            margin-top: 0;
        }
        .result {
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .success {
            background-color: #e3fcef;
            border: 1px solid #2ecc71;
            color: #2ecc71;
        }
        .error {
            background-color: #fde8e8;
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        .message-list {
            list-style-type: none;
            padding-left: 10px;
        }
        .message-list li {
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }
        .message-list li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: inherit;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #8c52ff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin-top: 15px;
        }
        .btn:hover {
            background-color: #6a3fc3;
        }
        .code-block {
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            margin: 20px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Configuração de Relações entre Categorias</h1>
        
        <div class="result <?php echo $success ? 'success' : 'error'; ?>">
            <h3><?php echo $success ? 'Configuração concluída!' : 'Ocorreram problemas na configuração'; ?></h3>
            <ul class="message-list">
                <?php foreach($messages as $message): ?>
                    <li><?php echo $message; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <h2>Próximos passos</h2>
        <p>A tabela <code>category_relations</code> permite criar relações entre tipos de produtos e categorias de animais, como "Alimentos para Cães" ou "Brinquedos para Gatos".</p>
        
        <p>Para gerenciar estas relações:</p>
        <ol>
            <li>Acesse a página <a href="categories.php">Gestão de Categorias</a></li>
            <li>Clique no ícone <code>🔗</code> ao lado de qualquer categoria de animal</li>
            <li>Selecione os tipos de produtos que se aplicam a esta categoria animal</li>
        </ol>
        
        <h2>Estrutura da tabela</h2>
        <div class="code-block">
<pre>CREATE TABLE `category_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_category_id` int(11) NOT NULL,
  `product_type_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_relation` (`animal_category_id`,`product_type_id`),
  KEY `product_type_id` (`product_type_id`),
  CONSTRAINT `category_relations_ibfk_1` FOREIGN KEY (`animal_category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_relations_ibfk_2` FOREIGN KEY (`product_type_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
);</pre>
        </div>
        
        <a href="categories.php" class="btn">Voltar para Gestão de Categorias</a>
    </div>
</body>
</html>
