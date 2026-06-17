<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: ListaAniversarioDAO.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Responsável pela camada de persistência relacionada a ListaAniversario,
| isolando consultas SQL do restante do sistema.
|
| SEGURANÇA APLICADA:
| - Prepared Statements para operações em listas de aniversário.
*/
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Lista.php';

class ListaAniversarioDAO {

    /**
     * FUNÇÃO: Lista registros para exibição no painel administrativo.
     * SEGURANÇA: Usa Prepared Statements ou fluxo controlado para reduzir risco de SQL Injection e alteração indevida.
     */
    public function listarTodas() {
        $conn = Database::conectar();

        $sql = "
            SELECT
                la.id,
                la.aniversariante_nome,
                la.aniversariante_cpf,
                la.data_evento,
                la.created_at,
                (
                    SELECT COUNT(*)
                    FROM db_listas_vip.lista_aniversario_convidados c
                    WHERE c.lista_aniversario_id = la.id
                ) AS total_convidados
            FROM db_listas_vip.listas_aniversario la
            ORDER BY la.data_evento ASC
        ";

        $stmt = $conn->prepare($sql);
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
                id,
                aniversariante_nome,
                aniversariante_cpf,
                data_evento,
                created_at
            FROM db_listas_vip.listas_aniversario
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ? Lista::fromArray($dados) : false;
    }
}
