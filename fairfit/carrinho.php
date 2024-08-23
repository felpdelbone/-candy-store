<?php
session_start();
include('conexao.php');

// Função para redirecionar
function redirecionar($url) {
    header("Location: $url");
    exit();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    redirecionar('index.php');
}

// Adicionar ao carrinho
if (isset($_POST['add_to_cart'])) {
    $produto_id = $_POST['produto_id'];
    $quantidade = 1;
    $preco = $_POST['preco'];

    if (!isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }

    if (isset($_SESSION['carrinho'][$produto_id])) {
        $_SESSION['carrinho'][$produto_id]['quantidade'] += $quantidade;
    } else {
        $_SESSION['carrinho'][$produto_id] = [
            'quantidade' => $quantidade,
            'preco' => $preco
        ];
    }

    redirecionar('carrinho.php');
}

// Finalizar pedido
if (isset($_POST['finalizar_pedido'])) {
    $usuario_id = $_SESSION['user_id'];
    $total = array_sum(array_map(function($item) {
        return $item['quantidade'] * $item['preco'];
    }, $_SESSION['carrinho']));
    $forma_pagamento = $_POST['forma_pagamento'];

    $sql = "INSERT INTO pedidos (usuario_id, total, forma_pagamento) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ids", $usuario_id, $total, $forma_pagamento);
    $stmt->execute();
    $pedido_id = $conn->insert_id;

    foreach ($_SESSION['carrinho'] as $produto_id => $item) {
        $sql = "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco) VALUES (?, ?, ?, ?)";
        $stmt->prepare($sql);
        $stmt->bind_param("iiid", $pedido_id, $produto_id, $item['quantidade'], $item['preco']);
        $stmt->execute();
    }

    unset($_SESSION['carrinho']);
    $mensagem = "Pedido finalizado com sucesso!";
}

echo '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Carrinho de Compras</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #5cb85c;
            padding: 10px;
            text-align: center;
            color: white;
        }
        header nav a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-weight: bold;
        }
        header nav a:hover {
            text-decoration: underline;
        }
        main {
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #5cb85c;
            text-align: center;
        }
        .item-carrinho {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ccc;
            padding: 10px 0;
        }
        .item-carrinho h3 {
            margin: 0;
            font-size: 1.1em;
        }
        .item-carrinho p {
            margin: 5px 0;
        }
        form {
            margin-top: 20px;
        }
        form select, form button {
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            width: 100%;
            margin-bottom: 10px;
        }
        form button {
            background-color: #5cb85c;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1em;
        }
        form button:hover {
            background-color: #4cae4c;
        }
        .message {
            color: green;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Carrinho de Compras</h1>
        <nav>
            <a href="index.php">Página Principal</a>
            <a href="?logout=1">Logout</a>
        </nav>
    </header>
    <main>
        <h2>Seu Carrinho</h2>
        ';

        if (!empty($_SESSION['carrinho'])) {
            foreach ($_SESSION['carrinho'] as $produto_id => $item) {
                $sql = "SELECT nome FROM produtos WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $produto_id);
                $stmt->execute();
                $stmt->bind_result($nome_produto);
                $stmt->fetch();

                echo "<div class='item-carrinho'>";
                echo "<h3>" . $nome_produto . "</h3>";
                echo "<p>Quantidade: " . $item['quantidade'] . "</p>";
                echo "<p>Preço Unitário: R$ " . number_format($item['preco'], 2, ',', '.') . "</p>";
                echo "</div>";
            }

            echo "<form method='POST' action=''>";
            echo "<h3>Forma de Pagamento</h3>";
            echo "<select name='forma_pagamento' required>";
            echo "<option value='Cartão de Crédito'>Cartão de Crédito</option>";
            echo "<option value='Boleto'>Boleto</option>";
            echo "<option value='Pix'>Pix</option>";
            echo "</select>";
            echo "<button type='submit' name='finalizar_pedido'>Finalizar Pedido</button>";
            echo "</form>";
        } else {
            echo "<p>Seu carrinho está vazio.</p>";
        }

        if (isset($mensagem)) {
            echo "<p class='message'>" . $mensagem . "</p>";
        }

        echo '
    </main>
</body>
</html>
';
?>
