<?php
include 'verifica_login.php';
include 'conexao.php';
include 'registrar_auditoria.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Requisicao invalida.";
    exit;
}

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo "Cliente invalido.";
    exit;
}

$stmtCliente = $conn->prepare(
    "SELECT id, nome_empresa
     FROM clientes
     WHERE id = ?
     LIMIT 1"
);
$stmtCliente->bind_param("i", $id);
$stmtCliente->execute();
$resultadoCliente = $stmtCliente->get_result();
$cliente = $resultadoCliente->fetch_assoc();

if (!$cliente) {
    echo "Cliente nao encontrado.";
    exit;
}

$stmtVinculos = $conn->prepare(
    "SELECT
        (SELECT COUNT(*) FROM usuarios WHERE cliente_id = ?) AS total_usuarios,
        (SELECT COUNT(*) FROM cotacoes WHERE cliente_id = ?) AS total_cotacoes,
        (SELECT COUNT(*) FROM compras WHERE cliente_id = ?) AS total_compras"
);
$stmtVinculos->bind_param("iii", $id, $id, $id);
$stmtVinculos->execute();
$vinculos = $stmtVinculos->get_result()->fetch_assoc();

$possuiVinculos = (
    intval($vinculos['total_usuarios']) > 0
    || intval($vinculos['total_cotacoes']) > 0
    || intval($vinculos['total_compras']) > 0
);

if ($possuiVinculos) {
    $stmt = $conn->prepare("UPDATE clientes SET ativo = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        registrarAuditoria(
            $conn,
            'Desativacao de cliente',
            'Usuario desativou o cliente ID ' . $id . ' (' . $cliente['nome_empresa'] . ') por possuir vinculos'
        );

        header("Location: clientes.php");
        exit;
    }

    echo "Erro ao desativar cliente.";
    exit;
}

$stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    registrarAuditoria(
        $conn,
        'Exclusao de cliente',
        'Usuario excluiu fisicamente o cliente ID ' . $id . ' (' . $cliente['nome_empresa'] . ') sem vinculos'
    );

    header("Location: clientes.php");
    exit;
}

echo "Erro ao excluir cliente.";
exit;
?>
