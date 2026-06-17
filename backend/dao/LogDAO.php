<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: LogDAO.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Responsável pela camada de persistência relacionada a Log, isolando consultas
| SQL do restante do sistema.
|
| SEGURANÇA APLICADA:
| - Registro de auditoria para ações sensíveis do sistema.
| - Prepared Statements para persistir logs sem montar SQL dinâmico.
*/
require_once __DIR__ . '/../config/Database.php';

class LogDAO {
    /**
     * Requisito de auditoria: registra ações sensíveis em logs_sistema com usuário, ação, descrição e IP.
     */
    public function registrar($usuarioId, $acao, $descricao = '') {
        try {
            $conn = Database::conectar();

            $sql = "
                INSERT INTO logs_sistema (usuario_id, acao, descricao, ip)
                VALUES (:usuario_id, :acao, :descricao, :ip)
            ";

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':usuario_id', $usuarioId ?: null, $usuarioId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':acao', substr((string) $acao, 0, 255));
            $stmt->bindValue(':descricao', (string) $descricao);
            $stmt->bindValue(':ip', substr((string) $ip, 0, 45));

            return $stmt->execute();
        } catch (Throwable $e) {
            error_log('Falha ao registrar log JamesBar: ' . $e->getMessage());
            return false;
        }
    }
}
