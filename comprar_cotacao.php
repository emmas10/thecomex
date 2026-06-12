<?php
include 'verifica_login.php';
include 'conexao.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$id = intval($_POST['id']);

$sql = "
INSERT INTO compras 
(produto, fornecedor, preco_pago, quantidade, data_compra, cotacao_id, status)

SELECT 
produto, fornecedor, preco, quantidade, CURDATE(), id, 'ativa'
FROM cotacoes
WHERE id = $id

ON DUPLICATE KEY UPDATE
status = 'ativa',
data_compra = CURDATE()
";

$conn->query($sql);

header("Location: index.php");
exit;
?>