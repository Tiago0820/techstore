<?php
session_start();

// mostrar erros durante desenvolvimento (remova/ajuste em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicializar carrinho
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Carregar configuração do DB (cria $pdo)
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

// Buscar produtos em destaque (apenas 4 mais recentes)
try {
    // Buscar produtos que tenham imagens definidas, ordenados do mais recente para o mais antigo
    $stmt = $pdo->query("SELECT * FROM products WHERE image IS NOT NULL AND image != '' ORDER BY id DESC LIMIT 4");
    $featured = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Erro ao buscar produtos: " . $e->getMessage());
    $featured = [];
}

// Buscar categorias
try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' LIMIT 6");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechShop - A sua loja de tecnologia</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
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
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="products.php">Produtos</a></li>
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

    <!-- Promotional Carousel -->
    <section class="promo-carousel">
        <div class="carousel-container">
            <div class="carousel-slides">
                <!-- Slide 1 - Promoção Smartphones -->
                <div class="carousel-slide active">
                    <div class="slide-content">
                        <div class="slide-text">
                            <span class="promo-badge">PROMOÇÃO LIMITADA</span>
                            <h2>Smartphones até -40%</h2>
                            <p>Compra agora e poupa até 300€ nos melhores smartphones do mercado</p>
                            <a href="products.php" class="promo-button">Ver Produtos</a>
                        </div>
                        <div class="slide-image">
                            <svg width="400" height="400" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="phoneGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
                                        <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
                                    </linearGradient>
                                </defs>
                                <!-- Smartphone -->
                                <rect x="120" y="50" width="160" height="300" rx="20" fill="url(#phoneGrad1)" opacity="0.9"/>
                                <rect x="130" y="70" width="140" height="250" rx="10" fill="#1a1a2e"/>
                                <circle cx="200" cy="330" r="15" fill="#ffffff" opacity="0.3"/>
                                <rect x="140" y="80" width="120" height="200" fill="#667eea" opacity="0.2"/>
                                <!-- Ícones flutuantes -->
                                <circle cx="80" cy="150" r="25" fill="#ffd700" opacity="0.8"/>
                                <text x="80" y="160" text-anchor="middle" fill="#fff" font-size="24">%</text>
                                <circle cx="320" cy="120" r="20" fill="#ff6b6b" opacity="0.8"/>
                                <text x="320" y="130" text-anchor="middle" fill="#fff" font-size="20">€</text>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Slide 2 - Promoção Laptops -->
                <div class="carousel-slide">
                    <div class="slide-content">
                        <div class="slide-text">
                            <span class="promo-badge">SUPER OFERTA</span>
                            <h2>Laptops Premium -30%</h2>
                            <p>Os melhores portáteis para trabalho e gaming com descontos incríveis</p>
                            <a href="products.php" class="promo-button">Explorar Agora</a>
                        </div>
                        <div class="slide-image">
                            <svg width="400" height="400" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="laptopGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#4facfe;stop-opacity:1" />
                                        <stop offset="100%" style="stop-color:#00f2fe;stop-opacity:1" />
                                    </linearGradient>
                                </defs>
                                <!-- Laptop -->
                                <rect x="80" y="140" width="240" height="150" rx="8" fill="url(#laptopGrad)"/>
                                <rect x="90" y="150" width="220" height="120" fill="#1a1a2e"/>
                                <rect x="100" y="160" width="200" height="100" fill="#4facfe" opacity="0.3"/>
                                <!-- Base do laptop -->
                                <path d="M 60 290 L 340 290 L 320 310 L 80 310 Z" fill="#667eea" opacity="0.8"/>
                                <!-- Estrelas de destaque -->
                                <circle cx="50" cy="100" r="15" fill="#ffd700"/>
                                <circle cx="350" cy="200" r="18" fill="#ff6b6b"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Slide 3 - Promoção Acessórios -->
                <div class="carousel-slide">
                    <div class="slide-content">
                        <div class="slide-text">
                            <span class="promo-badge">NOVIDADES</span>
                            <h2>Acessórios desde 9.99€</h2>
                            <p>Fones, capas, carregadores e muito mais com preços imbatíveis</p>
                            <a href="products.php" class="promo-button">Comprar Agora</a>
                        </div>
                        <div class="slide-image">
                            <svg width="400" height="400" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="accessGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#f093fb;stop-opacity:1" />
                                        <stop offset="100%" style="stop-color:#f5576c;stop-opacity:1" />
                                    </linearGradient>
                                </defs>
                                <!-- Fones de ouvido -->
                                <ellipse cx="150" cy="180" rx="40" ry="50" fill="url(#accessGrad)"/>
                                <ellipse cx="250" cy="180" rx="40" ry="50" fill="url(#accessGrad)"/>
                                <path d="M 150 140 Q 200 100 250 140" stroke="url(#accessGrad)" stroke-width="12" fill="none"/>
                                <!-- Círculos internos -->
                                <circle cx="150" cy="180" r="25" fill="#1a1a2e"/>
                                <circle cx="250" cy="180" r="25" fill="#1a1a2e"/>
                                <!-- Elementos decorativos -->
                                <circle cx="100" cy="280" r="30" fill="#ffd700" opacity="0.6"/>
                                <circle cx="300" cy="100" r="25" fill="#4facfe" opacity="0.6"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Slide 4 - Envio Grátis -->
                <div class="carousel-slide">
                    <div class="slide-content">
                        <div class="slide-text">
                            <span class="promo-badge">FRETE GRÁTIS</span>
                            <h2>Envio grátis em compras acima de 50€</h2>
                            <p>Recebe os teus produtos em casa sem custos adicionais</p>
                            <a href="products.php" class="promo-button">Aproveitar Oferta</a>
                        </div>
                        <div class="slide-image">
                            <svg width="400" height="400" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="truckGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
                                        <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
                                    </linearGradient>
                                </defs>
                                <!-- Caminhão de entrega -->
                                <rect x="100" y="150" width="120" height="80" rx="10" fill="url(#truckGrad)"/>
                                <rect x="220" y="170" width="80" height="60" rx="8" fill="#764ba2"/>
                                <!-- Rodas -->
                                <circle cx="130" cy="240" r="20" fill="#1a1a2e"/>
                                <circle cx="130" cy="240" r="12" fill="#667eea"/>
                                <circle cx="270" cy="240" r="20" fill="#1a1a2e"/>
                                <circle cx="270" cy="240" r="12" fill="#667eea"/>
                                <!-- Detalhes -->
                                <rect x="110" y="160" width="100" height="50" fill="#ffffff" opacity="0.2"/>
                                <!-- Linhas de velocidade -->
                                <line x1="50" y1="180" x2="80" y2="180" stroke="#ffd700" stroke-width="4" opacity="0.8"/>
                                <line x1="40" y1="200" x2="75" y2="200" stroke="#ffd700" stroke-width="4" opacity="0.6"/>
                                <line x1="55" y1="220" x2="85" y2="220" stroke="#ffd700" stroke-width="4" opacity="0.7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Arrows -->
            <button class="carousel-arrow carousel-prev" onclick="changeSlide(-1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="carousel-arrow carousel-next" onclick="changeSlide(1)">
                <i class="fas fa-chevron-right"></i>
            </button>

            <!-- Dots Indicator -->
            <div class="carousel-dots">
                <span class="dot active" onclick="currentSlide(0)"></span>
                <span class="dot" onclick="currentSlide(1)"></span>
                <span class="dot" onclick="currentSlide(2)"></span>
                <span class="dot" onclick="currentSlide(3)"></span>
            </div>
        </div>
    </section>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h2>Descubra a Tecnologia do Futuro</h2>
            <p>Os melhores produtos eletrônicos com os melhores preços</p>
            <a href="products.php" class="cta-button">Ver Produtos</a>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories-section">
        <h2>Categorias Populares</h2>
        <div class="categories-grid">
            <a href="products.php?category=Smartphones" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>Smartphones</h3>
                <p>Últimos lançamentos</p>
            </a>
            <a href="products.php?category=Laptops" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-laptop"></i>
                </div>
                <h3>Laptops</h3>
                <p>Para trabalho e gaming</p>
            </a>
            <a href="products.php?category=Tablets" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-tablet-alt"></i>
                </div>
                <h3>Tablets</h3>
                <p>Mobilidade e performance</p>
            </a>
            <a href="products.php?category=Acessórios" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-headphones"></i>
                </div>
                <h3>Acessórios</h3>
                <p>Fones, capas e mais</p>
            </a>
            <a href="products.php?category=Gaming" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-gamepad"></i>
                </div>
                <h3>Gaming</h3>
                <p>Consolas e acessórios</p>
            </a>
            <a href="products.php?category=Smart Home" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-home"></i>
                </div>
                <h3>Smart Home</h3>
                <p>Casa inteligente</p>
            </a>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products" id="produtos">
        <h2>Produtos em Destaque</h2>
        <div class="product-grid">
            <?php if (!empty($featured)): ?>
                <?php foreach ($featured as $row): ?>
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
                        
                        <?php if (!empty($row['image'])): 
                            // Verificar se o caminho já contém 'images/' no início
                            $imagePath = $row['image'];
                            if (strpos($imagePath, 'images/') !== 0 && strpos($imagePath, '/') !== 0 && strpos($imagePath, 'http') !== 0) {
                                $imagePath = 'images/' . $imagePath;
                            }
                        ?>
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
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
                                    <i class="fas fa-cart-plus"></i> Adicionar ao Carrinho
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #333; text-align: center; grid-column: 1 / -1;">Nenhum produto disponível no momento.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <h2>O Que Nossos Clientes Dizem</h2>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <p class="testimonial-text">"Excelente loja! Produtos de qualidade e entrega super rápida. Recomendo!"</p>
                <div class="testimonial-author">
                    <div class="author-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="author-info">
                        <h4>Maria Silva</h4>
                        <span>Cliente verificado</span>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <p class="testimonial-text">"Comprei um smartphone e chegou em perfeitas condições. Preços competitivos!"</p>
                <div class="testimonial-author">
                    <div class="author-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="author-info">
                        <h4>João Santos</h4>
                        <span>Cliente verificado</span>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <p class="testimonial-text">"Atendimento ao cliente impecável. Tive um problema e resolveram rapidamente!"</p>
                <div class="testimonial-author">
                    <div class="author-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="author-info">
                        <h4>Ana Costa</h4>
                        <span>Cliente verificado</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Payment Methods Section -->
    <section class="payment-methods-section">
        <div class="payment-container">
            <h3><i class="fas fa-credit-card"></i> Métodos de Pagamento Disponíveis</h3>
            <div class="payment-methods">
                <div class="payment-item" title="MB WAY">
                    <i class="fas fa-mobile-alt"></i>
                    <span class="payment-label">MB WAY</span>
                </div>
                <div class="payment-item" title="Multibanco">
                    <i class="fas fa-university"></i>
                    <span class="payment-label">Multibanco</span>
                </div>
                <div class="payment-item" title="Cartão de Crédito/Débito">
                    <i class="fas fa-credit-card"></i>
                    <span class="payment-label">Cartão</span>
                </div>
                <div class="payment-item" title="PayPal">
                    <i class="fab fa-cc-paypal"></i>
                    <span class="payment-label">PayPal</span>
                </div>
                <div class="payment-item" title="Pagamento à Cobrança">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span class="payment-label">À Cobrança</span>
                </div>
            </div>
            <p class="payment-secure">
                <i class="fas fa-lock"></i> Pagamentos 100% seguros e encriptados
            </p>
        </div>
    </section>

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
    
    <!-- Carousel Script -->
    <script>
        let currentSlideIndex = 0;
        let autoSlideInterval;
        
        // Iniciar carousel automático ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            showSlide(currentSlideIndex);
            startAutoSlide();
        });
        
        function showSlide(index) {
            const slides = document.querySelectorAll('.carousel-slide');
            const dots = document.querySelectorAll('.dot');
            
            if (index >= slides.length) {
                currentSlideIndex = 0;
            } else if (index < 0) {
                currentSlideIndex = slides.length - 1;
            } else {
                currentSlideIndex = index;
            }
            
            // Ocultar todos os slides
            slides.forEach(slide => {
                slide.classList.remove('active');
            });
            
            // Remover active de todos os dots
            dots.forEach(dot => {
                dot.classList.remove('active');
            });
            
            // Mostrar slide atual
            slides[currentSlideIndex].classList.add('active');
            dots[currentSlideIndex].classList.add('active');
        }
        
        function changeSlide(direction) {
            stopAutoSlide();
            showSlide(currentSlideIndex + direction);
            startAutoSlide();
        }
        
        function currentSlide(index) {
            stopAutoSlide();
            showSlide(index);
            startAutoSlide();
        }
        
        function startAutoSlide() {
            autoSlideInterval = setInterval(function() {
                showSlide(currentSlideIndex + 1);
            }, 5000); // Muda de slide a cada 5 segundos
        }
        
        function stopAutoSlide() {
            clearInterval(autoSlideInterval);
        }
        
        // Pausar ao passar o mouse
        const carouselContainer = document.querySelector('.carousel-container');
        if (carouselContainer) {
            carouselContainer.addEventListener('mouseenter', stopAutoSlide);
            carouselContainer.addEventListener('mouseleave', startAutoSlide);
        }
    </script>
    
    <script>
        (function() {
            'use strict';
            
            console.log('%c🚀 Sistema de Produtos Iniciando...', 'color: #667eea; font-size: 14px; font-weight: bold;');
            
            // Aguardar cart.js inicializar
            setTimeout(function() {
                initProductDetails();
            }, 600);
            
            function initProductDetails() {
                setupProductCards();
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
                        
                        // Se clicou em qualquer outra parte do card, redirecionar
                        console.log('✅ Redirecionando para página de detalhes...');
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const id = this.getAttribute('data-product-id');
                        
                        // Redirecionar para a página de detalhes
                        window.location.href = 'product_detail.php?id=' + id;
                    });
                });
                
                console.log('✅ Sistema de cards configurado!');
            }
            
        })();
    </script>
</body>
</html>
