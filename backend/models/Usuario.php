<?php

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

    public function __construct($dados = []) {
        foreach ($dados as $campo => $valor) {
            if (property_exists($this, $campo)) {
                $this->$campo = $valor;
            }
        }
    }

    public static function fromArray($dados) {
        return new self(is_array($dados) ? $dados : []);
    }

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

    public function jsonSerialize() {
        return $this->toArray();
    }

    public function offsetExists($offset): bool {
        return array_key_exists($offset, $this->toArray());
    }

    public function offsetGet($offset): mixed {
        $dados = $this->toArray();
        return $dados[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void {
        if (property_exists($this, $offset)) {
            $this->$offset = $value;
        }
    }

    public function offsetUnset($offset): void {
        if (property_exists($this, $offset)) {
            $this->$offset = null;
        }
    }

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getNome() { return $this->nome; }
    public function setNome($nome) { $this->nome = $nome; }

    public function getEmail() { return $this->email; }
    public function setEmail($email) { $this->email = $email; }

    public function getSenhaHash() { return $this->senha_hash; }
    public function setSenhaHash($senhaHash) { $this->senha_hash = $senhaHash; }

    public function getTipo() { return $this->tipo; }
    public function setTipo($tipo) { $this->tipo = $tipo; }

    public function getAtivo() { return $this->ativo; }
    public function setAtivo($ativo) { $this->ativo = $ativo; }

    public function getPrimeiroAcesso() { return $this->primeiro_acesso; }
    public function setPrimeiroAcesso($primeiroAcesso) { $this->primeiro_acesso = $primeiroAcesso; }

    public function getTentativasLogin() { return $this->tentativas_login; }
    public function setTentativasLogin($tentativasLogin) { $this->tentativas_login = $tentativasLogin; }

    public function getBloqueioLoginUntil() { return $this->bloqueio_login_until; }
    public function setBloqueioLoginUntil($bloqueioLoginUntil) { $this->bloqueio_login_until = $bloqueioLoginUntil; }

    public function getMfaSecret() { return $this->mfa_secret; }
    public function setMfaSecret($mfaSecret) { $this->mfa_secret = $mfaSecret; }

    public function getMfaSecretKey() { return $this->mfa_secret_key; }
    public function setMfaSecretKey($mfaSecretKey) { $this->mfa_secret_key = $mfaSecretKey; }

    public function getMfaAtivo() { return $this->mfa_ativo; }
    public function setMfaAtivo($mfaAtivo) { $this->mfa_ativo = $mfaAtivo; }

    public function getCreatedAt() { return $this->created_at; }
    public function setCreatedAt($createdAt) { $this->created_at = $createdAt; }
}