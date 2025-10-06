CREATE DATABASE happy_idosos;
USE happy_idosos;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    cpf VARCHAR(11) UNIQUE NOT NULL,
    nome VARCHAR(128) NOT NULL,
    telefone VARCHAR(15),
    data_nascimento DATE,
    email VARCHAR(128) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL
);

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
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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
    INDEX idx_token(token),
    INDEX idx_expira(expira_em)
);

CREATE TABLE eventos (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(128) NOT NULL,
    descricao TEXT,
    data_evento DATETIME NOT NULL,
    id_asilo INT NOT NULL,
    FOREIGN KEY (id_asilo) REFERENCES asilos(id_asilo) ON DELETE CASCADE
);

CREATE TABLE participacoes (
    id_participacao INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    id_usuario INT NOT NULL,
    data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- TABELA DE VÍDEOS CORRIGIDA
CREATE TABLE videos (
    id_video INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(128),
    descricao TEXT,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- COLUNA CORRIGIDA
    caminho VARCHAR(255) NOT NULL,
    tamanho INT,
    duracao INT,
    id_usuario INT,
    id_asilo INT,
    id_evento INT,
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    FOREIGN KEY (id_asilo) REFERENCES asilos(id_asilo) ON DELETE SET NULL,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE SET NULL
);

CREATE TABLE midias (
    id_midia INT AUTO_INCREMENT PRIMARY KEY,
    nome_midia VARCHAR(128),
    descricao VARCHAR(255),
    url VARCHAR(255),
    tipo ENUM('imagem', 'documento') DEFAULT 'imagem',
    id_usuario INT,
    id_asilo INT,
    id_evento INT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    FOREIGN KEY (id_asilo) REFERENCES asilos(id_asilo) ON DELETE SET NULL,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE SET NULL
);

CREATE TABLE contatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    mensagem TEXT NOT NULL,
    arquivo VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);