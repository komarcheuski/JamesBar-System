<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cliente.php';

class ClienteDAO {

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