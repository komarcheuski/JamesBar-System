<?php

require_once __DIR__ . '/../config/Database.php';

class ClienteDAO {

    public function buscarPorCpf($cpf) {
        $conn = Database::conectar();

        $sql = "
            SELECT
                id,
                nome,
                cpf,
                data_aniversario,
                dentro_balada,
                total_entradas
            FROM clientes
            WHERE cpf = :cpf
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
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