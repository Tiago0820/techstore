<?php
// Iniciar sessão apenas se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    require_once __DIR__ . '/config/db.php';
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as unread FROM contacts WHERE user_id = ? AND customer_unread = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $unreadTickets = $stmt->fetch()['unread'];
    } catch (Exception $e) {
        $unreadTickets = 0;
    }
}

// Verificar se há dados da encomenda
if (!isset($_SESSION['order_success'])) {
    header('Location: products.php');
    exit();
}

$orderData = $_SESSION['order_success'];
unset($_SESSION['order_success']); // Limpar dados após uso
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encomenda Confirmada - TechShop</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/checkout.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/cart.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dropdown.css?v=<?php echo time(); ?>">
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
                <li><a href="#">Contacto</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="backoffice/backoffice.php">Admin</a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['username'])): ?>
                    <li><a href="logout.php">Sair</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
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

    <main class="success-container">
        <div class="success-wrapper">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1>Encomenda Confirmada!</h1>
            
            <div class="success-message">
                <p>Obrigado pela sua compra, <strong><?php echo htmlspecialchars($orderData['customer_name']); ?></strong>!</p>
                <p>A sua encomenda foi registada com sucesso.</p>
            </div>

            <div class="order-details">
                <div class="detail-item">
                    <i class="fas fa-receipt"></i>
                    <div>
                        <strong>Número do Pedido:</strong>
                        <span>#<?php echo str_pad($orderData['order_id'], 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                </div>
                
                <div class="detail-item">
                    <i class="fas fa-euro-sign"></i>
                    <div>
                        <strong>Total Pago:</strong>
                        <span><?php echo number_format($orderData['total'], 2, ',', '.'); ?> €</span>
                    </div>
                </div>

                <div class="detail-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <strong>Email de Confirmação:</strong>
                        <span><?php echo htmlspecialchars($orderData['customer_email']); ?></span>
                    </div>
                </div>
            </div>

            <div class="success-info">
                <p><i class="fas fa-info-circle"></i> Receberá um email com os detalhes da sua encomenda em breve.</p>
                <p><i class="fas fa-truck"></i> O envio será processado dentro de 24-48 horas.</p>
            </div>

            <div class="success-actions">
                <a href="products.php" class="btn-primary">
                    <i class="fas fa-shopping-bag"></i> Continuar a Comprar
                </a>
                <a href="index.php" class="btn-secondary">
                    <i class="fas fa-home"></i> Voltar ao Início
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2024 TechShop. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="js/cart.js?v=<?php echo time(); ?>"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>
