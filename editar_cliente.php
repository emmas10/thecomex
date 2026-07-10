<?php
include 'verifica_login.php';
include 'conexao.php';
include 'registrar_auditoria.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : intval($_POST['id'] ?? 0);
$erro = '';

if ($id <= 0) {
    echo "Cliente invalido.";
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, nome_empresa, cnpj, responsavel, email, telefone
     FROM clientes
     WHERE id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$cliente = $resultado->fetch_assoc();

if (!$cliente) {
    echo "Cliente nao encontrado.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_empresa = trim($_POST['nome_empresa'] ?? '');
    $cnpj = trim($_POST['cnpj'] ?? '');
    $responsavel = trim($_POST['responsavel'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    if ($nome_empresa === '') {
        $erro = "Informe o nome da empresa.";
    } else {
        $stmtUpdate = $conn->prepare(
            "UPDATE clientes
             SET nome_empresa = ?,
                 cnpj = ?,
                 responsavel = ?,
                 email = ?,
                 telefone = ?
             WHERE id = ?"
        );
        $stmtUpdate->bind_param("sssssi", $nome_empresa, $cnpj, $responsavel, $email, $telefone, $id);

        if ($stmtUpdate->execute()) {
            registrarAuditoria(
                $conn,
                'Edicao de cliente',
                'Usuario editou o cliente ID ' . $id . ' (' . $nome_empresa . ')'
            );

            header("Location: clientes.php");
            exit;
        }

        $erro = "Erro ao atualizar cliente.";
    }

    $cliente['nome_empresa'] = $nome_empresa;
    $cliente['cnpj'] = $cnpj;
    $cliente['responsavel'] = $responsavel;
    $cliente['email'] = $email;
    $cliente['telefone'] = $telefone;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<h1>Editar Cliente</h1>

<a href="clientes.php" class="botao-exportar">Voltar</a>

<?php if ($erro !== '') { ?>
    <p><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php } ?>

<form method="POST">
    <input type="hidden" name="id" value="<?php echo intval($cliente['id']); ?>">
    <input type="text" name="nome_empresa" placeholder="Nome da empresa" value="<?php echo htmlspecialchars($cliente['nome_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
    <input type="text" name="cnpj" placeholder="CNPJ" value="<?php echo htmlspecialchars($cliente['cnpj'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <input type="text" name="responsavel" placeholder="Responsavel" value="<?php echo htmlspecialchars($cliente['responsavel'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <input type="email" name="email" placeholder="E-mail" value="<?php echo htmlspecialchars($cliente['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <input type="text" name="telefone" placeholder="Telefone" value="<?php echo htmlspecialchars($cliente['telefone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

    <button type="submit">Salvar Alteracoes</button>
</form>

</div>

</body>
</html>
