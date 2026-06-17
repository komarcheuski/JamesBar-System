<?php

class Cliente implements JsonSerializable, ArrayAccess {
    private $id;
    private $nome;
    private $cpf;
    private $data_aniversario;
    private $data_cadastro;
    private $dentro_balada;
    private $total_entradas;
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
            'cpf' => $this->cpf,
            'data_aniversario' => $this->data_aniversario,
            'data_cadastro' => $this->data_cadastro,
            'dentro_balada' => $this->dentro_balada,
            'total_entradas' => $this->total_entradas,
            'created_at' => $this->created_at
        ];
    }

    public function jsonSerialize(): mixed {
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

    public function getCpf() { return $this->cpf; }
    public function setCpf($cpf) { $this->cpf = $cpf; }

    public function getDataAniversario() { return $this->data_aniversario; }
    public function setDataAniversario($dataAniversario) { $this->data_aniversario = $dataAniversario; }

    public function getDataCadastro() { return $this->data_cadastro; }
    public function setDataCadastro($dataCadastro) { $this->data_cadastro = $dataCadastro; }

    public function getDentroBalada() { return $this->dentro_balada; }
    public function setDentroBalada($dentroBalada) { $this->dentro_balada = $dentroBalada; }

    public function getTotalEntradas() { return $this->total_entradas; }
    public function setTotalEntradas($totalEntradas) { $this->total_entradas = $totalEntradas; }

    public function getCreatedAt() { return $this->created_at; }
    public function setCreatedAt($createdAt) { $this->created_at = $createdAt; }
}