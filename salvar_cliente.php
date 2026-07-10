<?php
include 'verifica_login.php';
include 'conexao.php';
include 'registrar_auditoria.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$nome_empresa = trim($_POST['nome_empresa']);
$cnpj = trim($_POST['cnpj']);
$responsavel = trim($_POST['responsavel']);
$email = trim($_POST['email']);
$telefone = trim($_POST['telefone']);

$stmt = $conn->prepare(
    "INSERT INTO clientes
    (nome_empresa, cnpj, responsavel, email, telefone)
    VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssss", $nome_empresa, $cnpj, $responsavel, $email, $telefone);
$stmt->execute();

registrarAuditoria(
    $conn,
    'Cadastro de cliente',
    'Usuario cadastrou o cliente ' . $nome_empresa
);

header("Location: clientes.php");
exit;
?>
