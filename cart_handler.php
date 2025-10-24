<?php
// Iniciar sessão apenas se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializar carrinho se não existir
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Função para adicionar item ao carrinho
function addToCart($productId, $name, $price, $image = '') {
    if (!isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] = [
            'id' => $productId,
            'name' => $name,
            'price' => $price,
            'quantity' => 1,
            'image' => $image
        ];
    } else {
        $_SESSION['cart'][$productId]['quantity']++;
    }
    return true;
}

// Função para remover item do carrinho
function removeFromCart($productId) {
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        return true;
    }
    return false;
}

// Função para atualizar quantidade
function updateCartQuantity($productId, $quantity) {
    if (isset($_SESSION['cart'][$productId])) {
        if ($quantity <= 0) {
            removeFromCart($productId);
        } else {
            $_SESSION['cart'][$productId]['quantity'] = $quantity;
        }
        return true;
    }
    return false;
}

// Função para obter total do carrinho
function getCartTotal() {
    $total = 0.0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (isset($item['price']) && isset($item['quantity'])) {
                // Remover símbolo de euro e converter vírgula para ponto
                $price = str_replace(['€', ','], ['', '.'], $item['price']);
                $price = floatval($price);
                $quantity = intval($item['quantity']);
                $itemTotal = $price * $quantity;
                $total += $itemTotal;
            }
        }
    }
    return round($total, 2);
}

// Função para obter quantidade total de itens
function getCartCount() {
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    return $count;
}

// Processar ações do carrinho via AJAX apenas quando este ficheiro é o alvo direto
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])
) {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (isset($_POST['product_id'], $_POST['name'], $_POST['price'])) {
                    $image = $_POST['image'] ?? '';
                    addToCart($_POST['product_id'], $_POST['name'], $_POST['price'], $image);
                    $response['success'] = true;
                    $response['message'] = 'Produto adicionado ao carrinho!';
                    $response['cart_count'] = getCartCount();
                } else {
                    $response['message'] = 'Dados incompletos para adicionar produto';
                }
                break;
                
            case 'remove':
                if (isset($_POST['product_id'])) {
                    removeFromCart($_POST['product_id']);
                    $response['success'] = true;
                    $response['message'] = 'Produto removido do carrinho!';
                    $response['cart_count'] = getCartCount();
                } else {
                    $response['message'] = 'ID do produto não fornecido';
                }
                break;
                
            case 'update':
                if (isset($_POST['product_id'], $_POST['quantity'])) {
                    updateCartQuantity($_POST['product_id'], $_POST['quantity']);
                    $response['success'] = true;
                    $response['message'] = 'Carrinho atualizado!';
                    $response['cart_count'] = getCartCount();
                } else {
                    $response['message'] = 'Dados incompletos para atualizar';
                }
                break;
                
            case 'get_cart':
                $cart = $_SESSION['cart'] ?? [];
                $total = getCartTotal();
                $count = getCartCount();
                
                $response['success'] = true;
                $response['cart'] = $cart;
                $response['total'] = $total;
                $response['count'] = $count;
                
                // Log para debug
                error_log("Cart data - Total: $total, Count: $count, Items: " . json_encode($cart));
                break;
                
            case 'clear':
                $_SESSION['cart'] = [];
                $response['success'] = true;
                $response['message'] = 'Carrinho limpo!';
                $response['cart_count'] = 0;
                break;
                
            default:
                $response['message'] = 'Ação inválida: ' . $_POST['action'];
                break;
        }
    } else {
        $response['message'] = 'Nenhuma ação especificada';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>