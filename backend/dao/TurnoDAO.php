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