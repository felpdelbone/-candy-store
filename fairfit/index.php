<?php
session_start();
include('conexao.php'); // Certifique-se de que 'conexao.php' esteja configurado corretamente para definir a variável $conn

// Função para redirecionar
function redirecionar($url) {
    header("Location: $url");
    exit();
}

// Verifique se a conexão foi bem-sucedida
if (!$conn) {
    die("Falha na conexão: " . mysqli_connect_error());
}

// Login
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = "SELECT id FROM usuarios WHERE email = ? AND senha = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $senha);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $_SESSION['user_id'] = $user_id;
        $stmt->close(); // Feche a instrução
        redirecionar('carrinho.php'); // Redireciona para o carrinho após login bem-sucedido
    } else {
        $erro = "E-mail ou senha incorretos.";
    }

    $stmt->close(); // Feche a instrução
}

// Cadastro
if (isset($_POST['register'])) {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = "INSERT INTO usuarios (email, senha) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $senha);

    if ($stmt->execute()) {
        $mensagem = "Cadastro realizado com sucesso! Agora você pode fazer login.";
    } else {
        $erro = "Erro ao cadastrar. Verifique o e-mail e tente novamente.";
    }

    $stmt->close(); // Feche a instrução
}

// Recuperar senha
if (isset($_POST['reset_password'])) {
    $email = $_POST['email'];

    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $nova_senha = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
        $sql = "UPDATE usuarios SET senha = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nova_senha, $email);
        $stmt->execute();
        $mensagem = "Uma nova senha foi gerada e enviada para o seu e-mail.";
    } else {
        $erro = "E-mail não encontrado.";
    }

    $stmt->close(); // Feche a instrução
}

echo '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        input[type="email"], input[type="password"] {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            outline: none;
        }
        input[type="email"]:focus, input[type="password"]:focus {
            border-color: #007bff;
        }
        button {
            padding: 12px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        .link {
            margin-top: 15px;
        }
        .link a {
            color: #007bff;
            text-decoration: none;
        }
        .link a:hover {
            text-decoration: underline;
        }
        .message, .error {
            margin-top: 20px;
            font-size: 14px;
        }
        .message {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        ';

        if (isset($_GET['action']) && $_GET['action'] == 'register') {
            echo '
            <h2>Cadastro</h2>
            <form method="POST" action="">
                <input type="email" name="email" placeholder="E-mail" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <button type="submit" name="register">Cadastrar</button>
            </form>
            <div class="link"><a href="index.php">Já tem uma conta? Faça login</a></div>
            ';
        } elseif (isset($_GET['action']) && $_GET['action'] == 'reset') {
            echo '
            <h2>Recuperar Senha</h2>
            <form method="POST" action="">
                <input type="email" name="email" placeholder="E-mail" required>
                <button type="submit" name="reset_password">Recuperar Senha</button>
            </form>
            <div class="link"><a href="index.php">Voltar ao login</a></div>
            ';
        } else {
            echo '
            <h2>Login</h2>
            <form method="POST" action="">
                <input type="email" name="email" placeholder="E-mail" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <button type="submit" name="login">Entrar</button>
            </form>
            <div class="link"><a href="?action=register">Criar uma conta</a></div>
            <div class="link"><a href="?action=reset">Esqueci minha senha</a></div>
            ';
        }

        if (isset($mensagem)) {
            echo "<p class='message'>" . $mensagem . "</p>";
        }

        if (isset($erro)) {
            echo "<p class='error'>" . $erro . "</p>";
        }

        echo '
    </div>
</body>
</html>
';

// Fechar a conexão apenas se ela estiver aberta
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
