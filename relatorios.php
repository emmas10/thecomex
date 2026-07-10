<?php
include 'verifica_login.php';
include 'conexao.php';
include 'helpers_pdf_relatorio.php';

$usuarioAdmin = ($_SESSION['usuario_tipo'] ?? '') === 'admin';
$clienteSessao = intval($_SESSION['cliente_id'] ?? 0);

if (!$usuarioAdmin && $clienteSessao <= 0) {
    echo "Acesso negado.";
    exit;
}

$clientes = [];

if ($usuarioAdmin) {
    $sqlClientes = "SELECT id, nome_empresa FROM clientes WHERE ativo = 1 ORDER BY nome_empresa ASC";
    $resultadoClientes = $conn->query($sqlClientes);
} else {
    $stmtClientes = $conn->prepare("SELECT id, nome_empresa FROM clientes WHERE id = ? AND ativo = 1 LIMIT 1");
    $stmtClientes->bind_param("i", $clienteSessao);
    $stmtClientes->execute();
    $resultadoClientes = $stmtClientes->get_result();
}

while ($cliente = $resultadoClientes->fetch_assoc()) {
    $clientes[] = $cliente;
}

$produtosPorCliente = [];

foreach ($clientes as $cliente) {
    $clienteId = intval($cliente['id']);
    $produtosPorCliente[$clienteId] = buscarProdutosRelatorio($conn, $clienteId);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatorios PDF - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<h1>Relatorios PDF</h1>

<a href="index.php" class="botao-exportar">Voltar</a>

<form method="GET" target="_blank">
    <label for="cliente_id">Empresa:</label>
    <select name="cliente_id" id="cliente_id" required>
        <option value="">Selecione a empresa</option>

        <?php
        foreach ($clientes as $cliente) {
            $clienteId = intval($cliente['id']);
            $selecionado = (!$usuarioAdmin && $clienteId === $clienteSessao) ? 'selected' : '';
            echo "<option value='" . $clienteId . "' {$selecionado}>" . htmlspecialchars($cliente['nome_empresa'], ENT_QUOTES, 'UTF-8') . "</option>";
        }
        ?>
    </select>

    <label for="produto_grupo">Produto:</label>
    <select name="produto_grupo" id="produto_grupo">
        <option value="">Selecione o produto</option>
    </select>

    <button type="submit" formaction="gerar_pdf_cliente.php">Gerar PDF completo da empresa</button>
    <button type="submit" formaction="gerar_pdf_produto.php" id="botao_pdf_produto">Gerar PDF por Produto</button>
</form>

</div>

<script>
const produtosPorCliente = <?php echo json_encode($produtosPorCliente, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const selectCliente = document.getElementById('cliente_id');
const selectProduto = document.getElementById('produto_grupo');
const botaoPdfProduto = document.getElementById('botao_pdf_produto');

function carregarProdutos() {
    const clienteId = selectCliente.value;
    const produtos = produtosPorCliente[clienteId] || {};

    selectProduto.innerHTML = '<option value="">Selecione o produto</option>';

    Object.keys(produtos).forEach(function (grupo) {
        const option = document.createElement('option');
        option.value = grupo;
        option.textContent = produtos[grupo];
        selectProduto.appendChild(option);
    });
}

selectCliente.addEventListener('change', carregarProdutos);
botaoPdfProduto.addEventListener('click', function (event) {
    if (!selectProduto.value) {
        event.preventDefault();
        alert('Selecione um produto para gerar o PDF por produto.');
    }
});

carregarProdutos();
</script>

</body>
</html>
