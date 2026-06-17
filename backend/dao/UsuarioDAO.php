<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: UsuarioDAO.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Responsável pela camada de persistência relacionada a Usuario, isolando
| consultas SQL do restante do sistema.
|
| SEGURANÇA APLICADA:
| - Prepared Statements em todas as consultas para prevenção de SQL Injection.
| - Atualização de tentativas de login e bloqueio da conta.
| - Persistência de hash seguro de senha no campo senha_hash.
*/
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../security/SecurityHelper.php';

class UsuarioDAO {

    /**
     * FUNÇÃO: Busca usuário por e-mail para autenticação usando Prepared Statement.
     * SEGURANÇA: Usa Prepared Statements ou fluxo controlado para reduzir risco de SQL Injection e alteração indevida.
     */
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

    /**
     * FUNÇÃO: Busca um registro específico pelo identificador usando parâmetro preparado.
     * SEGURANÇA: Usa Prepared Statements ou fluxo controlado para reduzir risco de SQL Injection e alteração indevida.
     */
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

    /**
     * FUNÇÃO: Lista usuários do tipo caixa para gestão administrativa.
     * SEGURANÇA: Usa Prepared Statements ou fluxo controlado para reduzir risco de SQL Injection e alteração indevida.
     */
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

    /**
     * FUNÇÃO: Cadastra operador de caixa com senha protegida por hash seguro.
     * SEGURANÇA: Apoia o requisito de senha forte e armazenamento seguro de credenciais.
     */
    public function cadastrarCaixa($nome, $email, $senha) {
        $conn = Database::conectar();

        $senhaHash = jb_hash_senha($senha);

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

    /**
     * FUNÇÃO: Desativa operador sem apagar o histórico associado.
     * SEGURANÇA: Usa Prepared Statements ou fluxo controlado para reduzir risco de SQL Injection e alteração indevida.
     */
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

    /**
     * FUNÇÃO: Atualiza senha no primeiro acesso e remove obrigatoriedade de troca.
     * SEGURANÇA: Apoia o requisito de senha forte e armazenamento seguro de credenciais.
     */
    public function trocarSenhaPrimeiroAcesso($usuarioId, $novaSenha) {
        $conn = Database::conectar();

        $senhaHash = jb_hash_senha($novaSenha);

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

    /**
     * FUNÇÃO: Salva segredo MFA do administrador.
     * SEGURANÇA: Usa Prepared Statements ou fluxo controlado para reduzir risco de SQL Injection e alteração indevida.
     */
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

    /**
     * FUNÇÃO: Salva segredo MFA cifrado e chave protegida no banco.
     * SEGURANÇA: Apoia criptografia de dado sensível antes do armazenamento.
     */
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

    /**
     * FUNÇÃO: Ativa MFA para o usuário administrador.
     * SEGURANÇA: Apoia o MFA/TOTP do administrador.
     */
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

    /**
     * FUNÇÃO: Atualiza contador de falhas de login para controle de força bruta.
     * SEGURANÇA: Apoia controles de segurança contra uso indevido de sessão ou força bruta.
     */
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

    /**
     * FUNÇÃO: Define bloqueio temporário após excesso de tentativas inválidas.
     * SEGURANÇA: Apoia controles de segurança contra uso indevido de sessão ou força bruta.
     */
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

    /**
     * FUNÇÃO: Zera tentativas após autenticação válida.
     * SEGURANÇA: Apoia controles de segurança contra uso indevido de sessão ou força bruta.
     */
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

    /**
     * FUNÇÃO: Atualiza o hash de senha armazenado para formato seguro.
     * SEGURANÇA: Apoia o requisito de senha forte e armazenamento seguro de credenciais.
     */
    public function atualizarSenhaHash($usuarioId, $senhaHash) {
        $conn = Database::conectar();

        $sql = "
            UPDATE usuarios
            SET senha_hash = :senha_hash
            WHERE id = :id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':senha_hash', $senhaHash);
        $stmt->bindParam(':id', $usuarioId);

        return $stmt->execute();
    }

}
