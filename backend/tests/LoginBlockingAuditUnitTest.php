<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: LoginBlockingAuditUnitTest.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Teste unitário que valida regras do sistema, requisitos de segurança ou
| qualidade do código.
|
| SEGURANÇA APLICADA:
| - Testa regras de bloqueio de login e estrutura de auditoria.
*/
use PHPUnit\Framework\TestCase;

class LoginBlockingAuditUnitTest extends TestCase {

    /**
     * Teste estático do requisito de bloqueio: após falhas de login, o AuthController
     * precisa incrementar tentativas e gravar bloqueio_login_until.
     */
    public function testAuthControllerImplementaBloqueioPorTentativas(): void {
        $codigo = file_get_contents(__DIR__ . '/../controllers/AuthController.php');

        $this->assertStringContainsString('tentativas_login', $codigo);
        $this->assertStringContainsString('bloqueio_login_until', $codigo);
        $this->assertMatchesRegularExpression('/>=\s*5|>\s*4/', $codigo, 'O bloqueio deve ocorrer na quinta tentativa.');
        $this->assertStringContainsString('LOGIN_BLOQUEIO', $codigo);
    }

    /**
     * O banco deve possuir campos que sustentam o requisito de força bruta:
     * contador de tentativas e timestamp de desbloqueio.
     */
    public function testSchemaTemCamposDeBloqueioDeLogin(): void {
        $sql = file_get_contents(__DIR__ . '/../database/jamesbar.sql');

        $this->assertStringContainsString('tentativas_login INT NOT NULL DEFAULT 0', $sql);
        $this->assertStringContainsString('bloqueio_login_until TIMESTAMP NULL DEFAULT NULL', $sql);
    }

    /**
     * Requisito de auditoria: existe DAO e tabela para registrar ações relevantes,
     * como login, falha, bloqueio, criação/exclusão de caixas e turnos.
     */
    public function testAuditoriaTemDaoTabelaEAcoesNosControllers(): void {
        $sql = file_get_contents(__DIR__ . '/../database/jamesbar.sql');
        $logDao = file_get_contents(__DIR__ . '/../dao/LogDAO.php');
        $auth = file_get_contents(__DIR__ . '/../controllers/AuthController.php');
        $adm = file_get_contents(__DIR__ . '/../controllers/AdmController.php');

        $this->assertStringContainsString('CREATE TABLE logs_sistema', $sql);
        $this->assertStringContainsString('INSERT INTO logs_sistema', $logDao);
        $this->assertStringContainsString('REMOTE_ADDR', $logDao);
        $this->assertStringContainsString('LOGIN_SUCESSO', $auth);
        $this->assertStringContainsString('LOGIN_FALHA', $auth);
        $this->assertStringContainsString('LogDAO', $adm);
    }
}
