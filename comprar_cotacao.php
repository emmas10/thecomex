<?php
include 'verifica_login.php';
include 'conexao.php';
include 'helpers_preco.php';
include 'registrar_auditoria.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$id = intval($_POST['id']);
$usuario_id = intval($_SESSION['usuario_id']);

if (colunaProdutoBaseComprasExiste($conn) && colunaProdutoBaseExiste($conn)) {
    $stmt = $conn->prepare(
        "INSERT INTO compras
        (produto, produto_base, fornecedor, preco_pago, preco_pago_casas_decimais, quantidade, data_compra, cotacao_id, status, cliente_id, usuario_id)
        SELECT produto, COALESCE(NULLIF(TRIM(produto_base), ''), TRIM(LOWER(produto))), fornecedor, preco, preco_casas_decimais, quantidade, CURDATE(), id, 'ativa', cliente_id, ?
        FROM cotacoes
        WHERE id = ?
        ON DUPLICATE KEY UPDATE
        produto_base = VALUES(produto_base),
        status = 'ativa',
        data_compra = CURDATE(),
        preco_pago = VALUES(preco_pago),
        preco_pago_casas_decimais = VALUES(preco_pago_casas_decimais),
        usuario_id = ?"
    );
} else {
    $stmt = $conn->prepare(
        "INSERT INTO compras
        (produto, fornecedor, preco_pago, preco_pago_casas_decimais, quantidade, data_compra, cotacao_id, status, cliente_id, usuario_id)
        SELECT produto, fornecedor, preco, preco_casas_decimais, quantidade, CURDATE(), id, 'ativa', cliente_id, ?
        FROM cotacoes
        WHERE id = ?
        ON DUPLICATE KEY UPDATE
        status = 'ativa',
        data_compra = CURDATE(),
        preco_pago = VALUES(preco_pago),
        preco_pago_casas_decimais = VALUES(preco_pago_casas_decimais),
        usuario_id = ?"
    );
}
$stmt->bind_param("iii", $usuario_id, $id, $usuario_id);
$stmt->execute();

registrarAuditoria($conn, 'Compra de cotação', 'Usuário comprou a cotação ID ' . $id);

header("Location: index.php");
exit;
?>
