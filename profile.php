<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/config/db.php';

// Inicializar variáveis
$success = '';
$error = '';
$user = null;

// Buscar dados do usuário
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION = array();
        session_destroy();
        header("Location: login.php");
        exit();
    }
} catch (Exception $e) {
    $error = "Erro ao carregar perfil: " . $e->getMessage();
}

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $country = trim($_POST['country']);
    
    // Validações
    if (empty($name)) {
        $error = "O nome é obrigatório";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email inválido";
    } else {
        // Verificar se o email já está em uso por outro usuário
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $error = "Este email já está sendo usado por outro usuário";
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, phone = ?, address = ?, 
                        city = ?, postal_code = ?, country = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $email, $phone, $address, 
                    $city, $postal_code, $country, 
                    $_SESSION['user_id']
                ]);
                
                $success = "Perfil atualizado com sucesso!";
                
                // Recarregar dados do usuário
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                // Atualizar sessão se necessário
                if (isset($user['username'])) {
                    $_SESSION['username'] = $user['username'];
                }
            } catch (Exception $e) {
                $error = "Erro ao atualizar perfil: " . $e->getMessage();
            }
        }
    }
}

// Processar mudança de senha
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Todos os campos de senha são obrigatórios";
    } elseif ($new_password !== $confirm_password) {
        $error = "As novas senhas não coincidem";
    } elseif (strlen($new_password) < 6) {
        $error = "A nova senha deve ter pelo menos 6 caracteres";
    } else {
        // Verificar senha atual
        // Suportar ambos MD5 (sistema antigo) e password_hash (novo)
        $isPasswordCorrect = false;
        
        if (strlen($user['password']) == 32) {
            // Sistema antigo com MD5
            $isPasswordCorrect = ($user['password'] === md5($current_password));
        } else {
            // Sistema novo com password_hash
            $isPasswordCorrect = password_verify($current_password, $user['password']);
        }
        
        if (!$isPasswordCorrect) {
            $error = "Senha atual incorreta";
        } else {
            try {
                // Usar password_hash para a nova senha
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                
                $success = "Senha alterada com sucesso!";
                
                // Recarregar dados do usuário
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            } catch (Exception $e) {
                $error = "Erro ao alterar senha: " . $e->getMessage();
            }
        }
    }
}

// Calcular quantidade de itens no carrinho
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['quantity'])) {
            $cartCount += (int)$item['quantity'];
        }
    }
}

// Contar tickets não lidos
$unreadTickets = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread FROM contacts WHERE user_id = ? AND customer_unread = 1");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $unreadTickets = $result['unread'];
} catch (Exception $e) {
    $unreadTickets = 0;
}

// Buscar estatísticas do usuário
$totalOrders = 0;
$totalSpent = 0;
$totalTickets = 0;

try {
    // Total de pedidos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $totalOrders = $stmt->fetch()['total'];
    
    // Total gasto
    $stmt = $pdo->prepare("SELECT SUM(total) as spent FROM orders WHERE user_id = ? AND status != 'cancelled'");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $totalSpent = $result['spent'] ? $result['spent'] : 0;
    
    // Total de tickets
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM contacts WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $totalTickets = $stmt->fetch()['total'];
} catch (Exception $e) {
    // Se as tabelas não existirem, manter valores zerados
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - TechShop</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/profile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dropdown.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="logo">
                <h1><a href="index.php" style="text-decoration: none; color: inherit;">TechShop</a></h1>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Produtos</a></li>
                <li><a href="about.php">Sobre</a></li>
                <li><a href="contact.php">Contacto</a></li>
            </ul>

            <div class="nav-icons">
                <a href="#" class="search-icon"><i class="fas fa-search"></i></a>
                <a href="javascript:void(0);" class="cart-icon" id="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                </a>
                <div class="user-dropdown">
                    <a href="#" class="user-icon">
                        <i class="fas fa-user"></i> 
                        <span class="user-name"><?php echo htmlspecialchars($user['username'] ?? $user['name']); ?></span>
                    </a>
                    <div class="dropdown-content">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="backoffice/backoffice.php"><i class="fas fa-user-shield"></i> Admin</a>
                        <?php else: ?>
                            <a href="profile.php" class="active"><i class="fas fa-user-circle"></i> Perfil</a>
                            <a href="orders.php"><i class="fas fa-shopping-bag"></i> Pedidos</a>
                            <a href="wishlist.php"><i class="fas fa-heart"></i> Favoritos</a>
                            <a href="my_tickets.php">
                                <i class="fas fa-ticket-alt"></i> Meus Tickets
                                <?php if ($unreadTickets > 0): ?>
                                    <span class="badge-notification"><?php echo $unreadTickets; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Profile Content -->
    <div class="profile-container">
        <div class="profile-wrapper">
            <!-- Sidebar -->
            <aside class="profile-sidebar">
                <div class="profile-avatar">
                    <div class="avatar-circle">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                        <span class="badge-admin"><i class="fas fa-crown"></i> Administrador</span>
                    <?php endif; ?>
                </div>

                <nav class="profile-nav">
                    <a href="#info" class="profile-nav-item active" data-tab="info">
                        <i class="fas fa-user-circle"></i>
                        <span>Informações Pessoais</span>
                    </a>
                    <a href="#security" class="profile-nav-item" data-tab="security">
                        <i class="fas fa-lock"></i>
                        <span>Segurança</span>
                    </a>
                    <a href="#stats" class="profile-nav-item" data-tab="stats">
                        <i class="fas fa-chart-line"></i>
                        <span>Estatísticas</span>
                    </a>
                </nav>

                <div class="profile-quick-stats">
                    <div class="quick-stat">
                        <i class="fas fa-shopping-bag"></i>
                        <div>
                            <strong><?php echo $totalOrders; ?></strong>
                            <span>Pedidos</span>
                        </div>
                    </div>
                    <div class="quick-stat">
                        <i class="fas fa-ticket-alt"></i>
                        <div>
                            <strong><?php echo $totalTickets; ?></strong>
                            <span>Tickets</span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="profile-main">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Tab: Informações Pessoais -->
                <div class="profile-tab active" id="tab-info">
                    <div class="profile-card">
                        <div class="card-header">
                            <h2><i class="fas fa-user-edit"></i> Informações Pessoais</h2>
                            <p>Atualize seus dados pessoais e informações de contato</p>
                        </div>
                        <form method="POST" action="profile.php" class="profile-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">
                                        <i class="fas fa-user"></i> Nome Completo *
                                    </label>
                                    <input 
                                        type="text" 
                                        id="name" 
                                        name="name" 
                                        value="<?php echo htmlspecialchars($user['name']); ?>" 
                                        required
                                        class="form-control"
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="email">
                                        <i class="fas fa-envelope"></i> Email *
                                    </label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        value="<?php echo htmlspecialchars($user['email']); ?>" 
                                        required
                                        class="form-control"
                                    >
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone">
                                        <i class="fas fa-phone"></i> Telefone
                                    </label>
                                    <input 
                                        type="tel" 
                                        id="phone" 
                                        name="phone" 
                                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                        class="form-control"
                                        placeholder="+351 123 456 789"
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="country">
                                        <i class="fas fa-globe"></i> País
                                    </label>
                                    <input 
                                        type="text" 
                                        id="country" 
                                        name="country" 
                                        value="<?php echo htmlspecialchars($user['country'] ?? 'Portugal'); ?>"
                                        class="form-control"
                                    >
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">
                                    <i class="fas fa-map-marker-alt"></i> Morada
                                </label>
                                <input 
                                    type="text" 
                                    id="address" 
                                    name="address" 
                                    value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"
                                    class="form-control"
                                    placeholder="Rua, Número, Andar"
                                >
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">
                                        <i class="fas fa-city"></i> Cidade
                                    </label>
                                    <input 
                                        type="text" 
                                        id="city" 
                                        name="city" 
                                        value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>"
                                        class="form-control"
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="postal_code">
                                        <i class="fas fa-mail-bulk"></i> Código Postal
                                    </label>
                                    <input 
                                        type="text" 
                                        id="postal_code" 
                                        name="postal_code" 
                                        value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>"
                                        class="form-control"
                                        placeholder="0000-000"
                                    >
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tab: Segurança -->
                <div class="profile-tab" id="tab-security">
                    <div class="profile-card">
                        <div class="card-header">
                            <h2><i class="fas fa-shield-alt"></i> Alterar Senha</h2>
                            <p>Mantenha sua conta segura com uma senha forte</p>
                        </div>
                        <form method="POST" action="profile.php" class="profile-form">
                            <div class="form-group">
                                <label for="current_password">
                                    <i class="fas fa-key"></i> Senha Atual *
                                </label>
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password" 
                                    required
                                    class="form-control"
                                    placeholder="Digite sua senha atual"
                                >
                            </div>

                            <div class="form-group">
                                <label for="new_password">
                                    <i class="fas fa-lock"></i> Nova Senha *
                                </label>
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    required
                                    class="form-control"
                                    placeholder="Mínimo 6 caracteres"
                                    minlength="6"
                                >
                                <small class="form-hint">A senha deve ter pelo menos 6 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-lock"></i> Confirmar Nova Senha *
                                </label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    required
                                    class="form-control"
                                    placeholder="Digite a senha novamente"
                                    minlength="6"
                                >
                            </div>

                            <div class="security-tips">
                                <h4><i class="fas fa-info-circle"></i> Dicas de Segurança</h4>
                                <ul>
                                    <li>Use uma senha única que não utiliza em outros sites</li>
                                    <li>Combine letras maiúsculas, minúsculas, números e símbolos</li>
                                    <li>Evite informações pessoais óbvias</li>
                                    <li>Altere sua senha regularmente</li>
                                </ul>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Alterar Senha
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tab: Estatísticas -->
                <div class="profile-tab" id="tab-stats">
                    <div class="profile-card">
                        <div class="card-header">
                            <h2><i class="fas fa-chart-bar"></i> Suas Estatísticas</h2>
                            <p>Visão geral da sua atividade na loja</p>
                        </div>

                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $totalOrders; ?></h3>
                                    <p>Total de Pedidos</p>
                                </div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <i class="fas fa-euro-sign"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo number_format($totalSpent, 2, ',', '.'); ?>€</h3>
                                    <p>Total Gasto</p>
                                </div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $totalTickets; ?></h3>
                                    <p>Tickets Abertos</p>
                                </div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : 'N/A'; ?></h3>
                                    <p>Membro Desde</p>
                                </div>
                            </div>
                        </div>

                        <div class="account-info">
                            <h3><i class="fas fa-user-shield"></i> Informações da Conta</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">ID do Usuário:</span>
                                    <span class="info-value">#<?php echo $user['id']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Username:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Tipo de Conta:</span>
                                    <span class="info-value">
                                        <?php 
                                        if (isset($user['role'])) {
                                            echo $user['role'] === 'admin' ? 
                                                '<span class="badge-admin-inline">Administrador</span>' : 
                                                '<span class="badge-user-inline">Utilizador</span>';
                                        } elseif (isset($user['is_admin']) && $user['is_admin']) {
                                            echo '<span class="badge-admin-inline">Administrador</span>';
                                        } else {
                                            echo '<span class="badge-user-inline">Utilizador</span>';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Última Atualização:</span>
                                    <span class="info-value">
                                        <?php echo isset($user['updated_at']) ? date('d/m/Y H:i', strtotime($user['updated_at'])) : 'N/A'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="quick-actions">
                            <h3><i class="fas fa-bolt"></i> Ações Rápidas</h3>
                            <div class="actions-grid">
                                <a href="orders.php" class="action-btn">
                                    <i class="fas fa-shopping-bag"></i>
                                    <span>Ver Pedidos</span>
                                </a>
                                <a href="my_tickets.php" class="action-btn">
                                    <i class="fas fa-ticket-alt"></i>
                                    <span>Meus Tickets</span>
                                </a>
                                <a href="products.php" class="action-btn">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span>Continuar Comprando</span>
                                </a>
                                <a href="contact.php" class="action-btn">
                                    <i class="fas fa-headset"></i>
                                    <span>Suporte</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>TechShop</h3>
                <p>A sua loja de tecnologia de confiança</p>
            </div>
            <div class="footer-section">
                <h4>Links Rápidos</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Produtos</a></li>
                    <li><a href="about.php">Sobre</a></li>
                    <li><a href="contact.php">Contacto</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Suporte</h4>
                <ul>
                    <li><a href="my_tickets.php">Meus Tickets</a></li>
                    <li><a href="orders.php">Meus Pedidos</a></li>
                    <li><a href="contact.php">Ajuda</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Contacto</h4>
                <p><i class="fas fa-envelope"></i> suporte@techshop.com</p>
                <p><i class="fas fa-phone"></i> +351 123 456 789</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 TechShop. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Tab Navigation
        document.querySelectorAll('.profile-nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all items
                document.querySelectorAll('.profile-nav-item').forEach(nav => {
                    nav.classList.remove('active');
                });
                
                // Remove active class from all tabs
                document.querySelectorAll('.profile-tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Add active class to clicked item
                this.classList.add('active');
                
                // Show corresponding tab
                const tabId = this.getAttribute('data-tab');
                document.getElementById('tab-' + tabId).classList.add('active');
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Password confirmation validation
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
</body>
</html>
