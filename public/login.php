<?php
session_start();

// Conecta-se ao banco de dados 'intranet' para verificar os usuários

// Se o usuário já estiver logado, redireciona para a página principal
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$conn = new mysqli("localhost", "root", "", "intranet");
if ($conn->connect_error) {
    // Em um ambiente de produção, seria melhor logar o erro do que exibi-lo.
    die("Falha na conexão com o banco de dados. Por favor, contate o administrador.");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $error = "Por favor, preencha todos os campos.";
    } else {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Usando "Prepared Statements" para previnir Injeção de SQL
        // Ajustado para corresponder à sua tabela: id, username, password, role
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Verifica se a senha corresponde à hash salva no banco
            if (password_verify($password, $user['password'])) {
                // Regenera o ID da sessão para segurança
                session_regenerate_id(true);
                
                // Salva as informações do usuário na sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                // Como não há 'nome_completo', usaremos o 'username' para exibição.
                $_SESSION['nome_completo'] = $user['username'];
                $_SESSION['role'] = $user['role']; // Adicionado para futuras permissões
                
                // Redireciona para a página principal do sistema
                header("Location: index.php");
                exit();
            } else {
                $error = "Usuário ou senha incorretos.";
            }
        } else {
            $error = "Usuário ou senha incorretos.";
        }
        $stmt->close();
    }
}
$conn->close(); // Fecha a conexão
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Login - Sistema Vale Palete</title>
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: url('images/background.png') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #254c90;
        }
        .login-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 350px;
            position: relative;
        }
        .login-container img {
            width: 200px;
            max-width: 90%;
            margin-bottom: 20px;
        }
        .login-container h2 {
            color: #0052a5;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .login-container input {
            width: calc(100% - 20px);
            padding: 15px;
            margin: 15px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        .login-container input:focus {
            border-color: #0052a5;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 82, 165, 0.5);
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            background: #0052a5;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .login-container button:hover {
            background: #003d7a;
        }
        .login-container button:active {
            transform: scale(0.98);
        }
        @media (max-width: 600px) {
            body {
                background-image: none;
                background: #254c90;
            }
            .login-container {
                width: 100%;
                max-width: 320px;
                padding: 16px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="images/logo.svg" alt="Logo">
        <h2>Acessar Sistema</h2>
        <?php if (!empty($error)): ?>
            <div style="color: red; margin-bottom: 10px;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php" autocomplete="off">
            <input type="text" name="username" placeholder="Nome de Usuário" required>
            <input type="password" name="password" placeholder="Senha" required>
            <button type="submit">ENTRAR</button>
        </form>
    </div>
</body>
</html>