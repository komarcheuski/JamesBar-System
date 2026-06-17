<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: Cliente.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Model que representa clientes cadastrados, CPF, aniversário e estado dentro da
| balada.
*/
class Cliente implements JsonSerializable, ArrayAccess {
    private $id;
    private $nome;
    private $cpf;
    private $data_aniversario;
    private $data_cadastro;
    private $dentro_balada;
    private $total_entradas;
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
            'cpf' => $this->cpf,
            'data_aniversario' => $this->data_aniversario,
            'data_cadastro' => $this->data_cadastro,
            'dentro_balada' => $this->dentro_balada,
            'total_entradas' => $this->total_entradas,
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
    public function getCpf() { return $this->cpf; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setCpf($cpf) { $this->cpf = $cpf; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getDataAniversario() { return $this->data_aniversario; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setDataAniversario($dataAniversario) { $this->data_aniversario = $dataAniversario; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getDataCadastro() { return $this->data_cadastro; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setDataCadastro($dataCadastro) { $this->data_cadastro = $dataCadastro; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getDentroBalada() { return $this->dentro_balada; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setDentroBalada($dentroBalada) { $this->dentro_balada = $dentroBalada; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getTotalEntradas() { return $this->total_entradas; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setTotalEntradas($totalEntradas) { $this->total_entradas = $totalEntradas; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getCreatedAt() { return $this->created_at; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setCreatedAt($createdAt) { $this->created_at = $createdAt; }
}
