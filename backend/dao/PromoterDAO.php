<?php

require_once __DIR__ . '/../config/Database.php';

class PromoterDAO {

    public function limparListasAntigas() {
        $conn = Database::conectar();

        $conn->exec("
            DELETE FROM db_listas_vip.listas_promoters
            WHERE data_lista < CURDATE()
        ");

        $conn->exec("
            DELETE FROM db_listas_vip.listas_aniversario
            WHERE data_evento < CURDATE()
        ");
    }

    public function listarTodos() {
        $this->limparListasAntigas();

        $conn = Database::conectar();

        $sql = "
            SELECT *
            FROM db_listas_vip.promoters
            ORDER BY nome ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarDias() {
        $conn = Database::conectar();

        $sql = "
            SELECT id, nome
            FROM db_listas_vip.dias_promoters
            ORDER BY id ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cadastrarPromoter($nome, $telefone, $dias) {
        $conn = Database::conectar();

        $sql = "
            INSERT INTO db_listas_vip.promoters (
                nome,
                telefone,
                lista_quarta,
                lista_quinta,
                lista_sexta,
                lista_sabado,
                lista_domingo
            ) VALUES (
                :nome,
                :telefone,
                :quarta,
                :quinta,
                :sexta,
                :sabado,
                :domingo
            )
        ";

        $quarta = in_array('quarta', $dias) ? 1 : 0;
        $quinta = in_array('quinta', $dias) ? 1 : 0;
        $sexta = in_array('sexta', $dias) ? 1 : 0;
        $sabado = in_array('sabado', $dias) ? 1 : 0;
        $domingo = in_array('domingo', $dias) ? 1 : 0;

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':quarta', $quarta);
        $stmt->bindParam(':quinta', $quinta);
        $stmt->bindParam(':sexta', $sexta);
        $stmt->bindParam(':sabado', $sabado);
        $stmt->bindParam(':domingo', $domingo);

        return $stmt->execute();
    }

    public function editarPromoter($id, $nome, $telefone, $dias) {
        $conn = Database::conectar();

        $sql = "
            UPDATE db_listas_vip.promoters
            SET
                nome = :nome,
                telefone = :telefone,
                lista_quarta = :quarta,
                lista_quinta = :quinta,
                lista_sexta = :sexta,
                lista_sabado = :sabado,
                lista_domingo = :domingo
            WHERE id = :id
        ";

        $quarta = in_array('quarta', $dias) ? 1 : 0;
        $quinta = in_array('quinta', $dias) ? 1 : 0;
        $sexta = in_array('sexta', $dias) ? 1 : 0;
        $sabado = in_array('sabado', $dias) ? 1 : 0;
        $domingo = in_array('domingo', $dias) ? 1 : 0;

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':quarta', $quarta);
        $stmt->bindParam(':quinta', $quinta);
        $stmt->bindParam(':sexta', $sexta);
        $stmt->bindParam(':sabado', $sabado);
        $stmt->bindParam(':domingo', $domingo);

        return $stmt->execute();
    }

    public function excluirPromoter($id) {
        $conn = Database::conectar();

        $sql = "
            DELETE FROM db_listas_vip.promoters
            WHERE id = :id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    public function cadastrarListaPromoter($promoterId, $diaId, $dataLista, $convidados, $vips) {
        $this->limparListasAntigas();

        if (count($convidados) > 5) {
            return [
                'success' => false,
                'message' => 'A lista de convidados gratuitos pode ter no máximo 5 nomes.'
            ];
        }

        if (count($vips) > 20) {
            return [
                'success' => false,
                'message' => 'A lista VIP pode ter no máximo 20 nomes.'
            ];
        }

        $conn = Database::conectar();

        try {
            $conn->beginTransaction();

            $sqlLista = "
                INSERT INTO db_listas_vip.listas_promoters (
                    promoter_id,
                    dia_id,
                    data_lista
                ) VALUES (
                    :promoter_id,
                    :dia_id,
                    :data_lista
                )
            ";

            $stmtLista = $conn->prepare($sqlLista);
            $stmtLista->bindParam(':promoter_id', $promoterId);
            $stmtLista->bindParam(':dia_id', $diaId);
            $stmtLista->bindParam(':data_lista', $dataLista);
            $stmtLista->execute();

            $listaId = $conn->lastInsertId();

            $sqlConvidado = "
                INSERT INTO db_listas_vip.lista_promoters_convidados (
                    lista_id,
                    nome_convidado,
                    cpf
                ) VALUES (
                    :lista_id,
                    :nome,
                    :cpf
                )
            ";

            $stmtConvidado = $conn->prepare($sqlConvidado);

            foreach ($convidados as $convidado) {
                $nome = trim($convidado['nome'] ?? '');
                $cpf = trim($convidado['cpf'] ?? '');

                if ($nome !== '') {
                    $stmtConvidado->execute([
                        ':lista_id' => $listaId,
                        ':nome' => $nome,
                        ':cpf' => $cpf
                    ]);
                }
            }

            $sqlVip = "
                INSERT INTO db_listas_vip.lista_promoters_vips (
                    lista_id,
                    nome_vip,
                    cpf
                ) VALUES (
                    :lista_id,
                    :nome,
                    :cpf
                )
            ";

            $stmtVip = $conn->prepare($sqlVip);

            foreach ($vips as $vip) {
                $nome = trim($vip['nome'] ?? '');
                $cpf = trim($vip['cpf'] ?? '');

                if ($nome !== '') {
                    $stmtVip->execute([
                        ':lista_id' => $listaId,
                        ':nome' => $nome,
                        ':cpf' => $cpf
                    ]);
                }
            }

            $conn->commit();

            return [
                'success' => true,
                'message' => 'Lista do promoter cadastrada com sucesso.'
            ];

        } catch (Exception $e) {
            $conn->rollBack();

            return [
                'success' => false,
                'message' => 'Erro ao cadastrar lista. Talvez esse promoter já tenha lista para esse dia e data.'
            ];
        }
    }

    public function cadastrarListaAniversario($nome, $cpf, $dataEvento, $convidados) {
        $this->limparListasAntigas();

        if (count($convidados) > 20) {
            return [
                'success' => false,
                'message' => 'A lista de aniversário pode ter no máximo 20 convidados.'
            ];
        }

        $conn = Database::conectar();

        try {
            $conn->beginTransaction();

            $sqlLista = "
                INSERT INTO db_listas_vip.listas_aniversario (
                    aniversariante_nome,
                    aniversariante_cpf,
                    data_evento
                ) VALUES (
                    :nome,
                    :cpf,
                    :data_evento
                )
            ";

            $stmtLista = $conn->prepare($sqlLista);
            $stmtLista->execute([
                ':nome' => $nome,
                ':cpf' => $cpf,
                ':data_evento' => $dataEvento
            ]);

            $listaId = $conn->lastInsertId();

            $sqlConvidado = "
                INSERT INTO db_listas_vip.lista_aniversario_convidados (
                    lista_aniversario_id,
                    nome_convidado,
                    cpf
                ) VALUES (
                    :lista_id,
                    :nome,
                    :cpf
                )
            ";

            $stmtConvidado = $conn->prepare($sqlConvidado);

            foreach ($convidados as $convidado) {
                $nomeConvidado = trim($convidado['nome'] ?? '');
                $cpfConvidado = trim($convidado['cpf'] ?? '');

                if ($nomeConvidado !== '') {
                    $stmtConvidado->execute([
                        ':lista_id' => $listaId,
                        ':nome' => $nomeConvidado,
                        ':cpf' => $cpfConvidado
                    ]);
                }
            }

            $conn->commit();

            return [
                'success' => true,
                'message' => 'Lista de aniversário cadastrada com sucesso.'
            ];

        } catch (Exception $e) {
            $conn->rollBack();

            return [
                'success' => false,
                'message' => 'Erro ao cadastrar lista de aniversário.'
            ];
        }
    }

    public function listarListasPromoters($diaId = null) {
        $this->limparListasAntigas();

        $conn = Database::conectar();

        $where = '';

        if ($diaId) {
            $where = 'WHERE lp.dia_id = :dia_id';
        }

        $sql = "
            SELECT
                lp.id,
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

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarListasAniversario() {
        $this->limparListasAntigas();

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

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function statusEnvioPromoters($dataEvento) {
        $conn = Database::conectar();

        $sql = "
            SELECT
                p.id,
                p.nome,
                p.telefone,
                d.id AS dia_id,
                d.nome AS dia_nome,
                CASE
                    WHEN lp.id IS NULL THEN 'nao_enviou'
                    ELSE 'enviou'
                END AS status_envio
            FROM db_listas_vip.promoters p
            INNER JOIN db_listas_vip.dias_promoters d
                ON (
                    (d.nome = 'quarta' AND p.lista_quarta = TRUE) OR
                    (d.nome = 'quinta' AND p.lista_quinta = TRUE) OR
                    (d.nome = 'sexta' AND p.lista_sexta = TRUE) OR
                    (d.nome = 'sabado' AND p.lista_sabado = TRUE) OR
                    (d.nome = 'domingo' AND p.lista_domingo = TRUE)
                )
            LEFT JOIN db_listas_vip.listas_promoters lp
                ON lp.promoter_id = p.id
                AND lp.dia_id = d.id
                AND lp.data_lista = :data_evento
            ORDER BY d.id ASC, p.nome ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':data_evento', $dataEvento);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}