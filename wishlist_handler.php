<?php
// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'É necessário fazer login para usar favoritos',
        'redirect' => 'login.php'
    ]);
    exit;
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = $_SESSION['user_id'];
    
    switch ($_POST['action']) {
        case 'toggle':
            if (!isset($_POST['product_id'])) {
                $response['message'] = 'ID do produto não fornecido';
                break;
            }
            
            $productId = intval($_POST['product_id']);
            
            try {
                // Verificar se já está nos favoritos
                $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$userId, $productId]);
                $exists = $stmt->fetch();
                
                if ($exists) {
                    // Remover dos favoritos
                    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$userId, $productId]);
                    
                    $response['success'] = true;
                    $response['action'] = 'removed';
                    $response['message'] = 'Removido dos favoritos';
                } else {
                    // Adicionar aos favoritos
                    $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
                    $stmt->execute([$userId, $productId]);
                    
                    $response['success'] = true;
                    $response['action'] = 'added';
                    $response['message'] = 'Adicionado aos favoritos';
                }
                
                // Contar total de favoritos
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
                $stmt->execute([$userId]);
                $response['wishlist_count'] = $stmt->fetch()['total'];
                
            } catch (PDOException $e) {
                $response['message'] = 'Erro ao processar favorito: ' . $e->getMessage();
            }
            break;
            
        case 'check':
            if (!isset($_POST['product_id'])) {
                $response['message'] = 'ID do produto não fornecido';
                break;
            }
            
            $productId = intval($_POST['product_id']);
            
            try {
                $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$userId, $productId]);
                
                $response['success'] = true;
                $response['is_favorite'] = $stmt->fetch() ? true : false;
                
            } catch (PDOException $e) {
                $response['message'] = 'Erro ao verificar favorito: ' . $e->getMessage();
            }
            break;
            
        case 'get_all':
            try {
                $stmt = $pdo->prepare("
                    SELECT p.*, w.created_at as added_at 
                    FROM wishlist w 
                    JOIN products p ON w.product_id = p.id 
                    WHERE w.user_id = ? 
                    ORDER BY w.created_at DESC
                ");
                $stmt->execute([$userId]);
                
                $response['success'] = true;
                $response['favorites'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $response['message'] = 'Erro ao buscar favoritos: ' . $e->getMessage();
            }
            break;
            
        default:
            $response['message'] = 'Ação inválida';
            break;
    }
}

echo json_encode($response);
?>
