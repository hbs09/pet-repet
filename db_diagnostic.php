<?php
// Arquivo de diagnóstico para problemas de conexão com o banco de dados
// NOTA: Este arquivo deve ser removido em ambiente de produção!
session_start();

// Verificar se o utilizador está logado e é administrador
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("Acesso restrito. Este arquivo só pode ser acessado por administradores.");
}

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Conexão - Pet&Repet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f7f9fc;
        }
        h1, h2 {
            color: #4e73df;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .success {
            color: #0d8a4d;
            background-color: #e3fcef;
            border-left: 4px solid #0d8a4d;
            padding: 10px 15px;
            border-radius: 4px;
        }
        .error {
            color: #d32f2f;
            background-color: #ffebee;
            border-left: 4px solid #d32f2f;
            padding: 10px 15px;
            border-radius: 4px;
        }
        .warning {
            color: #e65100;
            background-color: #fff8e1;
            border-left: 4px solid #e65100;
            padding: 10px 15px;
            border-radius: 4px;
        }
        .info-row {
            display: flex;
            border-bottom: 1px solid #eee;
            padding: 8px 0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            width: 40%;
            font-weight: 500;
        }
        .info-value {
            width: 60%;
        }
        .btn {
            display: inline-block;
            background-color: #4e73df;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #2e59d9;
        }
        .icon {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <h1><i class="fas fa-database icon"></i> Diagnóstico da Conexão com o Banco de Dados</h1>
    
    <div class="card">
        <h2>Informações do Sistema</h2>
        <?php
        // Obter informações do sistema
        $dbConfig = new Database();
        $diagnosticInfo = $dbConfig->diagnosticInfo();
        ?>
        
        <div class="info-row">
            <div class="info-label">Versão do PHP:</div>
            <div class="info-value"><?php echo $diagnosticInfo['php_version']; ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">PDO MySQL instalado:</div>
            <div class="info-value">
                <?php if ($diagnosticInfo['pdo_installed']): ?>
                    <span class="success"><i class="fas fa-check-circle"></i> Sim</span>
                <?php else: ?>
                    <span class="error"><i class="fas fa-times-circle"></i> Não - Este é necessário!</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Software do Servidor:</div>
            <div class="info-value"><?php echo $diagnosticInfo['server_info']; ?></div>
        </div>
    </div>
    
    <div class="card">
        <h2>Estado da Conexão</h2>
        
        <div class="info-row">
            <div class="info-label">Servidor MySQL em execução:</div>
            <div class="info-value">
                <?php if ($diagnosticInfo['mysql_running']): ?>
                    <span class="success"><i class="fas fa-check-circle"></i> Sim</span>
                <?php else: ?>
                    <span class="error"><i class="fas fa-times-circle"></i> Não - Verifique se o serviço MySQL está ativo no XAMPP</span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($diagnosticInfo['mysql_running']): ?>
            <div class="info-row">
                <div class="info-label">Banco de dados existe:</div>
                <div class="info-value">
                    <?php if ($diagnosticInfo['database_exists']): ?>
                        <span class="success"><i class="fas fa-check-circle"></i> Sim</span>
                    <?php else: ?>
                        <span class="error"><i class="fas fa-times-circle"></i> Não - O banco de dados 'pet_repet' não foi encontrado</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($diagnosticInfo['database_exists']): ?>
                <div class="info-row">
                    <div class="info-label">Conexão bem-sucedida:</div>
                    <div class="info-value">
                        <?php if ($diagnosticInfo['can_connect']): ?>
                            <span class="success"><i class="fas fa-check-circle"></i> Sim</span>
                        <?php else: ?>
                            <span class="error"><i class="fas fa-times-circle"></i> Não - Problema com credenciais ou permissões</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($diagnosticInfo['can_connect']): ?>
                    <div class="info-row">
                        <div class="info-label">Versão MySQL:</div>
                        <div class="info-value"><?php echo $diagnosticInfo['mysql_version']; ?></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (!empty($diagnosticInfo['errors'])): ?>
            <div class="info-row">
                <div class="info-label">Erros encontrados:</div>
                <div class="info-value">
                    <?php foreach ($diagnosticInfo['errors'] as $error): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>Recomendações</h2>
        
        <?php if (!$diagnosticInfo['mysql_running']): ?>
            <div class="warning">
                <h3><i class="fas fa-exclamation-triangle"></i> O servidor MySQL não está em execução</h3>
                <p>Passos para resolver:</p>
                <ol>
                    <li>Abra o painel de controle do XAMPP</li>
                    <li>Clique em "Start" ao lado do serviço MySQL</li>
                    <li>Verifique se o serviço foi iniciado corretamente (luz verde)</li>
                    <li>Se continuar a falhar, verifique os logs do MySQL em XAMPP/mysql/data/[log_file]</li>
                </ol>
            </div>
        <?php elseif (!$diagnosticInfo['database_exists']): ?>
            <div class="warning">
                <h3><i class="fas fa-exclamation-triangle"></i> O banco de dados 'pet_repet' não existe</h3>
                <p>Passos para resolver:</p>
                <ol>
                    <li>Abra o phpMyAdmin (geralmente em http://localhost/phpmyadmin/)</li>
                    <li>Clique em "Novo" no painel esquerdo</li>
                    <li>Crie um novo banco de dados chamado "pet_repet"</li>
                    <li>Importe o arquivo SQL do diretório database/pet_repet.sql</li>
                </ol>
            </div>
        <?php elseif (!$diagnosticInfo['can_connect']): ?>
            <div class="warning">
                <h3><i class="fas fa-exclamation-triangle"></i> Não é possível conectar ao banco de dados</h3>
                <p>Verifique:</p>
                <ol>
                    <li>Se as credenciais no arquivo config/database.php estão corretas</li>
                    <li>Se o usuário MySQL tem permissões para acessar o banco de dados</li>
                    <li>Se o host definido está correto (geralmente é "localhost")</li>
                </ol>
            </div>
        <?php else: ?>
            <div class="success">
                <h3><i class="fas fa-check-circle"></i> A conexão com o banco de dados está funcionando corretamente!</h3>
                <p>Todos os testes foram bem-sucedidos. Você pode retornar para a aplicação.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div>
        <a href="admin/categories.php" class="btn"><i class="fas fa-arrow-left icon"></i> Voltar para Categorias</a>
        <a href="index.php" class="btn"><i class="fas fa-home icon"></i> Página Inicial</a>
        <?php if (!$diagnosticInfo['database_exists']): ?>
            <a href="#" class="btn" onclick="createDatabase(); return false;"><i class="fas fa-database icon"></i> Criar Banco de Dados</a>
        <?php endif; ?>
    </div>
    
    <?php if (!$diagnosticInfo['database_exists']): ?>
    <script>
        function createDatabase() {
            if (confirm("Deseja criar automaticamente o banco de dados 'pet_repet'?\nNota: Esta operação requer permissões administrativas no MySQL.")) {
                fetch('setup_database.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Banco de dados criado com sucesso! A página será recarregada.');
                        location.reload();
                    } else {
                        alert('Erro ao criar banco de dados: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Erro na solicitação: ' + error);
                });
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>
