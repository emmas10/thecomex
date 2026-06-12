<?php
include 'verifica_login.php';
include 'conexao.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = md5($_POST['senha']);
    $tipo = 'visualizacao';

    $sql = "INSERT INTO usuarios (nome, email, senha, tipo)
            VALUES ('$nome', '$email', '$senha', '$tipo')";

    if ($conn->query($sql) === TRUE) {
        $mensagem = "Usuário cadastrado com sucesso!";
    } else {
        $mensagem = "Erro: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Usuários - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Cadastro de Usuários</h1>

    <a href="index.php" class="botao-exportar">Voltar</a>

    <?php if ($mensagem != '') { echo "<p>$mensagem</p>"; } ?>

    <form method="POST">
        <input type="text" name="nome" placeholder="Nome" required>
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="senha" placeholder="Senha" required>


        <button type="submit">Cadastrar Usuário</button>
    </form>
</div>

</body>
</html>