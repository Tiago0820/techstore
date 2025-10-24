<?php
// Iniciar sessão apenas se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'cart_handler.php';

// Verificar se há itens no carrinho
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: products.php');
    exit();
}

$cartItems = $_SESSION['cart'];
$cartTotal = getCartTotal();
$cartCount = getCartCount();

// Informações do usuário se estiver logado
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : '';
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - TechShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/checkout.css">
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
                            <a href="profile.php">Perfil</a>
                            <a href="orders.php">Pedidos</a>
                            <a href="logout.php">Sair</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="user-icon" title="Fazer Login"><i class="fas fa-user"></i></a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="checkout-container">
        <div class="checkout-wrapper">
            <h1 class="page-title">Finalizar Compra</h1>

            <?php if (isset($_SESSION['checkout_error'])): ?>
                <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php 
                        echo htmlspecialchars($_SESSION['checkout_error']); 
                        unset($_SESSION['checkout_error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="checkout-grid">
                <!-- Formulário de dados -->
                <div class="checkout-form-section">
                    <h2>Dados de Envio</h2>
                    <form id="checkoutForm" method="POST" action="process_checkout.php">
                        <div class="form-group">
                            <label for="name">Nome Completo *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Telefone *</label>
                            <input type="tel" id="phone" name="phone" placeholder="+351 912 345 678" required>
                        </div>

                        <div class="form-group">
                            <label for="address">Morada *</label>
                            <input type="text" id="address" name="address" placeholder="Rua, número, andar, porta" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">Cidade *</label>
                                <input type="text" id="city" name="city" required>
                            </div>

                            <div class="form-group">
                                <label for="postal_code">Código Postal *</label>
                                <input type="text" id="postal_code" name="postal_code" placeholder="0000-000" pattern="\d{4}-\d{3}" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="payment_method">Método de Pagamento *</label>
                            <select id="payment_method" name="payment_method" required>
                                <option value="">Selecione...</option>
                                <option value="mbway">MB WAY</option>
                                <option value="multibanco">Multibanco</option>
                                <option value="card">Cartão de Crédito/Débito</option>
                                <option value="paypal">PayPal</option>
                                <option value="cash_on_delivery">Pagamento à Cobrança</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notas do Pedido (opcional)</label>
                            <textarea id="notes" name="notes" rows="3" placeholder="Observações sobre o pedido..."></textarea>
                        </div>

                        <div class="form-actions">
                            <a href="products.php" class="btn-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar às Compras
                            </a>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-check"></i> Confirmar Pedido
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Resumo do pedido -->
                <div class="order-summary-section">
                    <h2>Resumo do Pedido</h2>
                    <div class="order-summary">
                        <div class="summary-items">
                            <?php foreach($cartItems as $item): ?>
                                <div class="summary-item">
                                    <?php if(!empty($item['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="item-details">
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p class="item-quantity">Quantidade: <?php echo $item['quantity']; ?></p>
                                        <p class="item-price">
                                            <?php 
                                            $price = str_replace(['€', ','], ['', '.'], $item['price']);
                                            $price = floatval($price);
                                            $subtotal = $price * $item['quantity'];
                                            echo number_format($subtotal, 2, ',', '.') . ' €';
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="summary-totals">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span><?php echo number_format($cartTotal, 2, ',', '.'); ?> €</span>
                            </div>
                            <div class="total-row">
                                <span>Envio:</span>
                                <span class="free-shipping">Grátis</span>
                            </div>
                            <div class="total-row total">
                                <span>Total:</span>
                                <span><?php echo number_format($cartTotal, 2, ',', '.'); ?> €</span>
                            </div>
                        </div>

                        <div class="payment-info">
                            <i class="fas fa-lock"></i>
                            <p>Pagamento seguro e protegido</p>
                        </div>
                    </div>
                </div>
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
    <script>
        // Validação do formulário
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const phone = document.getElementById('phone').value;
            const postalCode = document.getElementById('postal_code').value;
            
            // Validar formato do código postal
            const postalPattern = /^\d{4}-\d{3}$/;
            if (!postalPattern.test(postalCode)) {
                e.preventDefault();
                alert('Por favor, insira um código postal válido no formato 0000-000');
                return false;
            }
            
            // Confirmação final
            if (!confirm('Confirma que pretende finalizar esta encomenda?')) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-formatação do código postal
        document.getElementById('postal_code').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 4) {
                value = value.substring(0, 4) + '-' + value.substring(4, 7);
            }
            e.target.value = value;
        });
    </script>
</body>
</html>
