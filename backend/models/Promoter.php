<?php

class Promoter implements JsonSerializable, ArrayAccess {
    private $id;
    private $nome;
    private $telefone;
    private $lista_quarta;
    private $lista_quinta;
    private $lista_sexta;
    private $lista_sabado;
    private $lista_domingo;
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
            'telefone' => $this->telefone,
            'lista_quarta' => $this->lista_quarta,
            'lista_quinta' => $this->lista_quinta,
            'lista_sexta' => $this->lista_sexta,
            'lista_sabado' => $this->lista_sabado,
            'lista_domingo' => $this->lista_domingo,
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

    public function getTelefone() { return $this->telefone; }
    public function setTelefone($telefone) { $this->telefone = $telefone; }

    public function getListaQuarta() { return $this->lista_quarta; }
    public function setListaQuarta($listaQuarta) { $this->lista_quarta = $listaQuarta; }

    public function getListaQuinta() { return $this->lista_quinta; }
    public function setListaQuinta($listaQuinta) { $this->lista_quinta = $listaQuinta; }

    public function getListaSexta() { return $this->lista_sexta; }
    public function setListaSexta($listaSexta) { $this->lista_sexta = $listaSexta; }

    public function getListaSabado() { return $this->lista_sabado; }
    public function setListaSabado($listaSabado) { $this->lista_sabado = $listaSabado; }

    public function getListaDomingo() { return $this->lista_domingo; }
    public function setListaDomingo($listaDomingo) { $this->lista_domingo = $listaDomingo; }

    public function getCreatedAt() { return $this->created_at; }
    public function setCreatedAt($createdAt) { $this->created_at = $createdAt; }
}