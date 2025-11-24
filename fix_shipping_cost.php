<?php
/**
 * Script para adicionar a coluna shipping_cost à tabela orders
 * Execute este ficheiro uma vez no browser: http://localhost/techstore2/fix_shipping_cost.php
 */

require_once 'config/db.php';

echo "<!DOCTYPE html>
<html lang='pt'>
<head>
    <meta charset='UTF-8'>
    <title>Correção Base de Dados - TechShop</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Correção da Base de Dados - TechShop</h1>
    <p>Este script irá adicionar a coluna <code>shipping_cost</code> à tabela <code>orders</code>.</p>
    <hr>";

try {
    // Verificar se a coluna já existe
    $result = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_cost'");
    
    if ($result->rowCount() > 0) {
        echo "<div class='info'><strong>Info:</strong> A coluna 'shipping_cost' já existe na tabela 'orders'. Nenhuma alteração necessária.</div>";
    } else {
        // Adicionar a coluna
        $sql = "ALTER TABLE `orders` 
                ADD COLUMN `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`";
        
        $pdo->exec($sql);
        
        echo "<div class='success'><strong>Sucesso!</strong> A coluna 'shipping_cost' foi adicionada à tabela 'orders' com sucesso.</div>";
    }
    
    // Mostrar estrutura atual da tabela
    echo "<h2>Estrutura atual da tabela 'orders':</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $columns = $pdo->query("SHOW COLUMNS FROM orders");
    while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr><div class='success'><strong>Tudo pronto!</strong> Pode agora <a href='checkout.php'>continuar com o checkout</a>.</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
