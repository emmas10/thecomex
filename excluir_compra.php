<?php
include 'verifica_login.php';
include 'conexao.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$id = $_POST['id'];

$sql = "UPDATE compras SET status = 'cancelada' WHERE id = $id";
$conn->query($sql);

header("Location: index.php");
exit;
?>