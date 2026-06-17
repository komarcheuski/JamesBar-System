<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: ClienteDAO.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Responsável pela camada de persistência relacionada a Cliente, isolando
| consultas SQL do restante do sistema.
|
| SEGURANÇA APLICADA:
| - Prepared Statements para consultas e cadastro de clientes.
| - Validação indireta de unicidade de CPF pelo banco de dados.
*/
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cliente.php';

class ClienteDAO {

    /**
     * FUNÇÃO: Busca cliente pelo CPF informado usando Prepared Statement.
     * SEGURANÇA: Usa Prepared Statements ou fluxo controlado para reduzir risco de SQL Injection e alteração indevida.
     */
    public function buscarPorCpf($cpf) {
        $conn = Database::conectar();

        $sql = "
            SELECT
                id,
                nome,
                cpf,
                data_aniversario,
                data_cadastro,
                dentro_balada,
                total_entradas
            FROM clientes
            WHERE cpf = :cpf
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->execute();

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ? Cliente::fromArray($dados) : false;
    }

    /**
     * FUNÇÃO: Cadastra novo registro no banco mantendo a persistência isolada no DAO.
     * SEGURANÇA: Usa Prepared Statements ou fluxo controlado para reduzir risco de SQL Injection e alteração indevida.
     */
    public function cadastrar($nome, $cpf, $dataAniversario) {
        $conn = Database::conectar();

        $sql = "
            INSERT INTO clientes (
                nome,
                cpf,
                data_aniversario
            ) VALUES (
                :nome,
                :cpf,
                :data_aniversario
            )
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->bindParam(':data_aniversario', $dataAniversario);
        $stmt->execute();

        return $conn->lastInsertId();
    }
}
