-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 23/06/2026 às 22:09
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `thecomex`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditoria`
--

CREATE TABLE `auditoria` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `usuario_nome` varchar(100) DEFAULT NULL,
  `tipo_acao` varchar(100) NOT NULL,
  `descricao` text NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `auditoria`
--

INSERT INTO `auditoria` (`id`, `usuario_id`, `usuario_nome`, `tipo_acao`, `descricao`, `criado_em`) VALUES
(1, 1, 'Administrador', 'Exclusão de cotação', 'Usuário excluiu a cotação ID 30', '2026-06-19 17:46:46'),
(2, 1, 'Administrador', 'Compra de cotação', 'Usuário comprou a cotação ID 13', '2026-06-19 18:18:25'),
(3, 1, 'Administrador', 'Exclusão de cotação', 'Usuário excluiu a cotação ID 30', '2026-06-19 18:18:29'),
(4, 1, 'Administrador', 'Compra de cotação', 'Usuário comprou a cotação ID 13', '2026-06-19 18:19:43'),
(5, 1, 'Administrador', 'Exclusão de cotação', 'Usuário excluiu a cotação ID 30', '2026-06-19 18:35:15'),
(6, 1, 'Administrador', 'Compra de cotação', 'Usuário comprou a cotação ID 14', '2026-06-19 19:26:29'),
(7, 1, 'Administrador', 'Exclusão de cotação', 'Usuário excluiu a cotação ID 34', '2026-06-19 19:30:25'),
(8, 1, 'Administrador', 'Compra de cotação', 'Usuário comprou a cotação ID 15', '2026-06-23 18:08:03'),
(9, 1, 'Administrador', 'Exclusão de cotação', 'Usuário excluiu a cotação ID 35', '2026-06-23 18:08:11'),
(10, 1, 'Administrador', 'Compra de cotação', 'Usuário comprou a cotação ID 15', '2026-06-23 18:25:45'),
(11, 1, 'Administrador', 'Exclusão de cotação', 'Usuário excluiu a cotação ID 35', '2026-06-23 18:25:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome_empresa` varchar(255) NOT NULL,
  `cnpj` varchar(50) DEFAULT NULL,
  `responsavel` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome_empresa`, `cnpj`, `responsavel`, `email`, `telefone`, `criado_em`) VALUES
(1, 'teste', '1212121212', 'Teste', 'teste@gmail.com', '41985008346', '2026-06-17 16:56:04'),
(2, 'teste 2', '1212121214', 'Teste2', 'teste2@gmail.com', '419850083465', '2026-06-17 17:07:55'),
(3, 'Penta', '15.211.234/0001-55', 'Rafael Zannin', 'penta@gamail.com', '313131313', '2026-06-19 19:34:47'),
(4, 'Quimitextil', '', '', '', '', '2026-06-19 19:35:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `compras`
--

CREATE TABLE `compras` (
  `id` int(11) NOT NULL,
  `produto` varchar(255) DEFAULT NULL,
  `fornecedor` varchar(255) DEFAULT NULL,
  `preco_pago` decimal(10,2) DEFAULT NULL,
  `quantidade` varchar(255) DEFAULT NULL,
  `data_compra` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'ativa',
  `cotacao_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `compras`
--

INSERT INTO `compras` (`id`, `produto`, `fornecedor`, `preco_pago`, `quantidade`, `data_compra`, `observacoes`, `criado_em`, `status`, `cotacao_id`, `cliente_id`, `usuario_id`) VALUES
(30, 'acido fosforico', 'emmanuel', 124560.00, '10000', '2026-06-19', NULL, '2026-06-18 20:08:33', 'cancelada', 13, 2, 1),
(34, 'Vitamina C ', 'Fornecedor 1', 12400.00, '10000', '2026-06-19', NULL, '2026-06-19 19:26:29', 'cancelada', 14, 1, 1),
(35, 'METASSILICATO', 'OTTO', 580.00, '1 FCL', '2026-06-23', NULL, '2026-06-23 18:08:03', 'cancelada', 15, 4, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `cotacoes`
--

CREATE TABLE `cotacoes` (
  `id` int(11) NOT NULL,
  `cotacao` varchar(255) DEFAULT NULL,
  `produto` varchar(255) DEFAULT NULL,
  `fornecedor` varchar(255) DEFAULT NULL,
  `preco` decimal(10,2) DEFAULT NULL,
  `origem` varchar(255) DEFAULT NULL,
  `pagamento` varchar(255) DEFAULT NULL,
  `quantidade` varchar(255) DEFAULT NULL,
  `data_cotacao` date DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `cliente_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cotacoes`
--

INSERT INTO `cotacoes` (`id`, `cotacao`, `produto`, `fornecedor`, `preco`, `origem`, `pagamento`, `quantidade`, `data_cotacao`, `criado_em`, `cliente_id`, `usuario_id`) VALUES
(15, '1', 'METASSILICATO', 'OTTO', 580.00, 'eua', '2026-09-17', '1 FCL', '2026-06-19', '2026-06-19 19:37:56', 4, 1),
(16, '2', 'METASSILICATO', 'AHA ', 610.00, 'eua', '2026-09-17', '1 FCL', '2026-06-19', '2026-06-19 19:38:38', 4, 1),
(17, '3', 'METASSILICATO', 'VIKING ', 618.00, 'eua', '2026-09-17', '1 FCL', '2026-06-19', '2026-06-19 19:39:14', 4, 1),
(18, '4', 'METASSILICATO', 'CHEMICAL GT', 635.00, 'eua', '2026-10-17', '1 FCL', '2026-06-19', '2026-06-19 19:40:02', 4, 1),
(19, '5', 'METASSILICATO', 'PLASTIKIM ', 645.00, 'eua', '2026-09-17', '1 FCL', '2026-06-19', '2026-06-19 19:40:32', 4, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('admin','visualizacao') NOT NULL DEFAULT 'visualizacao',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `cliente_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo`, `criado_em`, `cliente_id`) VALUES
(1, 'Administrador', 'admin@thecomex.com', 'e10adc3949ba59abbe56e057f20f883e', 'admin', '2026-06-11 20:22:03', NULL),
(2, 'Emmanuel', 'buyer.eks@gmail.com', '202cb962ac59075b964b07152d234b70', 'visualizacao', '2026-06-11 20:54:36', NULL),
(3, 'abc', 'abc@gmail.com', '202cb962ac59075b964b07152d234b70', 'visualizacao', '2026-06-17 17:08:14', 2),
(4, 'Quimitextil Cliente', 'cliente1@gmail.com', '202cb962ac59075b964b07152d234b70', 'visualizacao', '2026-06-19 19:41:22', 4);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unica_cotacao` (`cotacao_id`);

--
-- Índices de tabela `cotacoes`
--
ALTER TABLE `cotacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `cotacoes`
--
ALTER TABLE `cotacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
