<?php
/**
 * API de Pesquisa de Produtos
 * Retorna produtos que correspondem à query de pesquisa
 */

header('Content-Type: application/json');

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carregar configuração do DB
require_once __DIR__ . '/config/db.php';

// Obter query de pesquisa
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Validar query
if (empty($query) || strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Pesquisar produtos por nome ou descrição
    $searchTerm = '%' . $query . '%';
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            description,
            price,
            image,
            stock,
            on_promotion,
            promotion_price,
            discount_percentage
        FROM products 
        WHERE 
            name LIKE ? OR 
            description LIKE ?
        ORDER BY 
            CASE 
                WHEN name LIKE ? THEN 1
                WHEN name LIKE ? THEN 2
                ELSE 3
            END,
            name ASC
        LIMIT 10
    ");
    
    // Parâmetros: busca em nome e descrição, ordenação por relevância
    $exactMatch = $query . '%';
    $stmt->execute([
        $searchTerm,  // LIKE para name
        $searchTerm,  // LIKE para description
        $exactMatch,  // Ordenação: match exato no início
        $searchTerm   // Ordenação: match parcial
    ]);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retornar resultados
    echo json_encode($products);
    
} catch (Exception $e) {
    // Log do erro (em produção, use um sistema de logs apropriado)
    error_log("Erro na pesquisa de produtos: " . $e->getMessage());
    
    // Retornar array vazio em caso de erro
    echo json_encode([]);
}
