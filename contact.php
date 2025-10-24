<?php
session_start();
require_once __DIR__ . '/config/db.php';

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
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as unread FROM contacts WHERE user_id = ? AND customer_unread = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $unreadTickets = $stmt->fetch()['unread'];
    } catch (Exception $e) {
        $unreadTickets = 0;
    }
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validação básica
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um email válido.';
    } else {
        try {
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            $stmt = $pdo->prepare("INSERT INTO contacts (user_id, name, email, phone, subject, message, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $name, $email, $phone, $subject, $message]);
            
            $success = true;
        } catch (Exception $e) {
            $error = 'Erro ao enviar mensagem. Por favor, tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - TechShop</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/contact.css?v=<?php echo time(); ?>">
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
                <li><a href="products.php">Produtos</a></li>
                <li><a href="about.php">Sobre</a></li>
                <li><a href="contact.php" class="active">Contacto</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="backoffice/backoffice.php">Admin</a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['username'])): ?>
                    <li><a href="my_tickets.php">Meus Tickets</a></li>
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

    <!-- Hero -->
    <section class="contact-hero">
        <div class="contact-container">
            <h1><i class="fas fa-envelope"></i> Entre em Contacto</h1>
            <p>Estamos aqui para ajudar! Envie-nos uma mensagem e responderemos o mais breve possível.</p>
        </div>
    </section>

    <main class="contact-container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <strong>Mensagem enviada com sucesso!</strong> Entraremos em contacto em breve.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Erro:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="contact-content">
            <!-- Contact Form -->
            <div class="contact-form-section">
                <h2><i class="fas fa-paper-plane"></i> Envie-nos uma Mensagem</h2>
                <form method="POST" class="contact-form">
                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-user"></i> Nome Completo *
                        </label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email *
                        </label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-phone"></i> Telefone
                        </label>
                        <input type="tel" id="phone" name="phone" 
                               placeholder="+351 912 345 678">
                    </div>

                    <div class="form-group">
                        <label for="subject">
                            <i class="fas fa-tag"></i> Assunto *
                        </label>
                        <select id="subject" name="subject" required>
                            <option value="">Selecione um assunto</option>
                            <option value="Informação sobre Produto">Informação sobre Produto</option>
                            <option value="Apoio Técnico">Apoio Técnico</option>
                            <option value="Encomenda/Envio">Encomenda/Envio</option>
                            <option value="Devolução/Troca">Devolução/Troca</option>
                            <option value="Reclamação">Reclamação</option>
                            <option value="Sugestão">Sugestão</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">
                            <i class="fas fa-comment-alt"></i> Mensagem *
                        </label>
                        <textarea id="message" name="message" rows="6" required 
                                  placeholder="Descreva a sua questão ou pedido..."></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Enviar Mensagem
                    </button>
                </form>
            </div>

            <!-- Contact Info -->
            <div class="contact-info-section">
                <h2><i class="fas fa-info-circle"></i> Informações de Contacto</h2>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Morada</h3>
                        <p>Av. da Liberdade, 123<br>1250-142 Lisboa, Portugal</p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Email</h3>
                        <p><a href="mailto:contato@techshop.pt">contato@techshop.pt</a></p>
                        <p><a href="mailto:suporte@techshop.pt">suporte@techshop.pt</a></p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-content">
                        <h3>Telefone</h3>
                        <p><a href="tel:+351211234567">+351 21 123 4567</a></p>
                        <p class="info-small">Seg-Sex: 9h-18h</p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <h3>Horário de Atendimento</h3>
                        <p>Segunda a Sexta: 9h - 18h</p>
                        <p>Sábado: 10h - 14h</p>
                        <p>Domingo: Fechado</p>
                    </div>
                </div>

                <div class="social-links">
                    <h3><i class="fas fa-share-alt"></i> Redes Sociais</h3>
                    <div class="social-icons">
                        <a href="#" title="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <section class="faq-section">
            <h2><i class="fas fa-question-circle"></i> Perguntas Frequentes</h2>
            <div class="faq-grid">
                <div class="faq-item">
                    <h3><i class="fas fa-truck"></i> Quanto tempo demora o envio?</h3>
                    <p>Entregas em Portugal Continental: 2-3 dias úteis. Ilhas: 5-7 dias úteis.</p>
                </div>
                <div class="faq-item">
                    <h3><i class="fas fa-undo"></i> Qual é a política de devolução?</h3>
                    <p>Tem 30 dias para devolver produtos não utilizados com a embalagem original.</p>
                </div>
                <div class="faq-item">
                    <h3><i class="fas fa-credit-card"></i> Quais os métodos de pagamento?</h3>
                    <p>Aceitamos Multibanco, MB WAY, Cartão de Crédito e Paypal.</p>
                </div>
                <div class="faq-item">
                    <h3><i class="fas fa-shield-alt"></i> Os produtos têm garantia?</h3>
                    <p>Todos os produtos têm garantia mínima de 2 anos do fabricante.</p>
                </div>
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
                <p><a href="contact.php"><i class="fas fa-envelope"></i> Contacto</a></p>
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
</body>
</html>
