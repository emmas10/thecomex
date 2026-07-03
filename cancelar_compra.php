<?php
include 'verifica_login.php';
include 'conexao.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$id = intval($_POST['id']);

$stmt = $conn->prepare("UPDATE compras SET status = 'cancelada' WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: compras.php");
exit;
?>
