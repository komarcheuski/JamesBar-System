<?php

use PHPUnit\Framework\TestCase;

class SqlSchemaTest extends TestCase {

    private function sql() {
        $path = __DIR__ . '/../database/jamesbar.sql';

        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    public function testSqlTemDropsNoInicio() {
        $sql = $this->sql();

        $this->assertStringContainsString('DROP DATABASE IF EXISTS db_core', $sql);
        $this->assertStringContainsString('DROP DATABASE IF EXISTS db_listas_vip', $sql);
    }

    public function testSqlCriaBancosNecessarios() {
        $sql = $this->sql();

        $this->assertStringContainsString('CREATE DATABASE db_core', $sql);
        $this->assertStringContainsString('CREATE DATABASE db_listas_vip', $sql);
    }

    public function testSqlTemCamposMfaCriptografado() {
        $sql = $this->sql();

        $this->assertStringContainsString('mfa_secret', $sql);
        $this->assertStringContainsString('mfa_secret_key', $sql);
    }

    public function testSqlTemControleTentativasLogin() {
        $sql = $this->sql();

        $this->assertStringContainsString('tentativas_login', $sql);
        $this->assertStringContainsString('bloqueio_login_until', $sql);
    }

    public function testSqlTemTabelasCaixa() {
        $sql = $this->sql();

        $this->assertStringContainsString('CREATE TABLE usuarios', $sql);
        $this->assertStringContainsString('CREATE TABLE clientes', $sql);
        $this->assertStringContainsString('CREATE TABLE turnos_caixa', $sql);
        $this->assertStringContainsString('CREATE TABLE pausas_caixa', $sql);
        $this->assertStringContainsString('CREATE TABLE movimentacoes', $sql);
    }

    public function testSqlTemTabelasPromoters() {
        $sql = $this->sql();

        $this->assertStringContainsString('CREATE TABLE dias_promoters', $sql);
        $this->assertStringContainsString('CREATE TABLE promoters', $sql);
        $this->assertStringContainsString('CREATE TABLE listas_promoters', $sql);
        $this->assertStringContainsString('CREATE TABLE lista_promoters_convidados', $sql);
        $this->assertStringContainsString('CREATE TABLE lista_promoters_vips', $sql);
    }

    public function testSqlTemListasAniversario() {
        $sql = $this->sql();

        $this->assertStringContainsString('CREATE TABLE listas_aniversario', $sql);
        $this->assertStringContainsString('CREATE TABLE lista_aniversario_convidados', $sql);
    }

    public function testSqlTemTriggerMovimentacoes() {
        $sql = $this->sql();

        $this->assertStringContainsString('CREATE TRIGGER trg_movimentacoes_after_insert', $sql);
    }

    public function testSqlTemEventoApagarListas() {
        $sql = $this->sql();

        $this->assertStringContainsString('CREATE EVENT IF NOT EXISTS apagar_listas_expiradas', $sql);
    }
}