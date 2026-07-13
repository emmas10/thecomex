<?php
include 'verifica_login.php';
include 'conexao.php';
include 'helpers_preco.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ranking de Fornecedores - TheComex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <h1>Ranking de Fornecedores por Produto</h1>

    <a href="index.php" class="botao-exportar">Voltar para Cotações</a>

    <form method="GET" action="ranking.php" class="form-busca">
        <input 
            type="text" 
            name="produto" 
            placeholder="Digite o produto. Ex: Madeira, Ácido Fosfórico..."
            value="<?php echo isset($_GET['produto']) ? $_GET['produto'] : ''; ?>"
        >
        <button type="submit">Analisar</button>
    </form>

    <?php
    $produtoBusca = isset($_GET['produto']) ? trim($_GET['produto']) : '';

    if ($produtoBusca != '') {

        $produtoGrupoBusca = normalizarProdutoBase($produtoBusca);

        echo "<h2>Produto analisado: " . $produtoBusca . "</h2>";

        if ($_SESSION['usuario_tipo'] == 'admin') {
            $filtroClienteCotacoes = "";
            $filtroClienteCompras = "";
        } else {
            $cliente_id = intval($_SESSION['cliente_id']);
            $filtroClienteCotacoes = "AND c.cliente_id = ?";
            $filtroClienteCompras = "AND cp.cliente_id = ?";
        }

        $grupoCotacaoRanking = expressaoProdutoGrupoCotacaoSql('c');
        $grupoCompraRanking = colunaProdutoBaseComprasExiste($conn)
            ? expressaoProdutoGrupoCompraSql('cp', 'c_compra')
            : "COALESCE(NULLIF(TRIM(c_compra.produto_base), ''), TRIM(LOWER(cp.produto)))";

        $sqlRanking = "
            SELECT 
                fornecedor,
                MIN(menor_preco_cotado) AS menor_preco_cotado,
                SUBSTRING_INDEX(GROUP_CONCAT(preco_cotado_casas ORDER BY menor_preco_cotado ASC SEPARATOR ','), ',', 1) AS menor_preco_cotado_casas,
                MIN(menor_preco_comprado) AS menor_preco_comprado,
                SUBSTRING_INDEX(GROUP_CONCAT(preco_comprado_casas ORDER BY menor_preco_comprado ASC SEPARATOR ','), ',', 1) AS menor_preco_comprado_casas,
                SUM(qtd_cotacoes) AS qtd_cotacoes,
                SUM(qtd_compras) AS qtd_compras
            FROM (
                SELECT 
                    fornecedor,
                    MIN(preco) AS menor_preco_cotado,
                    SUBSTRING_INDEX(GROUP_CONCAT(preco_casas_decimais ORDER BY preco ASC SEPARATOR ','), ',', 1) AS preco_cotado_casas,
                    NULL AS menor_preco_comprado,
                    NULL AS preco_comprado_casas,
                    COUNT(*) AS qtd_cotacoes,
                    0 AS qtd_compras
                FROM cotacoes c
                WHERE {$grupoCotacaoRanking} = ?
                $filtroClienteCotacoes
                GROUP BY fornecedor

                UNION ALL

                SELECT 
                    fornecedor,
                    NULL AS menor_preco_cotado,
                    NULL AS preco_cotado_casas,
                    MIN(preco_pago) AS menor_preco_comprado,
                    SUBSTRING_INDEX(GROUP_CONCAT(preco_pago_casas_decimais ORDER BY preco_pago ASC SEPARATOR ','), ',', 1) AS preco_comprado_casas,
                    0 AS qtd_cotacoes,
                    COUNT(*) AS qtd_compras
                FROM compras cp
                LEFT JOIN cotacoes c_compra ON c_compra.id = cp.cotacao_id
                WHERE {$grupoCompraRanking} = ?
                AND cp.status = 'ativa'
                $filtroClienteCompras
                GROUP BY fornecedor
            ) base
            GROUP BY fornecedor
            ORDER BY COALESCE(menor_preco_comprado, menor_preco_cotado) ASC
        ";

        $stmtRanking = $conn->prepare($sqlRanking);

        if ($_SESSION['usuario_tipo'] == 'admin') {
            $stmtRanking->bind_param("ss", $produtoGrupoBusca, $produtoGrupoBusca);
        } else {
            $stmtRanking->bind_param("sisi", $produtoGrupoBusca, $cliente_id, $produtoGrupoBusca, $cliente_id);
        }

        $stmtRanking->execute();
        $resultadoRanking = $stmtRanking->get_result();

        if ($resultadoRanking->num_rows > 0) {
            echo "<table>";
            echo "<tr>";
            echo "<th>Fornecedor</th>";
            echo "<th>Menor Preço Cotado</th>";
            echo "<th>Menor Preço Comprado</th>";
            echo "<th>Qtd. Cotações</th>";
            echo "<th>Qtd. Compras</th>";
            echo "</tr>";

            while ($linha = $resultadoRanking->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $linha['fornecedor'] . "</td>";

                if ($linha['menor_preco_cotado'] !== null) {
                    echo "<td>" . formatarMoeda($linha['menor_preco_cotado'], $linha['menor_preco_cotado_casas'] ?? null) . "</td>";
                } else {
                    echo "<td>-</td>";
                }

                if ($linha['menor_preco_comprado'] !== null) {
                    echo "<td>" . formatarMoeda($linha['menor_preco_comprado'], $linha['menor_preco_comprado_casas'] ?? null) . "</td>";
                } else {
                    echo "<td>-</td>";
                }

                echo "<td>" . $linha['qtd_cotacoes'] . "</td>";
                echo "<td>" . $linha['qtd_compras'] . "</td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p>Nenhum dado encontrado para esse produto.</p>";
        }

    } else {
        echo "<p>Digite um produto para ver o ranking de fornecedores.</p>";
    }
    ?>

</div>

</body>
</html>
