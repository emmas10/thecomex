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
    <title>Clientes - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Clientes</h1>

    <a href="index.php" class="botao-exportar">Voltar</a>

    <form action="salvar_cliente.php" method="POST">
        <input type="text" name="nome_empresa" placeholder="Nome da empresa" required>
        <input type="text" name="cnpj" placeholder="CNPJ">
        <input type="text" name="responsavel" placeholder="Responsável">
        <input type="email" name="email" placeholder="E-mail">
        <input type="text" name="telefone" placeholder="Telefone">

        <button type="submit">Cadastrar Cliente</button>
    </form>

    <h2>Clientes Cadastrados</h2>

    <table>
        <tr>
            <th>Empresa</th>
            <th>CNPJ</th>
            <th>Responsável</th>
            <th>E-mail</th>
            <th>Telefone</th>
        </tr>

        <?php
        $sql = "SELECT * FROM clientes ORDER BY nome_empresa ASC";
        $resultado = $conn->query($sql);

        while ($linha = $resultado->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $linha['nome_empresa'] . "</td>";
            echo "<td>" . $linha['cnpj'] . "</td>";
            echo "<td>" . $linha['responsavel'] . "</td>";
            echo "<td>" . $linha['email'] . "</td>";
            echo "<td>" . $linha['telefone'] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</div>

</body>
</html>