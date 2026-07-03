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
    <title>Relatórios PDF - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<h1>Relatórios PDF</h1>

<a href="index.php" class="botao-exportar">Voltar</a>

<form action="gerar_pdf_cliente.php" method="GET" target="_blank">
    <select name="cliente_id" required>
        <option value="">Selecione a empresa</option>

        <?php
        $sql = "SELECT * FROM clientes ORDER BY nome_empresa ASC";
        $resultado = $conn->query($sql);

        while ($cliente = $resultado->fetch_assoc()) {
            echo "<option value='" . $cliente['id'] . "'>" . $cliente['nome_empresa'] . "</option>";
        }
        ?>
    </select>

    <button type="submit">Gerar PDF</button>
</form>

</div>

</body>
</html>