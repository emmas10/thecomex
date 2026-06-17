<?php
include 'verifica_login.php';
include 'conexao.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$nome_empresa = $_POST['nome_empresa'];
$cnpj = $_POST['cnpj'];
$responsavel = $_POST['responsavel'];
$email = $_POST['email'];
$telefone = $_POST['telefone'];

$sql = "INSERT INTO clientes
(nome_empresa, cnpj, responsavel, email, telefone)
VALUES
('$nome_empresa', '$cnpj', '$responsavel', '$email', '$telefone')";

$conn->query($sql);

header("Location: clientes.php");
exit;
?>