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
function addToCart($productId, $name, $price, $image = '', $originalPrice = '', $onPromotion = 0, $discountPercentage = 0, $quantity = 1) {
    if (!isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] = [
            'id' => $productId,
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
            'image' => $image,
            'original_price' => $originalPrice,
            'on_promotion' => $onPromotion,
            'discount_percentage' => $discountPercentage
        ];
    } else {
        $_SESSION['cart'][$productId]['quantity'] += $quantity;
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
                // Converter o preço para float (aceita tanto com ponto quanto com vírgula)
                $priceStr = is_numeric($item['price']) ? $item['price'] : str_replace(['€', ','], ['', '.'], $item['price']);
                $price = floatval($priceStr);
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

// Função para calcular o custo de envio
function getShippingCost() {
    $subtotal = getCartTotal();
    $shippingCost = 0.0;
    
    // Se o subtotal for menor que 50€, adicionar custo de envio de 5€
    if ($subtotal < 50) {
        $shippingCost = 5.0;
    }
    
    return $shippingCost;
}

// Função para obter o total final (incluindo envio)
function getFinalTotal() {
    $subtotal = getCartTotal();
    $shipping = getShippingCost();
    return round($subtotal + $shipping, 2);
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
                    $originalPrice = $_POST['original_price'] ?? '';
                    $onPromotion = $_POST['on_promotion'] ?? 0;
                    $discountPercentage = $_POST['discount_percentage'] ?? 0;
                    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
                    addToCart($_POST['product_id'], $_POST['name'], $_POST['price'], $image, $originalPrice, $onPromotion, $discountPercentage, $quantity);
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