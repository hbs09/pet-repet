<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';

// Redirecionar se já estiver logado
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$error_message = "";

if($_POST) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    
    // Validações
    if(empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error_message = "Por favor, preencha todos os campos obrigatórios.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Email inválido.";
    } elseif(strlen($password) < 8) {
        $error_message = "A password deve ter pelo menos 8 caracteres.";
    } elseif($password !== $confirm_password) {
        $error_message = "As passwords não coincidem.";
    } elseif($user->emailExists($email)) {
        $error_message = "Este email já está registado.";
    } else {
        // Criar utilizador
        $user->first_name = $first_name;
        $user->last_name = $last_name;
        $user->email = $email;
        $user->password = $password; // Corrigido de password_hash para password
        $user->phone = $phone;
        
        if($user->create()) {
            $_SESSION['success_message'] = "Conta criada com sucesso!";
            header("Location: login.php");
            exit;
        } else {
            $error_message = "Erro ao criar conta. Tente novamente.";
        }
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
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <title>Registo - Pet & Repet</title>
    <meta name="description" content="Crie a sua conta Pet & Repet">
</head>
<body class="auth-page-container page-register">
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
                <a href="index.php" class="auth-logo">Pet & Repet</a>
                <h2 class="auth-title">O seu pet merece o melhor.</h2>
                <p class="auth-subtitle">Junte-se a milhares de donos de animais satisfeitos.</p>
            </div>
            <div class="auth-panel-form">
                <div class="auth-header">
                    <h1 class="auth-title">Criar Conta</h1>
                    <p class="auth-subtitle">Junte-se à nossa família de amantes de animais</p>
                </div>

                <form method="POST" action="" id="registerForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">Nome *</label>
                            <div class="input-group">
                                <i class="input-icon fas fa-user"></i>
                                <input type="text" id="first_name" name="first_name" class="form-input" 
                                       required 
                                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="last_name" class="form-label">Apelido *</label>
                            <div class="input-group">
                                <i class="input-icon fas fa-user"></i>
                                <input type="text" id="last_name" name="last_name" class="form-input" 
                                        required 
                                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email" class="form-label">Endereço de Email *</label>
                            <div class="input-group">
                                <i class="input-icon fas fa-envelope"></i>
                                <input type="email" id="email" name="email" class="form-input" 
                                       required 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">Telefone</label>
                            <div class="input-group">
                                <input type="tel" id="phone" name="phone" class="form-input phone-input-field" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" class="form-label">Password *</label>
                            <div class="input-group">
                                <i class="input-icon fas fa-lock"></i>
                                <input type="password" id="password" name="password" class="form-input" 
                                        required>
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirmar Password *</label>
                            <div class="input-group">
                                <i class="input-icon fas fa-lock"></i>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                      required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="auth-button" id="submitBtn">
                        <span>Criar Conta</span>
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
                    <p>Já tem conta? <a href="login.php" class="form-link">Fazer login</a></p>
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

        // Password matching validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePasswordMatch() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.classList.add('error');
                return false;
            } else {
                confirmPassword.classList.remove('error');
                return true;
            }
        }
        
        confirmPassword.addEventListener('input', validatePasswordMatch);
        password.addEventListener('input', validatePasswordMatch);

        // Enhanced form validation
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            const inputGroup = input.closest('.input-group');
            const icon = inputGroup ? inputGroup.querySelector('.input-icon') : null;
            
            // Verificar estado inicial
            if (icon && input.value.trim() !== '') {
                icon.style.opacity = '0';
            }
            
            // Esconder ícone quando o input recebe foco
            input.addEventListener('focus', function() {
                if (icon) {
                    icon.style.opacity = '0';
                    icon.style.transition = 'opacity 0.2s ease';
                }
            });
            
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && this.value.trim() === '') {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
                
                // Mostrar o ícone novamente se o campo estiver vazio após perder o foco
                if (icon) {
                    icon.style.opacity = this.value.trim() === '' ? '1' : '0';
                    icon.style.transition = 'opacity 0.2s ease';
                }
            });

            input.addEventListener('input', function() {
                if (this.classList.contains('error') && this.value.trim() !== '') {
                    this.classList.remove('error');
                }
                
                // Manter o ícone oculto enquanto digita
                if (icon) {
                    icon.style.opacity = '0';
                    icon.style.transition = 'opacity 0.2s ease';
                }
            });
        });                                                                    


        // Adicionar evento para corrigir o layout após a página ser carregada completamente
        window.addEventListener('load', function() {
            // Corrigir layout dos inputs
            document.querySelectorAll('.form-input').forEach(input => {
                input.style.boxSizing = 'border-box';
            });
            
            // Aplicar estilo específico para o campo de telefone
            const phoneInput = document.querySelector('.phone-input-field');
            if (phoneInput) {
                phoneInput.style.paddingLeft = '12px';  // Ajustar padding sem ícone de bandeira
            }
        });

        // Add animated class to form groups on page load
        document.addEventListener('DOMContentLoaded', function() {
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                setTimeout(() => {
                    group.classList.add('animated');
                }, index * 100);
            });
            
            // Verificar inputs preenchidos ao carregar a página
            document.querySelectorAll('.form-input').forEach(input => {
                const inputGroup = input.closest('.input-group');
                const icon = inputGroup ? inputGroup.querySelector('.input-icon') : null;
                if (icon && input.value && input.value.trim() !== '') {
                    icon.style.opacity = '0';
                }
            });

            // Configuração simplificada do input de telefone (sem plugin intlTelInput)
            const phoneInputField = document.querySelector("#phone");
            if (phoneInputField) {
                // Remover qualquer formatação ou estrutura anterior do intlTelInput
                if (phoneInputField.parentElement.classList.contains('iti')) {
                    const parent = phoneInputField.parentElement;
                    const grandParent = parent.parentElement;
                    grandParent.appendChild(phoneInputField);
                    parent.remove();
                }
                
                // Configurar o campo de telefone como um input normal sem funcionalidade de países
                phoneInputField.setAttribute('placeholder', '+351 9xx xxx xxx');
                phoneInputField.value = '+351 ';

                // Adicionar formatação simples para números de telefone portugueses
                phoneInputField.addEventListener('input', function(e) {
                    const target = e.target;
                    let currentText = target.value;

                    // Garantir que o prefixo +351 sempre está presente
                    if (!currentText.startsWith('+351')) {
                        target.value = '+351 ';
                        return;
                    }

                    // Extrair os dígitos após o +351
                    let afterPrefix = currentText.substring(5).replace(/\D/g, '');
                    
                    // Limitar a 9 dígitos para telefone português
                    afterPrefix = afterPrefix.substring(0, 9);
                    
                    // Formatar com espaços a cada 3 dígitos (formato: 963 963 963)
                    let formattedNumber = '';
                    for (let i = 0; i < afterPrefix.length; i++) {
                        if (i > 0 && i % 3 === 0) {
                            formattedNumber += ' ';
                        }
                        formattedNumber += afterPrefix[i];
                    }
                    
                    // Construir o valor final com prefixo português
                    const newValue = '+351 ' + formattedNumber;
                    
                    // Atualizar o valor se for diferente
                    if (target.value !== newValue) {
                        target.value = newValue;
                        
                        // Posicionar o cursor no final do input
                        if (document.activeElement === target) {
                            const end = target.value.length;
                            target.setSelectionRange(end, end);
                        }
                    }
                });
                
                // Verificar validação no blur
                phoneInputField.addEventListener('blur', function() {
                    const valueWithoutSpaces = this.value.replace(/\s/g, '');
                    const digitsAfterPrefix = valueWithoutSpaces.substring(4);
                    
                    // Se tiver o prefixo +351 mas não tiver 9 dígitos depois, marcar como erro
                    if (this.value.startsWith('+351') && digitsAfterPrefix.length > 0 && digitsAfterPrefix.length < 9) {
                        this.classList.add('error');
                    } else {
                        this.classList.remove('error');
                    }
                });
            }

            // Form submission with loading state
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                const submitBtn = document.getElementById('submitBtn');
                
                // Validar o telefone antes do envio
                if (phoneInputField && phoneInputField.value.trim()) {
                    const valueWithoutSpaces = phoneInputField.value.replace(/\s/g, '');
                    const digitsAfterPrefix = valueWithoutSpaces.substring(4);
                    
                    // Se tiver conteúdo além do prefixo, mas não for um número válido de 9 dígitos
                    if (digitsAfterPrefix.length > 0 && digitsAfterPrefix.length < 9) {
                        e.preventDefault(); // Prevent form submission
                        phoneInputField.classList.add('error');
                        Toastify({
                            text: 'Número de telemóvel inválido. Deve ter 9 dígitos após +351.',
                            duration: 3000,
                            gravity: 'top',
                            position: 'right',
                            close: true,
                            style: {
                                background: "#e74c3c",
                            }
                        }).showToast();
                        return; // Stop execution
                    }
                }
                
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            });

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

    <!-- O script de posicionamento do dropdown foi removido pois não há mais dropdown de países -->
</body>
</html>
