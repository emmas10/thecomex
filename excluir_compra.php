<?php
include 'verifica_login.php';
include 'conexao.php';
include 'registrar_auditoria.php';


if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$id = $_POST['id'];

$sql = "UPDATE compras SET status = 'cancelada' WHERE id = $id";
$conn->query($sql);
registrarAuditoria($conn, 'Exclusão de cotação', 'Usuário excluiu a cotação ID ' . $id);

header("Location: index.php");
exit;
?>