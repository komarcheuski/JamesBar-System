<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: DomainRulesUnitTest.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Teste unitário que valida regras do sistema, requisitos de segurança ou
| qualidade do código.
*/
use PHPUnit\Framework\TestCase;

class DomainRulesUnitTest extends TestCase {

    /**
     * Regra de negócio principal: entrada marca cliente dentro da balada e incrementa total.
     * Saída marca o cliente como fora. Essa regra está garantida por trigger no banco.
     */
    public function testTriggerDeEntradaSaidaAtualizaCliente(): void {
        $sql = file_get_contents(__DIR__ . '/../database/jamesbar.sql');

        $this->assertStringContainsString('CREATE TRIGGER trg_movimentacoes_after_insert', $sql);
        $this->assertStringContainsString("IF NEW.tipo = 'entrada'", $sql);
        $this->assertStringContainsString('dentro_balada = TRUE', $sql);
        $this->assertStringContainsString('total_entradas = total_entradas + 1', $sql);
        $this->assertStringContainsString("ELSEIF NEW.tipo = 'saida'", $sql);
        $this->assertStringContainsString('dentro_balada = FALSE', $sql);
    }

    /**
     * Verifica se os modelos principais expõem getters/setters usados pelos controllers e DAOs.
     */
    public function testModeloClienteRepresentaEntidadeCentral(): void {
        $cliente = new Cliente();
        $cliente->setNome('Maria Oliveira');
        $cliente->setCpf('22222222222');
        $cliente->setDataAniversario('1999-08-20');
        $cliente->setDentroBalada(true);
        $cliente->setTotalEntradas(3);

        $this->assertSame('Maria Oliveira', $cliente->getNome());
        $this->assertSame('22222222222', $cliente->getCpf());
        $this->assertSame('1999-08-20', $cliente->getDataAniversario());
        $this->assertTrue($cliente->getDentroBalada());
        $this->assertSame(3, $cliente->getTotalEntradas());
    }

    /**
     * Listas antigas são removidas por evento do banco, evitando acumular dados desnecessários.
     */
    public function testEventoRemoveListasExpiradas(): void {
        $sql = file_get_contents(__DIR__ . '/../database/jamesbar.sql');

        $this->assertStringContainsString('CREATE EVENT IF NOT EXISTS apagar_listas_expiradas', $sql);
        $this->assertStringContainsString('WHERE data_lista < CURDATE()', $sql);
        $this->assertStringContainsString('WHERE data_evento < CURDATE()', $sql);
    }
}
