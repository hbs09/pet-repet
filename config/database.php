<?php
class Database {
    private $host = "localhost";
    private $db_name = "pet_repet";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        if (!extension_loaded('pdo_mysql')) {
            die("PDO MySQL driver not installed. Please enable it in your php.ini.");
        }
        try {
            // Adicionar timeout para a conexão
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5, // 5 segundos de timeout
                PDO::ATTR_PERSISTENT => false // conexões não persistentes para evitar problemas
            ];
            
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8", 
                                 $this->username, $this->password, $options);
        } catch(PDOException $exception) {
            // Registro de erro mais detalhado
            $errorMessage = "Connection error: " . $exception->getMessage();
            error_log($errorMessage); // Registrar o erro no log do PHP
            
            // Verificar se o erro é relacionado à recusa de conexão
            if (strpos($exception->getMessage(), 'refused') !== false) {
                echo "<div style='background:#ffebee;padding:15px;border-radius:5px;margin:20px;color:#d32f2f;'>";
                echo "<h3>Erro de Conexão com o Banco de Dados</h3>";
                echo "<p>Não foi possível conectar ao servidor MySQL. Verifique:</p>";
                echo "<ul>";
                echo "<li>Se o serviço MySQL/MariaDB está ativo no XAMPP</li>";
                echo "<li>Se o banco de dados <strong>{$this->db_name}</strong> existe</li>";
                echo "<li>Se as credenciais de acesso estão corretas</li>";
                echo "</ul>";
                echo "<p>Erro técnico: " . htmlspecialchars($exception->getMessage()) . "</p>";
                echo "</div>";
            } else {
                echo "Connection error: " . $exception->getMessage();
            }
            
            return null; // Retorna null explicitamente para indicar falha
        }
        return $this->conn;
    }
    
    /**
     * Função para diagnosticar problemas de conexão
     * @return array Informações de diagnóstico
     */
    public function diagnosticInfo() {
        $diagnosticInfo = [
            'mysql_running' => false,
            'database_exists' => false,
            'can_connect' => false,
            'php_version' => PHP_VERSION,
            'pdo_installed' => extension_loaded('pdo_mysql'),
            'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'errors' => []
        ];
        
        // Verificar se o serviço MySQL está em execução
        try {
            $tempConn = new PDO("mysql:host=" . $this->host, $this->username, $this->password, [
                PDO::ATTR_TIMEOUT => 3
            ]);
            $diagnosticInfo['mysql_running'] = true;
            
            // Verificar se o banco de dados existe
            $stmt = $tempConn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$this->db_name}'");
            $diagnosticInfo['database_exists'] = ($stmt && $stmt->rowCount() > 0);
            
            // Tentar conectar ao banco de dados específico
            if ($diagnosticInfo['database_exists']) {
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                     $this->username, $this->password);
                $diagnosticInfo['can_connect'] = true;
                $diagnosticInfo['mysql_version'] = $this->conn->getAttribute(PDO::ATTR_SERVER_VERSION);
            }
        } catch (PDOException $e) {
            $diagnosticInfo['errors'][] = $e->getMessage();
        }
        
        return $diagnosticInfo;
    }
}
?>
