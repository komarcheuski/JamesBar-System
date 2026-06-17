<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: FrontendSecurityTest.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Teste unitário que valida regras do sistema, requisitos de segurança ou
| qualidade do código.
|
| SEGURANÇA APLICADA:
| - Testa proteções implementadas no frontend, como autenticação e CSRF.
*/
use PHPUnit\Framework\TestCase;

class FrontendSecurityTest extends TestCase {

    private function rootPath($path) {
        return dirname(__DIR__, 2) . '/' . $path;
    }

    public function testAuthCheckExiste() {
        $this->assertFileExists($this->rootPath('frontend/assets/js/auth_check.js'));
    }

    public function testPaginasProtegidasCarregamAuthCheck() {
        $paginas = [
            'frontend/views/adm/dashboardAdm.html',
            'frontend/views/caixa/dashboardCaixa.html',
            'frontend/views/auth/mfa.html',
            'frontend/views/auth/trocar_senha.html'
        ];

        foreach ($paginas as $pagina) {
            $codigo = file_get_contents($this->rootPath($pagina));
            $this->assertStringContainsString('auth_check.js', $codigo, "$pagina deve carregar auth_check.js");
        }
    }

    public function testLoginNaoCarregaAuthCheck() {
        $codigo = file_get_contents($this->rootPath('frontend/views/auth/login.html'));
        $this->assertStringNotContainsString('auth_check.js', $codigo);
    }

    public function testAuthCheckChamaVerificarSessao() {
        $codigo = file_get_contents($this->rootPath('frontend/assets/js/auth_check.js'));
        $this->assertStringContainsString('verificar_sessao', $codigo);
        $this->assertStringContainsString('AuthController.php', $codigo);
    }

    public function testAuthCheckNaoUsaAcaoInexistenteMfaSessao() {
        $codigo = file_get_contents($this->rootPath('frontend/assets/js/auth_check.js'));
        $this->assertStringNotContainsString('verificar_mfa_sessao', $codigo);
    }
}
