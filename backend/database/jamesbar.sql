DROP DATABASE IF EXISTS db_core;
DROP DATABASE IF EXISTS db_listas_vip;

CREATE DATABASE db_core;
USE db_core;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    tipo ENUM('caixa', 'adm') NOT NULL DEFAULT 'caixa',
    ativo BOOLEAN DEFAULT TRUE,
    primeiro_acesso BOOLEAN NOT NULL DEFAULT TRUE,
    tentativas_login INT NOT NULL DEFAULT 0,
    bloqueio_login_until TIMESTAMP NULL DEFAULT NULL,
    mfa_secret VARCHAR(255) NULL,
    mfa_ativo BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    data_aniversario DATE NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dentro_balada BOOLEAN DEFAULT FALSE,
    total_entradas INT NOT NULL DEFAULT 0
);

CREATE TABLE turnos_caixa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    data_turno DATE NOT NULL,
    status ENUM('aberto', 'pausado', 'fechado') NOT NULL DEFAULT 'aberto',
    total_pausas INT NOT NULL DEFAULT 0,
    aberto_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pausado_em TIMESTAMP NULL,
    fechado_em TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_turno_usuario_data (usuario_id, data_turno)
);

CREATE TABLE pausas_caixa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turno_id INT NOT NULL,
    inicio_pausa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fim_pausa TIMESTAMP NULL,
    FOREIGN KEY (turno_id) REFERENCES turnos_caixa(id) ON DELETE CASCADE
);

CREATE TABLE movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    caixa_id INT NOT NULL,
    tipo ENUM('entrada','saida') NOT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (caixa_id) REFERENCES usuarios(id) ON DELETE RESTRICT
);

DELIMITER $$
CREATE TRIGGER trg_movimentacoes_after_insert
AFTER INSERT ON movimentacoes
FOR EACH ROW
BEGIN
    IF NEW.tipo = 'entrada' THEN
        UPDATE clientes
        SET dentro_balada = TRUE,
            total_entradas = total_entradas + 1
        WHERE id = NEW.cliente_id;
    ELSEIF NEW.tipo = 'saida' THEN
        UPDATE clientes
        SET dentro_balada = FALSE
        WHERE id = NEW.cliente_id;
    END IF;
END$$
DELIMITER ;

INSERT INTO usuarios (nome, email, senha_hash, tipo, primeiro_acesso) VALUES
('Administrador','admin@jamesbar.com',MD5('admin123'),'adm',TRUE),
('Caixa 01','caixa01@jamesbar.com',MD5('caixa01'),'caixa',TRUE),
('Caixa 02','caixa02@jamesbar.com',MD5('caixa02'),'caixa',TRUE),
('Caixa 03','caixa03@jamesbar.com',MD5('caixa03'),'caixa',TRUE),
('Caixa 04','caixa04@jamesbar.com',MD5('caixa04'),'caixa',TRUE),
('Caixa 05','caixa05@jamesbar.com',MD5('caixa05'),'caixa',TRUE),
('Caixa 06','caixa06@jamesbar.com',MD5('caixa06'),'caixa',TRUE);

INSERT INTO clientes (nome, cpf, data_aniversario) VALUES
('João da Silva','111.111.111-11','2000-05-10'),
('Maria Oliveira','222.222.222-22','1999-08-20'),
('Pedro Santos','333.333.333-33','2001-01-15');

CREATE DATABASE db_listas_vip;
USE db_listas_vip;

CREATE TABLE dias_promoters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(20) NOT NULL UNIQUE
);

INSERT INTO dias_promoters (id,nome) VALUES
(1,'quarta'),(2,'quinta'),(3,'sexta'),(4,'sabado'),(5,'domingo');

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

CREATE TABLE listas_promoters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    promoter_id INT NOT NULL,
    dia_id INT NOT NULL,
    data_lista DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (promoter_id) REFERENCES promoters(id) ON DELETE CASCADE,
    FOREIGN KEY (dia_id) REFERENCES dias_promoters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_lista_promoter_dia_data (promoter_id,dia_id,data_lista)
);

CREATE TABLE lista_promoters_convidados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lista_id INT NOT NULL,
    nome_convidado VARCHAR(100) NOT NULL,
    cpf VARCHAR(14),
    FOREIGN KEY (lista_id) REFERENCES listas_promoters(id) ON DELETE CASCADE
);

CREATE TABLE lista_promoters_vips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lista_id INT NOT NULL,
    nome_vip VARCHAR(100) NOT NULL,
    cpf VARCHAR(14),
    FOREIGN KEY (lista_id) REFERENCES listas_promoters(id) ON DELETE CASCADE
);

CREATE TABLE listas_aniversario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aniversariante_nome VARCHAR(100) NOT NULL,
    aniversariante_cpf VARCHAR(14),
    data_evento DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lista_aniversario_convidados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lista_aniversario_id INT NOT NULL,
    nome_convidado VARCHAR(100) NOT NULL,
    cpf VARCHAR(14),
    FOREIGN KEY (lista_aniversario_id) REFERENCES listas_aniversario(id) ON DELETE CASCADE
);