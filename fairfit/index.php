<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fairfitdados";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Verificar se o usuário está logado
if (isset($_SESSION['user_id'])) {
    // Processar ações do usuário logado
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

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
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
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiid", $pedido_id, $produto_id, $item['quantidade'], $item['preco']);
            $stmt->execute();
        }

        unset($_SESSION['carrinho']);
        $mensagem = "Pedido finalizado com sucesso!";
    }

    // Logout
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Página principal
    echo '
    <html>
    <head>
        <title>Loja de Doces Fitness</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                color: #333;
                margin: 0;
                padding: 0;
            }
            header {
                background-color: #333;
                color: #fff;
                padding: 10px;
                text-align: center;
            }
            main {
                padding: 20px;
            }
            .produtos, .carrinho {
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                margin-bottom: 20px;
                padding: 20px;
            }
            .lista-produtos {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
            }
            .produto {
                border: 1px solid #ccc;
                border-radius: 8px;
                padding: 10px;
                width: calc(33.333% - 20px);
                background-color: #fff;
            }
            .produto img {
                max-width: 100%;
                height: auto;
                border-radius: 8px;
            }
            .item-carrinho img {
                max-width: 100%;
                height: auto;
                border-radius: 8px;
            }
            .produto h3, .item-carrinho h3 {
                margin: 10px 0;
            }
            .produto p, .item-carrinho p {
                margin: 10px 0;
            }
            .produto button, .finalizar button {
                background-color: #5cb85c;
                color: #fff;
                padding: 10px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .produto button:hover, .finalizar button:hover {
                background-color: #4cae4c;
            }
            .finalizar {
                margin-top: 20px;
            }
            footer {
                text-align: center;
                padding: 10px;
                background-color: #333;
                color: #fff;
                position: relative;
                width: 100%;
            }
        </style>
    </head>
    <body>
        <header>
            <h1>Loja de Doces Fitness</h1>
            <a href="?logout=1" style="color: #fff;">Logout</a>
        </header>

        <main>
            <section class="produtos">
                <h2>Produtos</h2>
                <div class="lista-produtos">
                    ';
                    $sql = "SELECT * FROM produtos";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<div class='produto'>";
                            echo "<img src='" . $row['imagem'] . "' alt='" . $row['nome'] . "'>";
                            echo "<h3>" . $row['nome'] . "</h3>";
                            echo "<p>" . $row['descricao'] . "</p>";
                            echo "<p>R$ " . number_format($row['preco'], 2, ',', '.') . "</p>";
                            echo "<form method='POST' action=''>";
                            echo "<input type='hidden' name='produto_id' value='" . $row['id'] . "'>";
                            echo "<input type='hidden' name='preco' value='" . $row['preco'] . "'>";
                            echo "<button type='submit' name='add_to_cart'>Adicionar ao Carrinho</button>";
                            echo "</form>";
                            echo "</div>";
                        }
                    } else {
                        echo "<p>Nenhum produto encontrado.</p>";
                    }
                    echo '
                </div>
            </section>

            <section class="carrinho">
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
                        echo "<p>Total: R$ " . number_format($item['quantidade'] * $item['preco'], 2, ',', '.') . "</p>";
                        echo "</div>";
                    }

                    $total = array_sum(array_map(function($item) {
                        return $item['quantidade'] * $item['preco'];
                    }, $_SESSION['carrinho']));

                    echo "<div class='finalizar'>";
                    echo "<form method='POST' action=''>";
                    echo "<label for='forma_pagamento'>Forma de Pagamento:</label>";
                    echo "<select name='forma_pagamento' required>";
                    echo "<option value='cartao'>Cartão de Crédito</option>";
                    echo "<option value='boleto'>Boleto</option>";
                    echo "<option value='pix'>PIX</option>";
                    echo "</select>";
                    echo "<button type='submit' name='finalizar_pedido'>Finalizar Pedido</button>";
                    echo "</form>";
                    echo "</div>";
                } else {
                    echo "<p>Seu carrinho está vazio.</p>";
                }

                if (isset($mensagem)) {
                    echo "<p style='color: green;'>" . $mensagem . "</p>";
                }

                if (isset($erro)) {
                    echo "<p style='color: red;'>" . $erro . "</p>";
                }
                echo '
            </section>
        </main>

        <footer>
            <p>&copy; 2024 Loja de Doces Fitness. Todos os direitos reservados.</p>
        </footer>
    </body>
    </html>
    ';
} else {
    // Mostrar login, cadastro e recuperação de senha
    if (isset($_GET['recuperar_senha'])) {
        echo '
        <html>
        <head>
            <title>Loja de Doces Fitness - Recuperação de Senha</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 50px auto;
                    padding: 20px;
                    background-color: #fff;
                    border-radius: 8px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                }
                input[type="email"] {
                    width: 100%;
                    padding: 10px;
                    margin: 10px 0;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                }
                button {
                    background-color: #5cb85c;
                    color: #fff;
                    padding: 10px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    width: 100%;
                }
                button:hover {
                    background-color: #4cae4c;
                }
                .switch-form {
                    text-align: center;
                    margin-top: 20px;
                }
                .error {
                    color: red;
                }
                .success {
                    color: green;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h2>Recuperação de Senha</h2>
                <form method="POST" action="">
                    <input type="email" name="email" placeholder="Email" required>
                    <button type="submit" name="recuperar_senha">Enviar Link de Recuperação</button>
                </form>
                <div class="switch-form">
                    <p><a href="#">Voltar ao Login</a></p>
                </div>
                ';
                if (isset($erro)) {
                    echo "<p class='error'>$erro</p>";
                }
                if (isset($mensagem)) {
                    echo "<p class='success'>$mensagem</p>";
                }
                echo '
            </div>
        </body>
        </html>
        ';
        exit();
    }

    if (isset($_GET['cadastro'])) {
        echo '
        <html>
        <head>
            <title>Loja de Doces Fitness - Cadastro</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 50px auto;
                    padding: 20px;
                    background-color: #fff;
                    border-radius: 8px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                }
                input[type="email"], input[type="password"] {
                    width: 100%;
                    padding: 10px;
                    margin: 10px 0;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                }
                button {
                    background-color: #5cb85c;
                    color: #fff;
                    padding: 10px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    width: 100%;
                }
                button:hover {
                    background-color: #4cae4c;
                }
                .switch-form {
                    text-align: center;
                    margin-top: 20px;
                }
                .error {
                    color: red;
                }
                .success {
                    color: green;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h2>Cadastro</h2>
                <form method="POST" action="">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="senha" placeholder="Senha" required>
                    <button type="submit" name="cadastro">Cadastrar</button>
                </form>
                <div class="switch-form">
                    <p>Já tem uma conta? <a href="#">Faça login</a></p>
                </div>
                ';
                if (isset($erro)) {
                    echo "<p class='error'>$erro</p>";
                }
                if (isset($mensagem)) {
                    echo "<p class='success'>$mensagem</p>";
                }
                echo '
            </div>
        </body>
        </html>
        ';
        exit();
    }

    // Página de login
    echo '
    <html>
    <head>
        <title>Loja de Doces Fitness - Login</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            input[type="email"], input[type="password"] {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                border: 1px solid #ccc;
                border-radius: 4px;
            }
            button {
                background-color: #5cb85c;
                color: #fff;
                padding: 10px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                width: 100%;
            }
            button:hover {
                background-color: #4cae4c;
            }
            .switch-form {
                text-align: center;
                margin-top: 20px;
            }
            .error {
                color: red;
            }
            .success {
                color: green;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Login</h2>
            <form method="POST" action="">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <button type="submit" name="login">Entrar</button>
            </form>
            <div class="switch-form">
                <p>Não tem uma conta? <a href="?cadastro=1">Cadastre-se</a></p>
                <p><a href="?recuperar_senha=1">Esqueci minha senha</a></p>
            </div>
            ';
            if (isset($erro)) {
                echo "<p class='error'>$erro</p>";
            }
            if (isset($mensagem)) {
                echo "<p class='success'>$mensagem</p>";
            }
            echo '
        </div>
    </body>
    </html>
    ';
}
?>
