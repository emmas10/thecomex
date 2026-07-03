<?php
include 'verifica_login.php';
include 'conexao.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "Cotação inválida.";
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, produto, quantidade, cliente_id
     FROM cotacoes
     WHERE id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if (!$resultado || $resultado->num_rows === 0) {
    echo "Cotação não encontrada.";
    exit;
}

$cotacao = $resultado->fetch_assoc();
$usuarioTipo = $_SESSION['usuario_tipo'] ?? '';
$clienteSessao = isset($_SESSION['cliente_id']) ? intval($_SESSION['cliente_id']) : 0;
$clienteCotacao = isset($cotacao['cliente_id']) ? intval($cotacao['cliente_id']) : 0;

if ($usuarioTipo !== 'admin') {
    if ($clienteSessao <= 0 || $clienteCotacao !== $clienteSessao) {
        echo "Acesso negado.";
        exit;
    }
}

$stmtComprada = $conn->prepare(
    "SELECT id
     FROM compras
     WHERE cotacao_id = ?
     AND status = 'ativa'
     LIMIT 1"
);
$stmtComprada->bind_param("i", $id);
$stmtComprada->execute();
$resultadoComprada = $stmtComprada->get_result();

if ($resultadoComprada && $resultadoComprada->num_rows > 0) {
    echo "Esta cotação já foi comprada.";
    exit;
}

$assunto = "Pedido de Compra";
$corpo = "Bom dia!\n\n"
    . "Agradeço o envio da cotação, vamos seguir com a compra.\n\n"
    . "Item:\n"
    . "\n\n"
    . "Quantidade:\n"
    . "\n\n"
    . "Entrega e faturamento:\n\n"
    . "Número da OC:\n\n"
    . "Muito obrigado.";

$gmailUrl = "https://mail.google.com/mail/?view=cm&fs=1"
    . "&su=" . rawurlencode($assunto)
    . "&body=" . rawurlencode($corpo);

header("Location: " . $gmailUrl);
exit;
?>
