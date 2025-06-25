<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';

if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error_message = "";

if($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $user_data = $user->login($email, $password);

    if($user_data) {
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['user_first_name'] = $user_data['first_name'];
        $_SESSION['user_email'] = $user_data['email'];
        
        header("Location: index.php");
        exit;
    } else {
        $error_message = "Email ou password inválidos.";
    }
}
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
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <title>Login - Pet & Repet</title>
    <meta name="description" content="Faça login na sua conta Pet & Repet">
</head>
<body class="auth-page-container page-login">
    <div class="auth-container">
        <div class="floating-paws">
            <div class="floating-paw">🐾</div>
            <div class="floating-paw">🐾</div>
            <div class="floating-paw">🐾</div>
            <div class="floating-paw">🐾</div>
            <div class="floating-paw">🐾</div>
            <div class="floating-paw">🐾</div>
            <div class="floating-paw">🐾</div>
            <div class="floating-paw">🐾</div>
            <div class="floating-paw">🐾</div>
            <div class="floating-paw">🐾</div>
        </div>
        <div class="auth-card">
            <div class="auth-panel-promo">
                <h1 class="auth-logo">Pet & Repet</h1>
                <h2 class="auth-title">Bem-vindo de volta!</h2>
                <p class="auth-subtitle">Acesse sua conta e continue cuidando do seu pet.</p>
            </div>
            <div class="auth-panel-form">
                <div class="auth-header">
                    <h1 class="auth-title">Fazer Login</h1>
                    <p class="auth-subtitle">Entre na sua conta para continuar</p>
                </div>

                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label for="email" class="form-label">Endereço de Email *</label>
                        <div class="input-group">
                            <i class="input-icon fas fa-envelope"></i>
                            <input type="email" id="email" name="email" class="form-input" 
                                   placeholder="exemplo@email.com" required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <div class="input-group">
                            <i class="input-icon fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="form-input" 
                                   placeholder="Sua password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="auth-button" id="submitBtn">
                        <span>Entrar</span>
                    </button>
                </form>

                <div class="social-login-divider">ou continue com</div>

                <div class="social-login-buttons">
                    <a href="#" class="social-button google">
                        <svg class="google-icon" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                            <path fill="none" d="M0 0h48v48H0z"></path>
                        </svg>
                        Google
                    </a>
                    <a href="#" class="social-button facebook"><i class="fab fa-facebook-f"></i> Facebook</a>
                </div>

                <div class="form-footer">
                    <p>Não tem conta? <a href="registo.php" class="form-link">Criar conta</a></p>
                    <p style="margin-top: 12px;"><a href="index.php" class="form-link">← Voltar à loja</a></p>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = field.parentElement.querySelector('.password-toggle i');
            
            if (field.type === 'password') {
                field.type = 'text';
                toggle.className = 'far fa-eye-slash';
            } else {
                field.type = 'password';
                toggle.className = 'far fa-eye';
            }
        }

        // Enhanced form validation
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && this.value.trim() === '') {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });

            input.addEventListener('input', function() {
                if (this.classList.contains('error') && this.value.trim() !== '') {
                    this.classList.remove('error');
                }
            });
        });

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });

        document.addEventListener('DOMContentLoaded', function() {
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                setTimeout(() => {
                    group.classList.add('animated');
                }, index * 100);
            });

            <?php if(isset($_SESSION['success_message'])): ?>
                Toastify({
                    text: '<?php echo addslashes($_SESSION['success_message']); ?>',
                    duration: 5000,
                    gravity: 'top',
                    position: 'right',
                    close: true,
                    style: {
                        background: "#2e7d32",
                    }
                }).showToast();
            <?php unset($_SESSION['success_message']); endif; ?>

            <?php if(!empty($error_message)): ?>
                Toastify({
                    text: '<?php echo addslashes($error_message); ?>',
                    duration: 5000,
                    gravity: 'top',
                    position: 'right',
                    close: true,
                    style: {
                        background: "#e74c3c",
                    }
                }).showToast();
            <?php endif; ?>
        });
    </script>
</body>
</html>
