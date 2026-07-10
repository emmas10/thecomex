<?php
include 'verifica_login.php';
include 'conexao.php';
include 'helpers_pdf_relatorio.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

function valorReferenciaInput($valor)
{
    if ($valor === null || $valor === '') {
        return '';
    }

    return rtrim(rtrim(number_format((float) $valor, 6, '.', ''), '0'), '.');
}

$clientes = [];
$resultadoClientes = $conn->query("SELECT id, nome_empresa FROM clientes WHERE ativo = 1 ORDER BY nome_empresa ASC");

while ($cliente = $resultadoClientes->fetch_assoc()) {
    $clientes[] = $cliente;
}

$clienteIdSelecionado = intval($_GET['cliente_id'] ?? 0);

if ($clienteIdSelecionado <= 0 && count($clientes) > 0) {
    $clienteIdSelecionado = intval($clientes[0]['id']);
}

$clienteSelecionado = null;

foreach ($clientes as $cliente) {
    if (intval($cliente['id']) === $clienteIdSelecionado) {
        $clienteSelecionado = $cliente;
        break;
    }
}

$produtos = [];
$referencias = [];

if ($clienteSelecionado) {
    $produtos = buscarProdutosRelatorio($conn, $clienteIdSelecionado);
    $referencias = buscarReferenciasProdutoRelatorio($conn, $clienteIdSelecionado);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Produtos - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<h1>Produtos</h1>

<a href="index.php" class="botao-exportar">Voltar</a>

<form method="GET" action="produtos.php">
    <label for="cliente_id">Empresa:</label>
    <select name="cliente_id" id="cliente_id" required>
        <?php
        foreach ($clientes as $cliente) {
            $clienteId = intval($cliente['id']);
            $selecionado = ($clienteId === $clienteIdSelecionado) ? 'selected' : '';
            echo "<option value='" . $clienteId . "' {$selecionado}>" . htmlspecialchars($cliente['nome_empresa'], ENT_QUOTES, 'UTF-8') . "</option>";
        }
        ?>
    </select>
    <button type="submit">Ver produtos</button>
</form>

<?php if (!$clienteSelecionado) { ?>
    <p>Nenhuma empresa ativa encontrada.</p>
<?php } elseif (count($produtos) === 0) { ?>
    <p>Nenhum produto encontrado para esta empresa.</p>
<?php } else { ?>
    <h2><?php echo htmlspecialchars($clienteSelecionado['nome_empresa'], ENT_QUOTES, 'UTF-8'); ?></h2>

    <table>
        <tr>
            <th>Produto</th>
            <th>Produto padronizado</th>
            <th>Valor da ultima compra</th>
            <th>Data da ultima compra</th>
            <th>Acao</th>
        </tr>

        <?php foreach ($produtos as $produtoBase => $produtoNome) {
            $referencia = $referencias[$produtoBase] ?? null;
            $valor = $referencia ? valorReferenciaInput($referencia['valor_ultima_compra']) : '';
            $data = $referencia['data_ultima_compra'] ?? '';
            $formId = 'produto-referencia-' . md5($clienteIdSelecionado . '|' . $produtoBase);
        ?>
            <tr>
                <td><?php echo htmlspecialchars($produtoNome, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($produtoBase, ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <input form="<?php echo $formId; ?>" type="number" step="0.000001" min="0" name="valor_ultima_compra" value="<?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Valor">
                </td>
                <td>
                    <input form="<?php echo $formId; ?>" type="date" name="data_ultima_compra" value="<?php echo htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </td>
                <td>
                    <form id="<?php echo $formId; ?>" action="salvar_produto_referencia.php" method="POST">
                        <input type="hidden" name="cliente_id" value="<?php echo intval($clienteIdSelecionado); ?>">
                        <input type="hidden" name="produto_base" value="<?php echo htmlspecialchars($produtoBase, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="produto_nome" value="<?php echo htmlspecialchars($produtoNome, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit">Editar ultima compra</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>
<?php } ?>

</div>

</body>
</html>
