<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: Promoter.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Model que representa promotores e os dias em que suas listas ficam
| disponíveis.
*/
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
            'telefone' => $this->telefone,
            'lista_quarta' => $this->lista_quarta,
            'lista_quinta' => $this->lista_quinta,
            'lista_sexta' => $this->lista_sexta,
            'lista_sabado' => $this->lista_sabado,
            'lista_domingo' => $this->lista_domingo,
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
    public function getTelefone() { return $this->telefone; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setTelefone($telefone) { $this->telefone = $telefone; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getListaQuarta() { return $this->lista_quarta; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setListaQuarta($listaQuarta) { $this->lista_quarta = $listaQuarta; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getListaQuinta() { return $this->lista_quinta; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setListaQuinta($listaQuinta) { $this->lista_quinta = $listaQuinta; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getListaSexta() { return $this->lista_sexta; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setListaSexta($listaSexta) { $this->lista_sexta = $listaSexta; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getListaSabado() { return $this->lista_sabado; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setListaSabado($listaSabado) { $this->lista_sabado = $listaSabado; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getListaDomingo() { return $this->lista_domingo; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setListaDomingo($listaDomingo) { $this->lista_domingo = $listaDomingo; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getCreatedAt() { return $this->created_at; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setCreatedAt($createdAt) { $this->created_at = $createdAt; }
}
