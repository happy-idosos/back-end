create database happy_idosos;

use happy_idosos;

create table
    usuarios (
        id_usuario int auto_increment primary key,
        cpf varchar(11) unique not null,
        nome varchar(128) not null,
        telefone varchar(15),
        data_nascimento date,
        email varchar(128) unique not null,
        senha varchar(255) not null
    );

create table
    asilos (
        id_asilo int auto_increment primary key,
        cnpj varchar(14) unique not null,
        nome varchar(128) not null,
        endereco varchar(128),
        telefone varchar(15),
        email varchar(128) unique not null,
        senha varchar(255) not null
    );

-- Tabela de eventos
create table
    eventos (
        id_evento int auto_increment primary key,
        titulo varchar(128) not null,
        descricao text,
        data_evento datetime not null,
        id_asilo int not null,
        foreign key (id_asilo) references asilos (id_asilo) on delete cascade
    );

-- Participações de usuários em eventos
create table
    participacoes (
        id_participacao int auto_increment primary key,
        id_evento int not null,
        id_usuario int not null,
        data_inscricao timestamp default current_timestamp,
        foreign key (id_evento) references eventos (id_evento) on delete cascade,
        foreign key (id_usuario) references usuarios (id_usuario) on delete cascade
    );

-- Mídias vinculadas a eventos / usuários / asilos
create table
    midias (
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