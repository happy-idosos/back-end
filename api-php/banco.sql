create database banco;  -- Criação do banco de dados
USE banco;  -- Seleção do banco de dados

CREATE TABLE
    usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        tipo ENUM ('voluntario', 'asilos') NOT NULL,
        documento VARCHAR(20) NOT NULL, -- CPF ou CNPJ
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );