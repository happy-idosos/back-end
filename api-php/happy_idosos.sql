CREATE DATABASE IF NOT EXISTS happy_idosos;
USE happy_idosos;

-- Tabela de usuários (voluntários/visitantes)
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    cpf VARCHAR(11) UNIQUE NOT NULL,
    nome VARCHAR(128) NOT NULL,
    telefone VARCHAR(15),
    data_nascimento DATE,
    email VARCHAR(128) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_usuario_email (email),
    INDEX idx_usuario_cpf (cpf)
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
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_asilo_email (email),
    INDEX idx_asilo_cnpj (cnpj),
    INDEX idx_asilo_localizacao (cidade, estado)
);

-- Tabela de reset de senha
CREATE TABLE reset_senha (
    id_reset INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expira_em DATETIME NOT NULL,
    utilizado BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_reset_token (token),
    INDEX idx_reset_expiracao (expira_em)
);

-- Tabela de eventos
CREATE TABLE eventos (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(128) NOT NULL,
    descricao TEXT,
    data_evento DATETIME NOT NULL,
    local_evento VARCHAR(255),
    max_participantes INT,
    id_asilo INT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_asilo) REFERENCES asilos(id_asilo) ON DELETE CASCADE,
    INDEX idx_evento_data (data_evento),
    INDEX idx_evento_asilo (id_asilo)
);

-- Tabela de participações em eventos
CREATE TABLE participacoes (
    id_participacao INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    id_usuario INT NOT NULL,
    status ENUM('confirmada', 'pendente', 'cancelada') DEFAULT 'confirmada',
    data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    UNIQUE KEY uk_participacao_evento_usuario (id_evento, id_usuario),
    INDEX idx_participacao_usuario (id_usuario),
    INDEX idx_participacao_status (status)
);

-- Tabela de tipos de mídia
CREATE TABLE tipos_midia (
    id_tipo_midia INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    descricao VARCHAR(255),
    extensoes_permitidas VARCHAR(100)
);

-- Inserir tipos de mídia padrão
INSERT INTO tipos_midia (nome, descricao, extensoes_permitidas) VALUES
('video', 'Arquivos de vídeo', 'mp4,webm,ogg,avi,mov'),
('imagem', 'Imagens e fotos', 'jpg,jpeg,png,gif'),
('documento', 'Documentos diversos', 'pdf,doc,docx,txt');

-- Tabela de mídias (SIMPLIFICADA - mantendo compatibilidade)
CREATE TABLE midias (
    id_midia INT AUTO_INCREMENT PRIMARY KEY,
    nome_midia VARCHAR(128),
    descricao VARCHAR(255),
    url VARCHAR(255),
    id_usuario INT,
    id_asilo INT,
    id_evento INT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    FOREIGN KEY (id_asilo) REFERENCES asilos(id_asilo) ON DELETE SET NULL,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE SET NULL
);

-- Tabela de contatos
CREATE TABLE contatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    mensagem TEXT NOT NULL,
    arquivo VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);