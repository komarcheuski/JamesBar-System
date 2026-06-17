<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: AddedSecurityRequirementsTest.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Teste unitário que valida regras do sistema, requisitos de segurança ou
| qualidade do código.
|
| SEGURANÇA APLICADA:
| - Testa os requisitos adicionais implementados no sistema.
*/
use PHPUnit\Framework\TestCase;

class AddedSecurityRequirementsTest extends TestCase {

    public function testSecurityHelperTemPasswordHashECsrf() {
        $codigo = file_get_contents(__DIR__ . '/../security/SecurityHelper.php');

        $this->assertStringContainsString('password_hash', $codigo);
        $this->assertStringContainsString('password_verify', $codigo);
        $this->assertStringContainsString('csrf_token', $codigo);
        $this->assertStringContainsString('hash_equals', $codigo);
    }

    public function testControllersProtegemPostComCsrf() {
        foreach (['AdmController.php', 'CaixaController.php', 'PromoterController.php'] as $arquivo) {
            $codigo = file_get_contents(__DIR__ . '/../controllers/' . $arquivo);
            $this->assertStringContainsString('jb_require_csrf($dados)', $codigo, "$arquivo deve validar CSRF em POST autenticado");
        }
    }

    public function testAuthControllerRegeneraSessaoERegistraAuditoria() {
        $codigo = file_get_contents(__DIR__ . '/../controllers/AuthController.php');

        $this->assertStringContainsString('jb_regenerate_session', $codigo);
        $this->assertStringContainsString('LogDAO', $codigo);
        $this->assertStringContainsString('LOGIN_SUCESSO', $codigo);
        $this->assertStringContainsString('LOGIN_BLOQUEIO', $codigo);
    }

    public function testSessionConfigTemCookiesSeguros() {
        $codigo = file_get_contents(__DIR__ . '/../config/session.php');

        $this->assertStringContainsString('httponly', $codigo);
        $this->assertStringContainsString('samesite', $codigo);
        $this->assertStringContainsString('use_strict_mode', $codigo);
    }

    public function testDatabaseUsaEnvExample() {
        $this->assertFileExists(__DIR__ . '/../.env.example');
        $codigo = file_get_contents(__DIR__ . '/../config/Database.php');

        $this->assertStringContainsString('DB_HOST', $codigo);
        $this->assertStringContainsString('ATTR_EMULATE_PREPARES', $codigo);
    }
}
