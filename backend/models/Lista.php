<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: Lista.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Model que representa listas de promoter e aniversário usadas no sistema.
*/
class Lista implements JsonSerializable, ArrayAccess {
    private $id;
    private $promoter_id;
    private $dia_id;
    private $data_lista;
    private $created_at;
    private $promoter_nome;
    private $dia_nome;
    private $total_convidados;
    private $total_vips;
    private $aniversariante_nome;
    private $aniversariante_cpf;
    private $data_evento;

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
            'promoter_id' => $this->promoter_id,
            'dia_id' => $this->dia_id,
            'data_lista' => $this->data_lista,
            'created_at' => $this->created_at,
            'promoter_nome' => $this->promoter_nome,
            'dia_nome' => $this->dia_nome,
            'total_convidados' => $this->total_convidados,
            'total_vips' => $this->total_vips,
            'aniversariante_nome' => $this->aniversariante_nome,
            'aniversariante_cpf' => $this->aniversariante_cpf,
            'data_evento' => $this->data_evento
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
    public function getPromoterId() { return $this->promoter_id; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setPromoterId($promoterId) { $this->promoter_id = $promoterId; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getDiaId() { return $this->dia_id; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setDiaId($diaId) { $this->dia_id = $diaId; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getDataLista() { return $this->data_lista; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setDataLista($dataLista) { $this->data_lista = $dataLista; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getCreatedAt() { return $this->created_at; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setCreatedAt($createdAt) { $this->created_at = $createdAt; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getPromoterNome() { return $this->promoter_nome; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setPromoterNome($promoterNome) { $this->promoter_nome = $promoterNome; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getDiaNome() { return $this->dia_nome; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setDiaNome($diaNome) { $this->dia_nome = $diaNome; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getTotalConvidados() { return $this->total_convidados; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setTotalConvidados($totalConvidados) { $this->total_convidados = $totalConvidados; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getTotalVips() { return $this->total_vips; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setTotalVips($totalVips) { $this->total_vips = $totalVips; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getAniversarianteNome() { return $this->aniversariante_nome; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setAniversarianteNome($aniversarianteNome) { $this->aniversariante_nome = $aniversarianteNome; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getAniversarianteCpf() { return $this->aniversariante_cpf; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setAniversarianteCpf($aniversarianteCpf) { $this->aniversariante_cpf = $aniversarianteCpf; }

    /**
     * FUNÇÃO: Retorna o valor do atributo correspondente do model.
     */
    public function getDataEvento() { return $this->data_evento; }
    /**
     * FUNÇÃO: Atualiza o valor do atributo correspondente do model.
     */
    public function setDataEvento($dataEvento) { $this->data_evento = $dataEvento; }
}
