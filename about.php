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
} else {
    require_once __DIR__ . '/config/db.php';
}

// Buscar membros da equipa do banco de dados
$team_members = [];
try {
    $stmt = $pdo->query("SELECT * FROM team_members ORDER BY display_order ASC, id ASC");
    $team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug - remover depois de verificar
    // error_log("Team members fetched: " . print_r($team_members, true));
} catch (Exception $e) {
    // Log do erro para debug
    error_log("Erro ao buscar equipa: " . $e->getMessage());
    
    // Se a tabela ainda não existir ou houver erro, usar membros padrão
    $team_members = [
        ['name' => 'Tiago Silva', 'role' => 'Fundador & CEO', 'description' => 'Tiago lidera a visão estratégica da TechShop e coordena as operações globais.', 'avatar_initials' => 'TS'],
        ['name' => 'Maria Costa', 'role' => 'Responsável de Produto', 'description' => 'Maria seleciona os melhores produtos e garante alta qualidade em cada lista.', 'avatar_initials' => 'MC'],
        ['name' => 'Luis Pereira', 'role' => 'Suporte & Logística', 'description' => 'Luis gere o suporte ao cliente e a cadeia de abastecimento para entregas rápidas.', 'avatar_initials' => 'LP']
    ];
}

// Marcas dos produtos na loja (baseado nos produtos existentes)
$brands = [
    ['name' => 'Apple', 'icon' => 'fab fa-apple', 'description' => 'Inovação e design premium'],
    ['name' => 'Samsung', 'icon' => 'fab fa-samsung', 'description' => 'Tecnologia de ponta'],
    ['name' => 'Sony', 'icon' => 'fas fa-headphones', 'description' => 'Qualidade de som superior'],
    ['name' => 'Nintendo', 'icon' => 'fas fa-gamepad', 'description' => 'Gaming portátil'],
    ['name' => 'PlayStation', 'icon' => 'fab fa-playstation', 'description' => 'Consolas de nova geração'],
    ['name' => 'Logitech', 'icon' => 'fas fa-mouse', 'description' => 'Acessórios profissionais'],
    ['name' => 'LG', 'icon' => 'fas fa-tv', 'description' => 'Monitores de alta performance'],
    ['name' => 'Amazon', 'icon' => 'fab fa-amazon', 'description' => 'Smart home inteligente'],
];
?>

<!DOCTYPE html>
<html lang="pt">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sobre - TechShop</title>
	<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
	<link rel="stylesheet" href="css/about.css?v=<?php echo time(); ?>">
	<link rel="stylesheet" href="css/cart.css?v=<?php echo time(); ?>">
	<link rel="stylesheet" href="css/dropdown.css?v=<?php echo time(); ?>">
	<link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
	<link rel="stylesheet" href="css/brands.css?v=<?php echo time(); ?>">
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
			<li><a href="products.php">Produtos</a></li>
			<li><a href="about.php" class="active">Sobre</a></li>
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

	<!-- Hero -->
	<section class="about-hero">
		<div class="about-container">
			<h1>Sobre a TechShop</h1>
			<p>Na TechShop oferecemos tecnologia de ponta, atendimento dedicado e entrega rápida. Somos apaixonados por criar experiências digitais e fornecer produtos que melhoram a vida dos nossos clientes.</p>
		</div>
	</section>

	<main class="about-container">
		<!-- Mission -->
		<section class="mission-section">
			<h2><i class="fas fa-bullseye"></i> Nossa Missão</h2>
			<div class="mission-content">
				<p>A missão da TechShop é tornar a tecnologia acessível e confiável para todos. Selecionamos cuidadosamente cada produto, garantindo qualidade e suporte contínuo. Trabalhamos para oferecer preços justos, envio rápido e um pós-venda eficaz.</p>
			</div>
		</section>

		<!-- Stats -->
		<section class="stats-section">
			<div class="stat-card">
				<i class="fas fa-users"></i>
				<div class="stat-number">50K+</div>
				<div class="stat-label">Clientes Felizes</div>
			</div>
			<div class="stat-card">
				<i class="fas fa-box-open"></i>
				<div class="stat-number">10K+</div>
				<div class="stat-label">Produtos Vendidos</div>
			</div>
			<div class="stat-card">
				<i class="fas fa-star"></i>
				<div class="stat-number">4.9★</div>
				<div class="stat-label">Avaliação Média</div>
			</div>
		</section>

		<!-- Values -->
		<section class="values-section">
			<h2><i class="fas fa-heart"></i> Nossos Valores</h2>
			<div class="values-grid">
				<div class="value-card">
					<i class="fas fa-shipping-fast"></i>
					<h3>Entrega Rápida</h3>
					<p>Logística eficiente para receber seu pedido com rapidez e segurança.</p>
				</div>
				<div class="value-card">
					<i class="fas fa-lock"></i>
					<h3>Compra Segura</h3>
					<p>Processos de pagamento protegidos e foco em privacidade e segurança.</p>
				</div>
				<div class="value-card">
					<i class="fas fa-headset"></i>
					<h3>Suporte 24/7</h3>
					<p>Equipe pronta para ajudar antes, durante e depois da compra.</p>
				</div>
			</div>
		</section>

		<!-- Brands Section -->
		<section class="brands-section">
			<div class="brands-container">
				<h2 class="brands-title"><i class="fas fa-star"></i> Marcas de Confiança</h2>
				<p class="brands-subtitle">Trabalhamos apenas com as melhores marcas do mercado</p>
				
				<div class="brands-carousel-wrapper">
					<button class="brand-arrow brand-arrow-left" id="brandArrowLeft" onclick="scrollBrands(-1)" style="display: none;">
						<i class="fas fa-chevron-left"></i>
					</button>
					
					<div class="brands-carousel" id="brandsCarousel">
						<?php foreach ($brands as $brand): ?>
							<div class="brand-card">
								<i class="<?php echo $brand['icon']; ?>"></i>
								<h3><?php echo htmlspecialchars($brand['name']); ?></h3>
								<p><?php echo htmlspecialchars($brand['description']); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
					
					<button class="brand-arrow brand-arrow-right" id="brandArrowRight" onclick="scrollBrands(1)">
						<i class="fas fa-chevron-right"></i>
					</button>
				</div>
				
				<div class="brands-benefits">
					<div class="benefit-item">
						<i class="fas fa-certificate"></i>
						<span>Produtos Originais</span>
					</div>
					<div class="benefit-item">
						<i class="fas fa-shield-alt"></i>
						<span>Garantia Oficial</span>
					</div>
					<div class="benefit-item">
						<i class="fas fa-trophy"></i>
						<span>Melhor Preço</span>
					</div>
				</div>
			</div>
		</section>

		<!-- Team -->
		<section class="team-section">
			<h2><i class="fas fa-users-cog"></i> Nossa Equipa</h2>
			
			<div class="team-carousel-wrapper">
				<button class="team-arrow team-arrow-left" id="teamArrowLeft" onclick="scrollTeam(-1)" style="display: none;">
					<i class="fas fa-chevron-left"></i>
				</button>
				
				<div class="team-carousel" id="teamCarousel">
					<?php foreach ($team_members as $member): ?>
						<div class="team-member">
							<div class="team-avatar"><?php echo htmlspecialchars($member['avatar_initials']); ?></div>
							<h3><?php echo htmlspecialchars($member['name']); ?></h3>
							<div class="team-role"><?php echo htmlspecialchars($member['role']); ?></div>
							<p><?php echo htmlspecialchars($member['description']); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
				
				<button class="team-arrow team-arrow-right" id="teamArrowRight" onclick="scrollTeam(1)">
					<i class="fas fa-chevron-right"></i>
				</button>
			</div>
		</section>

		<!-- CTA -->
		<section class="cta-section">
			<h2>Pronto para explorar nossos produtos?</h2>
			<p>Visite a nossa loja e descubra ofertas especiais em smartphones, laptops, tablets e acessórios.</p>
			<div class="cta-buttons">
				<a href="products.php" class="cta-btn cta-btn-primary"><i class="fas fa-shopping-bag"></i> Ver Produtos</a>
				<a href="contact.php" class="cta-btn cta-btn-secondary"><i class="fas fa-envelope"></i> Contacte-nos</a>
		</div>
	</section>
</main>

	<!-- Footer -->
	<footer class="footer">
		<div class="footer-content">
			<div class="footer-section">
				<h3><i class="fas fa-store"></i> TechShop</h3>
				<p>Sua loja online de confiança para tecnologia de ponta.</p>
				<p style="margin-top: 15px;"><i class="fas fa-map-marker-alt"></i> Lisboa, Portugal</p>
			</div>
			<div class="footer-section">
				<h3><i class="fas fa-link"></i> Links Rápidos</h3>
				<p><a href="index.php"><i class="fas fa-home"></i> Home</a></p>
				<p><a href="products.php"><i class="fas fa-shopping-bag"></i> Produtos</a></p>
				<p><a href="about.php"><i class="fas fa-info-circle"></i> Sobre Nós</a></p>
			</div>
			<div class="footer-section">
				<h3><i class="fas fa-envelope"></i> Contacto</h3>
				<p><i class="fas fa-envelope"></i> contato@techshop.pt</p>
				<p><i class="fas fa-phone"></i> +351 21 123 4567</p>
				<p><i class="fas fa-clock"></i> Seg-Sex: 9h-18h</p>
			</div>
			<div class="footer-section">
				<h3><i class="fas fa-share-alt"></i> Redes Sociais</h3>
				<p><a href="#"><i class="fab fa-facebook"></i> Facebook</a></p>
				<p><a href="#"><i class="fab fa-instagram"></i> Instagram</a></p>
				<p><a href="#"><i class="fab fa-twitter"></i> Twitter</a></p>
			</div>
		</div>
		<div class="footer-bottom">
			<p>&copy; 2025 TechShop. Todos os direitos reservados.</p>
		</div>
	</footer>

	<script src="js/cart.js?v=<?php echo time(); ?>"></script>
	<script src="js/main.js?v=<?php echo time(); ?>"></script>
	<script src="js/search.js?v=<?php echo time(); ?>"></script>
	
	<script>
		// Controle do carousel de marcas
		const carousel = document.getElementById('brandsCarousel');
		const arrowLeft = document.getElementById('brandArrowLeft');
		const arrowRight = document.getElementById('brandArrowRight');
		
		// Função para rolar o carousel de marcas
		function scrollBrands(direction) {
			const scrollAmount = 300; // pixels para rolar
			carousel.scrollBy({
				left: direction * scrollAmount,
				behavior: 'smooth'
			});
			
			// Atualizar visibilidade das setas após scroll
			setTimeout(updateArrows, 300);
		}
		
		// Função para atualizar visibilidade das setas
		function updateArrows() {
			const scrollLeft = carousel.scrollLeft;
			const maxScroll = carousel.scrollWidth - carousel.clientWidth;
			
			// Mostrar/ocultar seta esquerda
			if (scrollLeft > 10) {
				arrowLeft.style.display = 'flex';
			} else {
				arrowLeft.style.display = 'none';
			}
			
			// Mostrar/ocultar seta direita
			if (scrollLeft < maxScroll - 10) {
				arrowRight.style.display = 'flex';
			} else {
				arrowRight.style.display = 'none';
			}
		}
		
		// Atualizar setas quando o usuário rolar manualmente
		carousel.addEventListener('scroll', updateArrows);
		
		// Verificar inicialmente
		window.addEventListener('load', updateArrows);
		window.addEventListener('resize', updateArrows);
		
		// Controle do carousel da equipa
		const teamCarousel = document.getElementById('teamCarousel');
		const teamArrowLeft = document.getElementById('teamArrowLeft');
		const teamArrowRight = document.getElementById('teamArrowRight');
		
		// Função para rolar o carousel da equipa
		function scrollTeam(direction) {
			const scrollAmount = 350; // pixels para rolar
			teamCarousel.scrollBy({
				left: direction * scrollAmount,
				behavior: 'smooth'
			});
			
			// Atualizar visibilidade das setas após scroll
			setTimeout(updateTeamArrows, 300);
		}
		
		// Função para atualizar visibilidade das setas da equipa
		function updateTeamArrows() {
			const scrollLeft = teamCarousel.scrollLeft;
			const maxScroll = teamCarousel.scrollWidth - teamCarousel.clientWidth;
			
			// Mostrar/ocultar seta esquerda
			if (scrollLeft > 10) {
				teamArrowLeft.style.display = 'flex';
			} else {
				teamArrowLeft.style.display = 'none';
			}
			
			// Mostrar/ocultar seta direita
			if (scrollLeft < maxScroll - 10) {
				teamArrowRight.style.display = 'flex';
			} else {
				teamArrowRight.style.display = 'none';
			}
		}
		
		// Atualizar setas quando o usuário rolar manualmente
		teamCarousel.addEventListener('scroll', updateTeamArrows);
		
		// Verificar inicialmente
		window.addEventListener('load', function() {
			updateArrows();
			updateTeamArrows();
		});
		window.addEventListener('resize', function() {
			updateArrows();
			updateTeamArrows();
		});
	</script>
</body>
</html>
