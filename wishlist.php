<?php
session_start();

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/db.php';
require_once 'cart_handler.php';

// Buscar favoritos do usuário
try {
    $stmt = $pdo->prepare("
        SELECT p.*, w.created_at as added_at 
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        WHERE w.user_id = ? 
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $favorites = $stmt->fetchAll();
} catch (Exception $e) {
    $favorites = [];
}

$cartCount = getCartCount();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Favoritos - TechShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="css/dropdown.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .wishlist-container {
            min-height: calc(100vh - 200px);
            padding: 120px 40px 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }

        .wishlist-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(240, 147, 251, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(245, 87, 108, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        .wishlist-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .page-title {
            text-align: center;
            color: white;
            font-size: 3rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.3);
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            animation: fadeInDown 0.6s ease;
        }

        .page-title i {
            animation: heartBeatTitle 1.5s infinite;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes heartBeatTitle {
            0%, 100% {
                transform: scale(1);
            }
            10%, 30% {
                transform: scale(1.1);
            }
            20%, 40% {
                transform: scale(1.05);
            }
        }

        .wishlist-subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            margin-bottom: 40px;
            font-weight: 400;
        }

        .wishlist-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px 50px;
            color: #764ba2;
            text-align: center;
            border: 3px solid #667eea;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4),
                        0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            transform: scale(1);
            transition: all 0.3s ease;
            animation: statPulse 2s ease-in-out infinite;
        }

        .stat-card:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.5),
                        0 0 0 1px rgba(255, 255, 255, 0.5) inset;
        }

        @keyframes statPulse {
            0%, 100% {
                box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4),
                            0 0 0 1px rgba(255, 255, 255, 0.5) inset,
                            0 0 20px rgba(102, 126, 234, 0.3);
            }
            50% {
                box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5),
                            0 0 0 1px rgba(255, 255, 255, 0.5) inset,
                            0 0 30px rgba(102, 126, 234, 0.5);
            }
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 900;
            display: block;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
            animation: numberGlow 2s ease-in-out infinite;
        }

        @keyframes numberGlow {
            0%, 100% {
                filter: drop-shadow(0 0 5px rgba(102, 126, 234, 0.4));
            }
            50% {
                filter: drop-shadow(0 0 15px rgba(102, 126, 234, 0.6));
            }
        }

        .stat-label {
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #667eea;
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
            animation: fadeInUp 0.6s ease 0.2s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .wishlist-empty {
            background: white;
            border-radius: 25px;
            padding: 80px 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 0.6s ease;
        }

        .wishlist-empty i {
            font-size: 6rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            animation: floatIcon 3s ease-in-out infinite;
        }

        @keyframes floatIcon {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        .wishlist-empty h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 2rem;
        }

        .wishlist-empty p {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .wishlist-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            animation: cardAppear 0.5s ease backwards;
        }

        @keyframes cardAppear {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .wishlist-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.3);
        }

        .wishlist-card-image {
            width: 100%;
            height: 280px;
            overflow: hidden;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            position: relative;
        }

        .wishlist-card-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .wishlist-card:hover .wishlist-card-image::before {
            opacity: 1;
        }

        .wishlist-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .wishlist-card:hover .wishlist-card-image img {
            transform: scale(1.1);
        }

        .remove-favorite {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 50%;
            color: #f5576c;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 2;
        }

        .remove-favorite:hover {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            transform: scale(1.15) rotate(90deg);
            box-shadow: 0 6px 20px rgba(245, 87, 108, 0.4);
        }

        .wishlist-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .wishlist-card-info {
            padding: 25px;
            background: white;
        }

        .wishlist-card-info h3 {
            font-size: 1.3rem;
            margin-bottom: 12px;
            color: #1a1a2e;
            font-weight: 700;
            line-height: 1.4;
            min-height: 2.8em;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .wishlist-card-price {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .wishlist-card-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #666;
            font-size: 0.85rem;
        }

        .meta-item i {
            color: #667eea;
            font-size: 0.9rem;
        }

        .wishlist-card-actions {
            display: flex;
            gap: 10px;
        }

        .btn-add-to-cart {
            flex: 1;
            padding: 14px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-add-to-cart::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-add-to-cart:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-add-to-cart:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }

        .btn-add-to-cart i,
        .btn-add-to-cart span {
            position: relative;
            z-index: 1;
        }

        .btn-buy-now {
            flex: 1;
            padding: 14px 16px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }

        .btn-buy-now::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-buy-now:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-buy-now:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(245, 87, 108, 0.5);
        }

        .btn-buy-now i,
        .btn-buy-now span {
            position: relative;
            z-index: 1;
        }

        .btn-view-product {
            width: 50px;
            height: 50px;
            padding: 0;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-view-product:hover {
            background: #667eea;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .wishlist-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .wishlist-card-image {
                height: 220px;
            }

            .stat-card {
                padding: 25px 35px;
            }

            .stat-number {
                font-size: 3rem;
            }

            .stat-label {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .wishlist-container {
                padding: 100px 20px 40px;
            }

            .wishlist-grid {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 1.8rem;
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
                <li><a href="contact.php">Contacto</a></li>
            </ul>

            <div class="nav-icons">
                <a href="javascript:void(0);" class="cart-icon" id="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                </a>
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
                            <a href="wishlist.php" class="active"><i class="fas fa-heart"></i> Favoritos</a>
                            <a href="my_tickets.php"><i class="fas fa-ticket-alt"></i> Meus Tickets</a>
                        <?php endif; ?>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="wishlist-container">
        <div class="wishlist-wrapper">
            <h1 class="page-title">
                <i class="fas fa-heart"></i> 
                Meus Favoritos
            </h1>
            
            <?php if (!empty($favorites)): ?>
                <p class="wishlist-subtitle">
                    Aqui estão os produtos que você salvou para mais tarde
                </p>
                
                <div class="wishlist-stats">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo count($favorites); ?></span>
                        <span class="stat-label">Produto<?php echo count($favorites) > 1 ? 's' : ''; ?> Favoritado<?php echo count($favorites) > 1 ? 's' : ''; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($favorites)): ?>
                <div class="wishlist-empty">
                    <i class="fas fa-heart-broken"></i>
                    <h2>Ainda não tem favoritos</h2>
                    <p>Adicione produtos aos favoritos para encontrá-los facilmente mais tarde.<br>
                    Clique no ícone de coração ❤️ nos produtos que você gostou!</p>
                    <a href="products.php" class="btn-add-to-cart" style="max-width: 300px; margin: 0 auto; display: inline-flex;">
                        <i class="fas fa-shopping-bag"></i> 
                        <span>Explorar Produtos</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="wishlist-grid">
                    <?php foreach ($favorites as $index => $product): ?>
                        <div class="wishlist-card" data-product-id="<?php echo $product['id']; ?>" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                            <div class="wishlist-card-image">
                                <?php if (!empty($product['image'])): 
                                    // Verificar se o caminho já contém 'images/' no início
                                    $imagePath = $product['image'];
                                    if (strpos($imagePath, 'images/') !== 0 && strpos($imagePath, '/') !== 0 && strpos($imagePath, 'http') !== 0) {
                                        $imagePath = 'images/' . $imagePath;
                                    }
                                ?>
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #ccc;">
                                        <i class="fas fa-image" style="font-size: 4rem;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($product['on_promotion']) && $product['on_promotion']): ?>
                                    <span class="wishlist-badge">-<?php echo number_format($product['discount_percentage'], 0); ?>% OFF</span>
                                <?php endif; ?>
                                
                                <button class="remove-favorite" data-product-id="<?php echo $product['id']; ?>" title="Remover dos favoritos">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="wishlist-card-info">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                
                                <div class="wishlist-card-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span>Adicionado <?php 
                                            $date = new DateTime($product['added_at']);
                                            $now = new DateTime();
                                            $diff = $now->diff($date);
                                            if ($diff->days == 0) {
                                                echo 'hoje';
                                            } elseif ($diff->days == 1) {
                                                echo 'ontem';
                                            } else {
                                                echo 'há ' . $diff->days . ' dias';
                                            }
                                        ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-box"></i>
                                        <span>Em stock</span>
                                    </div>
                                </div>
                                
                                <div class="wishlist-card-price">
                                    <?php if (isset($product['on_promotion']) && $product['on_promotion']): ?>
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            <span style="text-decoration: line-through; font-size: 1rem; opacity: 0.6;">
                                                €<?php echo number_format($product['price'], 2, ',', '.'); ?>
                                            </span>
                                            <span>
                                                €<?php echo number_format($product['promotion_price'], 2, ',', '.'); ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        €<?php echo number_format($product['price'], 2, ',', '.'); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="wishlist-card-actions">
                                    <button class="btn-add-to-cart add-to-cart-btn"
                                            data-product-id="<?php echo $product['id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                            data-product-price="<?php echo number_format(isset($product['on_promotion']) && $product['on_promotion'] ? $product['promotion_price'] : $product['price'], 2, '.', ''); ?>"
                                            data-product-image="<?php echo htmlspecialchars($product['image'] ?? ''); ?>">
                                        <i class="fas fa-cart-plus"></i>
                                        <span>Carrinho</span>
                                    </button>
                                    <button class="btn-buy-now buy-now-btn"
                                            data-product-id="<?php echo $product['id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                            data-product-price="<?php echo number_format(isset($product['on_promotion']) && $product['on_promotion'] ? $product['promotion_price'] : $product['price'], 2, '.', ''); ?>"
                                            data-product-image="<?php echo htmlspecialchars($product['image'] ?? ''); ?>">
                                        <i class="fas fa-bolt"></i>
                                        <span>Comprar</span>
                                    </button>
                                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn-view-product" title="Ver detalhes">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2024 TechShop. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="js/cart.js"></script>
    <script>
        // Remover favorito
        document.querySelectorAll('.remove-favorite').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.stopPropagation();
                const productId = this.getAttribute('data-product-id');
                const card = this.closest('.wishlist-card');
                
                if (!confirm('Deseja remover este produto dos favoritos?')) {
                    return;
                }
                
                // Animação de saída
                card.style.animation = 'fadeOut 0.5s ease forwards';
                
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
                        setTimeout(() => {
                            card.remove();
                            
                            // Se não houver mais favoritos, recarregar a página
                            if (document.querySelectorAll('.wishlist-card').length === 0) {
                                location.reload();
                            } else {
                                // Atualizar contador
                                updateStats();
                            }
                        }, 500);
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    alert('Erro ao remover favorito');
                    card.style.animation = '';
                }
            });
        });
        
        // Adicionar feedback visual ao adicionar ao carrinho
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const card = this.closest('.wishlist-card');
                card.classList.add('adding-to-cart');
                
                setTimeout(() => {
                    card.classList.remove('adding-to-cart');
                }, 500);
            });
        });
        
        // Funcionalidade do botão Comprar Agora
        document.querySelectorAll('.buy-now-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const productId = this.getAttribute('data-product-id');
                const productName = this.getAttribute('data-product-name');
                const productPrice = this.getAttribute('data-product-price');
                const productImage = this.getAttribute('data-product-image');
                
                const card = this.closest('.wishlist-card');
                card.classList.add('adding-to-cart');
                
                try {
                    // Adicionar ao carrinho
                    const response = await fetch('cart_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'add',
                            product_id: productId,
                            product_name: productName,
                            product_price: productPrice,
                            product_image: productImage,
                            quantity: 1
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Redirecionar para checkout após pequeno delay
                        setTimeout(() => {
                            window.location.href = 'checkout.php';
                        }, 300);
                    } else {
                        alert('Erro ao adicionar produto. Tente novamente.');
                        card.classList.remove('adding-to-cart');
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    alert('Erro ao processar. Tente novamente.');
                    card.classList.remove('adding-to-cart');
                }
            });
        });
        
        // Função para atualizar estatísticas
        function updateStats() {
            const count = document.querySelectorAll('.wishlist-card').length;
            const statNumber = document.querySelector('.stat-number');
            const statLabel = document.querySelector('.stat-label');
            
            if (statNumber && statLabel) {
                statNumber.textContent = count;
                statLabel.textContent = `Produto${count > 1 ? 's' : ''} Favoritado${count > 1 ? 's' : ''}`;
            }
        }
    </script>

    <style>
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: scale(1);
            }
            to {
                opacity: 0;
                transform: scale(0.8) rotate(5deg);
            }
        }
        
        @keyframes successPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        .adding-to-cart {
            animation: successPulse 0.5s ease;
        }
    </style>
</body>
</html>
