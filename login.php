<?php
session_start();
include 'conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        $senhaValida = password_verify($senha, $usuario['senha']);

        if (!$senhaValida && md5($senha) === $usuario['senha']) {
            $senhaValida = true;
            $novoHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmtUpdate = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmtUpdate->bind_param("si", $novoHash, $usuario['id']);
            $stmtUpdate->execute();
        }

        if ($senhaValida) {
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];
            $_SESSION['cliente_id'] = $usuario['cliente_id'];

            header("Location: index.php");
            exit;
        }
    }

    $erro = "E-mail ou senha incorretos.";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Login - TheComex</h1>

    <?php if ($erro != '') { ?>
        <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>

    <form method="POST">
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="senha" placeholder="Senha" required>

        <button type="submit">Entrar</button>
        <p style="margin-top:15px;">
            Não tem uma conta?
           <a href="cadastro.php">Cadastre-se aqui</a>
</p>
    </form>
</div>

</body>
</html>
