CREATE DATABASE IF NOT EXISTS happy_idosos;
USE happy_idosos;

-- Tabela de usuários
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    cpf VARCHAR(11) UNIQUE NOT NULL,
    nome VARCHAR(128) NOT NULL,
    telefone VARCHAR(15),
    data_nascimento DATE,
    email VARCHAR(128) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_cpf (cpf)
);

-- Tabela de asilos
CREATE TABLE asilos (
    id_asilo INT AUTO_INCREMENT PRIMARY KEY,
    cnpj VARCHAR(14) UNIQUE NOT NULL,
    nome VARCHAR(128) NOT NULL,
    endereco VARCHAR(255),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    cep VARCHAR(9),
    latitude DECIMAL(10, 7),
    longitude DECIMAL(10, 7),
    telefone VARCHAR(15),
    email VARCHAR(128) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_cnpj (cnpj),
    INDEX idx_cidade (cidade)
);

-- Tabela de reset de senha (suporta usuários e asilos)
CREATE TABLE reset_senha (
    id_reset INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    id_asilo INT NULL,
    tipo_usuario ENUM('usuario', 'asilo') NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expira_em DATETIME NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_asilo) REFERENCES asilos(id_asilo) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expira (expira_em)
);

-- Tabela de eventos
CREATE TABLE eventos (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(128) NOT NULL,
    descricao TEXT,
    data_evento DATETIME NOT NULL,
    id_asilo INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_asilo) REFERENCES asilos(id_asilo) ON DELETE CASCADE,
    INDEX idx_data_evento (data_evento),
    INDEX idx_asilo (id_asilo)
);

-- Tabela de participações em eventos
CREATE TABLE participacoes (
    id_participacao INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    id_usuario INT NOT NULL,
    data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    UNIQUE KEY unique_participacao (id_evento, id_usuario),
    INDEX idx_evento (id_evento),
    INDEX idx_usuario (id_usuario)
);

-- Tabela de mídias (vídeos, imagens, etc) - CORRIGIDA
CREATE TABLE midias (
    id_midia INT AUTO_INCREMENT PRIMARY KEY,
    nome_midia VARCHAR(255) NOT NULL,
    descricao TEXT,
    url VARCHAR(500) NOT NULL,
    tipo_midia ENUM('video', 'imagem', 'audio', 'documento') DEFAULT 'video',
    mime_type VARCHAR(100),
    tamanho_bytes BIGINT,
    duracao_segundos INT,
    id_usuario INT NULL,
    id_asilo INT NULL,
    id_evento INT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    FOREIGN KEY (id_asilo) REFERENCES asilos(id_asilo) ON DELETE SET NULL,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE SET NULL,
    INDEX idx_tipo (tipo_midia),
    INDEX idx_usuario (id_usuario),
    INDEX idx_asilo (id_asilo),
    INDEX idx_evento (id_evento),
    INDEX idx_criado (criado_em)
);

-- Tabela de contatos
CREATE TABLE contatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    mensagem TEXT NOT NULL,
    arquivo VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_criado (criado_em)
);

-- Tabela para perfil de voluntário (campos opcionais)
CREATE TABLE perfil_voluntario (
    id_perfil INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    habilidades TEXT,
    competencias TEXT,
    disponibilidade TEXT,
    sobre_voce TEXT,
    foto_perfil VARCHAR(500),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_usuario (id_usuario)
);

