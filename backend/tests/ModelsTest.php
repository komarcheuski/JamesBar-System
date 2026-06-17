<?php

use PHPUnit\Framework\TestCase;

class ModelsTest extends TestCase {

    public function testUsuarioArrayAccessEJson() {
        $usuario = Usuario::fromArray([
            'id' => 1,
            'nome' => 'Administrador',
            'email' => 'admin@jamesbar.com',
            'tipo' => 'adm',
            'ativo' => 1
        ]);

        $this->assertEquals('Administrador', $usuario['nome']);
        $this->assertEquals('admin@jamesbar.com', $usuario->getEmail());
        $this->assertJson(json_encode($usuario));
    }

    public function testClienteArrayAccessEJson() {
        $cliente = Cliente::fromArray([
            'id' => 1,
            'nome' => 'João da Silva',
            'cpf' => '11111111111',
            'total_entradas' => 3
        ]);

        $this->assertEquals('João da Silva', $cliente['nome']);
        $this->assertEquals(3, $cliente['total_entradas']);
        $this->assertJson(json_encode($cliente));
    }

    public function testPromoterArrayAccessEJson() {
        $promoter = Promoter::fromArray([
            'id' => 1,
            'nome' => 'Promoter Teste',
            'telefone' => '41999999999',
            'lista_sexta' => 1
        ]);

        $this->assertEquals('Promoter Teste', $promoter['nome']);
        $this->assertEquals(1, $promoter['lista_sexta']);
        $this->assertJson(json_encode($promoter));
    }

    public function testListaArrayAccessEJson() {
        $lista = Lista::fromArray([
            'id' => 1,
            'promoter_nome' => 'Promoter Teste',
            'dia_nome' => 'sexta',
            'total_convidados' => 5,
            'total_vips' => 20
        ]);

        $this->assertEquals('Promoter Teste', $lista['promoter_nome']);
        $this->assertEquals(20, $lista->getTotalVips());
        $this->assertJson(json_encode($lista));
    }

    public function testSettersUsuario() {
        $usuario = new Usuario();
        $usuario->setNome('Caixa 01');
        $usuario->setEmail('caixa01@jamesbar.com');

        $this->assertEquals('Caixa 01', $usuario->getNome());
        $this->assertEquals('caixa01@jamesbar.com', $usuario->getEmail());
    }

    public function testOffsetSetFunciona() {
        $cliente = new Cliente();
        $cliente['nome'] = 'Maria Oliveira';

        $this->assertEquals('Maria Oliveira', $cliente->getNome());
    }
}