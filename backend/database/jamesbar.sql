-- ==============================================
-- PROJETO JAMESBAR - BANCOS DE DADOS
-- ==============================================

-- ==============================================
-- BANCO PRINCIPAL
-- ==============================================

CREATE DATABASE IF NOT EXISTS db_core;
USE db_core;

-- TABELA DE USUÁRIOS DO SISTEMA (CAIXA E ADM)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    tipo ENUM('caixa', 'adm') NOT NULL DEFAULT 'caixa',
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABELA DE CLIENTES DA BALADA
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    data_aniversario DATE NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dentro_balada BOOLEAN DEFAULT FALSE,
    total_entradas INT NOT NULL DEFAULT 0
);

-- TABELA DE HISTÓRICO DE MOVIMENTAÇÕES (ENTRADA/SAÍDA)
CREATE TABLE IF NOT EXISTS movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

-- TRIGGER PARA ATUALIZAR STATUS DO CLIENTE
DELIMITER $$

CREATE TRIGGER trg_movimentacoes_after_insert
AFTER INSERT ON movimentacoes
FOR EACH ROW
BEGIN
    IF NEW.tipo = 'entrada' THEN
        UPDATE clientes
        SET
            dentro_balada = TRUE,
            total_entradas = total_entradas + 1
        WHERE id = NEW.cliente_id;

    ELSEIF NEW.tipo = 'saida' THEN
        UPDATE clientes
        SET dentro_balada = FALSE
        WHERE id = NEW.cliente_id;
    END IF;
END$$

DELIMITER ;

-- USUÁRIOS PADRÃO
INSERT INTO usuarios (nome, email, senha_hash, tipo) VALUES
('Administrador', 'admin@jamesbar.com', MD5('admin123'), 'adm'),
('Caixa 01', 'caixa01@jamesbar.com', MD5('caixa01'), 'caixa'),
('Caixa 02', 'caixa02@jamesbar.com', MD5('caixa02'), 'caixa'),
('Caixa 03', 'caixa03@jamesbar.com', MD5('caixa03'), 'caixa'),
('Caixa 04', 'caixa04@jamesbar.com', MD5('caixa04'), 'caixa'),
('Caixa 05', 'caixa05@jamesbar.com', MD5('caixa05'), 'caixa'),
('Caixa 06', 'caixa06@jamesbar.com', MD5('caixa06'), 'caixa');


-- ==============================================
-- BANCO DE LISTAS VIP
-- ==============================================

CREATE DATABASE IF NOT EXISTS db_listas_vip;
USE db_listas_vip;

-- TABELA DE DIAS DISPONÍVEIS
CREATE TABLE IF NOT EXISTS dias_promoters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(20) NOT NULL UNIQUE
);

-- INSERINDO OS DIAS
INSERT INTO dias_promoters (id, nome) VALUES
(1, 'quarta'),
(2, 'quinta'),
(3, 'sexta'),
(4, 'sabado'),
(5, 'domingo');

-- TABELA DE PROMOTERS
CREATE TABLE IF NOT EXISTS promoters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),

    lista_quarta BOOLEAN DEFAULT FALSE,
    lista_quinta BOOLEAN DEFAULT FALSE,
    lista_sexta BOOLEAN DEFAULT FALSE,
    lista_sabado BOOLEAN DEFAULT FALSE,
    lista_domingo BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABELA DE LISTAS DOS PROMOTERS
CREATE TABLE IF NOT EXISTS listas_promoters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    promoter_id INT NOT NULL,
    dia_id INT NOT NULL,
    data_lista DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (promoter_id) REFERENCES promoters(id) ON DELETE CASCADE,
    FOREIGN KEY (dia_id) REFERENCES dias_promoters(id) ON DELETE CASCADE,

    UNIQUE KEY unique_lista_promoter_dia_data (
        promoter_id,
        dia_id,
        data_lista
    )
);

-- CONVIDADOS DA LISTA DO PROMOTER
-- ENTRADA GRATUITA (MÁXIMO 5)
CREATE TABLE IF NOT EXISTS lista_promoters_convidados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lista_id INT NOT NULL,
    nome_convidado VARCHAR(100) NOT NULL,
    cpf VARCHAR(14),

    FOREIGN KEY (lista_id)
        REFERENCES listas_promoters(id)
        ON DELETE CASCADE
);

-- VIPS DA LISTA DO PROMOTER
-- PAGAM R$15 (MÁXIMO 20)
CREATE TABLE IF NOT EXISTS lista_promoters_vips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lista_id INT NOT NULL,
    nome_vip VARCHAR(100) NOT NULL,
    cpf VARCHAR(14),

    FOREIGN KEY (lista_id)
        REFERENCES listas_promoters(id)
        ON DELETE CASCADE
);

-- LISTAS DE ANIVERSÁRIO
CREATE TABLE IF NOT EXISTS listas_aniversario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aniversariante_nome VARCHAR(100) NOT NULL,
    aniversariante_cpf VARCHAR(14),
    data_evento DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CONVIDADOS DO ANIVERSARIANTE
-- DESCONTO DE R$10 (MÁXIMO 20)
CREATE TABLE IF NOT EXISTS lista_aniversario_convidados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lista_aniversario_id INT NOT NULL,
    nome_convidado VARCHAR(100) NOT NULL,
    cpf VARCHAR(14),

    FOREIGN KEY (lista_aniversario_id)
        REFERENCES listas_aniversario(id)
        ON DELETE CASCADE
);

-- ==============================================
-- DADOS DE EXEMPLO
-- ==============================================

-- PROMOTERS
INSERT INTO promoters (
    nome,
    telefone,
    lista_quarta,
    lista_quinta,
    lista_sexta,
    lista_sabado,
    lista_domingo
) VALUES
(
    'André Komarcheuski Rosa',
    '(41) 99999-1111',
    TRUE, -- QUARTA --
    FALSE, -- QUINTA --
    FALSE, -- SEXTA -
    TRUE, -- SABADO --
    FALSE -- DOMINGO --
),
(
    'Ana Clara Kelmer',
    '(41) 99999-2222',
    TRUE, -- QUARTA --
    FALSE, -- QUINTA --
    TRUE, -- SEXTA -
    TRUE, -- SABADO --
    FALSE -- DOMINGO --
),
(
    'Ricardo Ryu',
    '(41) 99999-3333',
    TRUE, -- QUARTA --
    TRUE, -- QUINTA --
    TRUE, -- SEXTA -
    TRUE, -- SABADO --
    TRUE -- DOMINGO --
);

-- LISTA DE EXEMPLO
INSERT INTO listas_promoters (
    promoter_id,
    dia_id,
    data_lista
) VALUES (
    1,
    3,
    CURDATE()
);

-- CONVIDADOS DE EXEMPLO
INSERT INTO lista_promoters_convidados (
    lista_id,
    nome_convidado
) VALUES
(1, 'João Convidado'),
(1, 'Maria Convidada');

-- ANIVERSÁRIO DE EXEMPLO
INSERT INTO listas_aniversario (
    aniversariante_nome,
    data_evento
) VALUES (
    'Pedro Aniversariante',
    CURDATE()
);

-- ==============================================
-- CONSULTAS DE VERIFICAÇÃO
-- ==============================================

SHOW DATABASES;

SELECT '=== BANCO CORE CRIADO COM SUCESSO ===' AS STATUS;

USE db_core;
SHOW TABLES;

SELECT '=== BANCO LISTAS VIP CRIADO COM SUCESSO ===' AS STATUS;

USE db_listas_vip;
SHOW TABLES;

-- VISUALIZAR PROMOTERS E DIAS DISPONÍVEIS

SELECT
    nome,
    telefone,
    lista_quarta AS quarta,
    lista_quinta AS quinta,
    lista_sexta AS sexta,
    lista_sabado AS sabado,
    lista_domingo AS domingo
FROM promoters;

-- VISUALIZAR CLIENTES E QUANTIDADE DE ENTRADAS

USE db_core;

SELECT
    nome,
    cpf,
    total_entradas,
    dentro_balada
FROM clientes;