-- ============================================
-- BANCO DE DADOS HAPPY IDOSOS - SCHEMA COMPLETO ATUALIZADO
-- ============================================

DROP DATABASE IF EXISTS happy_idosos;
CREATE DATABASE happy_idosos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE happy_idosos;

-- ============================================
-- TABELA DE USUÁRIOS (VOLUNTÁRIOS) - ATUALIZADA
-- ============================================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    cpf VARCHAR(11) UNIQUE NOT NULL,
    nome VARCHAR(128) NOT NULL,
    telefone VARCHAR(15),
    data_nascimento DATE,
    email VARCHAR(128) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    
    -- NOVOS CAMPOS ADICIONADOS
    endereco VARCHAR(255) COMMENT 'Endereço completo do voluntário',
    cidade VARCHAR(100) COMMENT 'Cidade do voluntário',
    estado VARCHAR(2) COMMENT 'Estado (UF) do voluntário',
    cep VARCHAR(9) COMMENT 'CEP do voluntário',
    
    -- Campos de perfil do voluntário
    habilidades TEXT COMMENT 'Habilidades e competências do voluntário',
    disponibilidade TEXT COMMENT 'Disponibilidade de horários',
    sobre_voce TEXT COMMENT 'Descrição sobre o voluntário',
    foto_perfil VARCHAR(500) COMMENT 'URL da foto de perfil',
    
    -- Campos de auditoria
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para performance
    INDEX idx_email (email),
    INDEX idx_cpf (cpf),
    INDEX idx_ativo (ativo),
    INDEX idx_criado (criado_em),
    INDEX idx_cidade (cidade),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE ASILOS
-- ============================================
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
    
    -- Campos adicionais
    descricao TEXT COMMENT 'Descrição do asilo',
    capacidade INT COMMENT 'Capacidade de idosos',
    logo VARCHAR(500) COMMENT 'URL do logo do asilo',
    
    -- Campos de auditoria
    ativo BOOLEAN DEFAULT TRUE,
    verificado BOOLEAN DEFAULT FALSE COMMENT 'Asilo verificado pela plataforma',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para performance
    INDEX idx_email (email),
    INDEX idx_cnpj (cnpj),
    INDEX idx_cidade (cidade),
    INDEX idx_estado (estado),
    INDEX idx_ativo (ativo),
    INDEX idx_verificado (verificado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE RESET DE SENHA
-- ============================================
CREATE TABLE reset_senha (
    id_reset INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    id_asilo INT NULL,
    tipo_usuario ENUM('usuario', 'asilo') NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expira_em DATETIME NOT NULL,
    usado BOOLEAN DEFAULT FALSE COMMENT 'Token já foi usado',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_asilo) REFERENCES asilos(id_asilo) ON DELETE CASCADE,
    
    -- Índices
    INDEX idx_token (token),
    INDEX idx_expira (expira_em),
    INDEX idx_tipo (tipo_usuario),
    INDEX idx_usado (usado),
    
    -- Constraint: deve ter id_usuario OU id_asilo, não ambos
    CHECK (
        (id_usuario IS NOT NULL AND id_asilo IS NULL) OR 
        (id_usuario IS NULL AND id_asilo IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE EVENTOS
-- ============================================
CREATE TABLE eventos (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(128) NOT NULL,
    descricao TEXT,
    data_evento DATETIME NOT NULL,
    data_fim DATETIME COMMENT 'Data de término do evento',
    local VARCHAR(255) COMMENT 'Local específico do evento',
    vagas INT COMMENT 'Número de vagas disponíveis',
    vagas_ocupadas INT DEFAULT 0 COMMENT 'Número de vagas já ocupadas',
    id_asilo INT NOT NULL,
    
    -- Status do evento
    status ENUM('ativo', 'cancelado', 'concluido', 'em_andamento') DEFAULT 'ativo',
    
    -- Campos de auditoria
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (id_asilo) REFERENCES asilos(id_asilo) ON DELETE CASCADE,
    
    -- Índices
    INDEX idx_data_evento (data_evento),
    INDEX idx_asilo (id_asilo),
    INDEX idx_status (status),
    INDEX idx_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE PARTICIPAÇÕES EM EVENTOS
-- ============================================
CREATE TABLE participacoes (
    id_participacao INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    id_usuario INT NOT NULL,
    
    -- Status da participação
    status ENUM('confirmado', 'cancelado', 'presente', 'ausente') DEFAULT 'confirmado',
    
    -- Avaliação pós-evento
    avaliacao INT COMMENT 'Avaliação de 1 a 5 estrelas',
    comentario TEXT COMMENT 'Comentário sobre a experiência',
    
    -- Campos de auditoria
    data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_cancelamento TIMESTAMP NULL,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    
    -- Constraint: um usuário só pode participar uma vez de cada evento
    UNIQUE KEY unique_participacao (id_evento, id_usuario),
    
    -- Índices
    INDEX idx_evento (id_evento),
    INDEX idx_usuario (id_usuario),
    INDEX idx_status (status),
    INDEX idx_data_inscricao (data_inscricao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE MÍDIAS
-- ============================================
CREATE TABLE midias (
    id_midia INT AUTO_INCREMENT PRIMARY KEY,
    nome_midia VARCHAR(255) NOT NULL,
    descricao TEXT,
    url VARCHAR(500) NOT NULL,
    
    -- Informações do arquivo
    tipo_midia ENUM('video', 'imagem', 'audio', 'documento') DEFAULT 'video',
    mime_type VARCHAR(100),
    tamanho_bytes BIGINT,
    duracao_segundos INT COMMENT 'Duração em segundos (para vídeos e áudios)',
    largura INT COMMENT 'Largura em pixels (para imagens e vídeos)',
    altura INT COMMENT 'Altura em pixels (para imagens e vídeos)',
    
    -- Relacionamentos (todos opcionais)
    id_usuario INT NULL COMMENT 'Usuário que enviou',
    id_asilo INT NULL COMMENT 'Asilo relacionado',
    id_evento INT NULL COMMENT 'Evento relacionado',
    
    -- Status e moderação
    status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'aprovado',
    moderado_em TIMESTAMP NULL,
    
    -- Campos de auditoria
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    FOREIGN KEY (id_asilo) REFERENCES asilos(id_asilo) ON DELETE SET NULL,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE SET NULL,
    
    -- Índices
    INDEX idx_tipo (tipo_midia),
    INDEX idx_usuario (id_usuario),
    INDEX idx_asilo (id_asilo),
    INDEX idx_evento (id_evento),
    INDEX idx_status (status),
    INDEX idx_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE CONTATOS
-- ============================================
CREATE TABLE contatos (
    id_contato INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    assunto VARCHAR(255) COMMENT 'Assunto da mensagem',
    mensagem TEXT NOT NULL,
    arquivo VARCHAR(255) COMMENT 'Arquivo anexado',
    
    -- Status
    status ENUM('novo', 'lido', 'respondido', 'arquivado') DEFAULT 'novo',
    respondido_em TIMESTAMP NULL,
    
    -- Campos de auditoria
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE NOTIFICAÇÕES
-- ============================================
CREATE TABLE notificacoes (
    id_notificacao INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Destinatário
    id_usuario INT NULL,
    id_asilo INT NULL,
    tipo_destinatario ENUM('usuario', 'asilo') NOT NULL,
    
    -- Conteúdo
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo ENUM('evento', 'participacao', 'sistema', 'aviso') DEFAULT 'sistema',
    
    -- Relacionamentos opcionais
    id_evento INT NULL,
    
    -- Status
    lida BOOLEAN DEFAULT FALSE,
    lida_em TIMESTAMP NULL,
    
    -- Campos de auditoria
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_asilo) REFERENCES asilos(id_asilo) ON DELETE CASCADE,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
    
    -- Índices
    INDEX idx_usuario (id_usuario),
    INDEX idx_asilo (id_asilo),
    INDEX idx_tipo (tipo),
    INDEX idx_lida (lida),
    INDEX idx_criado (criado_em),
    
    -- Constraint: deve ter id_usuario OU id_asilo, não ambos
    CHECK (
        (id_usuario IS NOT NULL AND id_asilo IS NULL) OR 
        (id_usuario IS NULL AND id_asilo IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DADOS DE EXEMPLO (OPCIONAL - REMOVER EM PRODUÇÃO)
-- ============================================

-- Usuário de teste COM DADOS COMPLETOS
INSERT INTO usuarios (cpf, nome, telefone, data_nascimento, email, senha, endereco, cidade, estado, cep, habilidades, disponibilidade, sobre_voce) VALUES
('12345678901', 'João Silva', '(11) 98765-4321', '1990-05-15', 'joao@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rua das Flores, 123 - Centro', 'São Paulo', 'SP', '01234-567', 'Música, Leitura, Artesanato', 'Sábados e Domingos pela manhã', 'Sou voluntário há 3 anos e adoro trabalhar com idosos. Tenho experiência em atividades recreativas e acompanhamento.');

-- Usuário adicional para testes
INSERT INTO usuarios (cpf, nome, telefone, data_nascimento, email, senha, endereco, cidade, estado, cep, habilidades, disponibilidade, sobre_voce) VALUES
('98765432100', 'Maria Santos', '(11) 91234-5678', '1985-08-20', 'maria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Av. Paulista, 1000 - Bela Vista', 'São Paulo', 'SP', '01310-100', 'Contação de histórias, Dança, Jogos', 'Finais de semana à tarde', 'Adoro trazer alegria para os idosos através de atividades lúdicas e conversas.');

-- Asilo de teste
INSERT INTO asilos (cnpj, nome, endereco, cidade, estado, cep, latitude, longitude, telefone, email, senha, descricao, capacidade) VALUES
('12345678000190', 'Lar dos Idosos Felizes', 'Rua das Flores, 123', 'São Paulo', 'SP', '01234-567', -23.550520, -46.633308, '(11) 3333-4444', 'contato@larfeliz.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Asilo dedicado ao cuidado e bem-estar de idosos há mais de 20 anos. Oferecemos atividades recreativas, acompanhamento médico e muito carinho.', 50);

-- Asilo adicional para testes
INSERT INTO asilos (cnpj, nome, endereco, cidade, estado, cep, latitude, longitude, telefone, email, senha, descricao, capacidade, verificado) VALUES
('98765432000101', 'Casa de Repouso Esperança', 'Rua das Palmeiras, 456', 'Rio de Janeiro', 'RJ', '22010-000', -22.9068, -43.1729, '(21) 2222-3333', 'contato@esperanca.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Instituição filantrópica que acolhe idosos em situação de vulnerabilidade. Trabalhamos com amor e dedicação.', 35, TRUE);

-- Eventos de teste
INSERT INTO eventos (titulo, descricao, data_evento, data_fim, local, vagas, id_asilo) VALUES
('Tarde Musical', 'Tarde de música e entretenimento com os idosos. Traga seu instrumento e venha compartilhar momentos de alegria!', '2025-01-15 14:00:00', '2025-01-15 17:00:00', 'Salão Principal', 10, 1),
('Oficina de Artesanato', 'Workshop de artesanato para idosos. Vamos criar lindas peças decorativas e trabalhar a coordenação motora.', '2025-01-20 09:00:00', '2025-01-20 12:00:00', 'Sala de Atividades', 8, 1),
('Roda de Conversa', 'Momento de descontração e troca de experiências com os idosos. Venha ouvir histórias incríveis!', '2025-01-25 15:00:00', '2025-01-25 16:30:00', 'Jardim Interno', 15, 2);

-- Participações de teste
INSERT INTO participacoes (id_evento, id_usuario, status) VALUES
(1, 1, 'confirmado'),
(1, 2, 'confirmado'),
(2, 1, 'confirmado');

-- ============================================
-- VIEWS ÚTEIS
-- ============================================

-- View de eventos com informações do asilo
CREATE VIEW vw_eventos_completos AS
SELECT 
    e.*,
    a.nome AS nome_asilo,
    a.cidade,
    a.estado,
    a.endereco,
    a.telefone AS telefone_asilo,
    a.email AS email_asilo,
    (e.vagas - e.vagas_ocupadas) AS vagas_disponiveis
FROM eventos e
INNER JOIN asilos a ON e.id_asilo = a.id_asilo
WHERE e.status = 'ativo' AND a.ativo = TRUE;

-- View de participações com informações completas
CREATE VIEW vw_participacoes_completas AS
SELECT 
    p.*,
    u.nome AS nome_usuario,
    u.email AS email_usuario,
    u.telefone AS telefone_usuario,
    u.foto_perfil AS foto_usuario,
    e.titulo AS titulo_evento,
    e.data_evento,
    e.data_fim,
    e.local AS local_evento,
    a.nome AS nome_asilo,
    a.cidade AS cidade_asilo
FROM participacoes p
INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
INNER JOIN eventos e ON p.id_evento = e.id_evento
INNER JOIN asilos a ON e.id_asilo = a.id_asilo;

-- View de usuários completos para perfil
CREATE VIEW vw_usuarios_completos AS
SELECT 
    id_usuario,
    cpf,
    nome,
    telefone,
    data_nascimento,
    email,
    endereco,
    cidade,
    estado,
    cep,
    habilidades,
    disponibilidade,
    sobre_voce,
    foto_perfil,
    ativo,
    criado_em,
    atualizado_em,
    TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) AS idade
FROM usuarios
WHERE ativo = TRUE;

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger para atualizar vagas ocupadas ao adicionar participação
DELIMITER //
CREATE TRIGGER trg_participacao_insert 
AFTER INSERT ON participacoes
FOR EACH ROW
BEGIN
    IF NEW.status = 'confirmado' THEN
        UPDATE eventos 
        SET vagas_ocupadas = vagas_ocupadas + 1 
        WHERE id_evento = NEW.id_evento;
    END IF;
END//

-- Trigger para atualizar vagas ocupadas ao cancelar participação
CREATE TRIGGER trg_participacao_update 
AFTER UPDATE ON participacoes
FOR EACH ROW
BEGIN
    IF OLD.status = 'confirmado' AND NEW.status = 'cancelado' THEN
        UPDATE eventos 
        SET vagas_ocupadas = vagas_ocupadas - 1 
        WHERE id_evento = NEW.id_evento;
    END IF;
    
    IF OLD.status = 'cancelado' AND NEW.status = 'confirmado' THEN
        UPDATE eventos 
        SET vagas_ocupadas = vagas_ocupadas + 1 
        WHERE id_evento = NEW.id_evento;
    END IF;
END//

-- Trigger para garantir que vagas_ocupadas não seja negativo
CREATE TRIGGER trg_participacao_before_update
BEFORE UPDATE ON eventos
FOR EACH ROW
BEGIN
    IF NEW.vagas_ocupadas < 0 THEN
        SET NEW.vagas_ocupadas = 0;
    END IF;
    
    IF NEW.vagas_ocupadas > NEW.vagas THEN
        SET NEW.vagas_ocupadas = NEW.vagas;
    END IF;
END//

DELIMITER ;

-- ============================================
-- PROCEDURES ÚTEIS
-- ============================================

DELIMITER //

-- Procedure para buscar perfil completo do usuário
CREATE PROCEDURE sp_buscar_perfil_usuario(IN p_id_usuario INT)
BEGIN
    SELECT * FROM vw_usuarios_completos WHERE id_usuario = p_id_usuario;
END//

-- Procedure para listar eventos disponíveis
CREATE PROCEDURE sp_listar_eventos_disponiveis()
BEGIN
    SELECT * FROM vw_eventos_completos 
    WHERE data_evento >= NOW() 
    AND vagas_disponiveis > 0
    ORDER BY data_evento ASC;
END//

-- Procedure para contar participações do usuário
CREATE PROCEDURE sp_contar_participacoes_usuario(IN p_id_usuario INT)
BEGIN
    SELECT 
        COUNT(*) AS total_participacoes,
        SUM(CASE WHEN status = 'confirmado' THEN 1 ELSE 0 END) AS confirmadas,
        SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) AS canceladas
    FROM participacoes 
    WHERE id_usuario = p_id_usuario;
END//

DELIMITER ;

-- ============================================
-- FIM DO SCHEMA
-- ============================================