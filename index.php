<?php
include 'verifica_login.php';
include 'conexao.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>TheComex - Cotações</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<?php
if ($_SESSION['usuario_tipo'] == 'admin') {

    $totalCotacoes = $conn->query(
        "SELECT COUNT(*) as total FROM cotacoes"
    )->fetch_assoc()['total'];

    $totalProdutos = $conn->query(
        "SELECT COUNT(DISTINCT produto) as total FROM cotacoes"
    )->fetch_assoc()['total'];

    $totalCompras = $conn->query(
        "SELECT COUNT(*) as total FROM compras
         WHERE status = 'ativa'"
    )->fetch_assoc()['total'];

} else {

    $cliente_id = $_SESSION['cliente_id'];

    $totalCotacoes = $conn->query(
        "SELECT COUNT(*) as total
         FROM cotacoes
         WHERE cliente_id = '$cliente_id'"
    )->fetch_assoc()['total'];

    $totalProdutos = $conn->query(
        "SELECT COUNT(DISTINCT produto) as total
         FROM cotacoes
         WHERE cliente_id = '$cliente_id'"
    )->fetch_assoc()['total'];

    $totalCompras = $conn->query(
        "SELECT COUNT(*) as total
         FROM compras
         WHERE status = 'ativa'
         AND cliente_id = '$cliente_id'"
    )->fetch_assoc()['total'];

}
?>

<h1>TheComex - Cadastro de Cotações</h1>

<div class="dashboard">
    <div class="card">
        <h3>Total de Cotações</h3>
        <p><?php echo $totalCotacoes; ?></p>
    </div>

    <div class="card">
        <h3>Produtos Monitorados</h3>
        <p><?php echo $totalProdutos; ?></p>
    </div>

    <div class="card">
        <h3>Compras Registradas</h3>
        <p><?php echo $totalCompras; ?></p>
    </div>
</div>
<div class="menu-sistema">

    <a href="index.php">🏠 Início</a>

    <a href="ranking.php">📊 Ranking de Fornecedores</a>

    <a href="compras.php">🛒 Histórico de Compras</a>
    <?php if ($_SESSION['usuario_tipo'] == 'admin') { ?>
    <a href="auditoria.php">📋 Auditoria</a>
    <a href="usuarios.php">👥 Usuários</a>
<?php } ?>

    <?php if ($_SESSION['usuario_tipo'] == 'admin') { ?>
        <a href="clientes.php">👥 Clientes</a>
        <a href="cadastro.php">👤 Cadastrar Usuário</a>
    <?php } ?>

    <a href="logout.php">🚪 Sair</a>
<p>
    Logado como: <strong><?php echo $_SESSION['usuario_nome']; ?></strong>
</p>
</div>

<h2>Menor Preço por Produto</h2>

<table>
    <tr>
        <th>Produto</th>
        <th>Melhor Fornecedor Cotado</th>
        <th>Menor Preço Cotado</th>
        <th>Melhor Fornecedor Comprado</th>
        <th>Menor Preço Comprado</th>
    </tr>

    <?php
if ($_SESSION['usuario_tipo'] == 'admin') {

$sqlProdutos = "
    SELECT 
        produtos.produto_base,

        cot.fornecedor AS fornecedor_cotado,
        cot.preco AS menor_preco_cotado,

        comp.fornecedor AS fornecedor_comprado,
        comp.preco_pago AS menor_preco_comprado

    FROM (
        SELECT TRIM(LOWER(produto)) AS produto_base FROM cotacoes

        UNION

        SELECT TRIM(LOWER(produto)) AS produto_base 
        FROM compras
        WHERE status = 'ativa'
    ) produtos

    LEFT JOIN cotacoes cot
    ON TRIM(LOWER(cot.produto)) = produtos.produto_base
    AND cot.preco = (
        SELECT MIN(c2.preco)
        FROM cotacoes c2
        WHERE TRIM(LOWER(c2.produto)) = produtos.produto_base
    )

    LEFT JOIN compras comp
    ON TRIM(LOWER(comp.produto)) = produtos.produto_base
    AND comp.status = 'ativa'
    AND comp.preco_pago = (
        SELECT MIN(c3.preco_pago)
        FROM compras c3
        WHERE TRIM(LOWER(c3.produto)) = produtos.produto_base
        AND c3.status = 'ativa'
    )

    WHERE cot.id IS NOT NULL 
       OR comp.id IS NOT NULL

    GROUP BY produtos.produto_base
    ORDER BY produtos.produto_base ASC
";

} else {

$cliente_id = $_SESSION['cliente_id'];

$sqlProdutos = "
    SELECT 
        produtos.produto_base,

        cot.fornecedor AS fornecedor_cotado,
        cot.preco AS menor_preco_cotado,

        comp.fornecedor AS fornecedor_comprado,
        comp.preco_pago AS menor_preco_comprado

    FROM (
        SELECT TRIM(LOWER(produto)) AS produto_base 
        FROM cotacoes
        WHERE cliente_id = '$cliente_id'

        UNION

        SELECT TRIM(LOWER(produto)) AS produto_base 
        FROM compras
        WHERE status = 'ativa'
        AND cliente_id = '$cliente_id'
    ) produtos

    LEFT JOIN cotacoes cot
    ON TRIM(LOWER(cot.produto)) = produtos.produto_base
    AND cot.cliente_id = '$cliente_id'
    AND cot.preco = (
        SELECT MIN(c2.preco)
        FROM cotacoes c2
        WHERE TRIM(LOWER(c2.produto)) = produtos.produto_base
        AND c2.cliente_id = '$cliente_id'
    )

    LEFT JOIN compras comp
    ON TRIM(LOWER(comp.produto)) = produtos.produto_base
    AND comp.status = 'ativa'
    AND comp.cliente_id = '$cliente_id'
    AND comp.preco_pago = (
        SELECT MIN(c3.preco_pago)
        FROM compras c3
        WHERE TRIM(LOWER(c3.produto)) = produtos.produto_base
        AND c3.status = 'ativa'
        AND c3.cliente_id = '$cliente_id'
    )

    WHERE cot.id IS NOT NULL 
       OR comp.id IS NOT NULL

    GROUP BY produtos.produto_base
    ORDER BY produtos.produto_base ASC
";
}

$resultadoProdutos = $conn->query($sqlProdutos);

if (!$resultadoProdutos) {
    echo "<tr><td colspan='5'>Erro no SQL: " . $conn->error . "</td></tr>";
} elseif ($resultadoProdutos->num_rows == 0) {
    echo "<tr><td colspan='5'>Nenhum produto encontrado.</td></tr>";
} else {
    while ($produto = $resultadoProdutos->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . ucfirst($produto['produto_base']) . "</td>";
        echo "<td>" . ($produto['fornecedor_cotado'] ?? 'Sem cotação') . "</td>";

        if ($produto['menor_preco_cotado'] !== null) {
            echo "<td>R$ " . number_format($produto['menor_preco_cotado'], 2, ',', '.') . "</td>";
        } else {
            echo "<td>-</td>";
        }

        echo "<td>" . ($produto['fornecedor_comprado'] ?? 'Sem compra') . "</td>";

        if ($produto['menor_preco_comprado'] !== null) {
            echo "<td>R$ " . number_format($produto['menor_preco_comprado'], 2, ',', '.') . "</td>";
        } else {
            echo "<td>-</td>";
        }

        echo "</tr>";
    }
}
?>
</table>
<?php if ($_SESSION['usuario_tipo'] == 'admin') { ?>
<h2>Nova Cotação</h2>

<form action="salvar.php" method="POST">

    <select name="cliente_id" required>
    <option value="">Selecione o cliente</option>

    <?php
    $sqlClientes = "SELECT * FROM clientes ORDER BY nome_empresa ASC";
    $resultadoClientes = $conn->query($sqlClientes);

    while ($cliente = $resultadoClientes->fetch_assoc()) {
        echo "<option value='" . $cliente['id'] . "'>" . $cliente['nome_empresa'] . "</option>";
    }
    ?>
</select>
    <input type="text" name="cotacao" placeholder="Nome/Nº da Cotação" required>
    <input type="text" name="produto" placeholder="Produto" required>
    <input type="text" name="fornecedor" placeholder="Fornecedor" required>
    <input type="number" step="0.01" name="preco" placeholder="Preço R$" required>
    <input type="text" name="origem" placeholder="Origem">
    <div class="campo">
    <label>Data do Pagamento</label>
    <input type="date" name="pagamento">
</div>
    <input type="text" name="quantidade" placeholder="Quantidade">
    <div class="campo">
        <label>Data da Cotação</label>
        <input type="date" name="data_cotacao" required>
    </div>
    <button type="submit">Salvar Cotação</button>
</form>
<?php } ?>

<h2>Últimas Cotações</h2>

<form method="GET" action="index.php" class="form-busca">
    <input 
        type="text" 
        name="busca" 
        placeholder="Buscar por produto, fornecedor ou cotação"
        value="<?php echo isset($_GET['busca']) ? $_GET['busca'] : ''; ?>"
    >
    <button type="submit">Buscar</button>
</form>
<div class="tabela-scroll"></div>
<table>
    <tr>

<?php if ($_SESSION['usuario_tipo'] == 'admin') { ?>
    <th>Empresa</th>
<?php } ?>

<?php if ($_SESSION['usuario_tipo'] == 'admin') { ?>
    <th>Quem Cotou</th>
    <th>Cotação</th>
<?php } ?>
<th>Produto</th>
<th>Preço Cotado</th>
<th>Fornecedor</th>
<th>Última Compra</th>
<th>Diferença</th>
<th>Origem</th>
<th>Data do Pagamento</th>
<th>Data</th>
<th>Ação</th>
</th>

</tr>

    <?php
    $busca = isset($_GET['busca']) ? $_GET['busca'] : '';

    if ($_SESSION['usuario_tipo'] == 'admin') {

    if ($busca != '') {
        $sql = "SELECT cotacoes.*, clientes.nome_empresa, usuarios.nome AS usuario_nome 
                FROM cotacoes 
                LEFT JOIN clientes ON cotacoes.cliente_id = clientes.id
                LEFT JOIN usuarios ON cotacoes.usuario_id = usuarios.id
                WHERE cotacoes.produto LIKE '%$busca%' 
                OR cotacoes.fornecedor LIKE '%$busca%'
                OR cotacoes.cotacao LIKE '%$busca%'
                OR clientes.nome_empresa LIKE '%$busca%'
                ORDER BY cotacoes.preco ASC";
    } else {
        $sql = "SELECT cotacoes.*, clientes.nome_empresa, usuarios.nome AS usuario_nome
                FROM cotacoes 
                LEFT JOIN clientes ON cotacoes.cliente_id = clientes.id
                LEFT JOIN usuarios ON cotacoes.usuario_id = usuarios.id
                ORDER BY cotacoes.preco ASC";
    }

} else {

    $cliente_id = $_SESSION['cliente_id'];

    if ($busca != '') {

        $sql = "SELECT cotacoes.*, usuarios.nome AS usuario_nome
                FROM cotacoes
                LEFT JOIN usuarios ON cotacoes.usuario_id = usuarios.id
                WHERE cotacoes.cliente_id = '$cliente_id'
                AND (
                    cotacoes.produto LIKE '%$busca%'
                    OR cotacoes.fornecedor LIKE '%$busca%'
                    OR cotacoes.cotacao LIKE '%$busca%'
                    OR usuarios.nome LIKE '%$busca%'
                )
                ORDER BY cotacoes.preco ASC";

    } else {

        $sql = "SELECT cotacoes.*, usuarios.nome AS usuario_nome
                FROM cotacoes
                LEFT JOIN usuarios ON cotacoes.usuario_id = usuarios.id
                WHERE cotacoes.cliente_id = '$cliente_id'
                ORDER BY cotacoes.preco ASC";

    }

}

    $resultado = $conn->query($sql);

    while ($linha = $resultado->fetch_assoc()) {
        $produtoAtual = $linha['produto'];
        $dataCotacaoAtual = $linha['data_cotacao'];

if ($_SESSION['usuario_tipo'] == 'admin') {

    $sqlMenor = "SELECT MIN(preco) AS menor_preco
                 FROM cotacoes
                 WHERE produto = '$produtoAtual'";

} else {

    $cliente_id = $_SESSION['cliente_id'];

    $sqlMenor = "SELECT MIN(preco) AS menor_preco
                 FROM cotacoes
                 WHERE produto = '$produtoAtual'
                 AND cliente_id = '$cliente_id'";

}

$resultadoMenor = $conn->query($sqlMenor);
$menor = $resultadoMenor->fetch_assoc()['menor_preco'];
        if ($linha['preco'] == $menor) {
            echo "<tr style='background-color: #7CFC00; font-weight: bold;'>";
        } else {
            echo "<tr>";
        }

        if ($_SESSION['usuario_tipo'] == 'admin') {
    echo "<td>" . $linha['nome_empresa'] . "</td>";
}

if ($_SESSION['usuario_tipo'] == 'admin') {
    echo "<td>" . ($linha['usuario_nome'] ?? 'Não informado') . "</td>";
    echo "<td>" . $linha['cotacao'] . "</td>";
}
echo "<td>" . $linha['produto'] . "</td>";
echo "<td>R$ " . number_format($linha['preco'], 2, ',', '.') . "</td>";
echo "<td>" . $linha['fornecedor'] . "</td>";
"</td>";
"</td>";

       if ($_SESSION['usuario_tipo'] == 'admin') {
    $idCotacaoAtual = $linha['id'];

$sqlCompra = "SELECT *
              FROM compras
              WHERE cotacao_id = '$idCotacaoAtual'
              AND status = 'ativa'
              LIMIT 1";
} else {
    $cliente_id = $_SESSION['cliente_id'];

    $sqlCompra = "SELECT * FROM compras 
                  WHERE produto = '$produtoAtual'
                  AND status = 'ativa'
                  AND cliente_id = '$cliente_id'
                  AND data_compra <= '$dataCotacaoAtual'
                  ORDER BY data_compra DESC 
                  LIMIT 1";
}

        $resultadoCompra = $conn->query($sqlCompra);

        if ($resultadoCompra->num_rows > 0) {
            $compra = $resultadoCompra->fetch_assoc();
            $precoPago = $compra['preco_pago'];

            $valorDiferenca = $linha['preco'] - $precoPago;
            $diferenca = ($valorDiferenca / $precoPago) * 100;

            echo "<td>R$ " . number_format($precoPago, 2, ',', '.') . "</td>";

            if ($valorDiferenca < 0) {
                echo "<td style='color:green;font-weight:bold;'>
                         Economia<br>
                        " . number_format(abs($diferenca), 2, ',', '.') . "%<br>
                        R$ " . number_format(abs($valorDiferenca), 2, ',', '.') . "
                      </td>";
            } elseif ($valorDiferenca > 0) {
                echo "<td style='color:red;font-weight:bold;'>
                         Aumento<br>
                        " . number_format($diferenca, 2, ',', '.') . "%<br>
                        R$ " . number_format($valorDiferenca, 2, ',', '.') . "
                      </td>";
            } else {
                echo "<td style='font-weight:bold;'>➖ Mesmo preço</td>";
            }
        } else {
            echo "<td>Sem histórico</td>";
            echo "<td>-</td>";
        }
        echo "<td>" . $linha['origem'] . "</td>";
echo "<td>" . $linha['pagamento'] . "</td>";
echo "<td>" . $linha['data_cotacao'] . "</td>";

echo "<td>";
        echo "<td>";

echo "<td>";

if ($_SESSION['usuario_tipo'] == 'admin') {

    $idCotacao = $linha['id'];


    $sqlComprada = "SELECT * FROM compras WHERE cotacao_id = $idCotacao AND status = 'ativa' LIMIT 1";
    $resultadoComprada = $conn->query($sqlComprada);

    if ($resultadoComprada->num_rows > 0) {
        $compra = $resultadoComprada->fetch_assoc();

        echo "<strong style='color:green;'>Já comprada</strong><br>";

        echo "<form action='excluir_compra.php' method='POST' style='display:inline;'>";
        echo "<input type='hidden' name='id' value='" . $compra['id'] . "'>";
        echo "<button type='submit' onclick=\"return confirm('Deseja cancelar esta compra?')\">Excluir Compra</button>";
        echo "</form>";

    } else {
        echo "<form action='comprar_cotacao.php' method='POST' style='display:inline;'>";
        echo "<input type='hidden' name='id' value='" . $linha['id'] . "'>";
        echo "<button type='submit' onclick='this.disabled=true; this.form.submit();'>Comprar Cotação</button>";
        echo "</form>";
    }

    echo " ";

    echo "<form action='excluir_cotacao.php' method='POST' style='display:inline;'>";
    echo "<input type='hidden' name='id' value='" . $linha['id'] . "'>";
    echo "<button type='submit' onclick=\"return confirm('Deseja excluir esta cotação?')\">Excluir Cotação</button>";
    echo "</form>";

} else {
    echo "Somente visualização";
}

echo "</td>";

echo "</tr>";
    }
    ?>
</table>
</div>
</div>

</body>
</html>