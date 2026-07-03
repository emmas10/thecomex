<?php
include 'verifica_login.php';
include 'conexao.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

if (!$usuario) {
    echo "Usuário não encontrado.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $cliente_id = intval($_POST['cliente_id']);

    $stmtUpdate = $conn->prepare(
        "UPDATE usuarios
        SET nome = ?, email = ?, cliente_id = ?
        WHERE id = ?"
    );
    $stmtUpdate->bind_param("ssii", $nome, $email, $cliente_id, $id);
    $stmtUpdate->execute();

    header("Location: usuarios.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<h1>Editar Usuário</h1>

<a href="usuarios.php" class="botao-exportar">Voltar</a>

<form method="POST">
    <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?>" required>
    <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?>" required>

    <select name="cliente_id" required>
        <option value="">Selecione o cliente</option>

        <?php
        $sqlClientes = "SELECT * FROM clientes ORDER BY nome_empresa ASC";
        $resultadoClientes = $conn->query($sqlClientes);

        while ($cliente = $resultadoClientes->fetch_assoc()) {
            $selecionado = ($cliente['id'] == $usuario['cliente_id']) ? "selected" : "";
            echo "<option value='" . intval($cliente['id']) . "' $selecionado>" . htmlspecialchars($cliente['nome_empresa'], ENT_QUOTES, 'UTF-8') . "</option>";
        }
        ?>
    </select>

    <button type="submit">Salvar Alterações</button>
</form>

</div>

</body>
</html>
