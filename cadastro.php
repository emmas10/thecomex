<?php
session_start();
include 'conexao.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = md5($_POST['senha']);
    $tipo = 'visualizacao';
    $cliente_id = $_POST['cliente_id'];

    $sql = "INSERT INTO usuarios (nome, email, senha, tipo, cliente_id)
            VALUES ('$nome', '$email', '$senha', '$tipo', '$cliente_id')";

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

    <?php if ($mensagem != '') { echo "<p>$mensagem</p>"; } ?>

    <form method="POST">
        <input type="text" name="nome" placeholder="Nome" required>
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="senha" placeholder="Senha" required>
        <select name="cliente_id" required>
    <option value="">Selecione o cliente</option>

    <?php
    $sqlClientes = "SELECT * FROM clientes ORDER BY nome_empresa ASC";
    $resultadoClientes = $conn->query($sqlClientes);

    while ($cliente = $resultadoClientes->fetch_assoc()) {
        echo "<option value='" . $cliente['id'] . "'>" . $cliente['nome_empresa'] . "</option>";
    }
    ?>
</select>

        <button type="submit">Cadastrar Usuário</button>
        <p style="margin-top:15px;">
           Já tem conta?
          <a href="login.php">Clique aqui</a>
</p>
    </form>
</div>

</body>
</html>