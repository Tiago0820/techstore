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

// Buscar especificações do produto
try {
    $stmt = $pdo->prepare("SELECT * FROM product_specifications WHERE product_id = ? ORDER BY name, price_modifier");
    $stmt->execute([$productId]);
    $specifications = $stmt->fetchAll();
    
    // Agrupar especificações por nome
    $groupedSpecs = [];
    foreach ($specifications as $spec) {
        $groupedSpecs[$spec['name']][] = $spec;
    }
} catch (Exception $e) {
    $specifications = [];
    $groupedSpecs = [];
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
    <link rel="stylesheet" href="css/home.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/cart.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/product_detail.css?v=<?php echo time(); ?>">
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
                <li><a href="products.php" class="active">Produtos</a></li>
                <li><a href="about.php">Sobre</a></li>
                <li><a href="contact.php">Contacto</a></li>
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
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <a href="backoffice/backoffice.php"><i class="fas fa-user-shield"></i> Admin</a>
                            <?php else: ?>
                                <a href="profile.php"><i class="fas fa-user-circle"></i> Perfil</a>
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
                <div class="product-main-image">
                    <!-- Badge de Novo -->
                    <?php 
                    $createdDate = isset($product['created_at']) ? strtotime($product['created_at']) : 0;
                    $isNew = $createdDate > 0 && (time() - $createdDate) <= (30 * 24 * 60 * 60); // 30 dias
                    if ($isNew): 
                    ?>
                        <div class="product-badge new-badge">NOVO</div>
                    <?php endif; ?>
                    
                    <!-- Badge de Promoção -->
                    <?php if (isset($product['on_promotion']) && $product['on_promotion']): ?>
                        <div class="product-badge promotion-badge">-<?php echo number_format($product['discount_percentage'], 0); ?>% OFF</div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['image'])): 
                        // Verificar se o caminho já contém 'images/' no início
                        $imagePath = $product['image'];
                        if (strpos($imagePath, 'images/') !== 0 && strpos($imagePath, '/') !== 0 && strpos($imagePath, 'http') !== 0) {
                            $imagePath = 'images/' . $imagePath;
                        }
                    ?>
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <img src="images/placeholder.png" alt="<?php echo htmlspecialchars($product['name']); ?>" style="opacity: 0.3;">
                    <?php endif; ?>
                </div>
            </div>

            <div class="product-info-section">
                <div class="product-category">
                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category'] ?? 'Tecnologia'); ?>
                </div>
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-price-box">
                    <div class="price-container">
                        <?php if (isset($product['on_promotion']) && $product['on_promotion']): ?>
                            <div class="original-price">
                                €<?php echo number_format($product['price'], 2, ',', '.'); ?>
                            </div>
                            <div class="current-price">
                                €<?php echo number_format($product['promotion_price'], 2, ',', '.'); ?>
                            </div>
                            <div class="promotion-badge-inline">
                                -<?php echo number_format($product['discount_percentage'], 0); ?>% OFF
                            </div>
                        <?php else: ?>
                            <div class="current-price">
                                €<?php echo number_format($product['price'], 2, ',', '.'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="product-description">
                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'Sem descrição disponível.')); ?></p>
                </div>

                <?php if (!empty($groupedSpecs)): ?>
                <div class="product-specifications">
                    <h3><i class="fas fa-sliders-h"></i> Escolha as suas opções</h3>
                    <form id="specifications-form">
                        <?php foreach ($groupedSpecs as $specName => $specs): ?>
                            <div class="spec-group">
                                <label class="spec-label"><?php echo htmlspecialchars($specName); ?>:</label>
                                <div class="spec-options">
                                    <?php foreach ($specs as $spec): ?>
                                        <div class="spec-option-item">
                                            <input type="radio" 
                                                   name="spec_<?php echo htmlspecialchars($specName); ?>" 
                                                   id="spec_<?php echo $spec['id']; ?>" 
                                                   value="<?php echo $spec['id']; ?>"
                                                   data-price-modifier="<?php echo $spec['price_modifier']; ?>"
                                                   data-spec-name="<?php echo htmlspecialchars($specName); ?>"
                                                   data-spec-value="<?php echo htmlspecialchars($spec['value']); ?>"
                                                   class="spec-radio"
                                                   <?php echo ($spec === reset($specs)) ? 'checked' : ''; ?>>
                                            <label for="spec_<?php echo $spec['id']; ?>" class="spec-option-label">
                                                <span class="spec-value"><?php echo htmlspecialchars($spec['value']); ?></span>
                                                <?php if ($spec['price_modifier'] != 0): ?>
                                                    <span class="spec-price-mod <?php echo $spec['price_modifier'] > 0 ? 'positive' : 'negative'; ?>">
                                                        <?php echo $spec['price_modifier'] > 0 ? '+' : ''; ?>€<?php echo number_format($spec['price_modifier'], 2); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="selected-specs-summary" id="specs-summary" style="display: none;">
                            <h4><i class="fas fa-check-circle"></i> Configuração Selecionada:</h4>
                            <div id="specs-list"></div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <div class="product-features">
                    <h3>Características principais</h3>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> Garantia de 2 anos</li>
                        <li><i class="fas fa-check-circle"></i> Produto original</li>
                        <li><i class="fas fa-check-circle"></i> Entrega rápida</li>
                        <li><i class="fas fa-check-circle"></i> Suporte técnico incluído</li>
                    </ul>
                </div>
                
                <div class="trust-badges">
                    <div class="badge-item">
                        <i class="fas fa-shipping-fast"></i>
                        <div class="badge-info">
                            <strong>Envio Rápido</strong>
                            <span>Entrega em 24-48h</span>
                        </div>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-shield-alt"></i>
                        <div class="badge-info">
                            <strong>Garantia Oficial</strong>
                            <span>2 anos de cobertura</span>
                        </div>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-credit-card"></i>
                        <div class="badge-info">
                            <strong>Pagamento Seguro</strong>
                            <span>Transação protegida</span>
                        </div>
                    </div>
                </div>
                
                <div class="product-actions">
                    <div class="actions-title">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Opções de Compra</span>
                    </div>
                    <button class="btn-buy-now" 
                            data-product-id="<?php echo $product['id']; ?>"
                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                            data-product-price="<?php echo number_format((isset($product['on_promotion']) && $product['on_promotion']) ? $product['promotion_price'] : $product['price'], 2, '.', ''); ?>"
                            data-product-image="<?php echo htmlspecialchars($product['image'] ?? ''); ?>"
                            data-original-price="<?php echo isset($product['on_promotion']) && $product['on_promotion'] ? number_format($product['price'], 2, '.', '') : ''; ?>"
                            data-on-promotion="<?php echo isset($product['on_promotion']) ? $product['on_promotion'] : 0; ?>"
                            data-discount-percentage="<?php echo isset($product['discount_percentage']) ? $product['discount_percentage'] : 0; ?>">
                        <i class="fas fa-bolt"></i>
                        Comprar Agora
                    </button>
                    <button class="btn-add-cart add-to-cart-btn" 
                            data-product-id="<?php echo $product['id']; ?>"
                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                            data-product-price="<?php echo number_format((isset($product['on_promotion']) && $product['on_promotion']) ? $product['promotion_price'] : $product['price'], 2, '.', ''); ?>"
                            data-product-image="<?php echo htmlspecialchars($product['image'] ?? ''); ?>"
                            data-original-price="<?php echo isset($product['on_promotion']) && $product['on_promotion'] ? number_format($product['price'], 2, '.', '') : ''; ?>"
                            data-on-promotion="<?php echo isset($product['on_promotion']) ? $product['on_promotion'] : 0; ?>"
                            data-discount-percentage="<?php echo isset($product['discount_percentage']) ? $product['discount_percentage'] : 0; ?>">
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
    <section class="featured-products related-products-section" style="padding-top: 60px;">
        <h2>Produtos Relacionados</h2>
        <div class="product-grid">
            <?php foreach ($relatedProducts as $related): ?>
                <div class="product-card" 
                     data-product-id="<?php echo $related['id']; ?>"
                     data-product-name="<?php echo htmlspecialchars($related['name']); ?>"
                     data-product-description="<?php echo htmlspecialchars($related['description'] ?? 'Sem descrição disponível.'); ?>"
                     data-product-price="<?php echo ($related['on_promotion'] ?? 0) ? $related['promotion_price'] : $related['price']; ?>"
                     data-product-image="<?php echo htmlspecialchars($related['image'] ?? ''); ?>">
                    <?php if (!empty($related['image'])): ?>
                        <div class="product-image">
                            <?php if ($related['on_promotion'] ?? 0): ?>
                                <div class="promotion-badge">-<?php echo number_format($related['discount_percentage'], 0); ?>%</div>
                            <?php endif; ?>
                            <img src="<?php echo htmlspecialchars($related['image']); ?>" alt="<?php echo htmlspecialchars($related['name']); ?>">
                        </div>
                    <?php else: ?>
                        <div class="product-image">
                            <?php if ($related['on_promotion'] ?? 0): ?>
                                <div class="promotion-badge">-<?php echo number_format($related['discount_percentage'], 0); ?>%</div>
                            <?php endif; ?>
                            <img src="images/placeholder.png" alt="<?php echo htmlspecialchars($related['name']); ?>" style="opacity: 0.3;">
                        </div>
                    <?php endif; ?>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($related['name']); ?></h3>
                        <?php if ($related['on_promotion'] ?? 0): ?>
                            <div class="price-container">
                                <span class="original-price">€<?php echo number_format($related['price'], 2, ',', '.'); ?></span>
                                <span class="price promotion-price">€<?php echo number_format($related['promotion_price'], 2, ',', '.'); ?></span>
                            </div>
                        <?php else: ?>
                            <span class="price">€<?php echo number_format($related['price'], 2, ',', '.'); ?></span>
                        <?php endif; ?>
                        <div class="product-actions">
                            <button class="add-to-cart add-to-cart-btn" 
                                    data-product-id="<?php echo $related['id']; ?>"
                                    data-product-name="<?php echo htmlspecialchars($related['name']); ?>"
                                    data-product-price="<?php echo ($related['on_promotion'] ?? 0) ? $related['promotion_price'] : $related['price']; ?>"
                                    data-product-image="<?php echo htmlspecialchars($related['image'] ?? ''); ?>">
                                <i class="fas fa-cart-plus"></i> Adicionar ao Carrinho
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
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

    <script src="js/cart.js"></script>
    <script src="js/search.js"></script>
    <script>
        // Função para mostrar notificações
        function showNotification(message, type = 'success') {
            if (window.cartManager && window.cartManager.showToast) {
                window.cartManager.showToast(message, type);
            }
        }
        
        function initRelatedProducts() {
            const cards = document.querySelectorAll('.related-products-section .product-card');
            console.log('✅ Cards de produtos relacionados encontrados:', cards.length);
            
            if (cards.length === 0) {
                return;
            }
            
            cards.forEach(function(card) {
                // Adicionar cursor
                card.style.cursor = 'pointer';
                
                // Click no card - redirecionar para product_detail.php
                card.addEventListener('click', function(e) {
                    // Se clicou no botão ou seus elementos internos, não fazer nada
                    if (e.target.closest('.add-to-cart-btn')) {
                        console.log('🛒 Click no botão - deixando cart.js processar');
                        return; // Deixa o cart.js processar
                    }
                    
                    // Se clicou em qualquer outra parte do card, redirecionar
                    console.log('✅ Redirecionando para página de detalhes...');
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const id = this.getAttribute('data-product-id');
                    
                    // Redirecionar para a página de detalhes
                    window.location.href = 'product_detail.php?id=' + id;
                });
            });
            
            console.log('✅ Sistema de cards relacionados configurado!');
        }
        
        // Configurar página ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar botão de favoritos
            const wishlistBtn = document.querySelector('.btn-wishlist');
            if (wishlistBtn) {
                const productId = document.querySelector('.btn-add-cart').getAttribute('data-product-id');
                
                fetch('wishlist_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'check',
                        product_id: productId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.is_favorite) {
                        wishlistBtn.classList.add('active');
                    }
                })
                .catch(error => console.error('Erro ao verificar favorito:', error));
                
                wishlistBtn.addEventListener('click', async function() {
                    const productId = document.querySelector('.btn-add-cart').getAttribute('data-product-id');
                    
                    try {
                        const response = await fetch('wishlist_handler.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'toggle',
                                product_id: productId
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            if (data.action === 'added') {
                                this.classList.add('active');
                                showNotification('❤️ Adicionado aos favoritos!', 'success');
                            } else {
                                this.classList.remove('active');
                                showNotification('Removido dos favoritos', 'info');
                            }
                        } else {
                            if (data.redirect) {
                                if (confirm(data.message + '. Deseja fazer login agora?')) {
                                    window.location.href = data.redirect;
                                }
                            } else {
                                showNotification(data.message, 'error');
                            }
                        }
                    } catch (error) {
                        console.error('Erro:', error);
                        showNotification('Erro ao processar favorito', 'error');
                    }
                });
            }
            
            // Configurar produtos relacionados (cards clicáveis)
            setTimeout(function() {
                initRelatedProducts();
            }, 600);
            
            // ============= SPECIFICATIONS SYSTEM =============
            initSpecificationsSystem();
        });
        
        function initSpecificationsSystem() {
            const specRadios = document.querySelectorAll('.spec-radio');
            const priceDisplay = document.querySelector('.current-price');
            const addToCartBtns = document.querySelectorAll('.add-to-cart-btn, .btn-buy-now');
            
            if (specRadios.length === 0) return;
            
            // Obter preço base do produto
            const basePrice = parseFloat(document.querySelector('.btn-add-cart')?.getAttribute('data-product-price') || 0);
            const isOnPromotion = parseInt(document.querySelector('.btn-add-cart')?.getAttribute('data-on-promotion') || 0);
            const originalPrice = parseFloat(document.querySelector('.btn-add-cart')?.getAttribute('data-original-price') || basePrice);
            
            // Função para calcular preço total
            function updatePrice() {
                let totalModifier = 0;
                let selectedSpecs = {};
                
                // Calcular modificadores das especificações selecionadas
                specRadios.forEach(radio => {
                    if (radio.checked) {
                        const modifier = parseFloat(radio.getAttribute('data-price-modifier') || 0);
                        totalModifier += modifier;
                        
                        const specName = radio.getAttribute('data-spec-name');
                        const specValue = radio.getAttribute('data-spec-value');
                        selectedSpecs[specName] = {
                            value: specValue,
                            modifier: modifier
                        };
                    }
                });
                
                // Calcular preço final
                let finalPrice = basePrice + totalModifier;
                
                // Atualizar display de preço
                if (priceDisplay) {
                    priceDisplay.textContent = '€' + finalPrice.toFixed(2).replace('.', ',');
                }
                
                // Atualizar preço nos botões
                addToCartBtns.forEach(btn => {
                    btn.setAttribute('data-product-price', finalPrice.toFixed(2));
                });
                
                // Atualizar resumo de especificações
                updateSpecsSummary(selectedSpecs, totalModifier);
                
                // Se estiver em promoção, atualizar preço original também
                if (isOnPromotion) {
                    const originalPriceDisplay = document.querySelector('.original-price');
                    if (originalPriceDisplay) {
                        const newOriginalPrice = originalPrice + totalModifier;
                        originalPriceDisplay.textContent = '€' + newOriginalPrice.toFixed(2).replace('.', ',');
                    }
                }
            }
            
            // Função para atualizar resumo
            function updateSpecsSummary(specs, totalModifier) {
                const summary = document.getElementById('specs-summary');
                const specsList = document.getElementById('specs-list');
                
                if (!summary || !specsList) return;
                
                if (Object.keys(specs).length === 0) {
                    summary.style.display = 'none';
                    return;
                }
                
                summary.style.display = 'block';
                specsList.innerHTML = '';
                
                Object.entries(specs).forEach(([name, data]) => {
                    const item = document.createElement('div');
                    item.className = 'spec-summary-item';
                    item.innerHTML = `
                        <span class="spec-summary-name">${name}:</span>
                        <span>
                            <span class="spec-summary-value">${data.value}</span>
                            ${data.modifier != 0 ? `<span class="spec-summary-price">${data.modifier > 0 ? '+' : ''}€${data.modifier.toFixed(2)}</span>` : ''}
                        </span>
                    `;
                    specsList.appendChild(item);
                });
                
                // Adicionar total se houver modificador
                if (totalModifier != 0) {
                    const totalItem = document.createElement('div');
                    totalItem.className = 'spec-summary-item';
                    totalItem.style.borderTop = '2px solid #dee2e6';
                    totalItem.style.marginTop = '10px';
                    totalItem.style.paddingTop = '15px';
                    totalItem.style.fontWeight = '700';
                    totalItem.innerHTML = `
                        <span class="spec-summary-name">Diferença Total:</span>
                        <span class="spec-summary-price" style="font-size: 1.1rem;">
                            ${totalModifier > 0 ? '+' : ''}€${totalModifier.toFixed(2)}
                        </span>
                    `;
                    specsList.appendChild(totalItem);
                }
            }
            
            // Adicionar event listeners aos radios
            specRadios.forEach(radio => {
                radio.addEventListener('change', updatePrice);
            });
            
            // Calcular preço inicial
            updatePrice();
            
            console.log('✅ Sistema de especificações inicializado!');
        }
    </script>

    <script src="js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>
