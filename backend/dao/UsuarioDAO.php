<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Usuario.php';

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
                tentativas_login,
                bloqueio_login_until,
                mfa_secret,
                mfa_secret_key,
                mfa_ativo,
                created_at
            FROM usuarios
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ? Usuario::fromArray($dados) : false;
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
                tentativas_login,
                bloqueio_login_until,
                mfa_secret,
                mfa_secret_key,
                mfa_ativo,
                created_at
            FROM usuarios
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ? Usuario::fromArray($dados) : false;
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

        $caixas = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $dados) {
            $caixas[] = Usuario::fromArray($dados);
        }

        return $caixas;
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
                primeiro_acesso,
                tentativas_login,
                bloqueio_login_until
            ) VALUES (
                :nome,
                :email,
                :senha_hash,
                'caixa',
                TRUE,
                TRUE,
                0,
                NULL
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
            SET
                mfa_secret = :secret,
                mfa_secret_key = NULL
            WHERE id = :id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':secret', $secret);
        $stmt->bindParam(':id', $usuarioId);

        return $stmt->execute();
    }

    public function salvarMfaSecretCriptografado($usuarioId, $secretCriptografado, $chaveCriptografada) {
        $conn = Database::conectar();

        $sql = "
            UPDATE usuarios
            SET
                mfa_secret = :secret,
                mfa_secret_key = :secret_key
            WHERE id = :id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':secret', $secretCriptografado);
        $stmt->bindParam(':secret_key', $chaveCriptografada);
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

    public function atualizarTentativasLogin($usuarioId, $tentativas) {
        $conn = Database::conectar();

        $sql = "
            UPDATE usuarios
            SET tentativas_login = :tentativas
            WHERE id = :id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':tentativas', $tentativas);
        $stmt->bindParam(':id', $usuarioId);

        return $stmt->execute();
    }

    public function bloquearLoginTemporariamente($usuarioId, $bloqueioAte) {
        $conn = Database::conectar();

        $sql = "
            UPDATE usuarios
            SET
                tentativas_login = 0,
                bloqueio_login_until = :bloqueio
            WHERE id = :id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':bloqueio', $bloqueioAte);
        $stmt->bindParam(':id', $usuarioId);

        return $stmt->execute();
    }

    public function limparTentativasLogin($usuarioId) {
        $conn = Database::conectar();

        $sql = "
            UPDATE usuarios
            SET
                tentativas_login = 0,
                bloqueio_login_until = NULL
            WHERE id = :id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $usuarioId);

        return $stmt->execute();
    }
}