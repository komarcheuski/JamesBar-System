<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: CsrfSessionUnitTest.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Teste unitário que valida regras do sistema, requisitos de segurança ou
| qualidade do código.
|
| SEGURANÇA APLICADA:
| - Testa geração e validação de tokens CSRF e configurações de sessão.
*/
use PHPUnit\Framework\TestCase;

class CsrfSessionUnitTest extends TestCase {

    protected function setUp(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void {
        $_SESSION = [];
    }

    /**
     * Requisito de segurança: toda ação sensível POST deve usar token CSRF.
     * O teste garante que token válido passa e token ausente/inválido falha.
     */
    public function testTokenCsrfValidoEInvalido(): void {
        $token = jb_csrf_token();

        $this->assertNotEmpty($token);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertTrue(jb_validate_csrf($token));
        $this->assertFalse(jb_validate_csrf('token-invalido'));
        $this->assertFalse(jb_validate_csrf(''));
    }

    /**
     * Requisito de segurança: após autenticação o sistema regenera a sessão
     * e troca o token CSRF, reduzindo risco de Session Fixation.
     */
    public function testRegeneracaoDeSessaoAtualizaCsrfToken(): void {
        $tokenAntes = jb_csrf_token();
        jb_regenerate_session();
        $tokenDepois = $_SESSION['csrf_token'] ?? '';

        $this->assertNotEmpty($tokenDepois);
        $this->assertNotSame($tokenAntes, $tokenDepois);
        $this->assertTrue(jb_validate_csrf($tokenDepois));
    }

    /**
     * Teste estático para garantir que os controllers principais chamam o
     * middleware de CSRF nas ações autenticadas.
     */
    public function testControllersChamamValidacaoCsrf(): void {
        foreach (['AdmController.php', 'CaixaController.php', 'PromoterController.php'] as $controller) {
            $codigo = file_get_contents(__DIR__ . '/../controllers/' . $controller);
            $this->assertStringContainsString('jb_require_csrf($dados)', $codigo, "$controller deve validar CSRF.");
        }
    }
}
