<?php
session_start();
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

$ticket_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verificar se o ticket pertence ao utilizador
$stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ? AND user_id = ?");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    echo json_encode(['error' => 'Ticket não encontrado']);
    exit();
}

// Marcar como lido pelo cliente
$stmt = $pdo->prepare("UPDATE contacts SET customer_unread = 0 WHERE id = ?");
$stmt->execute([$ticket_id]);

// Buscar todas as respostas
$stmt = $pdo->prepare("SELECT * FROM contact_replies WHERE contact_id = ? ORDER BY created_at ASC");
$stmt->execute([$ticket_id]);
$replies = $stmt->fetchAll();

// Preparar HTML
$html = '<div class="conversation">';

// Mensagem original
$html .= '<div class="message customer">';
$html .= '<div class="message-header">';
$html .= '<span class="message-sender"><i class="fas fa-user"></i> ' . htmlspecialchars($ticket['name']) . ' (Você)</span>';
$html .= '<span class="message-time">' . date('d/m/Y H:i', strtotime($ticket['created_at'])) . '</span>';
$html .= '</div>';
$html .= '<div class="message-text">' . nl2br(htmlspecialchars($ticket['message'])) . '</div>';
$html .= '</div>';

// Respostas
foreach ($replies as $reply) {
    $is_admin = $reply['sender_type'] === 'admin';
    $html .= '<div class="message ' . ($is_admin ? 'admin' : 'customer') . '">';
    $html .= '<div class="message-header">';
    $html .= '<span class="message-sender">';
    $html .= '<i class="fas fa-' . ($is_admin ? 'user-shield' : 'user') . '"></i> ';
    $html .= htmlspecialchars($reply['sender_name']);
    $html .= $is_admin ? ' (Suporte)' : ' (Você)';
    $html .= '</span>';
    $html .= '<span class="message-time">' . date('d/m/Y H:i', strtotime($reply['created_at'])) . '</span>';
    $html .= '</div>';
    $html .= '<div class="message-text">' . nl2br(htmlspecialchars($reply['message'])) . '</div>';
    $html .= '</div>';
}

$html .= '</div>';

// Formulário de resposta ou aviso de fechado
if ($ticket['status'] === 'closed') {
    $html .= '<div class="closed-notice">';
    $html .= '<i class="fas fa-lock"></i> Este ticket está fechado e não aceita mais respostas.';
    $html .= '</div>';
} else {
    $html .= '<form method="POST" action="my_tickets.php" class="reply-form">';
    $html .= '<input type="hidden" name="contact_id" value="' . $ticket_id . '">';
    $html .= '<h3><i class="fas fa-reply"></i> Responder</h3>';
    $html .= '<textarea name="reply_message" placeholder="Escreva a sua resposta..." required></textarea>';
    $html .= '<button type="submit"><i class="fas fa-paper-plane"></i> Enviar Resposta</button>';
    $html .= '</form>';
}

$status_labels = [
    'pending' => 'Pendente',
    'in_progress' => 'Em Progresso',
    'resolved' => 'Resolvido',
    'closed' => 'Fechado'
];

echo json_encode([
    'id' => $ticket['id'],
    'subject' => $ticket['subject'],
    'status' => $ticket['status'],
    'status_label' => $status_labels[$ticket['status']],
    'created_at' => date('d/m/Y H:i', strtotime($ticket['created_at'])),
    'html' => $html
]);
