<?php

require_once __DIR__ . '/../config/Database.php';

class MovimentacaoDAO {

    public function registrarEntrada($clienteId, $caixaId) {
        $conn = Database::conectar();

        $sql = "
            INSERT INTO movimentacoes (
                cliente_id,
                caixa_id,
                tipo
            ) VALUES (
                :cliente_id,
                :caixa_id,
                'entrada'
            )
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':cliente_id', $clienteId);
        $stmt->bindParam(':caixa_id', $caixaId);

        return $stmt->execute();
    }

    public function buscarContadores($caixaId) {
        $conn = Database::conectar();

        $sql = "
            SELECT
                SUM(CASE WHEN tipo = 'entrada' THEN 1 ELSE 0 END) AS total_entradas,
                SUM(CASE WHEN tipo = 'saida' THEN 1 ELSE 0 END) AS total_saidas
            FROM movimentacoes
            WHERE caixa_id = :caixa_id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':caixa_id', $caixaId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}