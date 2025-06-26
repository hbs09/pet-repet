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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css"/>
    <title>A Minha Conta - Pet & Repet</title>
</head>
<body>
    <?php
    $page_title = "A Minha Conta";
    include 'templates/header.php';

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Get user information
    $user_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    ?>

    <main class="account-container">
        <div class="container">
            <!-- Account Navigation & Content -->
            <div class="account-layout">
                <!-- Sidebar Navigation -->
                <aside class="account-sidebar">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>

                    <nav class="account-nav">
                        <a href="#personal-info" class="nav-tab active" data-tab="personal-info">
                            <i class="fas fa-user"></i>
                            <span>Informações Pessoais</span>
                        </a>
                        <a href="#security" class="nav-tab" data-tab="security">
                            <i class="fas fa-shield-alt"></i>
                            <span>Segurança</span>
                        </a>
                        <div class="nav-divider"></div>
                        <a href="orders.php" class="nav-tab external">
                            <i class="fas fa-box"></i>
                            <span>Encomendas</span>
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        <a href="wishlist.php" class="nav-tab external">
                            <i class="fas fa-heart"></i>
                            <span>Lista de Desejos</span>
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        <a href="carrinho.php" class="nav-tab external">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Carrinho</span>
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        <div class="nav-divider"></div>
                        <a href="logout.php" class="nav-tab logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Terminar Sessão</span>
                        </a>
                    </nav>
                </aside>

                <!-- Main Content Area -->
                <main class="account-content">
                    <!-- Personal Information Tab -->
                    <div id="personal-info" class="tab-content active">
                        <div class="tab-header">
                            <div class="tab-header-content">
                                <h2>Informações Pessoais</h2>
                                <p>Gerir os seus dados pessoais</p>
                            </div>
                            <div class="header-actions">
                                <button type="button" class="btn-save" id="saveProfileBtn" style="display:none;">
                                    <i class="fas fa-save"></i>
                                    Guardar
                                </button>
                                <button type="button" class="btn-edit" id="editProfileBtn">
                                    <i class="fas fa-edit"></i>
                                    Editar Perfil
                                </button>
                            </div>
                        </div>
                        <div class="section-body">
                            <!-- Display Mode -->
                            <div class="profile-display" id="profileDisplay">
                                <form id="inlineProfileForm">
                                    <div class="info-list">
                                        <!-- Primeira Linha: Nome + Email -->
                                        <div class="info-row">
                                            <div class="info-item">
                                                <div class="info-icon">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div class="info-content">
                                                    <span class="info-label">Nome</span>
                                                    <span class="info-value display-mode"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                                                    <div class="info-edit edit-mode" style="display: none;">
                                                        <div class="name-inputs-row">
                                                            <input type="text" name="first_name" class="inline-input name-input" value="<?php echo htmlspecialchars($user['first_name']); ?>" placeholder="Primeiro nome">
                                                            <input type="text" name="last_name" class="inline-input name-input" value="<?php echo htmlspecialchars($user['last_name']); ?>" placeholder="Sobrenome">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="info-item">
                                                <div class="info-icon">
                                                    <i class="fas fa-envelope"></i>
                                                </div>
                                                <div class="info-content">
                                                    <span class="info-label">Email</span>
                                                    <span class="info-value display-mode"><?php echo htmlspecialchars($user['email']); ?></span>
                                                    <div class="info-edit edit-mode" style="display: none;">
                                                        <input type="email" name="email" class="inline-input" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Email">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Segunda Linha: Telefone + Membro desde -->
                                        <div class="info-row">
                                            <div class="info-item">
                                                <div class="info-icon">
                                                    <i class="fas fa-phone"></i>
                                                </div>
                                                <div class="info-content">
                                                    <span class="info-label">Telefone</span>
                                                    <span class="info-value display-mode"><?php echo htmlspecialchars($user['phone'] ?? 'Não definido'); ?></span>
                                                    <div class="info-edit edit-mode" style="display: none;">
                                                        <input type="tel" name="phone" class="inline-input phone-input-field" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Telefone">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="info-item member-since-item">
                                                <div class="info-icon">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </div>
                                                <div class="info-content">
                                                    <span class="info-label">Membro desde</span>
                                                    <span class="info-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Botões de ação removidos, agora são mostrados na parte superior -->
                                    </div>
                                </form>
                            </div>

                            <!-- Edit Mode -->
                            <form id="profileForm" class="profile-edit" style="display: none;">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Nome</label>
                                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Apelido</label>
                                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Telefone</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Introduza o seu número de telefone">
                                </div>
                                <div class="form-actions">
                                    <button type="button" class="btn btn-secondary" id="cancelEditBtn">
                                        <i class="fas fa-times"></i>
                                        Cancelar
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        Guardar Alterações
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Security Tab -->
                    <div id="security" class="tab-content">
                        <div class="tab-header">
                            <div class="tab-header-content">
                                <h2>Segurança da Conta</h2>
                                <p>Gerir as definições de segurança</p>
                            </div>
                            <div class="header-actions">
                                <button type="button" class="btn-save" id="savePasswordBtn" disabled style="opacity: 0.6;">
                                    <i class="fas fa-check"></i>
                                    Confirmar Alteração
                                </button>
                            </div>
                        </div>
                        
                        <div class="section-body">
                            <!-- Password Change Section -->
                            <div class="info-list">
                                <!-- Password Change Form (inline style) -->

                                <div class="info-row">
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <i class="fas fa-key"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Nova Password</span>
                                            <div class="info-edit">
                                                <input type="password" name="new_password" class="inline-input" placeholder="Nova password" required>
                                            </div>
                                        </div>
                                    </div>
                                
                                    <div class="info-item">
                                        <div class="info-icon">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Confirmar Password</span>
                                            <div class="info-edit">
                                                <input type="password" name="confirm_password" class="inline-input" placeholder="Confirmar nova password" required>
                                                <div class="password-match-indicator" id="password-match-message" style="display: none;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>                       
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </main>

    <style>
    .account-container {
        padding: 160px 0 80px 0;
        min-height: 100vh;
        background: #f8f9fa;
        display: flex;
        align-items: center;
    }

    .account-container .container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 40px;
    }

    .account-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 30px;
        align-items: start;
        min-height: 100%; /* Ensure it takes up available space */
    }

    /* Sidebar Styles */
    .account-sidebar {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.06);
        position: sticky;
        top: 180px;
    }

    .user-profile {
        padding: 24px;
        text-align: center;
        border-bottom: 1px solid #e9ecef;
    }

    .user-avatar {
        font-size: 3.5rem;
        color: #3498db;
        margin-bottom: 12px;
    }

    .user-info h3 {
        color: #2c3e50;
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0 0 4px 0;
        line-height: 1.3;
    }

    .user-info p {
        color: #6c757d;
        font-size: 0.8rem;
        margin: 0;
        word-break: break-word;
    }

    .account-nav {
        padding: 8px 0;
    }

    .nav-tab {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        color: #6c757d;
        text-decoration: none;
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
        position: relative;
        font-size: 0.9rem;
    }

    .nav-tab:hover {
        background: #f8f9fa;
        color: #3498db;
        text-decoration: none;
    }

    .nav-tab.active {
        background: #f1f8fe;
        color: #3498db;
        border-left-color: #3498db;
    }

    .nav-tab.external {
        font-size: 0.85rem;
    }

    .nav-tab.external i:last-child {
        margin-left: auto;
        opacity: 0.5;
        font-size: 0.7rem;
    }

    .nav-tab.logout {
        color: #e74c3c;
    }

    .nav-tab.logout:hover {
        background: #fdf2f2;
        color: #c0392b;
    }

    .nav-divider {
        height: 1px;
        background: #e9ecef;
        margin: 8px 20px;
    }

    /* Content Area Styles */
    .account-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.06);
        display: flex;
        flex-direction: column;
        align-self: stretch;
        height: auto; /* Ajustar apenas à altura necessária do conteúdo */
        max-height: 100%;
    }

    .tab-content {
        display: none;
        padding: 25px;
        padding-right: 35px; /* Aumentar para equilibrar com a margem da esquerda */
        flex: 1;
        height: 100%; /* Take full height of parent */
        width: 100%; /* Usar toda a largura disponível */
    }

    .tab-content.active {
        display: flex;
        flex-direction: column;
        flex: 1;
        height: 100%; /* Take full height of parent */
        width: 100%; /* Usar toda a largura disponível */
    }
    
    /* Ocultar "Membro desde" no modo de edição */
    .edit-active .member-since-item {
        display: none !important;
    }

    .tab-header {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f8f9fa;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .tab-header-content {
        flex: 1;
        padding-right: 20px;
    }

    .tab-header h2 {
        color: #2c3e50;
        font-size: 1.75rem;
        font-weight: 600;
        margin: 0 0 6px 0;
    }

    .tab-header p {
        color: #6c757d;
        font-size: 1rem;
        margin: 0;
    }

    .section-header {
        background: #f8f9fa;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e9ecef;
        border: 1px solid #e9ecef;
        border-bottom: none;
        border-radius: 8px 8px 0 0;
        margin-top: 25px;
    }

    .section-header:first-of-type {
        margin-top: 0;
    }

    .section-body {
        padding: 20px;
    }

    /* Profile Header */
    .profile-header {
        display: none;
    }

    .btn-edit {
        background: #3498db;
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.15);
    }

    .btn-edit.cancel-mode {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        box-shadow: 0 2px 8px rgba(231, 76, 60, 0.25);
    }

    .header-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .btn-save {
        background: #27ae60;
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 8px rgba(39, 174, 96, 0.15);
    }
    /* Minimalist Info Layout */
    .info-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
        padding: 5px 0;
        width: 100%;
    }
    
    .info-row {
        display: flex;
        flex-direction: row;
        gap: 25px;
        width: 100%;
        padding-bottom: 18px;
    }
    
    .info-row + .info-row {
        margin-top: 12px;
    }
    
    .info-row:last-child {
        padding-bottom: 0;
    }
    
    .info-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 3px 0;
        transition: all 0.2s ease;
        position: relative;
        flex: 1;
    }
    
  
    
    
    .info-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background-color: rgba(52, 152, 219, 0.1);
        border-radius: 8px;
        color: #3498db;
        font-size: 1.1rem;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }
    
    .info-content {
        display: flex;
        flex-direction: column;
        gap: 5px;
        flex: 1;
    }
    
    .info-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 3px;
        display: block;
        position: relative;
    }
    
    .info-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        transition: color 0.2s ease;
        line-height: 1.4;
        position: relative;
        left: 1px;
    }
    
    /* Estilos para o modo de edição inline */
    .info-edit {
        width: 100%;
    }
    
    .inline-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #dde2e8;
        border-radius: 6px;
        font-size: 0.95rem;
        color: #1e293b;
        background: #f8fafc;
        transition: all 0.2s ease;
        margin-bottom: 5px;
    }
    
    .inline-input:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.15);
        outline: none;
        background: #ffffff;
    }
    
    /* Estilos específicos para o campo de telefone */
    .iti {
        width: 100%;
        margin-bottom: 5px;
    }
    
    .iti__flag-container {
        z-index: 10;
    }
    
    input[name="phone"] {
        padding-left: 90px !important;
    }
    
    input[name="phone"].error {
        border-color: #e74c3c;
    }
    
    .info-edit input + input:not(.name-input) {
        margin-top: 5px;
    }
    
    /* Estilos para inputs de nome lado a lado */
    .name-inputs-row {
        display: flex;
        gap: 10px;
        width: 100%;
    }
    
    .name-input {
        width: calc(50% - 5px);
        padding: 6px 10px;
        height: auto;
        min-height: unset;
    }
    
    /* Estilos para a seção de segurança */
    .password-display {
        color: #64748b;
        font-family: monospace;
        letter-spacing: 2px;
    }
    
    .password-match-indicator {
        font-size: 0.8rem;
        margin-top: 3px;
        transition: all 0.3s ease;
        display: none; /* Inicialmente oculto até ser necessário */
        clear: both;
        padding: 3px 5px;
        min-height: 0; /* Prevenir que ocupe espaço quando vazio */
    }
    
    .password-match-indicator.success {
        color: #27ae60;
        background-color: rgba(39, 174, 96, 0.1);
        border-radius: 4px;
        padding-left: 5px;
    }
    
    .password-match-indicator.error {
        color: #e74c3c;
        background-color: rgba(231, 76, 60, 0.1);
        border-radius: 4px;
        padding-left: 5px;
    }
    
    .security-info-header {
        margin-top: 30px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eaedf0;
        display: flex;
        align-items: center;
    }
    
    .security-info-header h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
        padding-left: 10px;
        position: relative;
    }
    
    .security-info-header h3::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 16px;
        background: #3498db;
        border-radius: 2px;
    }
    
    .security-info-list {
        padding-top: 5px;
    }
    
    .security-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .security-badge.verified {
        background-color: rgba(39, 174, 96, 0.1);
        color: #27ae60;
    }
    
    .info-icon.success-icon {
        background-color: rgba(39, 174, 96, 0.1);
        color: #27ae60;
    }
    
    .info-icon.info-icon {
        background-color: rgba(52, 152, 219, 0.1);
        color: #3498db;
    }
    
    .info-description {
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 3px;
        display: block;
    }
    
    .inline-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        width: 100%;
        margin-top: 10px;
    }
    
    .btn-primary-sm, .btn-secondary-sm {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
    }
    
    .btn-primary-sm {
        background: #3498db;
        color: white;
        border: none;
        box-shadow: 0 2px 5px rgba(52, 152, 219, 0.2);
    }
    
    .btn-primary-sm:hover {
        background: #2980b9;
        transform: translateY(-1px);
    }
    
    .btn-secondary-sm {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #dde2e8;
    }
    
    .btn-secondary-sm:hover {
        background: #e2e8f0;
        color: #475569;
    }

    /* Enhanced Form Styles */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 32px;
        margin-bottom: 32px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 32px;
        position: relative;
    }

    .form-group label {
        font-size: 0.9rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-group label::before {
        content: '';
        width: 3px;
        height: 16px;
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        border-radius: 2px;
    }

    .form-group input {
        padding: 20px 24px;
        border: 2px solid #e9ecef;
        border-radius: 14px;
        font-size: 1.1rem;
        color: #2c3e50;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        min-height: 64px;
        background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
        font-weight: 500;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        position: relative;
    }

    .form-group input:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.15);
        transform: translateY(-3px);
        background: #ffffff;
    }

    .form-group input:hover {
        border-color: #bdc3c7;
        background: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .form-group input::placeholder {
        color: #95a5a6;
        font-style: italic;
        font-weight: 400;
    }

    .input-with-icon {
        position: relative;
    }

    .input-with-icon input {
        padding-left: 64px;
    }

    .input-with-icon i {
        position: absolute;
        left: 24px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        font-size: 1.2rem;
        transition: all 0.3s ease;
        background: rgba(108, 117, 125, 0.1);
        padding: 8px;
        border-radius: 8px;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .input-with-icon input:focus + i {
        color: #3498db;
        background: rgba(52, 152, 219, 0.1);
        transform: translateY(-50%) scale(1.1);
    }

    .form-actions {
        display: flex;
        gap: 20px;
        justify-content: flex-end;
        padding-top: 32px;
        border-top: 2px solid #f1f3f4;
        margin-top: 28px;
        position: relative;
    }

    .form-actions::before {
        content: '';
        position: absolute;
        top: -2px;
        left: 0;
        width: 60px;
        height: 2px;
        background: linear-gradient(90deg, #3498db 0%, transparent 100%);
    }

    .btn {
        padding: 16px 32px;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        font-size: 1rem;
        min-height: 56px;
        min-width: 170px;
        justify-content: center;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
        overflow: hidden;
    }

    .btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: all 0.6s ease;
    }

    .btn:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        color: white;
        box-shadow: 0 4px 20px rgba(39, 174, 96, 0.3);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #229954 0%, #1e8449 100%);
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(39, 174, 96, 0.4);
    }

    .btn-secondary {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #6c757d;
        border: 2px solid #e9ecef;
        box-shadow: 0 4px 15px rgba(108, 117, 125, 0.2);
    }

    .btn-secondary:hover {
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        color: #495057;
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(108, 117, 125, 0.3);
        border-color: #bdc3c7;
    }

    /* Responsive Design Updates */
    @media (max-width: 992px) {
        .account-container {
            padding: 140px 0 40px 0;
        }

        .account-container .container {
            padding: 0 20px;
        }

        .account-layout {
            grid-template-columns: 1fr;
            gap: 20px;
            align-items: stretch;
        }

        .account-sidebar {
            position: static;
        }

        .account-content {
            align-self: auto;
        }

        .section-body {
            padding: 25px;
        }

        .tab-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .header-actions {
            width: 100%;
            justify-content: flex-end;
        }
        
        .btn-save {
            padding: 8px 16px;
        }

        .info-list {
            gap: 20px;
        }
        
        .info-row {
            gap: 20px;
            padding-bottom: 18px;
        }
        
        .info-row + .info-row {
            margin-top: 12px;
        }
        
        .info-item {
            padding: 8px 0;
            gap: 15px;
        }
        
        .info-icon {
            width: 36px;
            height: 36px;
            font-size: 1.1rem;
        }
        
        .info-value {
            font-size: 1.05rem;
        }
        
        .inline-input {
            padding: 6px 10px;
            font-size: 0.9rem;
        }
        
        .inline-actions {
            justify-content: flex-end;
        }

        .form-grid {
            grid-template-columns: 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group input {
            min-height: 55px;
            padding: 16px 20px;
            font-size: 1.05rem;
        }

        .input-with-icon input {
            padding-left: 55px;
        }

        .input-with-icon i {
            left: 20px;
            font-size: 1.1rem;
        }

        .btn {
            min-height: 50px;
            font-size: 0.95rem;
            padding: 14px 28px;
        }
    }

    @media (max-width: 768px) {
        .account-container {
            padding: 140px 0 30px 0;
        }

        .account-container .container {
            padding: 0 15px;
        }

        .user-profile {
            padding: 20px 16px 16px;
        }

        .tab-header h2 {
            font-size: 1.3rem;
        }
        
        .tab-header {
            margin-bottom: 15px;
            padding-bottom: 12px;
        }

        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .section-body {
            padding: 15px;
        }

        .btn-edit, .btn-save {
            flex: 1;
            justify-content: center;
        }
        
        .header-actions {
            width: 100%;
            display: flex;
            gap: 8px;
        }

        .info-list {
            gap: 15px;
        }
        
        .info-row {
            flex-direction: column;
            gap: 15px;
            padding-bottom: 15px;
        }
        
        .info-row + .info-row {
            margin-top: 10px;
            padding-bottom: 15px;
        }
        
        .info-item {
            padding: 8px 0;
            gap: 12px;
        }
        
        .info-icon {
            width: 32px;
            height: 32px;
            font-size: 1rem;
        }
        
        .info-label {
            font-size: 0.7rem;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            color: #64748b;
        }
        
        .info-value {
            font-size: 1.05rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .info-content {
            gap: 4px;
        }
        
        .inline-input {
            padding: 6px 10px;
            font-size: 0.9rem;
        }
        
        .btn-primary-sm, .btn-secondary-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        
        .inline-actions {
            justify-content: space-between;
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .account-container .container {
            padding: 0 10px;
        }

        .tab-content {
            padding: 15px;
        }

        .section-body {
            padding: 15px;
        }

        .profile-header {
            margin-bottom: 18px;
        }

        .user-info h3 {
            font-size: 1rem;
        }

        .nav-tab {
            padding: 10px 16px;
            font-size: 0.85rem;
        }

        .info-list {
            gap: 10px;
        }
        
        .info-row {
            gap: 10px;
            padding-bottom: 14px;
        }
        
        .info-row + .info-row {
            margin-top: 8px;
        }
        
        .info-item {
            padding: 5px 0;
            gap: 10px;
        }
        
        .info-icon {
            width: 28px;
            height: 28px;
            font-size: 0.9rem;
        }
        
        .info-label {
            font-size: 0.65rem;
            letter-spacing: 0.4px;
            margin-bottom: 1px;
            color: #64748b;
        }
        
        .info-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .info-content {
            gap: 3px;
        }
        
        .inline-input {
            padding: 5px 8px;
            font-size: 0.85rem;
            margin-bottom: 4px;
        }
        
        .name-inputs-row {
            gap: 6px;
        }
        
        .btn-primary-sm, .btn-secondary-sm {
            padding: 5px 10px;
            font-size: 0.75rem;
            width: calc(50% - 5px);
        }
        
        .inline-actions {
            justify-content: space-between;
            width: 100%;
            flex-wrap: wrap;
        }

        .btn {
            padding: 13px 22px;
            min-height: 46px;
            font-size: 0.85rem;
            min-width: 130px;
        }

        .form-actions {
            padding-top: 22px;
            margin-top: 18px;
        }
    }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Tab navigation
        $('.nav-tab:not(.external):not(.logout)').click(function(e) {
            e.preventDefault();
            const targetTab = $(this).data('tab');
            
            // Update active states
            $('.nav-tab').removeClass('active');
            $(this).addClass('active');
            
            // Show target content
            $('.tab-content').removeClass('active');
            $(`#${targetTab}`).addClass('active');
            
            // Re-equalize heights after tab change
            setTimeout(equalizeContentHeight, 10); // Small delay to ensure DOM is updated
        });

        // Profile editing - modo inline
        $('#editProfileBtn').click(function() {
            // Alternar entre modos de exibição e edição
            if ($(this).hasClass('cancel-mode')) {
                // Se estiver no modo de cancelar, voltar para o modo de exibição
                exitEditMode();
            } else {
                // Se estiver no modo de exibição, mudar para o modo de edição
                enterEditMode();
            }
        });
        
        // Função para entrar no modo de edição
        function enterEditMode() {
            $('.display-mode').hide();
            $('.edit-mode').show();
            $('#saveProfileBtn').show();
            $('#editProfileBtn').html('<i class="fas fa-times"></i> Cancelar');
            $('#editProfileBtn').addClass('cancel-mode');
            $('.personal-info').addClass('edit-active');
        }
        
        // Função para sair do modo de edição
        function exitEditMode(skipReset = false) {
            // Obter os valores atuais de exibição para uso posterior
            const currentName = $('.info-item:contains("Nome") .info-value').text().trim();
            const currentEmail = $('.info-item:contains("Email") .info-value').text().trim();
            const currentPhone = $('.info-item:contains("Telefone") .info-value').text().trim();
            
            $('.display-mode').show();
            $('.edit-mode').hide();
            $('#saveProfileBtn').hide();
            $('#editProfileBtn').html('<i class="fas fa-edit"></i> Editar Perfil');
            $('#editProfileBtn').removeClass('cancel-mode');
            $('.personal-info').removeClass('edit-active');
            
            // Reset dos valores dos inputs para os valores originais, a menos que skipReset seja true
            if (!skipReset) {
                // Resetar o formulário, mas não perder valores que foram atualizados via Ajax
                const form = $('#inlineProfileForm')[0];
                form.reset();
                
                // Se tivermos valores que foram atualizados (exibição), atualizamos os inputs também
                if (currentName && currentName !== 'Não definido') {
                    const nameParts = currentName.split(' ');
                    if (nameParts.length >= 2) {
                        const firstName = nameParts[0];
                        const lastName = nameParts.slice(1).join(' ');
                        $('input[name="first_name"]').val(firstName);
                        $('input[name="last_name"]').val(lastName);
                    }
                }
                
                if (currentEmail && currentEmail !== 'Não definido') {
                    $('input[name="email"]').val(currentEmail);
                }
                
                if (currentPhone && currentPhone !== 'Não definido') {
                    // Atualizar o valor do telefone para o mais recente
                    const phoneInput = $('input[name="phone"]');
                    phoneInput.val(currentPhone);
                    
                    // Se tivermos intlTelInput ativo, tentar atualizar seu valor
                    if (window.iti) {
                        try {
                            window.iti.setNumber(currentPhone);
                        } catch (e) {
                            console.warn('Não foi possível atualizar o intlTelInput:', e);
                        }
                    }
                }
            }
        }
        
        // Botão de salvar na barra superior
        $('#saveProfileBtn').click(function() {
            $('#inlineProfileForm').submit();
        });
        
        // Envio do formulário inline
        $('#inlineProfileForm').submit(function(e) {
            e.preventDefault();
            
            $.ajax({
                url: 'ajax/update_profile.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        showNotification('Perfil atualizado com sucesso!', 'success');
                        
                        // Atualizar os valores exibidos sem recarregar a página
                        const firstName = $('input[name="first_name"]').val();
                        const lastName = $('input[name="last_name"]').val();
                        const email = $('input[name="email"]').val();
                        
                        // Obter o número de telefone formatado do campo de telefone
                        let phone;
                        const phoneInput = $('input[name="phone"]');
                        if (window.iti && phoneInput.val() && phoneInput.val().trim()) {
                            // Se temos o plugin intlTelInput ativo, usamos seu método getNumber()
                            try {
                                phone = window.iti.getNumber();
                            } catch(e) {
                                console.warn('Não foi possível obter o número formatado:', e);
                                phone = phoneInput.val();
                            }
                        } else if (phoneInput.val() && phoneInput.val().trim()) {
                            phone = phoneInput.val();
                        } else {
                            phone = 'Não definido';
                        }
                        
                        // Atualizar os valores exibidos
                        $('.info-item:contains("Nome") .info-value').text(firstName + ' ' + lastName);
                        $('.info-item:contains("Email") .info-value').text(email);
                        $('.info-item:contains("Telefone") .info-value').text(phone);
                        
                        // Voltar para o modo de exibição usando a função existente
                        exitEditMode();
                        
                        // Atualizar também as informações do usuário no topo do sidebar
                        $('.user-info h3').text(firstName + ' ' + lastName);
                        $('.user-info p').text(email);
                    } else {
                        showNotification(response.message || 'Erro ao atualizar perfil', 'error');
                    }
                },
                error: function() {
                    showNotification('Erro de conexão ao atualizar o perfil', 'error');
                }
            });
        });
        
        // O form antigo foi removido, mas mantemos este código caso precise voltar
        $('#profileForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'ajax/update_profile.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        showNotification('Perfil atualizado com sucesso!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(response.message || 'Erro ao atualizar perfil', 'error');
                    }
                }
            });
        });
        
        // Função para validar se as passwords coincidem
        function validatePasswordMatch() {
            const newPass = $('input[name="new_password"]').val();
            const confirmPass = $('input[name="confirm_password"]').val();
            const messageEl = $('#password-match-message');
            const saveBtn = $('#savePasswordBtn');
            
            if(newPass && confirmPass) {
                // Certifique-se de que o elemento é visível quando temos valores para ambas as senhas
                messageEl.show();
                
                if(newPass === confirmPass) {
                    $('input[name="confirm_password"]').css('border-color', '#27ae60');
                    $('input[name="new_password"]').css('border-color', '#27ae60');
                    messageEl.html('<i class="fas fa-check-circle"></i> As passwords coincidem').removeClass('error').addClass('success');
                    saveBtn.prop('disabled', false).css('opacity', 1);
                    return true;
                } else {
                    $('input[name="confirm_password"]').css('border-color', '#e74c3c');
                    $('input[name="new_password"]').css('border-color', '');
                    messageEl.html('<i class="fas fa-exclamation-circle"></i> As passwords não coincidem').removeClass('success').addClass('error');
                    saveBtn.prop('disabled', true).css('opacity', 0.6);
                    return false;
                }
            } else {
                // Esconder o elemento quando um dos campos está vazio
                messageEl.hide().text('');
                $('input[name="confirm_password"]').css('border-color', '');
                $('input[name="new_password"]').css('border-color', '');
                saveBtn.prop('disabled', true).css('opacity', 0.6);
            }
            return false;
        }
        
        // Adicionar eventos de validação assim que o documento estiver pronto
        $('input[name="confirm_password"]').on('keyup', validatePasswordMatch);
        $('input[name="new_password"]').on('keyup', validatePasswordMatch);
        
        // Validar as senhas inicialmente
        validatePasswordMatch();
        
        // Botão de salvar password
        $('#savePasswordBtn').click(function() {
            // Validar as passwords antes de enviar
            const newPass = $('input[name="new_password"]').val();
            const confirmPass = $('input[name="confirm_password"]').val();
            
            if(!newPass || !confirmPass) {
                showNotification('Por favor, preencha ambos os campos de password', 'error');
                return;
            }
            
            if(newPass !== confirmPass) {
                showNotification('As passwords não coincidem', 'error');
                return;
            }
            
            // Enviar diretamente os valores dos campos
            $.ajax({
                url: 'ajax/change_password.php',
                method: 'POST',
                data: {
                    new_password: newPass,
                    confirm_password: confirmPass
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        showNotification('Password alterada com sucesso!', 'success');
                        // Limpar os campos de senha após alteração bem-sucedida
                        $('input[name="new_password"]').val('');
                        $('input[name="confirm_password"]').val('');
                        $('input[name="confirm_password"]').css('border-color', '');
                        $('input[name="new_password"]').css('border-color', '');
                        $('#password-match-message').text('').removeClass('success').removeClass('error').hide();
                        $('#savePasswordBtn').prop('disabled', true).css('opacity', 0.6);
                    } else {
                        showNotification(response.message || 'Erro ao alterar password', 'error');
                    }
                },
                error: function() {
                    showNotification('Erro de conexão ao alterar a password', 'error');
                }
            });
        });
        
        // O formulário de password agora é manipulado diretamente pelo botão savePasswordBtn
        
        // Notification system
        function showNotification(message, type) {
            const notification = $(`
                <div class="notification ${type}">
                    <span>${message}</span>
                    <button class="notification-close">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
            
            notification.find('.notification-close').click(() => {
                notification.remove();
            });
        }

        // Function to equalize account-content height with account-nav
        function equalizeContentHeight() {
            // Ajustar espaçamento para layout minimalista
            $('.info-list').css('padding-right', '5px');
            
            // Ajustar espaçamento entre itens em linhas horizontais
            if ($(window).width() > 768) {
                $('.info-row').css('justify-content', 'space-between');
                $('.info-item').css('flex-basis', 'calc(50% - 12px)');
            } else {
                $('.info-item').css('flex-basis', 'auto');
            }
            
            // Reduzir a altura do content para se igualar ao sidebar
            const sidebarHeight = $('.account-sidebar').outerHeight();
            if (sidebarHeight) {
                // Definir altura máxima igual ao sidebar
                $('.account-content').css({
                    'height': 'auto',
                    'max-height': sidebarHeight + 'px'
                });
                
                // Removemos o overflow-y para evitar a barra de rolagem
                $('.tab-content.active').css({
                    'overflow-y': 'visible'
                });
            }
            
            // Garantir que o tab content tenha margens iguais
            if ($(window).width() > 992) {
                $('.tab-content').css('padding-right', '35px');
            } else {
                $('.tab-content').css('padding-right', '25px');
            }
        }

        // Execute on page load, window resize, and after window load completes
        equalizeContentHeight();
        $(window).on('resize', equalizeContentHeight);
        $(window).on('load', equalizeContentHeight);
    });
    </script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar o intl-tel-input para o campo de telefone
        const phoneInputField = document.querySelector("input[name='phone']");
        let iti;
        
        if (phoneInputField) {
            iti = window.intlTelInput(phoneInputField, {
                initialCountry: "pt", // Definir Portugal como país padrão
                separateDialCode: false, // Não separar o código - será parte do input
                nationalMode: false, // Show dial code in the input
                autoPlaceholder: "off", // Desligar o placeholder automático para usar nosso formato personalizado
                customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
                    // Para Portugal, usar nosso formato personalizado
                    if (selectedCountryData.iso2 === 'pt') {
                        return "+351 9xx xxx xxx";
                    }
                    return selectedCountryPlaceholder;
                },
                placeholderNumberType: "MOBILE", // Show mobile number placeholder 
                preferredCountries: ['pt', 'br', 'es', 'fr', 'de', 'gb'], // Países preferidos no topo
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
            });

            // Disponibilizar globalmente para acesso por outras funções
            window.iti = iti;

            // Esperar pelo carregamento do plugin e scripts utils
            iti.promise.then(function() {
                
                // Set initial value with dial code for Portugal
                if (phoneInputField.value.trim() === '') {
                    const countryData = iti.getSelectedCountryData();
                    const dialCode = '+' + countryData.dialCode;
                    phoneInputField.value = dialCode + ' ';
                    
                    // Definir o placeholder customizado
                    if (countryData.iso2 === 'pt') {
                        phoneInputField.setAttribute('placeholder', '+351 9xx xxx xxx');
                    }
                }

                // Listener for blur to validate
                phoneInputField.addEventListener('blur', function() {
                    this.classList.remove('error');
                    const dialCode = '+' + iti.getSelectedCountryData().dialCode;
                    // Only validate if the user has entered some digits beyond the dial code
                    if (iti.getNumber().length > dialCode.length && !iti.isValidNumber()) {
                        this.classList.add('error');
                    }
                });

                // Listener para quando o usuário muda o país
                phoneInputField.addEventListener("countrychange", function() {
                    // Atualizar o prefixo do país no valor do input
                    const selectedCountryData = iti.getSelectedCountryData();
                    const dialCode = '+' + selectedCountryData.dialCode;
                    
                    // Se o input estiver vazio ou tiver apenas o código antigo, inserir novo código
                    if (!phoneInputField.value || phoneInputField.value.trim() === '' || 
                        phoneInputField.value.match(/^\+\d+$/)) {
                        phoneInputField.value = dialCode + ' ';
                    }
                });

                // Listener para formatar o número durante a digitação
                phoneInputField.addEventListener('input', function(e) {
                    const target = e.target;
                    const currentText = target.value;
                    const countryData = iti.getSelectedCountryData();
                    const dialCode = '+' + countryData.dialCode;
                    
                    // Se o usuário estiver tentando apagar o código do país, impedir
                    if (!currentText.includes('+')) {
                        target.value = dialCode + ' ';
                        return;
                    }

                    // Apply Portugal-specific formatting
                    if (countryData.iso2 === 'pt') {
                        // Extrair apenas os números após o código do país
                        const rawInput = currentText.replace(/\D/g, '');
                        const countryCode = countryData.dialCode;
                        
                        // Remover o código do país dos dígitos (se estiver presente)
                        let phoneDigits = '';
                        if (rawInput.startsWith(countryCode)) {
                            phoneDigits = rawInput.substring(countryCode.length);
                        } else {
                            phoneDigits = rawInput;
                        }
                        
                        // Limitar a exatamente 9 dígitos para números portugueses
                        phoneDigits = phoneDigits.substring(0, 9);
                        
                        // Formatar com espaços a cada 3 dígitos (formato: 963 963 963)
                        let formattedNumber = '';
                        for (let i = 0; i < phoneDigits.length; i++) {
                            if (i > 0 && i % 3 === 0) {
                                formattedNumber += ' ';
                            }
                            formattedNumber += phoneDigits[i];
                        }
                        
                        // Construir o valor final com código do país
                        const newValue = dialCode + ' ' + formattedNumber;
                        
                        // Atualizar o valor do input se for diferente
                        if (target.value !== newValue) {
                            target.value = newValue;
                            
                            // Posicionar o cursor no final do input se estiver ativamente digitando
                            if (document.activeElement === target) {
                                const end = target.value.length;
                                target.setSelectionRange(end, end);
                            }
                        }
                    }
                });
            });
            
            // Atualizar o manipulador de eventos de submit no formulário de perfil
            $('#inlineProfileForm').on('submit', function(e) {
                if (iti && phoneInputField.value.trim()) {
                    if (!iti.isValidNumber()) {
                        e.preventDefault(); // Impedir envio do formulário
                        phoneInputField.classList.add('error');
                        showNotification('Número de telemóvel inválido.', 'error');
                        return false;
                    }
                    // Atualizar o valor do input de telefone para o número internacional completo
                    phoneInputField.value = iti.getNumber();
                }
            });
        }
    });
    </script>

</body>
</html>