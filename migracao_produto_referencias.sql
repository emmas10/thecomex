CREATE TABLE IF NOT EXISTS produto_referencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    produto_base VARCHAR(255) NOT NULL,
    valor_ultima_compra DECIMAL(15,6) NULL,
    data_ultima_compra DATE NULL,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY cliente_produto (cliente_id, produto_base),
    KEY idx_produto_referencias_cliente (cliente_id),
    CONSTRAINT fk_produto_referencias_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
