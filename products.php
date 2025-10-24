<?php
session_start();

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

// Configuração do banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "techshop";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
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

// Buscar todos os produtos
$result = $conn->query("SELECT * FROM products ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - TechShop</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/products.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/home.css?v=<?php echo time(); ?>">
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
                <li><a href="products.php" class="active">Produtos</a></li>
                <li><a href="about.php">Sobre</a></li>
                <li><a href="contact.php">Contacto</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
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

    <div class="products-header">
        <h1>Nossos Produtos</h1>
        <p>Explore nossa coleção completa de tecnologia</p>
    </div>

    <div class="products-container">
        <?php while($row = $result->fetch_assoc()) { ?>
            <div class="product-card" 
                 data-product-id="<?php echo $row['id']; ?>"
                 data-product-name="<?php echo htmlspecialchars($row['name']); ?>"
                 data-product-description="<?php echo htmlspecialchars($row['description'] ?? 'Sem descrição disponível.'); ?>"
                 data-product-price="<?php echo $row['price']; ?>"
                 data-product-image="<?php echo htmlspecialchars($row['image'] ?? ''); ?>">
                <?php if (!empty($row['image'])): ?>
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                    </div>
                <?php else: ?>
                    <div class="product-image">
                        <img src="images/placeholder.png" alt="<?php echo htmlspecialchars($row['name']); ?>" style="opacity: 0.3;">
                    </div>
                <?php endif; ?>
                <div class="product-info">
                    <h3 class="product-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                    <span class="price">€<?php echo number_format($row['price'], 2, ',', '.'); ?></span>
                    <div class="product-actions">
                        <button class="add-to-cart add-to-cart-btn" 
                                data-product-id="<?php echo $row['id']; ?>"
                                data-product-name="<?php echo htmlspecialchars($row['name']); ?>"
                                data-product-price="<?php echo $row['price']; ?>"
                                data-product-image="<?php echo htmlspecialchars($row['image'] ?? ''); ?>">
                            <i class="fas fa-cart-plus"></i> Adicionar ao Carrinho
                        </button>
                    </div>
                </div>
            </div>
        <?php } ?>
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

    <!-- Modal de Detalhes do Produto -->
    <div id="productModal" class="modal">
        <div class="modal-content modal-fullscreen">
            <button class="modal-close modal-back-btn" aria-label="Voltar">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar</span>
            </button>
            <div class="modal-body">
                <div class="modal-product-gallery">
                    <div class="modal-product-image">
                        <img id="modalProductImage" src="" alt="">
                    </div>
                    <div class="product-badge">Novo</div>
                </div>
                <div class="modal-product-details">
                    <div class="product-category">
                        <i class="fas fa-tag"></i> Tecnologia
                    </div>
                    <h2 id="modalProductName" class="product-title"></h2>
                    
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
                    
                    <div class="product-price-section">
                        <div class="current-price">
                            <span class="currency">€</span>
                            <span id="modalProductPrice" class="price-value"></span>
                        </div>
                        <div class="price-info">
                            <i class="fas fa-truck"></i> Envio grátis
                        </div>
                    </div>
                    
                    <div class="product-tabs">
                        <div class="tabs-header">
                            <button class="tab-btn active" data-tab="about">
                                <i class="fas fa-info-circle"></i>
                                <span>Sobre o Produto</span>
                            </button>
                            <button class="tab-btn" data-tab="features">
                                <i class="fas fa-check-circle"></i>
                                <span>Características</span>
                            </button>
                        </div>
                        <div class="tabs-content">
                            <div class="tab-panel active" id="tab-about">
                                <div class="description-content">
                                    <p id="modalProductDescription" class="description-text"></p>
                                </div>
                            </div>
                            <div class="tab-panel" id="tab-features">
                                <ul class="features-list">
                                    <li><i class="fas fa-shield-alt"></i> Garantia de 2 anos</li>
                                    <li><i class="fas fa-sync-alt"></i> 30 dias para devolução</li>
                                    <li><i class="fas fa-headset"></i> Suporte técnico incluído</li>
                                    <li><i class="fas fa-lock"></i> Pagamento seguro</li>
                                    <li><i class="fas fa-certificate"></i> Produto original certificado</li>
                                    <li><i class="fas fa-star"></i> Melhor avaliado pelos clientes</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="product-actions-modal">
                        <div class="quantity-selector">
                            <button class="qty-btn" onclick="decreaseQty()">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" id="modalQuantity" value="1" min="1" max="99" readonly>
                            <button class="qty-btn" onclick="increaseQty()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button class="add-to-cart-modal add-to-cart-btn" id="modalAddToCart">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Adicionar ao Carrinho</span>
                        </button>
                    </div>
                    
                    <div class="product-meta">
                        <div class="meta-item">
                            <i class="fas fa-barcode"></i>
                            <span>SKU: <strong id="modalProductSKU">TEC-001</strong></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-box"></i>
                            <span>Disponibilidade: <strong class="in-stock">Em Stock</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/cart.js?v=<?php echo time(); ?>"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        (function() {
            'use strict';
            
            console.log('%c🚀 Sistema de Detalhes de Produtos Iniciando...', 'color: #667eea; font-size: 14px; font-weight: bold;');
            
            // Aguardar cart.js inicializar
            setTimeout(function() {
                initProductDetails();
            }, 600);
            
            function initProductDetails() {
                setupProductCards();
                setupModal();
            }
            
            function setupProductCards() {
                const cards = document.querySelectorAll('.product-card');
                console.log('✅ Cards encontrados:', cards.length);
                
                if (cards.length === 0) {
                    console.warn('⚠️ Nenhum card de produto encontrado!');
                    return;
                }
                
                cards.forEach(function(card, index) {
                    // Adicionar cursor
                    card.style.cursor = 'pointer';
                    
                    // Click no card - usar capture phase para capturar ANTES do cart.js
                    card.addEventListener('click', function(e) {
                        // Se clicou no botão ou seus elementos internos, não fazer nada
                        if (e.target.closest('.add-to-cart-btn')) {
                            console.log('🛒 Click no botão - deixando cart.js processar');
                            return; // Deixa o cart.js processar
                        }
                        
                        // Se clicou em qualquer outra parte do card, abrir modal
                        console.log('✅ Abrindo modal...');
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const id = this.getAttribute('data-product-id');
                        const name = this.getAttribute('data-product-name');
                        const description = this.getAttribute('data-product-description') || 'Sem descrição disponível.';
                        const price = parseFloat(this.getAttribute('data-product-price')) || 0;
                        const image = this.getAttribute('data-product-image') || '';
                        
                        openModal(id, name, description, price, image);
                    });
                });
                
                console.log('✅ Sistema de cards configurado!');
            }
            
            function setupModal() {
                const modal = document.getElementById('productModal');
                const closeBtn = document.querySelector('.modal-close');
                
                if (!modal) {
                    console.error('❌ Modal não encontrado!');
                    return;
                }
                
                // Fechar com X
                if (closeBtn) {
                    closeBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        closeModal();
                    });
                }
                
                // Fechar clicando fora
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                });
                
                // Fechar com ESC
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && modal.style.display === 'flex') {
                        closeModal();
                    }
                });
                
                console.log('✅ Sistema de modal configurado!');
            }
            
            function openModal(id, name, description, price, image) {
                console.log('📦 Abrindo modal para:', name);
                
                const modal = document.getElementById('productModal');
                const modalName = document.getElementById('modalProductName');
                const modalPrice = document.getElementById('modalProductPrice');
                const modalDescription = document.getElementById('modalProductDescription');
                const modalImage = document.getElementById('modalProductImage');
                const modalAddBtn = document.getElementById('modalAddToCart');
                
                if (!modal) {
                    console.error('❌ Elementos do modal não encontrados!');
                    return;
                }
                
                // Preencher dados
                if (modalName) modalName.textContent = name;
                if (modalPrice) modalPrice.textContent = price.toFixed(2).replace('.', ',');
                if (modalDescription) modalDescription.textContent = description;
                
                // Imagem
                if (image && image !== '' && modalImage) {
                    modalImage.src = image;
                    modalImage.alt = name;
                    if (modalImage.parentElement) {
                        modalImage.parentElement.style.display = 'flex';
                    }
                } else if (modalImage && modalImage.parentElement) {
                    modalImage.parentElement.style.display = 'none';
                }
                
                // Configurar botão adicionar
                if (modalAddBtn) {
                    modalAddBtn.setAttribute('data-product-id', id);
                    modalAddBtn.setAttribute('data-product-name', name);
                    modalAddBtn.setAttribute('data-product-price', price);
                    modalAddBtn.setAttribute('data-product-image', image);
                }
                
                // Mostrar modal
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                console.log('✅ Modal aberto com sucesso!');
            }
            
            function closeModal() {
                const modal = document.getElementById('productModal');
                if (modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    console.log('✅ Modal fechado');
                }
            }
            
            // Exportar funções globalmente
            window.openProductModal = openModal;
            window.closeProductModal = closeModal;
            
        })();
        
        // Funções para o seletor de quantidade
        function increaseQty() {
            const input = document.getElementById('modalQuantity');
            let value = parseInt(input.value) || 1;
            if (value < 99) {
                input.value = value + 1;
            }
        }
        
        function decreaseQty() {
            const input = document.getElementById('modalQuantity');
            let value = parseInt(input.value) || 1;
            if (value > 1) {
                input.value = value - 1;
            }
        }
        
        // Sistema de Tabs para o Modal
        document.addEventListener('click', function(e) {
            if (e.target.closest('.tab-btn')) {
                const tabBtn = e.target.closest('.tab-btn');
                const tabName = tabBtn.getAttribute('data-tab');
                
                // Remover classe active de todos os botões e painéis
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
                
                // Adicionar classe active ao botão e painel clicados
                tabBtn.classList.add('active');
                document.getElementById('tab-' + tabName).classList.add('active');
            }
        });
    </script>
</body>
</html>