<?php
// Iniciar sessão apenas se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/db.php';
require_once 'cart_handler.php';

$user_id = $_SESSION['user_id'];
$cartCount = getCartCount();

// Contar tickets não lidos
$unreadTickets = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread FROM contacts WHERE user_id = ? AND customer_unread = 1");
    $stmt->execute([$user_id]);
    $unreadTickets = $stmt->fetch()['unread'];
} catch (Exception $e) {
    $unreadTickets = 0;
}

// Buscar encomendas do usuário
$stmt = $pdo->prepare("
    SELECT o.*, 
    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Encomendas - TechShop</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/checkout.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/cart.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dropdown.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/orders.css?v=<?php echo time(); ?>">
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
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <a href="backoffice/backoffice.php"><i class="fas fa-user-shield"></i> Admin</a>
                            <?php else: ?>
                                <a href="profile.php"><i class="fas fa-user-circle"></i> Perfil</a>
                                <a href="orders.php" class="active"><i class="fas fa-shopping-bag"></i> Pedidos</a>
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
                <?php else: ?>
                    <a href="login.php" class="user-icon" title="Fazer Login"><i class="fas fa-user"></i></a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="orders-page">
        <div class="orders-container">
            <div class="page-header">
                <h1><i class="fas fa-shopping-bag"></i> Minhas Encomendas</h1>
                <p>Acompanhe todas as suas compras realizadas na TechShop</p>
            </div>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <i class="fas fa-inbox"></i>
                    <h2>Ainda não tem encomendas</h2>
                    <p>Quando efetuar uma compra, as suas encomendas aparecerão aqui.</p>
                    <a href="products.php" class="btn-primary">
                        <i class="fas fa-shopping-cart"></i> Começar a Comprar
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header-info">
                            <div class="order-number">
                                <i class="fas fa-receipt"></i> Pedido #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                            </div>
                            <div class="order-status <?php echo $order['status']; ?>">
                                <?php
                                $status_labels = [
                                    'pending' => 'Pendente',
                                    'processing' => 'Em Processamento',
                                    'completed' => 'Concluída',
                                    'cancelled' => 'Cancelada'
                                ];
                                echo $status_labels[$order['status']] ?? $order['status'];
                                ?>
                            </div>
                        </div>

                        <div class="order-info-grid">
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <div>
                                    <strong>Data do Pedido</strong>
                                    <span><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></span>
                                </div>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-box"></i>
                                <div>
                                    <strong>Itens</strong>
                                    <span><?php echo $order['item_count']; ?> produto(s)</span>
                                </div>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-euro-sign"></i>
                                <div>
                                    <strong>Total</strong>
                                    <span><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> €</span>
                                </div>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-credit-card"></i>
                                <div>
                                    <strong>Pagamento</strong>
                                    <span>
                                        <?php
                                        $payment_methods = [
                                            'mbway' => 'MB WAY',
                                            'multibanco' => 'Multibanco',
                                            'card' => 'Cartão',
                                            'paypal' => 'PayPal',
                                            'cash_on_delivery' => 'À Cobrança'
                                        ];
                                        echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button class="btn-view-details" onclick="toggleOrderItems(<?php echo $order['id']; ?>)">
                            <i class="fas fa-eye"></i> Ver Detalhes
                        </button>

                        <div id="order-items-<?php echo $order['id']; ?>" class="order-items-section items-hidden">
                            <h3><i class="fas fa-list"></i> Produtos da Encomenda</h3>
                            <?php
                            $items_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                            $items_stmt->execute([$order['id']]);
                            $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($items as $item):
                            ?>
                                <div class="order-item">
                                    <?php if (!empty($item['product_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    <?php else: ?>
                                        <div style="width: 80px; height: 80px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image" style="font-size: 2rem; color: #ccc;"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="order-item-details">
                                        <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                        <p>Quantidade: <?php echo $item['quantity']; ?> x <?php echo number_format($item['product_price'], 2, ',', '.'); ?> €</p>
                                    </div>
                                    
                                    <div class="order-item-price">
                                        <?php echo number_format($item['subtotal'], 2, ',', '.'); ?> €
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div style="background: white; padding: 15px; border-radius: 8px; margin-top: 15px;">
                                <p><strong>Morada de Envio:</strong></p>
                                <p><?php echo htmlspecialchars($order['customer_address']); ?></p>
                                <p><?php echo htmlspecialchars($order['customer_postal_code']); ?> - <?php echo htmlspecialchars($order['customer_city']); ?></p>
                                <p><strong>Telefone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                <?php if ($order['notes']): ?>
                                    <p style="margin-top: 10px;"><strong>Notas:</strong> <?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
    <script>
        function toggleOrderItems(orderId) {
            const itemsDiv = document.getElementById('order-items-' + orderId);
            const btn = event.target.closest('.btn-view-details');
            
            if (itemsDiv.classList.contains('items-hidden')) {
                itemsDiv.classList.remove('items-hidden');
                btn.innerHTML = '<i class="fas fa-eye-slash"></i> Ocultar Detalhes';
            } else {
                itemsDiv.classList.add('items-hidden');
                btn.innerHTML = '<i class="fas fa-eye"></i> Ver Detalhes';
            }
        }
    </script>
</body>
</html>
