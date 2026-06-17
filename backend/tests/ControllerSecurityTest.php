<?php

use PHPUnit\Framework\TestCase;

class ControllerSecurityTest extends TestCase {

    private function controllerPath($arquivo) {
        return __DIR__ . '/../controllers/' . $arquivo;
    }

    public function testControllersPrincipaisExistem() {
        foreach (['AuthController.php', 'AdmController.php', 'CaixaController.php', 'PromoterController.php'] as $arquivo) {
            $this->assertFileExists($this->controllerPath($arquivo));
        }
    }

    public function testControllersCarregamSecurityHelper() {
        foreach (['AuthController.php', 'AdmController.php', 'CaixaController.php', 'PromoterController.php'] as $arquivo) {
            $codigo = file_get_contents($this->controllerPath($arquivo));
            $this->assertStringContainsString('SecurityHelper.php', $codigo, "$arquivo deve carregar SecurityHelper.php");
        }
    }

    public function testAdmControllerExigeLoginAdm() {
        $codigo = file_get_contents($this->controllerPath('AdmController.php'));
        $this->assertStringContainsString("jb_require_login('adm')", $codigo);
    }

    public function testPromoterControllerExigeLoginAdm() {
        $codigo = file_get_contents($this->controllerPath('PromoterController.php'));
        $this->assertStringContainsString("jb_require_login('adm')", $codigo);
    }

    public function testCaixaControllerExigeLoginCaixa() {
        $codigo = file_get_contents($this->controllerPath('CaixaController.php'));
        $this->assertStringContainsString("jb_require_login('caixa')", $codigo);
    }

    public function testAuthControllerTemVerificacaoDeSessao() {
        $codigo = file_get_contents($this->controllerPath('AuthController.php'));
        $this->assertStringContainsString('verificar_sessao', $codigo);
    }

    public function testAuthControllerTemBloqueioTemporario() {
        $codigo = file_get_contents($this->controllerPath('AuthController.php'));
        $this->assertStringContainsString('bloqueio_login_until', $codigo);
        $this->assertStringContainsString('bloquearLoginTemporariamente', $codigo);
    }

    public function testAuthControllerTemCriptografiaMfa() {
        $codigo = file_get_contents($this->controllerPath('AuthController.php'));
        $this->assertStringContainsString('criptografarMfaSecret', $codigo);
        $this->assertStringContainsString('descriptografarMfaSecret', $codigo);
    }

    public function testControllersDecodificamJsonDoBody() {
        foreach (['AuthController.php', 'AdmController.php', 'CaixaController.php', 'PromoterController.php'] as $arquivo) {
            $codigo = file_get_contents($this->controllerPath($arquivo));
            $this->assertStringContainsString('php://input', $codigo, "$arquivo deve ler JSON do body");
        }
    }
}
