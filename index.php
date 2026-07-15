<?php
include 'verifica_login.php';
include 'conexao.php';
include 'helpers_preco.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Cotações - Latin America Chemicals</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<?php
if ($_SESSION['usuario_tipo'] == 'admin') {

    $grupoCotacaoSql = expressaoProdutoGrupoCotacaoSql('c');
    $grupoCompraSql = colunaProdutoBaseComprasExiste($conn)
        ? expressaoProdutoGrupoCompraSql('cp', 'c_compra')
        : "COALESCE(NULLIF(TRIM(c_compra.produto_base), ''), TRIM(LOWER(cp.produto)))";

    $totalCotacoes = $conn->query(
        "SELECT COUNT(*) as total FROM cotacoes"
    )->fetch_assoc()['total'];

    $totalProdutos = $conn->query(
        "SELECT COUNT(DISTINCT produto_base) AS total
         FROM (
            SELECT {$grupoCotacaoSql} AS produto_base FROM cotacoes c
            UNION
            SELECT {$grupoCompraSql} AS produto_base
            FROM compras cp
            LEFT JOIN cotacoes c_compra ON c_compra.id = cp.cotacao_id
            WHERE cp.status = 'ativa'
         ) produtos"
    )->fetch_assoc()['total'];

    $totalCompras = $conn->query(
        "SELECT COUNT(*) as total FROM compras
         WHERE status = 'ativa'"
    )->fetch_assoc()['total'];

} else {

    $cliente_id = intval($_SESSION['cliente_id']);
    $grupoCotacaoSql = expressaoProdutoGrupoCotacaoSql('c');
    $grupoCompraSql = colunaProdutoBaseComprasExiste($conn)
        ? expressaoProdutoGrupoCompraSql('cp', 'c_compra')
        : "COALESCE(NULLIF(TRIM(c_compra.produto_base), ''), TRIM(LOWER(cp.produto)))";

    $totalCotacoes = $conn->query(
        "SELECT COUNT(*) as total
         FROM cotacoes
         WHERE cliente_id = '$cliente_id'"
    )->fetch_assoc()['total'];

    $totalProdutos = $conn->query(
        "SELECT COUNT(DISTINCT produto_base) AS total
         FROM (
            SELECT {$grupoCotacaoSql} AS produto_base
            FROM cotacoes c WHERE c.cliente_id = {$cliente_id}
            UNION
            SELECT {$grupoCompraSql} AS produto_base
            FROM compras cp
            LEFT JOIN cotacoes c_compra ON c_compra.id = cp.cotacao_id
            WHERE cp.status = 'ativa' AND cp.cliente_id = {$cliente_id}
         ) produtos"
    )->fetch_assoc()['total'];

    $totalCompras = $conn->query(
        "SELECT COUNT(*) as total
         FROM compras
         WHERE status = 'ativa'
         AND cliente_id = '$cliente_id'"
    )->fetch_assoc()['total'];

}
?>

<h1> Latin America Chemicals - Cadastro de Cotações</h1>

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

    <a href="index.php"> Início</a>


    <a href="compras.php"> Histórico de Compras</a>
    <?php if ($_SESSION['usuario_tipo'] == 'admin') { ?>
    <a href="auditoria.php"> Auditoria</a>
    <a href="usuarios.php"> Usuários</a>
<?php } ?>

    <?php if ($_SESSION['usuario_tipo'] == 'admin') { ?>
        <a href="clientes.php"> Clientes</a>
        <a href="produtos.php"> Produtos</a>
        <a href="cadastro.php"> Cadastrar Usuário</a>
    <?php } ?>

    <a href="relatorios.php"> Relatórios PDF</a>

    <a href="logout.php"> Sair</a>
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
$grupoCotacaoDashboard = expressaoProdutoGrupoCotacaoSql('c');
$grupoCompraDashboard = colunaProdutoBaseComprasExiste($conn)
    ? expressaoProdutoGrupoCompraSql('cp', 'c_compra')
    : "COALESCE(NULLIF(TRIM(c_compra.produto_base), ''), TRIM(LOWER(cp.produto)))";
$filtroCotacaoDashboard = '';
$filtroCompraDashboard = "WHERE cp.status = 'ativa'";

if ($_SESSION['usuario_tipo'] != 'admin') {
    $cliente_id = intval($_SESSION['cliente_id']);
    $filtroCotacaoDashboard = "WHERE c.cliente_id = {$cliente_id}";
    $filtroCompraDashboard = "WHERE cp.status = 'ativa' AND cp.cliente_id = {$cliente_id}";
}

$sqlProdutos = "
    WITH cotacoes_ordenadas AS (
        SELECT
            {$grupoCotacaoDashboard} AS produto_base,
            c.produto,
            c.fornecedor,
            c.preco,
            c.preco_casas_decimais,
            ROW_NUMBER() OVER (
                PARTITION BY {$grupoCotacaoDashboard}
                ORDER BY c.preco ASC, c.id ASC
            ) AS posicao
        FROM cotacoes c
        {$filtroCotacaoDashboard}
    ),
    compras_ordenadas AS (
        SELECT
            {$grupoCompraDashboard} AS produto_base,
            cp.produto,
            cp.fornecedor,
            cp.preco_pago,
            cp.preco_pago_casas_decimais,
            ROW_NUMBER() OVER (
                PARTITION BY {$grupoCompraDashboard}
                ORDER BY cp.preco_pago ASC, cp.id ASC
            ) AS posicao
        FROM compras cp
        LEFT JOIN cotacoes c_compra ON c_compra.id = cp.cotacao_id
        {$filtroCompraDashboard}
    ),
    produtos AS (
        SELECT produto_base FROM cotacoes_ordenadas
        UNION
        SELECT produto_base FROM compras_ordenadas
    )
    SELECT
        produtos.produto_base,
        COALESCE(cot.produto, comp.produto, produtos.produto_base) AS produto_nome,
        cot.fornecedor AS fornecedor_cotado,
        cot.preco AS menor_preco_cotado,
        cot.preco_casas_decimais AS menor_preco_cotado_casas,
        comp.fornecedor AS fornecedor_comprado,
        comp.preco_pago AS menor_preco_comprado,
        comp.preco_pago_casas_decimais AS menor_preco_comprado_casas
    FROM produtos
    LEFT JOIN cotacoes_ordenadas cot
        ON cot.produto_base = produtos.produto_base AND cot.posicao = 1
    LEFT JOIN compras_ordenadas comp
        ON comp.produto_base = produtos.produto_base AND comp.posicao = 1
    WHERE produtos.produto_base IS NOT NULL AND produtos.produto_base <> ''
    ORDER BY produto_nome ASC
";

$resultadoProdutos = $conn->query($sqlProdutos);

if (!$resultadoProdutos) {
    echo "<tr><td colspan='5'>Erro no SQL: " . $conn->error . "</td></tr>";
} elseif ($resultadoProdutos->num_rows == 0) {
    echo "<tr><td colspan='5'>Nenhum produto encontrado.</td></tr>";
} else {
    while ($produto = $resultadoProdutos->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($produto['produto_nome'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td>" . ($produto['fornecedor_cotado'] ?? 'Sem cotação') . "</td>";

        if ($produto['menor_preco_cotado'] !== null) {
            echo "<td>" . formatarMoeda($produto['menor_preco_cotado'], $produto['menor_preco_cotado_casas'] ?? null) . "</td>";
        } else {
            echo "<td>-</td>";
        }

        echo "<td>" . ($produto['fornecedor_comprado'] ?? 'Sem compra') . "</td>";

        if ($produto['menor_preco_comprado'] !== null) {
            echo "<td>" . formatarMoeda($produto['menor_preco_comprado'], $produto['menor_preco_comprado_casas'] ?? null) . "</td>";
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
    $sqlClientes = "SELECT id, nome_empresa FROM clientes WHERE ativo = 1 ORDER BY nome_empresa ASC";
    $resultadoClientes = $conn->query($sqlClientes);

    while ($cliente = $resultadoClientes->fetch_assoc()) {
        echo "<option value='" . intval($cliente['id']) . "'>" . htmlspecialchars($cliente['nome_empresa'], ENT_QUOTES, 'UTF-8') . "</option>";
    }
    ?>
</select>
    <input type="text" name="cotacao" placeholder="Nome/Nº da Cotação" required>
    <input type="text" name="produto" placeholder="Produto" required>
    <input type="text" name="produto_base" placeholder="Produto base/padronizado">
    <input type="text" name="fornecedor" placeholder="Fornecedor" required>
    <input type="number" step="0.000001" min="0" name="preco" placeholder="Preço US$" required>
    <input type="text" name="origem" placeholder="Origem">
    <div class="campo">
    <label>Data do Pagamento</label>
    <input type="text" name="pagamento">
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
        $produtoGrupoAtual = obterProdutoGrupoCotacao($linha);
        $dataCotacaoAtual = $linha['data_cotacao'];

$grupoCotacaoMenor = expressaoProdutoGrupoCotacaoSql();

if ($_SESSION['usuario_tipo'] == 'admin') {

    $stmtMenor = $conn->prepare(
        "SELECT MIN(preco) AS menor_preco
         FROM cotacoes
         WHERE {$grupoCotacaoMenor} = ?"
    );
    $stmtMenor->bind_param("s", $produtoGrupoAtual);

} else {

    $cliente_id = intval($_SESSION['cliente_id']);

    $stmtMenor = $conn->prepare(
        "SELECT MIN(preco) AS menor_preco
         FROM cotacoes
         WHERE {$grupoCotacaoMenor} = ?
         AND cliente_id = ?"
    );
    $stmtMenor->bind_param("si", $produtoGrupoAtual, $cliente_id);

}

$stmtMenor->execute();
$resultadoMenor = $stmtMenor->get_result();
$menor = $resultadoMenor->fetch_assoc()['menor_preco'];
        if ($linha['preco'] == $menor) {
            echo "<tr style='background-color: #75b337; font-weight: bold;'>";
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
echo "<td>" . formatarMoeda($linha['preco'], $linha['preco_casas_decimais'] ?? null) . "</td>";
echo "<td>" . $linha['fornecedor'] . "</td>";


      $clienteCotacao = intval($linha['cliente_id']);
      $idCotacaoAtual = intval($linha['id']);

$grupoCompraAnterior = colunaProdutoBaseComprasExiste($conn)
    ? expressaoProdutoGrupoCompraSql('compras', 'cotacao_compra')
    : "COALESCE(NULLIF(TRIM(cotacao_compra.produto_base), ''), TRIM(LOWER(compras.produto)))";
$grupoCotacaoCompraAnterior = expressaoProdutoGrupoCotacaoSql('cotacao_compra');

$stmtCompra = $conn->prepare(
    "SELECT compras.*
     FROM compras
     LEFT JOIN cotacoes cotacao_compra
     ON compras.cotacao_id = cotacao_compra.id
     WHERE {$grupoCompraAnterior} = ?
     AND compras.status = 'ativa'
     AND compras.cliente_id = ?
     AND (compras.cotacao_id IS NULL OR compras.cotacao_id <> ?)
     AND (
        (
            compras.cotacao_id IS NOT NULL
            AND cotacao_compra.cliente_id = ?
            AND {$grupoCotacaoCompraAnterior} = ?
            AND (
                cotacao_compra.data_cotacao < ?
                OR (
                    cotacao_compra.data_cotacao = ?
                    AND cotacao_compra.id < ?
                )
            )
        )
        OR (
            compras.cotacao_id IS NULL
            AND compras.data_compra <= ?
        )
     )
     ORDER BY
        COALESCE(cotacao_compra.data_cotacao, compras.data_compra) DESC,
        COALESCE(cotacao_compra.id, 0) DESC,
        compras.data_compra DESC,
        compras.id DESC
     LIMIT 1"
);
$stmtCompra->bind_param(
    "siiisssis",
    $produtoGrupoAtual,
    $clienteCotacao,
    $idCotacaoAtual,
    $clienteCotacao,
    $produtoGrupoAtual,
    $dataCotacaoAtual,
    $dataCotacaoAtual,
    $idCotacaoAtual,
    $dataCotacaoAtual
);
$stmtCompra->execute();
$resultadoCompra = $stmtCompra->get_result();

if ($resultadoCompra && $resultadoCompra->num_rows > 0) {
    $compra = $resultadoCompra->fetch_assoc();
    $precoPago = floatval($compra['preco_pago']);
    $precoAtual = floatval($linha['preco']);

    $valorDiferenca = $precoAtual - $precoPago;

    echo "<td>" . formatarMoeda($precoPago, $compra['preco_pago_casas_decimais'] ?? null) . "</td>";

    if ($valorDiferenca < 0) {
        $diferenca = ($precoAtual != 0) ? ($valorDiferenca / $precoAtual) * 100 : 0;
        echo "<td style='color:green;font-weight:bold;'>Economia<br>" . formatarNumeroDecimal(abs($diferenca)) . "%<br>" . formatarMoeda(abs($valorDiferenca)) . "</td>";
    } elseif ($valorDiferenca > 0) {
        $diferenca = ($precoPago != 0) ? ($valorDiferenca / $precoPago) * 100 : 0;
        echo "<td style='color:red;font-weight:bold;'>Aumento<br>" . formatarNumeroDecimal($diferenca) . "%<br>" . formatarMoeda($valorDiferenca) . "</td>";
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

$idCotacao = intval($linha['id']);
$stmtComprada = $conn->prepare(
    "SELECT id
     FROM compras
     WHERE cotacao_id = ?
     AND status = 'ativa'
     LIMIT 1"
);
$stmtComprada->bind_param("i", $idCotacao);
$stmtComprada->execute();
$resultadoComprada = $stmtComprada->get_result();
$cotacaoComprada = ($resultadoComprada && $resultadoComprada->num_rows > 0);

if ($_SESSION['usuario_tipo'] == 'admin') {

    echo "<a href='editar_cotacao.php?id=" . htmlspecialchars((string) $idCotacao, ENT_QUOTES, 'UTF-8') . "'>";
    echo "<button type='button'>Editar Cota&ccedil;&atilde;o</button>";
    echo "</a>";

    if ($cotacaoComprada) {
        $compra = $resultadoComprada->fetch_assoc();

        echo "<strong style='color:green;'>Já comprada</strong><br>";

        echo "<form action='excluir_compra.php' method='POST'>";
        echo "<input type='hidden' name='id' value='" . $compra['id'] . "'>";
        echo "<button type='submit'>Excluir Compra</button>";
        echo "</form>";

    } else {

        echo "<a href='gerar_email_pedido.php?id=" . htmlspecialchars((string) $idCotacao, ENT_QUOTES, 'UTF-8') . "' target='_blank'>";
        echo "<button type='button'> Gerar Pedido por E-mail</button>";
        echo "</a>";

        echo "<form action='comprar_cotacao.php' method='POST'>";
        echo "<input type='hidden' name='id' value='" . htmlspecialchars((string) $idCotacao, ENT_QUOTES, 'UTF-8') . "'>";
        echo "<button type='submit'>Comprar Cotação</button>";
        echo "</form>";
    }

    echo "<form action='excluir_cotacao.php' method='POST'>";
    echo "<input type='hidden' name='id' value='" . htmlspecialchars((string) $idCotacao, ENT_QUOTES, 'UTF-8') . "'>";
    echo "<button type='submit'>Excluir Cotação</button>";
    echo "</form>";

} else {

    if (!$cotacaoComprada) {
        echo "<a href='gerar_email_pedido.php?id=" . htmlspecialchars((string) $idCotacao, ENT_QUOTES, 'UTF-8') . "' target='_blank'>";
        echo "<button type='button'> Gerar Pedido por E-mail</button>";
        echo "</a>";
    } else {
        echo "Cotação já comprada";
    }

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
