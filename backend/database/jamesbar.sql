-- ==============================================
-- PROJETO JAMESBAR - BANCOS DE DADOS
-- ==============================================

DROP DATABASE IF EXISTS db_listas_vip;
DROP DATABASE IF EXISTS db_core;

-- ==============================================
-- BANCO PRINCIPAL
-- ==============================================

CREATE DATABASE db_core;
USE db_core;

-- ==============================================
-- TABELA DE USUÁRIOS
-- ==============================================

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    tipo ENUM('caixa', 'adm') NOT NULL DEFAULT 'caixa',
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==============================================
-- TABELA DE CLIENTES
-- ==============================================

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    data_aniversario DATE NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dentro_balada BOOLEAN DEFAULT FALSE,
    total_entradas INT NOT NULL DEFAULT 0
);

-- ==============================================
-- TABELA DE TURNOS DOS CAIXAS
-- ==============================================

CREATE TABLE turnos_caixa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    data_turno DATE NOT NULL,
    status ENUM('aberto', 'pausado', 'fechado') NOT NULL DEFAULT 'aberto',
    total_pausas INT NOT NULL DEFAULT 0,
    aberto_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pausado_em TIMESTAMP NULL,
    fechado_em TIMESTAMP NULL,

    FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE RESTRICT,

    UNIQUE KEY unique_turno_usuario_data (
        usuario_id,
        data_turno
    )
);

-- ==============================================
-- TABELA DE PAUSAS DOS CAIXAS
-- ==============================================

CREATE TABLE pausas_caixa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turno_id INT NOT NULL,
    inicio_pausa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fim_pausa TIMESTAMP NULL,

    FOREIGN KEY (turno_id)
        REFERENCES turnos_caixa(id)
        ON DELETE CASCADE
);

-- ==============================================
-- TABELA DE MOVIMENTAÇÕES
-- ==============================================

CREATE TABLE movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    caixa_id INT NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (cliente_id)
        REFERENCES clientes(id)
        ON DELETE CASCADE,

    FOREIGN KEY (caixa_id)
        REFERENCES usuarios(id)
        ON DELETE RESTRICT
);

-- ==============================================
-- TRIGGER DE ENTRADA/SAÍDA
-- ==============================================

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
        SET
            dentro_balada = FALSE
        WHERE id = NEW.cliente_id;

    END IF;
END$$

DELIMITER ;

-- ==============================================
-- USUÁRIOS PADRÃO
-- ==============================================

INSERT INTO usuarios (
    nome,
    email,
    senha_hash,
    tipo
) VALUES
(
    'Administrador',
    'admin@jamesbar.com',
    MD5('admin123'),
    'adm'
),
(
    'Caixa 01',
    'caixa01@jamesbar.com',
    MD5('caixa01'),
    'caixa'
),
(
    'Caixa 02',
    'caixa02@jamesbar.com',
    MD5('caixa02'),
    'caixa'
),
(
    'Caixa 03',
    'caixa03@jamesbar.com',
    MD5('caixa03'),
    'caixa'
),
(
    'Caixa 04',
    'caixa04@jamesbar.com',
    MD5('caixa04'),
    'caixa'
),
(
    'Caixa 05',
    'caixa05@jamesbar.com',
    MD5('caixa05'),
    'caixa'
),
(
    'Caixa 06',
    'caixa06@jamesbar.com',
    MD5('caixa06'),
    'caixa'
);

-- ==============================================
-- CLIENTES DE EXEMPLO
-- ==============================================

INSERT INTO clientes (
    nome,
    cpf,
    data_aniversario
) VALUES
(
    'João da Silva',
    '111.111.111-11',
    '2000-05-10'
),
(
    'Maria Oliveira',
    '222.222.222-22',
    '1999-08-20'
),
(
    'Pedro Santos',
    '333.333.333-33',
    '2001-01-15'
);

-- ==============================================
-- BANCO DE LISTAS VIP
-- ==============================================

CREATE DATABASE db_listas_vip;
USE db_listas_vip;

-- ==============================================
-- TABELA DE DIAS DOS PROMOTERS
-- ==============================================

CREATE TABLE dias_promoters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(20) NOT NULL UNIQUE
);

INSERT INTO dias_promoters (
    id,
    nome
) VALUES
(1, 'quarta'),
(2, 'quinta'),
(3, 'sexta'),
(4, 'sabado'),
(5, 'domingo');

-- ==============================================
-- TABELA DE PROMOTERS
-- ==============================================

CREATE TABLE promoters (
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

-- ==============================================
-- TABELA DE LISTAS DOS PROMOTERS
-- ==============================================

CREATE TABLE listas_promoters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    promoter_id INT NOT NULL,
    dia_id INT NOT NULL,
    data_lista DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (promoter_id)
        REFERENCES promoters(id)
        ON DELETE CASCADE,

    FOREIGN KEY (dia_id)
        REFERENCES dias_promoters(id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_lista_promoter_dia_data (
        promoter_id,
        dia_id,
        data_lista
    )
);

-- ==============================================
-- CONVIDADOS DOS PROMOTERS
-- ==============================================

CREATE TABLE lista_promoters_convidados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lista_id INT NOT NULL,
    nome_convidado VARCHAR(100) NOT NULL,
    cpf VARCHAR(14),

    FOREIGN KEY (lista_id)
        REFERENCES listas_promoters(id)
        ON DELETE CASCADE
);

-- ==============================================
-- VIPS DOS PROMOTERS
-- ==============================================

CREATE TABLE lista_promoters_vips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lista_id INT NOT NULL,
    nome_vip VARCHAR(100) NOT NULL,
    cpf VARCHAR(14),

    FOREIGN KEY (lista_id)
        REFERENCES listas_promoters(id)
        ON DELETE CASCADE
);

-- ==============================================
-- LISTAS DE ANIVERSÁRIO
-- ==============================================

CREATE TABLE listas_aniversario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aniversariante_nome VARCHAR(100) NOT NULL,
    aniversariante_cpf VARCHAR(14),
    data_evento DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==============================================
-- CONVIDADOS DE ANIVERSÁRIO
-- ==============================================

CREATE TABLE lista_aniversario_convidados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lista_aniversario_id INT NOT NULL,
    nome_convidado VARCHAR(100) NOT NULL,
    cpf VARCHAR(14),

    FOREIGN KEY (lista_aniversario_id)
        REFERENCES listas_aniversario(id)
        ON DELETE CASCADE
);

-- ==============================================
-- PROMOTERS DE EXEMPLO
-- ==============================================

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
    TRUE,
    FALSE,
    FALSE,
    TRUE,
    FALSE
),
(
    'Ana Clara Kelmer',
    '(41) 99999-2222',
    TRUE,
    FALSE,
    TRUE,
    TRUE,
    FALSE
),
(
    'Ricardo Ryu',
    '(41) 99999-3333',
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE
);

-- ==============================================
-- LISTA DE PROMOTER DE EXEMPLO
-- ==============================================

INSERT INTO listas_promoters (
    promoter_id,
    dia_id,
    data_lista
) VALUES
(
    1,
    3,
    CURDATE()
);

INSERT INTO lista_promoters_convidados (
    lista_id,
    nome_convidado
) VALUES
(
    1,
    'João Convidado'
),
(
    1,
    'Maria Convidada'
);

-- ==============================================
-- ANIVERSÁRIO DE EXEMPLO
-- ==============================================

INSERT INTO listas_aniversario (
    aniversariante_nome,
    data_evento
) VALUES
(
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

SELECT
    id,
    nome,
    email,
    tipo,
    ativo
FROM usuarios;

SELECT
    id,
    nome,
    cpf,
    data_aniversario,
    data_cadastro,
    dentro_balada,
    total_entradas
FROM clientes;

SELECT
    u.nome AS caixa,
    t.data_turno,
    t.status,
    t.total_pausas,
    t.aberto_em,
    t.pausado_em,
    t.fechado_em
FROM turnos_caixa t
INNER JOIN usuarios u
    ON t.usuario_id = u.id;

SELECT
    pc.id,
    u.nome AS caixa,
    t.data_turno,
    pc.inicio_pausa,
    pc.fim_pausa
FROM pausas_caixa pc
INNER JOIN turnos_caixa t
    ON pc.turno_id = t.id
INNER JOIN usuarios u
    ON t.usuario_id = u.id;

SELECT '=== BANCO LISTAS VIP CRIADO COM SUCESSO ===' AS STATUS;

USE db_listas_vip;
SHOW TABLES;

SELECT
    nome,
    telefone,
    lista_quarta AS quarta,
    lista_quinta AS quinta,
    lista_sexta AS sexta,
    lista_sabado AS sabado,
    lista_domingo AS domingo
FROM promoters;