<?php
// Iniciar sessão apenas se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ativar relatório de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';
require_once 'cart_handler.php';

// Verificar se há itens no carrinho
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['checkout_error'] = 'Carrinho vazio. Adicione produtos antes de finalizar a compra.';
    header('Location: products.php');
    exit();
}

// Verificar se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit();
}

// Debug - Log dos dados recebidos
error_log("=== CHECKOUT DEBUG ===");
error_log("POST DATA: " . print_r($_POST, true));
error_log("SESSION CART: " . print_r($_SESSION['cart'], true));

try {
    // Verificar se as tabelas existem
    try {
        $pdo->query("SELECT 1 FROM orders LIMIT 1");
    } catch (PDOException $e) {
        // Criar tabelas se não existirem
        $sql = file_get_contents(__DIR__ . '/database/add_orders_system.sql');
        if ($sql) {
            $pdo->exec($sql);
            error_log("Tabelas de encomendas criadas automaticamente");
        } else {
            throw new Exception("Não foi possível criar as tabelas. Execute database/add_orders_system.sql manualmente.");
        }
    }

    // Iniciar transação
    $pdo->beginTransaction();

    // Recolher dados do formulário
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $customer_name = trim($_POST['name']);
    $customer_email = trim($_POST['email']);
    $customer_phone = trim($_POST['phone']);
    $customer_address = trim($_POST['address']);
    $customer_city = trim($_POST['city']);
    $customer_postal_code = trim($_POST['postal_code']);
    $payment_method = $_POST['payment_method'];
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
    
    // Calcular total com envio
    $subtotal = getCartTotal();
    $shipping_cost = getShippingCost();
    $total_amount = getFinalTotal();

    // Inserir a encomenda
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            user_id, customer_name, customer_email, customer_phone,
            customer_address, customer_city, customer_postal_code,
            payment_method, total_amount, shipping_cost, notes, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $user_id,
        $customer_name,
        $customer_email,
        $customer_phone,
        $customer_address,
        $customer_city,
        $customer_postal_code,
        $payment_method,
        $total_amount,
        $shipping_cost,
        $notes
    ]);

    // Obter ID da encomenda
    $order_id = $pdo->lastInsertId();

    // Inserir itens da encomenda
    $stmt_items = $pdo->prepare("
        INSERT INTO order_items (
            order_id, product_id, product_name, product_price,
            quantity, subtotal, product_image
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($_SESSION['cart'] as $item) {
        // Limpar e converter preço
        $price = str_replace(['€', ','], ['', '.'], $item['price']);
        $price = floatval($price);
        $quantity = intval($item['quantity']);
        $subtotal = $price * $quantity;

        // Tentar obter o product_id real da base de dados
        $product_id = null;
        if (isset($item['id'])) {
            $check_stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
            $check_stmt->execute([$item['id']]);
            $product = $check_stmt->fetch();
            if ($product) {
                $product_id = $product['id'];
            }
        }

        $stmt_items->execute([
            $order_id,
            $product_id,
            $item['name'],
            $price,
            $quantity,
            $subtotal,
            $item['image'] ?? null
        ]);
    }

    // Confirmar transação
    $pdo->commit();

    // Limpar carrinho
    $_SESSION['cart'] = [];

    // Redirecionar para página de sucesso
    $_SESSION['order_success'] = [
        'order_id' => $order_id,
        'total' => $total_amount,
        'customer_name' => $customer_name,
        'customer_email' => $customer_email
    ];

    header('Location: order_success.php');
    exit();

} catch (Exception $e) {
    // Reverter transação em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log do erro detalhado
    error_log("Erro ao processar checkout: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Redirecionar com mensagem de erro específica
    $_SESSION['checkout_error'] = 'Erro: ' . $e->getMessage() . ' - Por favor, tente novamente ou contacte o suporte.';
    header('Location: checkout.php');
    exit();
}
?>
