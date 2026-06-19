<?php
include 'verifica_login.php';
include 'conexao.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    echo "Acesso negado.";
    exit;
}

$id = $_GET['id'];

$sql = "SELECT * FROM usuarios WHERE id = $id";
$resultado = $conn->query($sql);
$usuario = $resultado->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $cliente_id = $_POST['cliente_id'];

    $sqlUpdate = "UPDATE usuarios 
                  SET nome = '$nome',
                      email = '$email',
                      cliente_id = '$cliente_id'
                  WHERE id = $id";

    $conn->query($sqlUpdate);

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
    <input type="text" name="nome" value="<?php echo $usuario['nome']; ?>" required>
    <input type="email" name="email" value="<?php echo $usuario['email']; ?>" required>

    <select name="cliente_id" required>
        <option value="">Selecione o cliente</option>

        <?php
        $sqlClientes = "SELECT * FROM clientes ORDER BY nome_empresa ASC";
        $resultadoClientes = $conn->query($sqlClientes);

        while ($cliente = $resultadoClientes->fetch_assoc()) {
            $selecionado = ($cliente['id'] == $usuario['cliente_id']) ? "selected" : "";
            echo "<option value='" . $cliente['id'] . "' $selecionado>" . $cliente['nome_empresa'] . "</option>";
        }
        ?>
    </select>

    <button type="submit">Salvar Alterações</button>
</form>

</div>

</body>
</html>