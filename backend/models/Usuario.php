<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: Usuario.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Model que representa usuários do sistema, seus perfis e dados de autenticação.
*/
class Usuario implements JsonSerializable, ArrayAccess {
    private $id;
    private $nome;
    private $email;
    private $senha_hash;
    private $tipo;
    private $ativo;
    private $primeiro_acesso;
    private $tentativas_login;
    private $bloqueio_login_until;
    private $mfa_secret;
    private $mfa_secret_key;
    private $mfa_ativo;
    private $created_at;

    /**
     * FUNÇÃO: Inicializa o objeto com dados recebidos do banco ou da aplicação.
     */
    public function __construct($dados = []) {
        foreach ($dados as $campo => $valor) {
            if (property_exists($this, $campo)) {
                $this->$campo = $valor;
            }
        }
    }

    /**
     * FUNÇÃO: Cria instância do model a partir de array associativo.
     */
    public static function fromArray($dados) {
        return new self(is_array($dados) ? $dados : []);
    }

    /**
     * FUNÇÃO: Converte o model para array, facilitando respostas JSON e testes.
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'senha_hash' => $this->senha_hash,
            'tipo' => $this->tipo,
            'ativo' => $this->ativo,
            'primeiro_acesso' => $this->primeiro_acesso,
            'tentativas_login' => $this->tentativas_login,
            'bloqueio_login_until' => $this->bloqueio_login_until,
            'mfa_secret' => $this->mfa_secret,
            'mfa_secret_key' => $this->mfa_secret_key,
            'mfa_ativo' => $this->mfa_ativo,
            'created_at' => $this->created_at
        ];
    }

    /**
     * FUNÇÃO: Permite serialização segura do model como JSON.
     */
    public function jsonSerialize() {
        return $this->toArray();
    }

    /**
     * FUNÇÃO: Permite acessar propriedades do model como array.
     */
    public function offsetExists($offset): bool {
        return array_key_exists($offset, $this->toArray());
    }

    /**
     * FUNÇÃO: Permite acessar propriedades do model como array.
     */
    public function offsetGet($offset): mixed {
        $dados = $this->toArray();
        return $dados[$offset] ?? null;
    }

    /**
     * FUNÇÃO: Permite acessar propriedades do model como array.
     */
    public function offsetSet($offset, $value): void {
        if (property_exists($this, $offset)) {
            $this->$offset = $value;
        }
    }

    /**
     * FUNÇÃO: Permite acessar propriedades do model como array.
     */
    public function offsetUnset($offset): void {
        if (property_exists($this, $offset)) {
            $this->$offset = null;
        }
    }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getId() { return $this->id; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setId($id) { $this->id = $id; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getNome() { return $this->nome; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setNome($nome) { $this->nome = $nome; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getEmail() { return $this->email; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setEmail($email) { $this->email = $email; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getSenhaHash() { return $this->senha_hash; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setSenhaHash($senhaHash) { $this->senha_hash = $senhaHash; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getTipo() { return $this->tipo; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setTipo($tipo) { $this->tipo = $tipo; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getAtivo() { return $this->ativo; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setAtivo($ativo) { $this->ativo = $ativo; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getPrimeiroAcesso() { return $this->primeiro_acesso; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setPrimeiroAcesso($primeiroAcesso) { $this->primeiro_acesso = $primeiroAcesso; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getTentativasLogin() { return $this->tentativas_login; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setTentativasLogin($tentativasLogin) { $this->tentativas_login = $tentativasLogin; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getBloqueioLoginUntil() { return $this->bloqueio_login_until; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setBloqueioLoginUntil($bloqueioLoginUntil) { $this->bloqueio_login_until = $bloqueioLoginUntil; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getMfaSecret() { return $this->mfa_secret; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setMfaSecret($mfaSecret) { $this->mfa_secret = $mfaSecret; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getMfaSecretKey() { return $this->mfa_secret_key; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setMfaSecretKey($mfaSecretKey) { $this->mfa_secret_key = $mfaSecretKey; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getMfaAtivo() { return $this->mfa_ativo; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setMfaAtivo($mfaAtivo) { $this->mfa_ativo = $mfaAtivo; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getCreatedAt() { return $this->created_at; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setCreatedAt($createdAt) { $this->created_at = $createdAt; }
}
