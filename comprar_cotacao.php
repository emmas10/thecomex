<?php
include 'verifica_login.php';
include 'conexao.php';
include 'registrar_auditoria.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$id = intval($_POST['id']);
$usuario_id = $_SESSION['usuario_id'];

$sql = "
INSERT INTO compras
(produto, fornecedor, preco_pago, quantidade, data_compra, cotacao_id, status, cliente_id, usuario_id)

SELECT
produto, fornecedor, preco, quantidade, CURDATE(), id, 'ativa', cliente_id, '$usuario_id'
FROM cotacoes
WHERE id = $id

ON DUPLICATE KEY UPDATE
status = 'ativa',
data_compra = CURDATE(),
usuario_id = '$usuario_id'
";

$conn->query($sql);
registrarAuditoria($conn, 'Compra de cotação', 'Usuário comprou a cotação ID ' . $id);

header("Location: index.php");
exit;
?>