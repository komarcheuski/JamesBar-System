<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: ListaPromoterDAO.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Responsável pela camada de persistência relacionada a ListaPromoter, isolando
| consultas SQL do restante do sistema.
|
| SEGURANÇA APLICADA:
| - Prepared Statements para operações em listas de promoters.
| - Prepared Statements para operações de promoters.
*/
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Lista.php';

class ListaPromoterDAO {

    /**
     * FUNÇÃO: Lista registros para exibição no painel administrativo.
     * SEGURANÇA: Usa Prepared Statements ou fluxo controlado para reduzir risco de SQL Injection e alteração indevida.
     */
    public function listarTodas($diaId = null) {
        $conn = Database::conectar();

        $where = '';

        if ($diaId) {
            $where = 'WHERE lp.dia_id = :dia_id';
        }

        $sql = "
            SELECT
                lp.id,
                lp.promoter_id,
                lp.dia_id,
                lp.data_lista,
                lp.created_at,
                p.nome AS promoter_nome,
                d.nome AS dia_nome,
                (
                    SELECT COUNT(*)
                    FROM db_listas_vip.lista_promoters_convidados c
                    WHERE c.lista_id = lp.id
                ) AS total_convidados,
                (
                    SELECT COUNT(*)
                    FROM db_listas_vip.lista_promoters_vips v
                    WHERE v.lista_id = lp.id
                ) AS total_vips
            FROM db_listas_vip.listas_promoters lp
            INNER JOIN db_listas_vip.promoters p ON lp.promoter_id = p.id
            INNER JOIN db_listas_vip.dias_promoters d ON lp.dia_id = d.id
            $where
            ORDER BY lp.data_lista ASC, p.nome ASC
        ";

        $stmt = $conn->prepare($sql);

        if ($diaId) {
            $stmt->bindParam(':dia_id', $diaId);
        }

        $stmt->execute();

        $listas = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $dados) {
            $listas[] = Lista::fromArray($dados);
        }

        return $listas;
    }

    /**
     * FUNÇÃO: Busca um registro específico pelo identificador usando parâmetro preparado.
     * SEGURANÇA: Usa Prepared Statements ou fluxo controlado para reduzir risco de SQL Injection e alteração indevida.
     */
    public function buscarPorId($id) {
        $conn = Database::conectar();

        $sql = "
            SELECT
                lp.id,
                lp.promoter_id,
                lp.dia_id,
                lp.data_lista,
                lp.created_at,
                p.nome AS promoter_nome,
                d.nome AS dia_nome
            FROM db_listas_vip.listas_promoters lp
            INNER JOIN db_listas_vip.promoters p ON lp.promoter_id = p.id
            INNER JOIN db_listas_vip.dias_promoters d ON lp.dia_id = d.id
            WHERE lp.id = :id
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ? Lista::fromArray($dados) : false;
    }
}
