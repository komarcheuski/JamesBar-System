<?php

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

    public function getPromoterId() { return $this->promoter_id; }
    public function setPromoterId($promoterId) { $this->promoter_id = $promoterId; }

    public function getDiaId() { return $this->dia_id; }
    public function setDiaId($diaId) { $this->dia_id = $diaId; }

    public function getDataLista() { return $this->data_lista; }
    public function setDataLista($dataLista) { $this->data_lista = $dataLista; }

    public function getCreatedAt() { return $this->created_at; }
    public function setCreatedAt($createdAt) { $this->created_at = $createdAt; }

    public function getPromoterNome() { return $this->promoter_nome; }
    public function setPromoterNome($promoterNome) { $this->promoter_nome = $promoterNome; }

    public function getDiaNome() { return $this->dia_nome; }
    public function setDiaNome($diaNome) { $this->dia_nome = $diaNome; }

    public function getTotalConvidados() { return $this->total_convidados; }
    public function setTotalConvidados($totalConvidados) { $this->total_convidados = $totalConvidados; }

    public function getTotalVips() { return $this->total_vips; }
    public function setTotalVips($totalVips) { $this->total_vips = $totalVips; }

    public function getAniversarianteNome() { return $this->aniversariante_nome; }
    public function setAniversarianteNome($aniversarianteNome) { $this->aniversariante_nome = $aniversarianteNome; }

    public function getAniversarianteCpf() { return $this->aniversariante_cpf; }
    public function setAniversarianteCpf($aniversarianteCpf) { $this->aniversariante_cpf = $aniversarianteCpf; }

    public function getDataEvento() { return $this->data_evento; }
    public function setDataEvento($dataEvento) { $this->data_evento = $dataEvento; }
}