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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .orders-page {
            min-height: calc(100vh - 200px);
            padding: 40px 20px;
            background: #f5f5f5;
        }

        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
        }

        .no-orders {
            background: white;
            padding: 60px 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .no-orders i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-orders h2 {
            color: #666;
            margin-bottom: 15px;
        }

        .no-orders p {
            color: #999;
            margin-bottom: 30px;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .order-header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .order-number {
            font-size: 1.3rem;
            color: #333;
            font-weight: 600;
        }

        .order-status {
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .order-status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .order-status.processing {
            background: #d1ecf1;
            color: #0c5460;
        }

        .order-status.completed {
            background: #d4edda;
            color: #155724;
        }

        .order-status.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-item i {
            color: #667eea;
            font-size: 1.2rem;
            width: 25px;
        }

        .info-item div strong {
            display: block;
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 3px;
        }

        .info-item div span {
            color: #333;
            font-weight: 600;
        }

        .order-items-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .order-items-section h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .order-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-details h4 {
            color: #333;
            margin-bottom: 8px;
        }

        .order-item-details p {
            color: #666;
            font-size: 0.9rem;
        }

        .order-item-price {
            text-align: right;
            color: #667eea;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .btn-view-details {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
        }

        .btn-view-details:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .items-hidden {
            display: none;
        }

        @media (max-width: 768px) {
            .order-info-grid {
                grid-template-columns: 1fr;
            }

            .order-item {
                flex-direction: column;
                text-align: center;
            }

            .order-item img {
                width: 100%;
                height: 150px;
            }
        }
    </style>
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
                            <a href="orders.php" class="active"><i class="fas fa-shopping-bag"></i> Pedidos</a>
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
