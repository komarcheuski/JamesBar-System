<?php

require_once __DIR__ . '/../config/Database.php';

class UsuarioDAO {

    public function buscarPorEmail($email) {
        $conn = Database::conectar();

        $sql = "
            SELECT
                id,
                nome,
                email,
                senha_hash,
                tipo,
                ativo,
                primeiro_acesso,
                mfa_secret,
                mfa_ativo
            FROM usuarios
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscarPorId($id) {
        $conn = Database::conectar();

        $sql = "
            SELECT
                id,
                nome,
                email,
                senha_hash,
                tipo,
                ativo,
                primeiro_acesso,
                mfa_secret,
                mfa_ativo
            FROM usuarios
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listarCaixas() {
        $conn = Database::conectar();

        $sql = "
            SELECT
                id,
                nome,
                email,
                ativo,
                primeiro_acesso,
                created_at
            FROM usuarios
            WHERE tipo = 'caixa'
            ORDER BY nome ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cadastrarCaixa($nome, $email, $senha) {
        $conn = Database::conectar();

        $senhaHash = md5($senha);

        $sql = "
            INSERT INTO usuarios (
                nome,
                email,
                senha_hash,
                tipo,
                ativo,
                primeiro_acesso
            ) VALUES (
                :nome,
                :email,
                :senha_hash,
                'caixa',
                TRUE,
                TRUE
            )
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha_hash', $senhaHash);

        return $stmt->execute();
    }

    public function desativarCaixa($id) {
        $conn = Database::conectar();

        $sql = "
            UPDATE usuarios
            SET ativo = FALSE
            WHERE id = :id
            AND tipo = 'caixa'
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    public function trocarSenhaPrimeiroAcesso($usuarioId, $novaSenha) {
        $conn = Database::conectar();

        $senhaHash = md5($novaSenha);

        $sql = "
            UPDATE usuarios
            SET
                senha_hash = :senha_hash,
                primeiro_acesso = FALSE
            WHERE id = :id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':senha_hash', $senhaHash);
        $stmt->bindParam(':id', $usuarioId);

        return $stmt->execute();
    }

    public function salvarMfaSecret($usuarioId, $secret) {
        $conn = Database::conectar();

        $sql = "
            UPDATE usuarios
            SET mfa_secret = :secret
            WHERE id = :id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':secret', $secret);
        $stmt->bindParam(':id', $usuarioId);

        return $stmt->execute();
    }

    public function ativarMfa($usuarioId) {
        $conn = Database::conectar();

        $sql = "
            UPDATE usuarios
            SET mfa_ativo = TRUE
            WHERE id = :id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $usuarioId);

        return $stmt->execute();
    }
}