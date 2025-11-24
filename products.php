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

// Buscar categorias únicas dos produtos
$categoriesResult = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
$categories = [];
if ($categoriesResult) {
    while ($cat = $categoriesResult->fetch_assoc()) {
        $categories[] = $cat['category'];
    }
}

// Aplicar filtros
$whereConditions = [];
$params = [];
$types = '';

// Filtro de categoria
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $whereConditions[] = "category = ?";
    $params[] = $_GET['category'];
    $types .= 's';
}

// Filtro de estado (novo, promoção, etc)
if (isset($_GET['estado'])) {
    if ($_GET['estado'] == 'novo') {
        $whereConditions[] = "(created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))";
    } elseif ($_GET['estado'] == 'promocao') {
        $whereConditions[] = "on_promotion = 1";
    }
}

// Filtro de pesquisa
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $whereConditions[] = "(name LIKE ? OR description LIKE ?)";
    $searchTerm = '%' . $_GET['search'] . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

// Construir query
$sql = "SELECT * FROM products";
if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}
$sql .= " ORDER BY name ASC";

// Preparar e executar query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Contar produtos em promoção
$promoCountResult = $conn->query("SELECT COUNT(*) as count FROM products WHERE on_promotion = 1");
$promoCount = $promoCountResult ? $promoCountResult->fetch_assoc()['count'] : 0;

$newCountResult = $conn->query("SELECT COUNT(*) as count FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$newCount = $newCountResult ? $newCountResult->fetch_assoc()['count'] : 0;

$totalCountResult = $conn->query("SELECT COUNT(*) as count FROM products");
$totalCount = $totalCountResult ? $totalCountResult->fetch_assoc()['count'] : 0;
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
            
            <!-- Hamburger Menu for Mobile -->
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php" class="active">Produtos</a></li>
                <li><a href="about.php">Sobre</a></li>
                <li><a href="contact.php">Contacto</a></li>
                <?php if (isset($_SESSION['username'])): ?>
                    <li class="mobile-only"><a href="logout.php">Sair</a></li>
                <?php else: ?>
                    <li class="mobile-only"><a href="login.php">Login</a></li>
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
                    <a href="login.php" class="user-icon"><i class="fas fa-user"></i></a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- Hero Banner -->
    <section class="products-hero">
        <div class="products-hero-content">
            <div class="products-hero-text">
                <span class="products-badge">NOVA COLEÇÃO</span>
                <h1>Descubra os Melhores Produtos de Tecnologia</h1>
                <p>Explore nossa seleção cuidadosamente escolhida dos produtos mais inovadores e de alta qualidade do mercado</p>
                <div class="products-hero-stats">
                    <div class="stat-item">
                        <i class="fas fa-box"></i>
                        <div>
                            <strong><?php echo $totalCount; ?>+</strong>
                            <span>Produtos</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-tags"></i>
                        <div>
                            <strong><?php echo $promoCount; ?></strong>
                            <span>Em Promoção</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-shipping-fast"></i>
                        <div>
                            <strong>Grátis</strong>
                            <span>Envio >50€</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="products-hero-image">
                <div class="floating-product">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="floating-product delay-1">
                    <i class="fas fa-laptop"></i>
                </div>
                <div class="floating-product delay-2">
                    <i class="fas fa-headphones"></i>
                </div>
            </div>
        </div>
    </section>

    <div class="products-page-container">
        <!-- Sidebar com filtros -->
        <aside class="filters-sidebar">
            <!-- Categorias -->
            <div class="filter-section">
                <div class="filter-header" onclick="toggleFilter('categories')">
                    <h3>Categorias</h3>
                    <i class="fas fa-chevron-up"></i>
                </div>
                <div class="filter-content active" id="categories">
                    <div class="search-box">
                        <input type="text" id="categorySearch" placeholder="Pesquisa" class="filter-search">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="filter-options" id="categoryList">
                        <?php 
                        // Buscar apenas categorias que existem na base de dados
                        $categoriesQuery = $conn->query("SELECT DISTINCT category, COUNT(*) as count FROM products WHERE category IS NOT NULL AND category != '' GROUP BY category ORDER BY category ASC");
                        
                        // Ícones para categorias
                        $categoryIcons = [
                            'Tecnologia' => 'fa-laptop',
                            'Smartphones' => 'fa-mobile',
                            'Tablets' => 'fa-tablet-alt',
                            'Audio' => 'fa-headphones',
                            'Gaming' => 'fa-gamepad',
                            'Wearables' => 'fa-watch',
                            'Casa Inteligente' => 'fa-home',
                            'Fotografia' => 'fa-camera',
                            'Informática' => 'fa-computer',
                            'TV' => 'fa-tv'
                        ];
                        
                        if ($categoriesQuery) {
                            while ($cat = $categoriesQuery->fetch_assoc()): 
                                $category = $cat['category'];
                                $count = $cat['count'];
                                $icon = $categoryIcons[$category] ?? 'fa-tag';
                            ?>
                                <label class="filter-option">
                                    <input type="checkbox" name="category" value="<?php echo htmlspecialchars($category); ?>" 
                                           <?php echo (isset($_GET['category']) && $_GET['category'] == $category) ? 'checked' : ''; ?>>
                                <span><i class="fas <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($category); ?></span>
                                <span class="count">(<?php echo $count; ?>)</span>
                            </label>
                        <?php endwhile; 
                        } // Fecha o if ($categoriesQuery)
                        ?>
                    </div>
                </div>
            </div>

            <!-- Estado -->
            <div class="filter-section">
                <div class="filter-header" onclick="toggleFilter('estado')">
                    <h3>Estado</h3>
                    <i class="fas fa-chevron-up"></i>
                </div>
                <div class="filter-content active" id="estado">
                    <div class="filter-options">
                        <label class="filter-option">
                            <input type="checkbox" name="estado" value="novo" 
                                   <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'novo') ? 'checked' : ''; ?>>
                            <span>Novo</span>
                            <span class="count">(<?php echo $newCount; ?>)</span>
                        </label>
                        <label class="filter-option">
                            <input type="checkbox" name="estado" value="promocao" 
                                   <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'promocao') ? 'checked' : ''; ?>>
                            <span>Em Promoção</span>
                            <span class="count">(<?php echo $promoCount; ?>)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Botões de ação -->
            <div class="filter-actions">
                <button class="btn-clear-filters" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Limpar Filtros
                </button>
                <button class="btn-apply-filters" onclick="applyFilters()">
                    <i class="fas fa-check"></i> Aplicar
                </button>
            </div>
        </aside>

        <!-- Grid de produtos -->
        <div class="products-main-content">
            <!-- Breadcrumb e Filtros Rápidos -->
            <div class="products-breadcrumb">
                <div class="breadcrumb-left">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Produtos</span>
                    <?php if (isset($_GET['category'])): ?>
                        <i class="fas fa-chevron-right"></i>
                        <span><?php echo htmlspecialchars($_GET['category']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="quick-filters">
                    <a href="products.php?estado=novo" class="quick-filter-btn <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'novo') ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Novidades
                    </a>
                    <a href="products.php?estado=promocao" class="quick-filter-btn <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'promocao') ? 'active' : ''; ?>">
                        <i class="fas fa-fire"></i> Promoções
                    </a>
                </div>
            </div>

            <div class="products-toolbar">
                <div class="products-count">
                    <i class="fas fa-check-circle"></i>
                    <span><strong><?php echo $result->num_rows; ?></strong> produtos encontrados</span>
                </div>
                <div class="products-view-options">
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid" title="Grade">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="view-btn" data-view="list" title="Lista">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    <div class="products-sort">
                        <label><i class="fas fa-sort-amount-down"></i></label>
                        <select id="sortProducts" onchange="sortProducts(this.value)">
                            <option value="relevance">Mais Relevantes</option>
                            <option value="name-asc">Nome (A-Z)</option>
                            <option value="name-desc">Nome (Z-A)</option>
                            <option value="price-asc">Menor Preço</option>
                            <option value="price-desc">Maior Preço</option>
                            <option value="newest">Mais Recentes</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="products-container">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()) { ?>
            <div class="product-card" 
                 data-product-id="<?php echo $row['id']; ?>"
                 data-product-name="<?php echo htmlspecialchars($row['name']); ?>"
                 data-product-description="<?php echo htmlspecialchars($row['description'] ?? 'Sem descrição disponível.'); ?>"
                 data-product-price="<?php echo ($row['on_promotion'] ?? 0) ? $row['promotion_price'] : $row['price']; ?>"
                 data-product-image="<?php echo htmlspecialchars($row['image'] ?? ''); ?>">
                
                <!-- Badge de Promoção -->
                <?php if ($row['on_promotion'] ?? 0): ?>
                    <div class="promotion-badge">-<?php echo number_format($row['discount_percentage'], 0); ?>%</div>
                <?php endif; ?>
                
                <!-- Badge de Novo -->
                <?php 
                $createdDate = isset($row['created_at']) ? strtotime($row['created_at']) : 0;
                $isNew = $createdDate > 0 && (time() - $createdDate) <= (30 * 24 * 60 * 60); // 30 dias
                if ($isNew): 
                ?>
                    <div class="new-badge">NOVO</div>
                <?php endif; ?>
                
                <!-- Botão de Favoritos -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <button class="wishlist-btn" 
                        title="Adicionar aos favoritos"
                        onclick="event.stopPropagation(); addToWishlist(<?php echo $row['id']; ?>, this)">
                    <i class="far fa-heart"></i>
                </button>
                <?php endif; ?>
                
                <?php if (!empty($row['image'])): 
                    // Verificar se o caminho já contém 'images/' no início
                    $imagePath = $row['image'];
                    if (strpos($imagePath, 'images/') !== 0 && strpos($imagePath, '/') !== 0 && strpos($imagePath, 'http') !== 0) {
                        $imagePath = 'images/' . $imagePath;
                    }
                ?>
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                        <div class="product-overlay">
                            <button class="quick-view-btn" onclick="event.stopPropagation(); window.location.href='product_detail.php?id=<?php echo $row['id']; ?>'">
                                <i class="fas fa-eye"></i> Vista Rápida
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="product-image placeholder">
                        <i class="fas fa-image"></i>
                        <span>Sem Imagem</span>
                    </div>
                <?php endif; ?>
                
                <div class="product-info">
                    <!-- Categoria -->
                    <?php if (!empty($row['category'])): ?>
                        <span class="product-category">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($row['category']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <h3 class="product-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                    
                    <!-- Preço -->
                    <?php if ($row['on_promotion'] ?? 0): ?>
                        <div class="price-container">
                            <span class="original-price">€<?php echo number_format($row['price'], 2, ',', '.'); ?></span>
                            <span class="price promotion-price">€<?php echo number_format($row['promotion_price'], 2, ',', '.'); ?></span>
                        </div>
                    <?php else: ?>
                        <span class="price">€<?php echo number_format($row['price'], 2, ',', '.'); ?></span>
                    <?php endif; ?>
                    
                    <div class="product-actions">
                        <button class="add-to-cart-btn" 
                                data-product-id="<?php echo $row['id']; ?>"
                                data-product-name="<?php echo htmlspecialchars($row['name']); ?>"
                                data-product-price="<?php echo ($row['on_promotion'] ?? 0) ? $row['promotion_price'] : $row['price']; ?>"
                                data-product-image="<?php echo htmlspecialchars($row['image'] ?? ''); ?>"
                                data-original-price="<?php echo $row['price']; ?>"
                                data-on-promotion="<?php echo $row['on_promotion'] ?? 0; ?>"
                                data-discount-percentage="<?php echo $row['discount_percentage'] ?? 0; ?>">
                            <i class="fas fa-shopping-cart"></i> Adicionar ao Carrinho
                        </button>
                    </div>
                </div>
            </div>
            <?php } ?>
        <?php else: ?>
            <div class="no-products">
                <i class="fas fa-box-open"></i>
                <h3>Nenhum produto encontrado</h3>
                <p>Tente ajustar os filtros ou pesquisar por outro termo</p>
                <button onclick="clearFilters()" class="btn-primary">
                    <i class="fas fa-redo"></i> Limpar Filtros
                </button>
            </div>
        <?php endif; ?>
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
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
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
                    
                    // Click no card - redirecionar para product_detail.php
                    card.addEventListener('click', function(e) {
                        // Se clicou no botão ou seus elementos internos, não fazer nada
                        if (e.target.closest('.add-to-cart-btn')) {
                            console.log('🛒 Click no botão - deixando cart.js processar');
                            return; // Deixa o cart.js processar
                        }
                        
                        // Se clicou em qualquer outra parte do card, redirecionar para product_detail.php
                        console.log('✅ Redirecionando para detalhes...');
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const id = this.getAttribute('data-product-id');
                        window.location.href = 'product_detail.php?id=' + id;
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

    <script>
        // Toggle filtros
        function toggleFilter(id) {
            const content = document.getElementById(id);
            const header = content.previousElementSibling;
            const icon = header.querySelector('i');
            
            content.classList.toggle('active');
            icon.classList.toggle('fa-chevron-up');
            icon.classList.toggle('fa-chevron-down');
        }

        // Pesquisa de categorias
        document.getElementById('categorySearch').addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            const options = document.querySelectorAll('#categoryList .filter-option');
            
            options.forEach(option => {
                const text = option.querySelector('span').textContent.toLowerCase();
                if (text.includes(search)) {
                    option.style.display = 'flex';
                } else {
                    option.style.display = 'none';
                }
            });
        });

        // Toggle View Grid/List
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const view = this.getAttribute('data-view');
                const container = document.querySelector('.products-container');
                
                if (view === 'list') {
                    container.style.gridTemplateColumns = '1fr';
                } else {
                    container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(280px, 1fr))';
                }
            });
        });

        // Aplicar filtros
        function applyFilters() {
            const url = new URL(window.location.href);
            
            // Categoria
            const categoryChecked = document.querySelector('input[name="category"]:checked');
            if (categoryChecked) {
                url.searchParams.set('category', categoryChecked.value);
            } else {
                url.searchParams.delete('category');
            }
            
            // Estado
            const estadoChecked = document.querySelector('input[name="estado"]:checked');
            if (estadoChecked) {
                url.searchParams.set('estado', estadoChecked.value);
            } else {
                url.searchParams.delete('estado');
            }
            
            window.location.href = url.toString();
        }

        // Limpar filtros
        function clearFilters() {
            const url = new URL(window.location.href);
            url.searchParams.delete('category');
            url.searchParams.delete('estado');
            url.searchParams.delete('search');
            window.location.href = url.toString();
        }

        // Ordenar produtos
        function sortProducts(sortBy) {
            const container = document.querySelector('.products-container');
            const products = Array.from(container.querySelectorAll('.product-card'));
            
            products.sort((a, b) => {
                const nameA = a.querySelector('.product-name').textContent;
                const nameB = b.querySelector('.product-name').textContent;
                const priceA = parseFloat(a.getAttribute('data-product-price'));
                const priceB = parseFloat(b.getAttribute('data-product-price'));
                
                switch(sortBy) {
                    case 'name-asc':
                        return nameA.localeCompare(nameB);
                    case 'name-desc':
                        return nameB.localeCompare(nameA);
                    case 'price-asc':
                        return priceA - priceB;
                    case 'price-desc':
                        return priceB - priceA;
                    case 'newest':
                        return 0; // Manter ordem original
                    case 'relevance':
                    default:
                        return 0;
                }
            });
            
            container.innerHTML = '';
            products.forEach(product => container.appendChild(product));
        }

        // Permitir apenas um checkbox por grupo
        document.querySelectorAll('input[name="category"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    document.querySelectorAll('input[name="category"]').forEach(cb => {
                        if (cb !== this) cb.checked = false;
                    });
                }
            });
        });

        document.querySelectorAll('input[name="estado"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    document.querySelectorAll('input[name="estado"]').forEach(cb => {
                        if (cb !== this) cb.checked = false;
                    });
                }
            });
        });

        // Animação de entrada dos cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '0';
                    entry.target.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        entry.target.style.transition = 'all 0.6s ease-out';
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, 100);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.product-card').forEach(card => {
            observer.observe(card);
        });

        // Função para adicionar aos favoritos
        function addToWishlist(productId, button) {
            const icon = button.querySelector('i');
            
            fetch('wishlist_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=toggle&product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle do ícone
                    if (data.action === 'added') {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        button.style.background = '#ff6b6b';
                        button.querySelector('i').style.color = 'white';
                        
                        // Mostrar notificação
                        showNotification('Adicionado aos favoritos!', 'success');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        button.style.background = 'white';
                        button.querySelector('i').style.color = '#ff6b6b';
                        
                        // Mostrar notificação
                        showNotification('Removido dos favoritos!', 'info');
                    }
                } else {
                    showNotification(data.message || 'Erro ao processar', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showNotification('Erro ao adicionar aos favoritos', 'error');
            });
        }

        // Função para mostrar notificação
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = 'notification notification-' + type;
            notification.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle') + '"></i> ' + message;
            notification.style.cssText = 'position: fixed; top: 100px; right: 20px; background: ' + (type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8') + '; color: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 10000; animation: slideIn 0.3s ease-out;';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>