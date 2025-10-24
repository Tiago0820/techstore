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

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Processar resposta do cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $contact_id = (int)$_POST['contact_id'];
    $reply_message = trim($_POST['reply_message']);
    
    if (!empty($reply_message)) {
        try {
            // Verificar se o ticket pertence ao utilizador
            $stmt = $pdo->prepare("SELECT id, status FROM contacts WHERE id = ? AND user_id = ?");
            $stmt->execute([$contact_id, $user_id]);
            $ticket = $stmt->fetch();
            
            if ($ticket) {
                // Não permitir respostas em tickets fechados
                if ($ticket['status'] === 'closed') {
                    $error = 'Não é possível responder a um ticket fechado.';
                } else {
                    // Inserir resposta
                    $stmt = $pdo->prepare("INSERT INTO contact_replies (contact_id, user_id, sender_type, sender_name, message) 
                                          VALUES (?, ?, 'customer', ?, ?)");
                    $stmt->execute([$contact_id, $user_id, $_SESSION['name'] ?? $_SESSION['username'], $reply_message]);
                    
                    // Atualizar ticket
                    $stmt = $pdo->prepare("UPDATE contacts SET 
                                          last_reply_by = 'customer',
                                          customer_unread = 0,
                                          admin_unread = 1,
                                          status = CASE WHEN status = 'resolved' THEN 'in_progress' ELSE status END,
                                          updated_at = NOW()
                                          WHERE id = ?");
                    $stmt->execute([$contact_id]);
                    
                    $success = 'Resposta enviada com sucesso!';
                }
            }
        } catch (Exception $e) {
            $error = 'Erro ao enviar resposta.';
        }
    } else {
        $error = 'Por favor, escreva uma mensagem.';
    }
}

// Marcar ticket como lido pelo cliente
if (isset($_GET['view']) && isset($_GET['ticket_id'])) {
    $ticket_id = (int)$_GET['ticket_id'];
    $stmt = $pdo->prepare("UPDATE contacts SET customer_unread = 0 WHERE id = ? AND user_id = ?");
    $stmt->execute([$ticket_id, $user_id]);
}

// Buscar tickets do utilizador
$stmt = $pdo->prepare("SELECT * FROM contacts WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll();

// Contar tickets não lidos
$stmt = $pdo->prepare("SELECT COUNT(*) as unread FROM contacts WHERE user_id = ? AND customer_unread = 1");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetch()['unread'];
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Tickets - TechShop</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/contact.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/cart.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dropdown.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .tickets-container {
            max-width: 1200px;
            margin: 100px auto 50px;
            padding: 20px;
        }
        
        .page-header {
            color: white;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
        }
        
        .unread-badge {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 1rem;
        }
        
        .ticket-list {
            display: grid;
            gap: 20px;
        }
        
        .ticket-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
        }
        
        .ticket-card.unread {
            border-left-color: #dc3545;
            background: linear-gradient(to right, rgba(220, 53, 69, 0.05), white);
        }
        
        .ticket-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .ticket-title {
            flex: 1;
        }
        
        .ticket-title h3 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1.3rem;
        }
        
        .ticket-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #666;
            flex-wrap: wrap;
        }
        
        .ticket-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-in_progress { background: #d1ecf1; color: #0c5460; }
        .status-resolved { background: #d4edda; color: #155724; }
        .status-closed { background: #e2e3e5; color: #383d41; }
        
        .ticket-preview {
            color: #555;
            margin-top: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .new-reply-indicator {
            background: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Modal de Detalhes */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            overflow-y: auto;
        }
        
        .modal.active {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 50px 20px;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            border-radius: 20px 20px 0 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .modal-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .conversation {
            margin-bottom: 30px;
        }
        
        .message {
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 12px;
            position: relative;
        }
        
        .message.customer {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-left: 4px solid #667eea;
        }
        
        .message.admin {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
            border-left: 4px solid #28a745;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .message-sender {
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .message-time {
            color: #888;
            font-size: 0.85rem;
            font-weight: normal;
        }
        
        .message-text {
            color: #555;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .reply-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
        }
        
        .reply-form h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .reply-form textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            margin-bottom: 15px;
        }
        
        .reply-form textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .reply-form button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .reply-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .closed-notice {
            background: #e2e3e5;
            color: #383d41;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
        }
        
        .no-tickets {
            text-align: center;
            padding: 80px 20px;
            color: white;
        }
        
        .no-tickets i {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .no-tickets h2 {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .no-tickets a {
            display: inline-block;
            margin-top: 20px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .no-tickets a:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
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
                <li><a href="my_tickets.php" class="active">Meus Tickets</a></li>
            </ul>
            <div class="nav-icons">
                <a href="#" class="search-icon"><i class="fas fa-search"></i></a>
                <a href="javascript:void(0);" class="cart-icon" id="cart-icon"><i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                </a>
                <div class="user-dropdown">
                    <a href="#" class="user-icon">
                        <i class="fas fa-user"></i>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </a>
                    <div class="dropdown-content">
                        <a href="orders.php">Pedidos</a>
                        <a href="my_tickets.php">Tickets</a>
                        <a href="logout.php">Sair</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div class="tickets-container">
        <div class="page-header">
            <h1><i class="fas fa-ticket-alt"></i> Meus Tickets de Suporte</h1>
            <?php if ($unread_count > 0): ?>
                <span class="unread-badge">
                    <i class="fas fa-bell"></i> <?php echo $unread_count; ?> nova(s) resposta(s)
                </span>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($tickets)): ?>
            <div class="no-tickets">
                <i class="fas fa-inbox"></i>
                <h2>Ainda não tem tickets</h2>
                <p>Quando precisar de ajuda, crie um ticket de suporte.</p>
                <a href="contact.php"><i class="fas fa-plus-circle"></i> Criar Primeiro Ticket</a>
            </div>
        <?php else: ?>
            <div class="ticket-list">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-card <?php echo $ticket['customer_unread'] ? 'unread' : ''; ?>" 
                         onclick="openTicket(<?php echo $ticket['id']; ?>)">
                        <div class="ticket-header">
                            <div class="ticket-title">
                                <h3>
                                    <i class="fas fa-tag"></i> 
                                    <?php echo htmlspecialchars($ticket['subject']); ?>
                                    <?php if ($ticket['customer_unread']): ?>
                                        <span class="new-reply-indicator">
                                            <i class="fas fa-bell"></i> Nova resposta
                                        </span>
                                    <?php endif; ?>
                                </h3>
                                <div class="ticket-meta">
                                    <span><i class="fas fa-hashtag"></i> Ticket #<?php echo $ticket['id']; ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></span>
                                    <span><i class="fas fa-clock"></i> Atualizado: <?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?></span>
                                </div>
                            </div>
                            <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                <?php 
                                $status_labels = [
                                    'pending' => 'Pendente',
                                    'in_progress' => 'Em Progresso',
                                    'resolved' => 'Resolvido',
                                    'closed' => 'Fechado'
                                ];
                                echo $status_labels[$ticket['status']];
                                ?>
                            </span>
                        </div>
                        <div class="ticket-preview">
                            <?php echo htmlspecialchars($ticket['message']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de Detalhes do Ticket -->
    <div id="ticketModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <button class="modal-close" onclick="closeModal()">&times;</button>
                <h2 id="modalSubject"></h2>
                <div id="modalMeta" style="margin-top: 10px; opacity: 0.9;"></div>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Conteúdo carregado via AJAX -->
            </div>
        </div>
    </div>

    <script src="js/cart.js?v=<?php echo time(); ?>"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        function openTicket(ticketId) {
            const modal = document.getElementById('ticketModal');
            modal.classList.add('active');
            
            // Carregar detalhes via fetch
            fetch('ticket_details.php?id=' + ticketId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalSubject').innerHTML = 
                        '<i class="fas fa-tag"></i> ' + data.subject;
                    document.getElementById('modalMeta').innerHTML = 
                        '<i class="fas fa-hashtag"></i> Ticket #' + data.id + ' | ' +
                        '<i class="fas fa-calendar"></i> ' + data.created_at + ' | ' +
                        '<span class="status-badge status-' + data.status + '">' + data.status_label + '</span>';
                    document.getElementById('modalBody').innerHTML = data.html;
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('modalBody').innerHTML = 
                        '<p style="color: red;">Erro ao carregar detalhes do ticket.</p>';
                });
        }
        
        function closeModal() {
            document.getElementById('ticketModal').classList.remove('active');
            // Recarregar página para atualizar badges
            setTimeout(() => location.reload(), 300);
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('ticketModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
