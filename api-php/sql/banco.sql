-- Criação do banco de dados
DROP DATABASE IF EXISTS banco;

CREATE DATABASE banco;

USE banco;

-- Criação da tabela principal de usuários
CREATE TABLE
    usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        tipo ENUM ('voluntario', 'asilos') NOT NULL,
        documento VARCHAR(20) NOT NULL, -- CPF ou CNPJ
        endereco TEXT, -- Endereço textual fornecido pelo usuário
        latitude DECIMAL(10, 8), -- Coordenada de latitude
        longitude DECIMAL(11, 8), -- Coordenada de longitude
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );