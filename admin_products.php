<?php<?php

session_start();session_start();



// Verificar se o usuário está logado e é admin// Verificar se o usuário está logado e é admin

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {

    header('Location: login.php');    header('Location: login.php');

    exit();    exit();

}}



require_once __DIR__ . '/config/db.php';require_once __DIR__ . '/config/db.php';



// Processar formulário de adição/edição de produto// Processar formulário de adição/edição de produto

if ($_SERVER['REQUEST_METHOD'] === 'POST') {if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action'])) {    if (isset($_POST['action'])) {

        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {

            $name = $_POST['name'];            $name = $_POST['name'];

            $description = $_POST['description'];            $description = $_POST['description'];

            $price = $_POST['price'];            $price = $_POST['price'];

            $stock = $_POST['stock'];            $stock = $_POST['stock'];

            $category = $_POST['category'];            $category = $_POST['category'];

                        

            // Processar upload de imagem            // Processar upload de imagem

            $image_path = '';            $image_path = '';

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

                $upload_dir = 'images/';                $upload_dir = 'images/';

                $image_name = basename($_FILES['image']['name']);                $image_name = basename($_FILES['image']['name']);

                $target_path = $upload_dir . $image_name;                $target_path = $upload_dir . $image_name;

                                

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {

                    $image_path = $target_path;                    $image_path = $target_path;

                }                }

            }            }



            if ($_POST['action'] === 'add') {            if ($_POST['action'] === 'add') {

                // Inserir novo produto                // Inserir novo produto

                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category, image_path) VALUES (?, ?, ?, ?, ?, ?)");                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category, image_path) VALUES (?, ?, ?, ?, ?, ?)");

                $stmt->execute([$name, $description, $price, $stock, $category, $image_path]);                $stmt->execute([$name, $description, $price, $stock, $category, $image_path]);

            } else {            } else {

                // Atualizar produto existente                // Atualizar produto existente

                $id = $_POST['product_id'];                $id = $_POST['product_id'];

                if ($image_path) {                if ($image_path) {

                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ?, image_path = ? WHERE id = ?");                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ?, image_path = ? WHERE id = ?");

                    $stmt->execute([$name, $description, $price, $stock, $category, $image_path, $id]);                    $stmt->execute([$name, $description, $price, $stock, $category, $image_path, $id]);

                } else {                } else {

                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ? WHERE id = ?");                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ? WHERE id = ?");

                    $stmt->execute([$name, $description, $price, $stock, $category, $id]);                    $stmt->execute([$name, $description, $price, $stock, $category, $id]);

                }                }

            }            }

        } elseif ($_POST['action'] === 'delete' && isset($_POST['product_id'])) {        } elseif ($_POST['action'] === 'delete' && isset($_POST['product_id'])) {

            // Deletar produto            // Deletar produto

            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");

            $stmt->execute([$_POST['product_id']]);            $stmt->execute([$_POST['product_id']]);

        }        }

                

        // Redirecionar para evitar reenvio do formulário        // Redirecionar para evitar reenvio do formulário

        header('Location: admin_products.php');        header('Location: admin_products.php');

        exit();        exit();

    }    }

}}



// Buscar todos os produtos// Buscar todos os produtos

$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>?>



<!DOCTYPE html><!DOCTYPE html>

<html lang="pt"><html lang="pt">

<head><head>

    <meta charset="UTF-8">    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestão de Produtos - TechShop</title>    <title>Gestão de Produtos - TechShop</title>

    <link rel="stylesheet" href="css/style.css">    <link rel="stylesheet" href="css/style.css">

    <link rel="stylesheet" href="css/admin.css">    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">    <style>

</head>        .admin-container {

<body>            max-width: 1200px;

    <!-- Header -->            margin: 20px auto;

    <header class="header">            padding: 20px;

        <nav class="navbar">        }

            <div class="logo">        .product-form {

                <h1>TechShop Admin</h1>            background: #f5f5f5;

            </div>            padding: 20px;

            <ul class="nav-links">            border-radius: 8px;

                <li><a href="index.php">Home</a></li>            margin-bottom: 20px;

                <li><a href="backoffice/backoffice.php">Voltar ao Backoffice</a></li>        }

            </ul>        .form-group {

            <div class="nav-icons">            margin-bottom: 15px;

                <?php if (isset($_SESSION['username'])): ?>        }

                    <div class="user-dropdown">        .form-group label {

                        <a href="#" class="user-icon">            display: block;

                            <i class="fas fa-user"></i>            margin-bottom: 5px;

                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>        }

                        </a>        .form-group input[type="text"],

                        <div class="dropdown-content">        .form-group input[type="number"],

                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>        .form-group textarea,

                        </div>        .form-group select {

                    </div>            width: 100%;

                <?php endif; ?>            padding: 8px;

            </div>            border: 1px solid #ddd;

        </nav>            border-radius: 4px;

    </header>        }

        .products-table {

    <div class="admin-container">            width: 100%;

        <h2><i class="fas fa-box"></i> Gestão de Produtos</h2>            border-collapse: collapse;

                    margin-top: 20px;

        <!-- Formulário de Adição/Edição de Produto -->        }

        <div class="product-form">        .products-table th,

            <h3><i class="fas fa-plus-circle"></i> Adicionar Novo Produto</h3>        .products-table td {

            <form action="admin_products.php" method="POST" enctype="multipart/form-data">            padding: 10px;

                <input type="hidden" name="action" value="add">            border: 1px solid #ddd;

                <div class="form-group">            text-align: left;

                    <label for="name"><i class="fas fa-tag"></i> Nome do Produto:</label>        }

                    <input type="text" id="name" name="name" required>        .products-table th {

                </div>            background: #f0f0f0;

                <div class="form-group">        }

                    <label for="description"><i class="fas fa-align-left"></i> Descrição:</label>        .btn {

                    <textarea id="description" name="description" required rows="4"></textarea>            padding: 8px 15px;

                </div>            border: none;

                <div class="form-group">            border-radius: 4px;

                    <label for="price"><i class="fas fa-euro-sign"></i> Preço:</label>            cursor: pointer;

                    <input type="number" id="price" name="price" step="0.01" required>        }

                </div>        .btn-primary {

                <div class="form-group">            background: #007bff;

                    <label for="stock"><i class="fas fa-boxes"></i> Stock:</label>            color: white;

                    <input type="number" id="stock" name="stock" required>        }

                </div>        .btn-danger {

                <div class="form-group">            background: #dc3545;

                    <label for="category"><i class="fas fa-folder"></i> Categoria:</label>            color: white;

                    <select id="category" name="category" required>        }

                        <option value="">Selecione uma categoria</option>        .btn-warning {

                        <option value="smartphones">Smartphones</option>            background: #ffc107;

                        <option value="laptops">Laptops</option>            color: black;

                        <option value="tablets">Tablets</option>        }

                        <option value="accessories">Acessórios</option>    </style>

                    </select></head>

                </div><body>

                <div class="form-group">    <!-- Header -->

                    <label for="image"><i class="fas fa-image"></i> Imagem:</label>    <header class="header">

                    <input type="file" id="image" name="image" accept="image/*" required>        <nav class="navbar">

                </div>            <div class="logo">

                <button type="submit" class="btn btn-primary">                <h1>TechShop</h1>

                    <i class="fas fa-plus-circle"></i> Adicionar Produto            </div>

                </button>            <ul class="nav-links">

            </form>                <li><a href="index.php">Home</a></li>

        </div>                <li><a href="backoffice/backoffice.php">Voltar ao Backoffice</a></li>

            </ul>

        <!-- Lista de Produtos -->            <div class="nav-icons">

        <h3><i class="fas fa-list"></i> Produtos Existentes</h3>                <?php if (isset($_SESSION['username'])): ?>

        <div class="table-responsive">                    <div class="user-dropdown">

            <table class="products-table">                        <a href="#" class="user-icon">

                <thead>                            <i class="fas fa-user"></i>

                    <tr>                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>

                        <th>ID</th>                        </a>

                        <th>Imagem</th>                        <div class="dropdown-content">

                        <th>Nome</th>                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>

                        <th>Descrição</th>                        </div>

                        <th>Preço</th>                    </div>

                        <th>Stock</th>                <?php endif; ?>

                        <th>Categoria</th>            </div>

                        <th>Ações</th>        </nav>

                    </tr>    </header>

                </thead>

                <tbody>    <div class="admin-container">

                    <?php foreach ($products as $product): ?>        <h2>Gestão de Produtos</h2>

                    <tr>        

                        <td><?php echo htmlspecialchars($product['id']); ?></td>        <!-- Formulário de Adição/Edição de Produto -->

                        <td>        <div class="product-form">

                            <?php if ($product['image_path']): ?>            <h3>Adicionar Novo Produto</h3>

                                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="Product Image" class="product-image">            <form action="admin_products.php" method="POST" enctype="multipart/form-data">

                            <?php else: ?>                <input type="hidden" name="action" value="add">

                                <i class="fas fa-image"></i>                <div class="form-group">

                            <?php endif; ?>                    <label for="name">Nome do Produto:</label>

                        </td>                    <input type="text" id="name" name="name" required>

                        <td><?php echo htmlspecialchars($product['name']); ?></td>                </div>

                        <td><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></td>                <div class="form-group">

                        <td><?php echo number_format($product['price'], 2); ?>€</td>                    <label for="description">Descrição:</label>

                        <td><?php echo htmlspecialchars($product['stock']); ?></td>                    <textarea id="description" name="description" required></textarea>

                        <td><?php echo htmlspecialchars($product['category']); ?></td>                </div>

                        <td>                <div class="form-group">

                            <button class="btn btn-warning" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">                    <label for="price">Preço:</label>

                                <i class="fas fa-edit"></i> Editar                    <input type="number" id="price" name="price" step="0.01" required>

                            </button>                </div>

                            <form action="admin_products.php" method="POST" style="display: inline;">                <div class="form-group">

                                <input type="hidden" name="action" value="delete">                    <label for="stock">Stock:</label>

                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">                    <input type="number" id="stock" name="stock" required>

                                <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir este produto?')">                </div>

                                    <i class="fas fa-trash"></i> Excluir                <div class="form-group">

                                </button>                    <label for="category">Categoria:</label>

                            </form>                    <select id="category" name="category" required>

                        </td>                        <option value="smartphones">Smartphones</option>

                    </tr>                        <option value="laptops">Laptops</option>

                    <?php endforeach; ?>                        <option value="tablets">Tablets</option>

                </tbody>                        <option value="accessories">Acessórios</option>

            </table>                    </select>

        </div>                </div>

    </div>                <div class="form-group">

                    <label for="image">Imagem:</label>

    <script>                    <input type="file" id="image" name="image" accept="image/*" required>

    function editProduct(product) {                </div>

        // Preencher o formulário com os dados do produto                <button type="submit" class="btn btn-primary">Adicionar Produto</button>

        document.querySelector('input[name="action"]').value = 'edit';            </form>

        document.querySelector('input[name="name"]').value = product.name;        </div>

        document.querySelector('textarea[name="description"]').value = product.description;

        document.querySelector('input[name="price"]').value = product.price;        <!-- Lista de Produtos -->

        document.querySelector('input[name="stock"]').value = product.stock;        <h3>Produtos Existentes</h3>

        document.querySelector('select[name="category"]').value = product.category;        <table class="products-table">

                    <thead>

        // Adicionar campo hidden para o ID do produto                <tr>

        let idInput = document.querySelector('input[name="product_id"]');                    <th>ID</th>

        if (!idInput) {                    <th>Nome</th>

            idInput = document.createElement('input');                    <th>Descrição</th>

            idInput.type = 'hidden';                    <th>Preço</th>

            idInput.name = 'product_id';                    <th>Stock</th>

            document.querySelector('form').appendChild(idInput);                    <th>Categoria</th>

        }                    <th>Imagem</th>

        idInput.value = product.id;                    <th>Ações</th>

                </tr>

        // Atualizar o texto do botão de submit e ícone            </thead>

        const submitButton = document.querySelector('form button[type="submit"]');            <tbody>

        submitButton.innerHTML = '<i class="fas fa-save"></i> Atualizar Produto';                <?php foreach ($products as $product): ?>

                        <tr>

        // Atualizar o título do formulário                    <td><?php echo htmlspecialchars($product['id']); ?></td>

        document.querySelector('.product-form h3').innerHTML = '<i class="fas fa-edit"></i> Editar Produto';                    <td><?php echo htmlspecialchars($product['name']); ?></td>

                            <td><?php echo htmlspecialchars($product['description']); ?></td>

        // A imagem não pode ser preenchida automaticamente por segurança                    <td><?php echo number_format($product['price'], 2); ?>€</td>

        document.querySelector('input[name="image"]').required = false;                    <td><?php echo htmlspecialchars($product['stock']); ?></td>

                            <td><?php echo htmlspecialchars($product['category']); ?></td>

        // Scroll até o formulário                    <td>

        document.querySelector('.product-form').scrollIntoView({ behavior: 'smooth' });                        <?php if ($product['image_path']): ?>

    }                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="Product Image" style="max-width: 50px;">

    </script>                        <?php endif; ?>

</body>                    </td>

</html>                    <td>
                        <button class="btn btn-warning" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                            Editar
                        </button>
                        <form action="admin_products.php" method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                Excluir
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    function editProduct(product) {
        // Preencher o formulário com os dados do produto
        document.querySelector('input[name="action"]').value = 'edit';
        document.querySelector('input[name="name"]').value = product.name;
        document.querySelector('textarea[name="description"]').value = product.description;
        document.querySelector('input[name="price"]').value = product.price;
        document.querySelector('input[name="stock"]').value = product.stock;
        document.querySelector('select[name="category"]').value = product.category;
        
        // Adicionar campo hidden para o ID do produto
        let idInput = document.querySelector('input[name="product_id"]');
        if (!idInput) {
            idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'product_id';
            document.querySelector('form').appendChild(idInput);
        }
        idInput.value = product.id;

        // Atualizar o texto do botão de submit
        document.querySelector('form button[type="submit"]').textContent = 'Atualizar Produto';
        
        // Atualizar o título do formulário
        document.querySelector('.product-form h3').textContent = 'Editar Produto';
        
        // A imagem não pode ser preenchida automaticamente por segurança
        document.querySelector('input[name="image"]').required = false;
        
        // Scroll até o formulário
        document.querySelector('.product-form').scrollIntoView({ behavior: 'smooth' });
    }
    </script>
</body>
</html>
