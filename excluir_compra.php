<?php
include 'verifica_login.php';
include 'conexao.php';
include 'registrar_auditoria.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$id = intval($_POST['id']);

$stmt = $conn->prepare("UPDATE compras SET status = 'cancelada' WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

registrarAuditoria($conn, 'Cancelamento de compra', 'Usuário cancelou a compra ID ' . $id);

header("Location: index.php");
exit;
?>
