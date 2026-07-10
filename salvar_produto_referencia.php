<?php
include 'verifica_login.php';
include 'conexao.php';
include 'helpers_preco.php';
include 'registrar_auditoria.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$clienteId = intval($_POST['cliente_id'] ?? 0);
$produtoBase = normalizarProdutoBase($_POST['produto_base'] ?? '');
$produtoNome = trim($_POST['produto_nome'] ?? $produtoBase);
$valorInformado = trim($_POST['valor_ultima_compra'] ?? '');
$dataUltimaCompra = trim($_POST['data_ultima_compra'] ?? '');

if ($clienteId <= 0 || $produtoBase === '') {
    echo "Produto ou empresa invalida.";
    exit;
}

$stmtCliente = $conn->prepare("SELECT id, nome_empresa FROM clientes WHERE id = ? AND ativo = 1 LIMIT 1");
$stmtCliente->bind_param("i", $clienteId);
$stmtCliente->execute();
$cliente = $stmtCliente->get_result()->fetch_assoc();

if (!$cliente) {
    echo "Cliente invalido ou desativado.";
    exit;
}

$valorUltimaCompra = null;

if ($valorInformado !== '') {
    $precoEntrada = parsePrecoEntrada($valorInformado);

    if ($precoEntrada === false) {
        echo "Valor invalido. Use ate 6 casas decimais.";
        exit;
    }

    $valorUltimaCompra = $precoEntrada['valor'];
}

if ($dataUltimaCompra !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataUltimaCompra)) {
    echo "Data invalida.";
    exit;
}

$dataBanco = ($dataUltimaCompra !== '') ? $dataUltimaCompra : null;

$stmt = $conn->prepare(
    "INSERT INTO produto_referencias
        (cliente_id, produto_base, valor_ultima_compra, data_ultima_compra)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
        valor_ultima_compra = VALUES(valor_ultima_compra),
        data_ultima_compra = VALUES(data_ultima_compra)"
);
$stmt->bind_param("isss", $clienteId, $produtoBase, $valorUltimaCompra, $dataBanco);

if ($stmt->execute()) {
    registrarAuditoria(
        $conn,
        'Edicao de referencia de produto',
        'Usuario alterou a ultima compra do produto ' . $produtoBase . ' para o cliente ID ' . $clienteId
    );

    header("Location: produtos.php?cliente_id=" . $clienteId);
    exit;
}

echo "Erro ao salvar referencia.";
?>
