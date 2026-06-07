CREATE DATABASE luxestay;

USE luxestay;

CREATE TABLE usuario (
	id_usuario INTEGER PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255),
    email VARCHAR(255) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'cliente') DEFAULT 'cliente'
);

CREATE TABLE hospedes (
    id_hospede INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    telefone VARCHAR(20),
    email VARCHAR(100)
);

CREATE TABLE funcionarios (
    id_funcionario INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    cargo VARCHAR(50) NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(100),
    status VARCHAR(30) NOT NULL
);

CREATE TABLE quartos (
    id_quarto INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(10) NOT NULL UNIQUE,
    andar INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    valor_diaria DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL
);

CREATE TABLE reservas (
    id_reserva INT PRIMARY KEY AUTO_INCREMENT,
    id_hospede INT NOT NULL,
    id_quarto INT NOT NULL,
    data_entrada DATE NOT NULL,
    data_saida DATE NOT NULL,
    status VARCHAR(30) NOT NULL,
    valor_total DECIMAL(10,2),

    CONSTRAINT fk_reserva_hospede
        FOREIGN KEY (id_hospede)
        REFERENCES hospedes(id_hospede),

    CONSTRAINT fk_reserva_quarto
        FOREIGN KEY (id_quarto)
        REFERENCES quartos(id_quarto)
);

CREATE TABLE solicitacoes_limpeza (
    id_solicitacao INT PRIMARY KEY AUTO_INCREMENT,
    id_quarto INT NOT NULL,
    id_funcionario INT,
    data_solicitacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(30) NOT NULL,
    observacao TEXT,

    CONSTRAINT fk_limpeza_quarto
        FOREIGN KEY (id_quarto)
        REFERENCES quartos(id_quarto),

    CONSTRAINT fk_limpeza_funcionario
        FOREIGN KEY (id_funcionario)
        REFERENCES funcionarios(id_funcionario)
);

INSERT INTO usuario (nome, email, senha, tipo)
VALUES
(
    'Administrador',
    'admin@luxestay.com',
    MD5('admin123'),
    'admin'
),
(
    'Lucas Barbosa Dos Reis',
    'lucas@luxestay.com',
    MD5('lucas123'),
    'cliente'
),
(
    'Kaiky',
    'user@luxestay.com',
    MD5('user123'),
    'cliente'
);

SELECT * FROM quartos;

DROP TABLE usuario;

TRUNCATE quartos;

DROP DATABASE luxestay;