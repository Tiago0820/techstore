<?php
session_start();
require_once 'config/db.php';

// Inicializar carrinho
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Calcular quantidade de itens no carrinho
$cartCount = 0;
foreach ($_SESSION['cart'] as $item) {
    if (isset($item['quantity'])) {
        $cartCount += (int)$item['quantity'];
    }
}

// Contar tickets não lidos (se o utilizador estiver logado)
$unreadTickets = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as unread FROM contacts WHERE user_id = ? AND customer_unread = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $unreadTickets = $stmt->fetch()['unread'];
    } catch (Exception $e) {
        $unreadTickets = 0;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        $error = 'Preencha todos os campos';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :login OR email = :login LIMIT 1');
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch();

        if ($user) {
            $storedHash = $user['password'];
            $isPasswordValid = password_verify($password, $storedHash);
            $needsRehash = false;

            if (!$isPasswordValid) {
                $isLegacyHash = strlen($storedHash) === 32 && ctype_xdigit($storedHash);
                if ($isLegacyHash && md5($password) === $storedHash) {
                    $isPasswordValid = true;
                    $needsRehash = true; // Atualizar hashes antigos para password_hash
                }
            }

            if ($isPasswordValid) {
                if ($needsRehash || password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
                    $updateStmt->execute([
                        'password' => $newHash,
                        'id' => $user['id'],
                    ]);
                    $storedHash = $newHash;
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                $loginStmt = $pdo->prepare('INSERT INTO logins (user_id) VALUES (:user_id)');
                $loginStmt->execute(['user_id' => $user['id']]);

                header('Location: index.php');
                exit();
            }

            $error = 'Password incorreta';
        } else {
            $error = 'Utilizador não encontrado';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TechShop</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/cart.css?v=<?php echo time(); ?>">
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
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="backoffice/backoffice.php">Admin</a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['username'])): ?>
                    <li><a href="logout.php">Sair</a></li>
                <?php endif; ?>
            </ul>

            <div class="nav-icons">
                <a href="#" class="search-icon"><i class="fas fa-search"></i></a>
                <a href="javascript:void(0);" class="cart-icon" id="cart-icon"><i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                </a>
                <?php if (isset($_SESSION['username'])): ?>
                    <div class="user-dropdown">
                        <a href="#" class="user-icon"><i class="fas fa-user"></i> <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span></a>
                        <div class="dropdown-content">
                            <a href="profile.php"><i class="fas fa-user-circle"></i> Perfil</a>
                            <a href="orders.php"><i class="fas fa-shopping-bag"></i> Pedidos</a>
                            <a href="my_tickets.php">
                                <i class="fas fa-ticket-alt"></i> Meus Tickets
                                <?php if ($unreadTickets > 0): ?>
                                    <span class="badge-notification"><?php echo $unreadTickets; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="user-icon" title="Fazer Login"><i class="fas fa-user"></i></a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- Login Form -->
    <div class="auth-container">
        <div class="auth-box">
            <h2>Iniciar Sessão</h2>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">Username ou Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Digite seu username ou email" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
                    </div>
                </div>
                <button type="submit" class="auth-button">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>
            <div class="auth-links">
                <a href="register.php">Não tem uma conta? Registre-se</a>
                <a href="forgot-password.php">Esqueceu sua senha?</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Sobre Nós</h3>
                <p>A TechShop é sua loja online de confiança para eletrônicos de alta qualidade.</p>
            </div>
            <div class="footer-section">
                <h3>Contato</h3>
                <p>Email: contato@techshop.com</p>
                <p>Telefone: (123) 456-7890</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 TechShop. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="js/cart.js?v=<?php echo time(); ?>"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
</body>
</html>