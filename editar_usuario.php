<?php
include 'verifica_login.php';
include 'conexao.php';
include 'registrar_auditoria.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$erro = '';

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

if (!$usuario) {
    echo "Usuario nao encontrado.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $cliente_id = ($_POST['cliente_id'] !== '') ? intval($_POST['cliente_id']) : null;
    $tipo = $_POST['tipo'] ?? '';
    $tiposPermitidos = ['admin', 'visualizacao'];

    if (!in_array($tipo, $tiposPermitidos, true)) {
        $erro = "Tipo de usuario invalido.";
    }

    if ($erro === '' && $cliente_id !== null) {
        $stmtCliente = $conn->prepare("SELECT id FROM clientes WHERE id = ? AND ativo = 1 LIMIT 1");
        $stmtCliente->bind_param("i", $cliente_id);
        $stmtCliente->execute();

        if (!$stmtCliente->get_result()->fetch_assoc()) {
            $erro = "Cliente invalido ou desativado.";
        }
    }

    if ($erro === '') {
        $removendoProprioAdmin = (
            intval($_SESSION['usuario_id']) === $id
            && $usuario['tipo'] === 'admin'
            && $tipo !== 'admin'
        );

        if ($removendoProprioAdmin) {
            $tipoAdmin = 'admin';
            $stmtAdmins = $conn->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE tipo = ?");
            $stmtAdmins->bind_param("s", $tipoAdmin);
            $stmtAdmins->execute();
            $totalAdmins = intval($stmtAdmins->get_result()->fetch_assoc()['total']);

            if ($totalAdmins <= 1) {
                $erro = "Nao e permitido remover seu proprio acesso admin porque voce e o unico administrador do sistema.";
            }
        }
    }

    if ($erro === '') {
        $tipoAnterior = $usuario['tipo'];

        $stmtUpdate = $conn->prepare(
            "UPDATE usuarios
            SET nome = ?, email = ?, cliente_id = ?, tipo = ?
            WHERE id = ?"
        );
        $stmtUpdate->bind_param("ssisi", $nome, $email, $cliente_id, $tipo, $id);
        $stmtUpdate->execute();

        if ($tipoAnterior !== $tipo) {
            registrarAuditoria(
                $conn,
                'Alteracao de tipo de usuario',
                'Usuario ID ' . $id . ' teve o tipo alterado de ' . $tipoAnterior . ' para ' . $tipo
            );

            if (intval($_SESSION['usuario_id']) === $id) {
                $_SESSION['usuario_tipo'] = $tipo;
            }
        }

        header("Location: usuarios.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<h1>Editar Usuario</h1>

<a href="usuarios.php" class="botao-exportar">Voltar</a>

<?php if ($erro !== '') { ?>
    <p><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php } ?>

<form method="POST">
    <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?>" required>
    <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?>" required>

    <select name="tipo" required>
        <option value="admin" <?php echo ($usuario['tipo'] === 'admin') ? 'selected' : ''; ?>>admin</option>
        <option value="visualizacao" <?php echo ($usuario['tipo'] === 'visualizacao') ? 'selected' : ''; ?>>visualizacao</option>
    </select>

    <select name="cliente_id">
        <option value="">Sem empresa</option>

        <?php
        $sqlClientes = "SELECT id, nome_empresa FROM clientes WHERE ativo = 1 ORDER BY nome_empresa ASC";
        $resultadoClientes = $conn->query($sqlClientes);

        while ($cliente = $resultadoClientes->fetch_assoc()) {
            $selecionado = ($cliente['id'] == $usuario['cliente_id']) ? "selected" : "";
            echo "<option value='" . intval($cliente['id']) . "' $selecionado>" . htmlspecialchars($cliente['nome_empresa'], ENT_QUOTES, 'UTF-8') . "</option>";
        }
        ?>
    </select>

    <button type="submit">Salvar Alteracoes</button>
</form>

</div>

</body>
</html>
