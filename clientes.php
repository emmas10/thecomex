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
        <input type="text" name="responsavel" placeholder="Responsavel">
        <input type="email" name="email" placeholder="E-mail">
        <input type="text" name="telefone" placeholder="Telefone">

        <button type="submit">Cadastrar Cliente</button>
    </form>

    <h2>Clientes Cadastrados</h2>

    <table>
        <tr>
            <th>Empresa</th>
            <th>CNPJ</th>
            <th>Responsavel</th>
            <th>E-mail</th>
            <th>Telefone</th>
            <th>Acao</th>
        </tr>

        <?php
        $stmt = $conn->prepare(
            "SELECT id, nome_empresa, cnpj, responsavel, email, telefone
             FROM clientes
             WHERE ativo = 1
             ORDER BY nome_empresa ASC"
        );
        $stmt->execute();
        $resultado = $stmt->get_result();

        while ($linha = $resultado->fetch_assoc()) {
            $idCliente = intval($linha['id']);

            echo "<tr>";
            echo "<td>" . htmlspecialchars($linha['nome_empresa'], ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>" . htmlspecialchars($linha['cnpj'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>" . htmlspecialchars($linha['responsavel'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>" . htmlspecialchars($linha['email'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>" . htmlspecialchars($linha['telefone'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>";
            echo "<a href='editar_cliente.php?id=" . $idCliente . "' class='botao-exportar'>Editar</a> ";
            echo "<form action='excluir_cliente.php' method='POST' style='display:inline;' onsubmit=\"return confirm('Confirma excluir ou desativar este cliente?');\">";
            echo "<input type='hidden' name='id' value='" . $idCliente . "'>";
            echo "<button type='submit'>Excluir/Desativar</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</div>

</body>
</html>
