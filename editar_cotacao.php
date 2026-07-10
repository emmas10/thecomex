<?php
include 'verifica_login.php';
include 'conexao.php';
include 'helpers_preco.php';
include 'registrar_auditoria.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$erro = '';

if ($id <= 0) {
    echo "Cotacao invalida.";
    exit;
}

$stmt = $conn->prepare(
    "SELECT cotacoes.*, clientes.nome_empresa
     FROM cotacoes
     LEFT JOIN clientes ON cotacoes.cliente_id = clientes.id
     WHERE cotacoes.id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$cotacao = $resultado->fetch_assoc();

if (!$cotacao) {
    echo "Cotacao nao encontrada.";
    exit;
}

$stmtCompra = $conn->prepare(
    "SELECT id
     FROM compras
     WHERE cotacao_id = ?
     AND status = 'ativa'
     LIMIT 1"
);
$stmtCompra->bind_param("i", $id);
$stmtCompra->execute();
$resultadoCompra = $stmtCompra->get_result();
$possuiCompraVinculada = ($resultadoCompra && $resultadoCompra->num_rows > 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = intval($_POST['cliente_id'] ?? 0);
    $cotacaoNome = trim($_POST['cotacao'] ?? '');
    $produto = trim($_POST['produto'] ?? '');
    $produto_base = trim($_POST['produto_base'] ?? '');
    if ($produto_base === '') {
        $produto_base = $produto;
    }
    $produto_base = normalizarProdutoBase($produto_base);
    $fornecedor = trim($_POST['fornecedor'] ?? '');
    $precoEntrada = parsePrecoEntrada($_POST['preco'] ?? '');
    $origem = trim($_POST['origem'] ?? '');
    $pagamento = trim($_POST['pagamento'] ?? '');
    $quantidade = trim($_POST['quantidade'] ?? '');
    $data_cotacao = trim($_POST['data_cotacao'] ?? '');

    if ($cliente_id <= 0) {
        $erro = "Selecione uma empresa valida.";
    } elseif ($cotacaoNome === '' || $produto === '' || $fornecedor === '' || $data_cotacao === '') {
        $erro = "Preencha os campos obrigatorios.";
    } elseif ($precoEntrada === false) {
        $erro = "Preco invalido. Use ate 6 casas decimais.";
    } else {
        $stmtCliente = $conn->prepare("SELECT id FROM clientes WHERE id = ? AND ativo = 1 LIMIT 1");
        $stmtCliente->bind_param("i", $cliente_id);
        $stmtCliente->execute();

        if (!$stmtCliente->get_result()->fetch_assoc()) {
            $erro = "Cliente invalido ou desativado.";
        }
    }

    if ($erro === '') {
        $preco = $precoEntrada['valor'];
        $preco_casas_decimais = $precoEntrada['casas'];

        if (colunaProdutoBaseExiste($conn)) {
            $stmtUpdate = $conn->prepare(
                "UPDATE cotacoes
                 SET cliente_id = ?,
                     cotacao = ?,
                     produto = ?,
                     produto_base = ?,
                     fornecedor = ?,
                     preco = ?,
                     preco_casas_decimais = ?,
                     origem = ?,
                     pagamento = ?,
                     quantidade = ?,
                     data_cotacao = ?
                 WHERE id = ?"
            );
            $stmtUpdate->bind_param(
                "isssssissssi",
                $cliente_id,
                $cotacaoNome,
                $produto,
                $produto_base,
                $fornecedor,
                $preco,
                $preco_casas_decimais,
                $origem,
                $pagamento,
                $quantidade,
                $data_cotacao,
                $id
            );
        } else {
            $stmtUpdate = $conn->prepare(
                "UPDATE cotacoes
                 SET cliente_id = ?,
                     cotacao = ?,
                     produto = ?,
                     fornecedor = ?,
                     preco = ?,
                     preco_casas_decimais = ?,
                     origem = ?,
                     pagamento = ?,
                     quantidade = ?,
                     data_cotacao = ?
                 WHERE id = ?"
            );
            $stmtUpdate->bind_param(
                "issssissssi",
                $cliente_id,
                $cotacaoNome,
                $produto,
                $fornecedor,
                $preco,
                $preco_casas_decimais,
                $origem,
                $pagamento,
                $quantidade,
                $data_cotacao,
                $id
            );
        }

        if ($stmtUpdate->execute()) {
            registrarAuditoria(
                $conn,
                'Edicao de cotacao',
                'Usuario editou a cotacao ID ' . $id
            );

            header("Location: index.php");
            exit;
        }

        $erro = "Erro ao atualizar cotacao.";
    }

    $cotacao['cliente_id'] = $cliente_id;
    $cotacao['cotacao'] = $cotacaoNome;
    $cotacao['produto'] = $produto;
    $cotacao['produto_base'] = $produto_base;
    $cotacao['fornecedor'] = $fornecedor;
    $cotacao['preco'] = $_POST['preco'] ?? '';
    $cotacao['preco_casas_decimais'] = ($precoEntrada !== false) ? $precoEntrada['casas'] : ($cotacao['preco_casas_decimais'] ?? null);
    $cotacao['origem'] = $origem;
    $cotacao['pagamento'] = $pagamento;
    $cotacao['quantidade'] = $quantidade;
    $cotacao['data_cotacao'] = $data_cotacao;
}

function valorPrecoInput($valor, $casas)
{
    if ($valor === null || $valor === '') {
        return '';
    }

    $valor = str_replace(',', '.', (string) $valor);

    if ($casas !== null && $casas !== '') {
        $casas = max(0, min(6, intval($casas)));
        $partes = explode('.', $valor, 2);
        $inteiro = $partes[0] !== '' ? $partes[0] : '0';
        $decimal = isset($partes[1]) ? substr(str_pad($partes[1], $casas, '0'), 0, $casas) : str_repeat('0', $casas);
        return $casas > 0 ? $inteiro . '.' . $decimal : $inteiro;
    }

    return rtrim(rtrim(number_format((float) $valor, 6, '.', ''), '0'), '.');
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Cota&ccedil;&atilde;o - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<h1>Editar Cota&ccedil;&atilde;o</h1>

<a href="index.php" class="botao-exportar">Voltar</a>

<?php if ($possuiCompraVinculada) { ?>
    <p><strong>Aviso:</strong> esta cota&ccedil;&atilde;o possui compra ativa vinculada. A edi&ccedil;&atilde;o da cota&ccedil;&atilde;o n&atilde;o altera automaticamente o hist&oacute;rico da compra.</p>
<?php } ?>

<?php if ($erro !== '') { ?>
    <p><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php } ?>

<form method="POST">
    <select name="cliente_id" required>
        <option value="">Selecione a empresa</option>
        <?php
        $resultadoClientes = $conn->query("SELECT id, nome_empresa FROM clientes WHERE ativo = 1 ORDER BY nome_empresa ASC");

        while ($cliente = $resultadoClientes->fetch_assoc()) {
            $clienteId = intval($cliente['id']);
            $selecionado = ($clienteId === intval($cotacao['cliente_id'])) ? 'selected' : '';
            echo "<option value='" . $clienteId . "' " . $selecionado . ">" . htmlspecialchars($cliente['nome_empresa'], ENT_QUOTES, 'UTF-8') . "</option>";
        }
        ?>
    </select>

    <input type="text" name="cotacao" placeholder="Nome/N&ordm; da Cota&ccedil;&atilde;o" value="<?php echo htmlspecialchars($cotacao['cotacao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
    <input type="text" name="produto" placeholder="Produto" value="<?php echo htmlspecialchars($cotacao['produto'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
    <input type="text" name="produto_base" placeholder="Produto base/padronizado" value="<?php echo htmlspecialchars($cotacao['produto_base'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <input type="text" name="fornecedor" placeholder="Fornecedor" value="<?php echo htmlspecialchars($cotacao['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
    <input type="number" step="0.000001" min="0" name="preco" placeholder="Preco US$" value="<?php echo htmlspecialchars(valorPrecoInput($cotacao['preco'] ?? '', $cotacao['preco_casas_decimais'] ?? null), ENT_QUOTES, 'UTF-8'); ?>" required>
    <input type="text" name="origem" placeholder="Origem" value="<?php echo htmlspecialchars($cotacao['origem'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <input type="text" name="pagamento" placeholder="Pagamento" value="<?php echo htmlspecialchars($cotacao['pagamento'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <input type="text" name="quantidade" placeholder="Quantidade" value="<?php echo htmlspecialchars($cotacao['quantidade'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <input type="date" name="data_cotacao" value="<?php echo htmlspecialchars($cotacao['data_cotacao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

    <button type="submit">Salvar Altera&ccedil;&otilde;es</button>
</form>

</div>

</body>
</html>
