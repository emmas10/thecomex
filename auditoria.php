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
    <title>Auditoria - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<h1>Auditoria do Sistema</h1>

<a href="index.php" class="botao-exportar">Voltar</a>

<table>
<tr>
    <th>Usuário</th>
    <th>Ação</th>
    <th>Descrição</th>
    <th>Data e Hora</th>
</tr>

<?php

$sql = "SELECT * FROM auditoria ORDER BY criado_em DESC";
$resultado = $conn->query($sql);

while ($linha = $resultado->fetch_assoc()) {

    echo "<tr>";

    echo "<td>" . htmlspecialchars($linha['usuario_nome'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($linha['tipo_acao'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($linha['descricao'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($linha['criado_em'], ENT_QUOTES, 'UTF-8') . "</td>";

    echo "</tr>";
}

?>

</table>

</div>

</body>
</html>
