<?php
include 'verifica_login.php';
include 'conexao.php';
include 'helpers_preco.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$produto = trim($_POST['produto']);
$produto_base = normalizarProdutoBase($produto);
$fornecedor = trim($_POST['fornecedor']);
$precoEntrada = parsePrecoEntrada($_POST['preco_pago']);
if ($precoEntrada === false) {
    echo "Preço inválido. Use até 6 casas decimais.";
    exit;
}

$preco_pago = $precoEntrada['valor'];
$preco_pago_casas_decimais = $precoEntrada['casas'];
$quantidade = trim($_POST['quantidade']);
$data_compra = $_POST['data_compra'];
$observacoes = trim($_POST['observacoes']);
$cliente_id = isset($_POST['cliente_id']) ? intval($_POST['cliente_id']) : 0;
$usuario_id = intval($_SESSION['usuario_id']);

if ($cliente_id <= 0) {
    echo "Cliente inválido.";
    exit;
}

if (colunaProdutoBaseComprasExiste($conn)) {
    $stmt = $conn->prepare(
        "INSERT INTO compras
        (produto, produto_base, fornecedor, preco_pago, preco_pago_casas_decimais, quantidade, data_compra, observacoes, cliente_id, usuario_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssssisssii", $produto, $produto_base, $fornecedor, $preco_pago, $preco_pago_casas_decimais, $quantidade, $data_compra, $observacoes, $cliente_id, $usuario_id);
} else {
    $stmt = $conn->prepare(
        "INSERT INTO compras
        (produto, fornecedor, preco_pago, preco_pago_casas_decimais, quantidade, data_compra, observacoes, cliente_id, usuario_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssisssii", $produto, $fornecedor, $preco_pago, $preco_pago_casas_decimais, $quantidade, $data_compra, $observacoes, $cliente_id, $usuario_id);
}

if ($stmt->execute()) {
    header("Location: index.php");
    exit;
}

echo "Erro ao salvar compra.";
?>
