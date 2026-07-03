<?php
function registrarAuditoria($conn, $tipo_acao, $descricao) {
    $usuario_id = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : null;
    $usuario_nome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'Não informado';

    $stmt = $conn->prepare(
        "INSERT INTO auditoria (usuario_id, usuario_nome, tipo_acao, descricao)
        VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("isss", $usuario_id, $usuario_nome, $tipo_acao, $descricao);
    $stmt->execute();
}
?>
