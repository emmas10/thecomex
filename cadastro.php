<?php
include 'verifica_login.php';
include 'conexao.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $tipo = 'visualizacao';
    $cliente_id = intval($_POST['cliente_id']);

    $stmtCliente = $conn->prepare("SELECT id FROM clientes WHERE id = ? AND ativo = 1 LIMIT 1");
    $stmtCliente->bind_param("i", $cliente_id);
    $stmtCliente->execute();
    $clienteAtivo = $stmtCliente->get_result()->fetch_assoc();

    if (!$clienteAtivo) {
        $mensagem = "Cliente invalido ou desativado.";
    } else {
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo, cliente_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $nome, $email, $senha, $tipo, $cliente_id);

        if ($stmt->execute()) {
            $mensagem = "Usuario cadastrado com sucesso!";
        } else {
            $mensagem = "Erro ao cadastrar usuario.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Usuarios - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Cadastro de Usuarios</h1>

    <?php if ($mensagem != '') { echo "<p>" . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . "</p>"; } ?>

    <form method="POST">
        <input type="text" name="nome" placeholder="Nome" required>
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="senha" placeholder="Senha" required>
        <select name="cliente_id" required>
            <option value="">Selecione o cliente</option>

            <?php
            $sqlClientes = "SELECT id, nome_empresa FROM clientes WHERE ativo = 1 ORDER BY nome_empresa ASC";
            $resultadoClientes = $conn->query($sqlClientes);

            while ($cliente = $resultadoClientes->fetch_assoc()) {
                echo "<option value='" . intval($cliente['id']) . "'>" . htmlspecialchars($cliente['nome_empresa'], ENT_QUOTES, 'UTF-8') . "</option>";
            }
            ?>
        </select>

        <button type="submit">Cadastrar Usuario</button>
        <p style="margin-top:15px;">
           Ja tem conta?
          <a href="login.php">Clique aqui</a>
        </p>
    </form>
</div>

</body>
</html>
