<?php

require_once __DIR__ . '/../config/Database.php';

class TurnoDAO {

    public function buscarTurnoHoje($usuarioId) {
        $conn = Database::conectar();

        $sql = "
            SELECT *
            FROM turnos_caixa
            WHERE usuario_id = :usuario_id
            AND data_turno = CURDATE()
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuarioId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function abrirOuRetomarTurno($usuarioId) {
        $conn = Database::conectar();

        $turno = $this->buscarTurnoHoje($usuarioId);

        if (!$turno) {
            $sql = "
                INSERT INTO turnos_caixa (
                    usuario_id,
                    data_turno,
                    status,
                    aberto_em
                ) VALUES (
                    :usuario_id,
                    CURDATE(),
                    'aberto',
                    NOW()
                )
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuarioId);
            return $stmt->execute();
        }

        if ($turno['status'] === 'pausado') {
            $this->finalizarUltimaPausa($turno['id']);

            $sql = "
                UPDATE turnos_caixa
                SET status = 'aberto'
                WHERE id = :id
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $turno['id']);
            return $stmt->execute();
        }

        return true;
    }

    public function pausarTurno($usuarioId) {
        $conn = Database::conectar();

        $turno = $this->buscarTurnoHoje($usuarioId);

        if (!$turno || $turno['status'] !== 'aberto') {
            return false;
        }

        $sqlTurno = "
            UPDATE turnos_caixa
            SET
                status = 'pausado',
                pausado_em = NOW(),
                total_pausas = total_pausas + 1
            WHERE id = :turno_id
        ";

        $stmtTurno = $conn->prepare($sqlTurno);
        $stmtTurno->bindParam(':turno_id', $turno['id']);
        $stmtTurno->execute();

        $sqlPausa = "
            INSERT INTO pausas_caixa (
                turno_id,
                inicio_pausa
            ) VALUES (
                :turno_id,
                NOW()
            )
        ";

        $stmtPausa = $conn->prepare($sqlPausa);
        $stmtPausa->bindParam(':turno_id', $turno['id']);

        return $stmtPausa->execute();
    }

    public function fecharTurno($usuarioId) {
        $conn = Database::conectar();

        $turno = $this->buscarTurnoHoje($usuarioId);

        if (!$turno) {
            return false;
        }

        if ($turno['status'] === 'pausado') {
            $this->finalizarUltimaPausa($turno['id']);
        }

        $sql = "
            UPDATE turnos_caixa
            SET
                status = 'fechado',
                fechado_em = NOW()
            WHERE id = :turno_id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turno_id', $turno['id']);

        return $stmt->execute();
    }

    public function buscarResumoOperacional($usuarioId) {
        $conn = Database::conectar();

        $sql = "
            SELECT
                COALESCE(SUM(CASE WHEN m.tipo = 'entrada' THEN 1 ELSE 0 END), 0) AS total_entradas,
                COALESCE(SUM(CASE WHEN m.tipo = 'saida' THEN 1 ELSE 0 END), 0) AS total_saidas,
                COALESCE(t.total_pausas, 0) AS total_pausas
            FROM usuarios u
            LEFT JOIN movimentacoes m
                ON m.caixa_id = u.id
                AND DATE(m.data_hora) = CURDATE()
            LEFT JOIN turnos_caixa t
                ON t.usuario_id = u.id
                AND t.data_turno = CURDATE()
            WHERE u.id = :usuario_id
            GROUP BY u.id, t.total_pausas
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuarioId);
        $stmt->execute();

        $resumo = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resumo ?: [
            'total_entradas' => 0,
            'total_saidas' => 0,
            'total_pausas' => 0
        ];
    }

    public function listarPausasDoTurno($turnoId) {
        $conn = Database::conectar();

        $sql = "
            SELECT
                inicio_pausa,
                fim_pausa
            FROM pausas_caixa
            WHERE turno_id = :turno_id
            ORDER BY inicio_pausa ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turno_id', $turnoId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function finalizarUltimaPausa($turnoId) {
        $conn = Database::conectar();

        $sql = "
            UPDATE pausas_caixa
            SET fim_pausa = NOW()
            WHERE turno_id = :turno_id
            AND fim_pausa IS NULL
            ORDER BY id DESC
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turno_id', $turnoId);

        return $stmt->execute();
    }
}