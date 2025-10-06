create database happy_idosos;

use happy_idosos;

create table usuarios (
    id_usuario int auto_increment primary key,
    cpf varchar(11) unique not null,
    nome varchar(128) not null,
    telefone varchar(15),
    data_nascimento date,
    email varchar(128) unique not null,
    senha varchar(255) not null
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

-- ✅ TABELA CORRIGIDA - AGORA COM SUPORTE PARA ASILOS
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

create table eventos (
    id_evento int auto_increment primary key,
    titulo varchar(128) not null,
    descricao text,
    data_evento datetime not null,
    id_asilo int not null,
    foreign key (id_asilo) references asilos (id_asilo) on delete cascade
);

create table participacoes (
    id_participacao int auto_increment primary key,
    id_evento int not null,
    id_usuario int not null,
    data_inscricao timestamp default current_timestamp,
    foreign key (id_evento) references eventos (id_evento) on delete cascade,
    foreign key (id_usuario) references usuarios (id_usuario) on delete cascade
);

create table midias (
    id_midia int auto_increment primary key,
    nome_midia varchar(128),
    descricao varchar(255),
    url varchar(255),
    id_usuario int,
    id_asilo int,
    id_evento int,
    foreign key (id_usuario) references usuarios (id_usuario) on delete set null,
    foreign key (id_asilo) references asilos (id_asilo) on delete set null,
    foreign key (id_evento) references eventos (id_evento) on delete set null
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

