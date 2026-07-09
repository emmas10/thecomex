<?php
include 'verifica_login.php';
include 'conexao.php';
include 'registrar_auditoria.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$id = intval($_POST['id']);
$stmt = $conn->prepare("DELETE FROM cotacoes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

registrarAuditoria($conn, 'Exclusão de cotação', 'Usuário excluiu a cotação ID ' . $id);

header("Location: index.php");
exit;
?>
