<?php
session_start();

// Inicializar carrinho
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Carregar configuração do DB
require_once __DIR__ . '/config/db.php';

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

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$productId = (int)$_GET['id'];

// Buscar produto pelo ID
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: products.php');
        exit();
    }
} catch (Exception $e) {
    header('Location: products.php');
    exit();
}

// Buscar produtos relacionados (mesma categoria ou aleatórios)
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id != ? ORDER BY RAND() LIMIT 4");
    $stmt->execute([$productId]);
    $relatedProducts = $stmt->fetchAll();
} catch (Exception $e) {
    $relatedProducts = [];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - TechShop</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/cart.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/product_detail.css?v=<?php echo time(); ?>">
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
                <li><a href="products.php" class="active">Produtos</a></li>
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
                <a href="javascript:void(0);" class="cart-icon" id="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                </a>
                <?php if (isset($_SESSION['username'])): ?>
                    <div class="user-dropdown">
                        <a href="#" class="user-icon">
                            <i class="fas fa-user"></i> 
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </a>
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

    <!-- Product Detail -->
    <div class="product-detail-container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <span>/</span>
            <a href="products.php">Produtos</a>
            <span>/</span>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <!-- Main Product Info -->
        <div class="product-main">
            <div class="product-gallery">
                <div class="product-badge">Novo</div>
                <div class="product-main-image">
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <img src="images/placeholder.png" alt="<?php echo htmlspecialchars($product['name']); ?>" style="opacity: 0.3;">
                    <?php endif; ?>
                </div>
            </div>

            <div class="product-info-section">
                <div class="product-category">
                    <i class="fas fa-tag"></i> Tecnologia
                </div>
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-rating">
                    <div class="stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <span class="rating-count">(4.5 - 127 avaliações)</span>
                </div>
                
                <div class="product-price-box">
                    <div class="current-price">
                        €<?php echo number_format($product['price'], 2, ',', '.'); ?>
                    </div>
                    <div class="price-info">
                        <i class="fas fa-truck"></i> Envio grátis para encomendas acima de €50
                    </div>
                </div>
                
                <div class="product-description">
                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'Sem descrição disponível.')); ?></p>
                </div>

                <div class="product-features">
                    <h3>Características principais</h3>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> Garantia de 2 anos</li>
                        <li><i class="fas fa-check-circle"></i> Produto original</li>
                        <li><i class="fas fa-check-circle"></i> Entrega rápida</li>
                        <li><i class="fas fa-check-circle"></i> Suporte técnico incluído</li>
                    </ul>
                </div>
                
                <div class="quantity-selector">
                    <label>Quantidade:</label>
                    <div class="quantity-controls">
                        <button class="quantity-btn" onclick="decreaseQuantity()">−</button>
                        <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="99">
                        <button class="quantity-btn" onclick="increaseQuantity()">+</button>
                    </div>
                </div>
                
                <div class="product-actions">
                    <button class="btn-add-cart add-to-cart-btn" 
                            data-product-id="<?php echo $product['id']; ?>"
                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                            data-product-price="<?php echo $product['price']; ?>"
                            data-product-image="<?php echo htmlspecialchars($product['image'] ?? ''); ?>">
                        <i class="fas fa-shopping-cart"></i>
                        Adicionar ao Carrinho
                    </button>
                    <button class="btn-wishlist" title="Adicionar aos favoritos">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
                
                <div class="product-meta">
                    <div class="meta-item">
                        <i class="fas fa-box"></i>
                        <span>Em stock</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Compra segura</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-undo"></i>
                        <span>Devolução grátis em 30 dias</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <div class="related-products">
        <h2>Produtos Relacionados</h2>
        <div class="related-grid">
            <?php foreach ($relatedProducts as $related): ?>
                <a href="product_detail.php?id=<?php echo $related['id']; ?>" class="related-card">
                    <div class="related-card-image">
                        <?php if (!empty($related['image'])): ?>
                            <img src="<?php echo htmlspecialchars($related['image']); ?>" alt="<?php echo htmlspecialchars($related['name']); ?>">
                        <?php else: ?>
                            <img src="images/placeholder.png" alt="<?php echo htmlspecialchars($related['name']); ?>" style="opacity: 0.3;">
                        <?php endif; ?>
                    </div>
                    <div class="related-card-info">
                        <h3><?php echo htmlspecialchars($related['name']); ?></h3>
                        <div class="related-card-price">€<?php echo number_format($related['price'], 2, ',', '.'); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

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

    <!-- Cart Sidebar (incluir do index.php) -->
    <div id="cart-sidebar" class="cart-sidebar">
        <div class="cart-header">
            <h2><i class="fas fa-shopping-cart"></i> Carrinho</h2>
            <button id="close-cart" class="close-cart"><i class="fas fa-times"></i></button>
        </div>
        <div id="cart-items" class="cart-items"></div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total:</span>
                <span id="cart-total-price">€0.00</span>
            </div>
            <button class="checkout-btn" onclick="window.location.href='checkout.php'">
                <i class="fas fa-credit-card"></i> Finalizar Compra
            </button>
        </div>
    </div>

    <script src="js/cart.js"></script>
    <script>
        function decreaseQuantity() {
            const input = document.getElementById('quantity');
            if (input.value > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }

        function increaseQuantity() {
            const input = document.getElementById('quantity');
            if (input.value < 99) {
                input.value = parseInt(input.value) + 1;
            }
        }

        // Modificar o evento de adicionar ao carrinho para incluir a quantidade
        document.addEventListener('DOMContentLoaded', function() {
            const addToCartBtn = document.querySelector('.btn-add-cart');
            
            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', function() {
                    const quantity = parseInt(document.getElementById('quantity').value);
                    const productId = this.getAttribute('data-product-id');
                    const productName = this.getAttribute('data-product-name');
                    const productPrice = parseFloat(this.getAttribute('data-product-price'));
                    const productImage = this.getAttribute('data-product-image');

                    // Adicionar produto ao carrinho com quantidade especificada
                    for (let i = 0; i < quantity; i++) {
                        addToCart({
                            id: productId,
                            name: productName,
                            price: productPrice,
                            image: productImage
                        });
                    }

                    // Feedback visual
                    this.innerHTML = '<i class="fas fa-check"></i> Adicionado!';
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-shopping-cart"></i> Adicionar ao Carrinho';
                    }, 2000);
                });
            }
        });
    </script>

    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script>
</html>
