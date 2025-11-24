<?php
// Iniciar sessão apenas se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'cart_handler.php';
require_once 'config/db.php';

// Verificar se há itens no carrinho
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: products.php');
    exit();
}

// Atualizar informações de promoção dos produtos no carrinho
foreach ($_SESSION['cart'] as $productId => &$item) {
    // Buscar informações atualizadas do produto na BD
    $stmt = $conn->prepare("SELECT price, on_promotion, promotion_price, discount_percentage FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Atualizar informações de promoção
        $item['original_price'] = $row['price'];
        $item['on_promotion'] = $row['on_promotion'];
        $item['discount_percentage'] = $row['discount_percentage'];
        
        // Se está em promoção, usar o preço promocional
        if ($row['on_promotion'] && !empty($row['promotion_price'])) {
            $item['price'] = $row['promotion_price'];
        } else {
            $item['price'] = $row['price'];
        }
    }
    $stmt->close();
}
unset($item); // Importante: quebrar a referência

// Debug: Ver o que está no carrinho
error_log("=== CHECKOUT DEBUG ===");
error_log("Carrinho: " . print_r($_SESSION['cart'], true));

$cartItems = $_SESSION['cart'];
$cartTotal = getCartTotal();
$shippingCost = getShippingCost();
$finalTotal = getFinalTotal();
$cartCount = getCartCount();

error_log("Total: $cartTotal");
error_log("Count: $cartCount");
error_log("Items Count: " . count($cartItems));

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
    <link rel="stylesheet" href="css/search.css">
    <link rel="stylesheet" href="css/dropdown.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php 
    // Obter contagem de tickets não lidos para o header
    $unreadTickets = 0;
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM contacts WHERE user_id = ? AND customer_unread = 1");
        if ($stmt) {
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $unreadTickets = $row['unread'];
            }
            $stmt->close();
        }
    }
    include 'includes/header.php'; 
    ?>

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
                    <h2><i class="fas fa-shipping-fast"></i> Dados de Envio</h2>
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
                    <h2><i class="fas fa-receipt"></i> Resumo do Pedido</h2>
                    <div class="order-summary">
                        <div class="summary-items">
                            <?php 
                            // Debug
                            if (empty($cartItems)) {
                                echo "<!-- CARRINHO VAZIO -->";
                            } else {
                                echo "<!-- CARRINHO TEM " . count($cartItems) . " ITENS -->";
                            }
                            
                            foreach($cartItems as $item): 
                                // Debug de cada item
                                error_log("Item: " . print_r($item, true));
                            ?>
                                <div class="summary-item">
                                    <div class="summary-item-image">
                                        <?php if(!empty($item['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name'] ?? 'Produto'); ?>">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-details">
                                        <h3><?php echo htmlspecialchars($item['name'] ?? 'Sem nome'); ?></h3>
                                        <p class="item-quantity">Quantidade: <?php echo intval($item['quantity'] ?? 1); ?></p>
                                        
                                        <?php 
                                        // Processar preços
                                        $price = isset($item['price']) ? floatval(str_replace(['€', ','], ['', '.'], $item['price'])) : 0;
                                        $quantity = intval($item['quantity'] ?? 1);
                                        $subtotal = $price * $quantity;
                                        
                                        // Verificar se está em promoção
                                        $isOnPromotion = isset($item['on_promotion']) && $item['on_promotion'] == 1;
                                        
                                        if ($isOnPromotion && isset($item['original_price']) && $item['original_price'] > 0): 
                                            $originalPrice = floatval($item['original_price']);
                                            $discountPercentage = isset($item['discount_percentage']) ? floatval($item['discount_percentage']) : 0;
                                            
                                            // Se desconto não foi calculado, calcular agora
                                            if ($discountPercentage == 0 && $originalPrice > $price) {
                                                $discountPercentage = (($originalPrice - $price) / $originalPrice) * 100;
                                            }
                                        ?>
                                            <div class="item-promotion-info">
                                                <span class="promotion-badge-small">
                                                    <i class="fas fa-tag"></i> -<?php echo number_format($discountPercentage, 0); ?>%
                                                </span>
                                            </div>
                                            <p class="item-price">
                                                <span class="original-price-checkout">€<?php echo number_format($originalPrice, 2, ',', '.'); ?></span>
                                                <span class="promotion-price-checkout">€<?php echo number_format($price, 2, ',', '.'); ?></span>
                                            </p>
                                            <p class="item-subtotal">Subtotal: <strong>€<?php echo number_format($subtotal, 2, ',', '.'); ?></strong></p>
                                        <?php else: ?>
                                            <p class="item-price">
                                                €<?php echo number_format($price, 2, ',', '.'); ?>
                                            </p>
                                            <p class="item-subtotal">Subtotal: <strong>€<?php echo number_format($subtotal, 2, ',', '.'); ?></strong></p>
                                        <?php endif; ?>
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
                                <?php if ($shippingCost > 0): ?>
                                    <span><?php echo number_format($shippingCost, 2, ',', '.'); ?> €</span>
                                <?php else: ?>
                                    <span class="free-shipping">Grátis</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($cartTotal < 50): ?>
                                <div class="shipping-notice" style="background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 0.9em; color: #856404;">
                                    <i class="fas fa-info-circle"></i> Adicione mais <?php echo number_format(50 - $cartTotal, 2, ',', '.'); ?> € para envio grátis!
                                </div>
                            <?php endif; ?>
                            <div class="total-row total">
                                <span>Total:</span>
                                <span><?php echo number_format($finalTotal, 2, ',', '.'); ?> €</span>
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

    <!-- Search Overlay -->
    <div id="searchOverlay" class="search-overlay">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Pesquisar produtos...">
            <button class="close-search">&times;</button>
        </div>
        <div id="searchResults" class="search-results"></div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2024 TechShop. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        // Bloquear abertura do carrinho na página de checkout
        document.addEventListener('DOMContentLoaded', function() {
            const cartIcon = document.querySelector('.cart-icon');
            if (cartIcon) {
                cartIcon.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Você já está na página de checkout!');
                    return false;
                });
            }
        });

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
    
    <script src="js/main.js"></script>
    <script src="js/cart.js"></script>
    <script src="js/search.js"></script>
</body>
</html>
