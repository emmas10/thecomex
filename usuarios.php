<?php
include 'verifica_login.php';
include 'conexao.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Usuários - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<h1>Usuários Cadastrados</h1>

<a href="index.php" class="botao-exportar">Voltar</a>

<table>
    <tr>
        <th>Nome</th>
        <th>E-mail</th>
        <th>Tipo</th>
        <th>Empresa</th>
        <th>Cadastrado em</th>
        <th>Ação</th>
    </tr>

    <?php
    $sql = "SELECT usuarios.*, clientes.nome_empresa
            FROM usuarios
            LEFT JOIN clientes ON usuarios.cliente_id = clientes.id
            ORDER BY usuarios.nome ASC";

    $resultado = $conn->query($sql);

    while ($linha = $resultado->fetch_assoc()) {

    echo "<tr>";
    echo "<td>" . $linha['nome'] . "</td>";
    echo "<td>" . $linha['email'] . "</td>";
    echo "<td>" . $linha['tipo'] . "</td>";
    echo "<td>" . ($linha['nome_empresa'] ?? 'Sem empresa') . "</td>";
    echo "<td>" . $linha['criado_em'] . "</td>";

    echo "<td>";
    echo "<a href='editar_usuario.php?id=" . $linha['id'] . "' class='botao-exportar'>Editar</a>";
    echo "</td>";

    echo "</tr>";
}
    ?>
</table>

</div>

</body>
</html>