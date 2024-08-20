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

// Função para redirecionar
function redirecionar($url) {
    header("Location: $url");
    exit();
}

// Função para gerar senha aleatória
function gerarSenha() {
    return substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
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
        redirecionar('index.php');
    } else {
        $erro = "E-mail ou senha incorretos.";
    }
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
        $nova_senha = gerarSenha();
        $sql = "UPDATE usuarios SET senha = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nova_senha, $email);

        if ($stmt->execute()) {
            // Enviar e-mail com a nova senha
            mail($email, "Nova Senha", "Sua nova senha é: $nova_senha");

            $mensagem = "Uma nova senha foi enviada para seu e-mail.";
        } else {
            $erro = "Erro ao atualizar a senha. Tente novamente.";
        }
    } else {
        $erro = "E-mail não encontrado.";
    }
}

echo '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        input[type="text"], input[type="password"], input[type="email"] {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px;
            border: none;
            border-radius: 4px;
            background-color: #5cb85c;
            color: #fff;
            cursor: pointer;
        }
        button:hover {
            background-color: #4cae4c;
        }
        .message {
            color: green;
            margin-top: 10px;
            text-align: center;
        }
        .error {
            color: red;
            margin-top: 10px;
            text-align: center;
        }
        .link {
            text-align: center;
            margin-top: 15px;
        }
        .link a {
            color: #5cb85c;
            text-decoration: none;
        }
        .link a:hover {
            text-decoration: underline;
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
$conn->close();
?>
