<?php
function registrarAuditoria($conn, $tipo_acao, $descricao) {
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : NULL;
    $usuario_nome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'Não informado';

    $sql = "INSERT INTO auditoria (usuario_id, usuario_nome, tipo_acao, descricao)
            VALUES ('$usuario_id', '$usuario_nome', '$tipo_acao', '$descricao')";

    $conn->query($sql);
}
?>