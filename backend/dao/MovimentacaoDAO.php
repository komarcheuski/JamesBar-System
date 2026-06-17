<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: MovimentacaoDAO.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Responsável pela camada de persistência relacionada a Movimentacao, isolando
| consultas SQL do restante do sistema.
|
| SEGURANÇA APLICADA:
| - Prepared Statements para registro de entrada e saída.
| - Mantém rastreabilidade da movimentação associada ao caixa autenticado.
*/
require_once __DIR__ . '/../config/Database.php';

class MovimentacaoDAO {

    /**
     * FUNÇÃO: Registra entrada do cliente e associa a movimentação ao caixa autenticado.
     * SEGURANÇA: Usa Prepared Statements ou fluxo controlado para reduzir risco de SQL Injection e alteração indevida.
     */
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

    /**
     * FUNÇÃO: Consulta totais operacionais do caixa para atualizar o dashboard.
     * SEGURANÇA: Usa Prepared Statements ou fluxo controlado para reduzir risco de SQL Injection e alteração indevida.
     */
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
