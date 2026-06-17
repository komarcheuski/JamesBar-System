<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: SecurityHelperTest.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Teste unitário que valida regras do sistema, requisitos de segurança ou
| qualidade do código.
|
| SEGURANÇA APLICADA:
| - Validação de força de senha.
| - Geração e validação de token CSRF.
| - Criptografia e descriptografia de dados sensíveis com chave configurada.
| - Sanitização e validação de entradas utilizadas pelo sistema.
*/
use PHPUnit\Framework\TestCase;

class SecurityHelperTest extends TestCase {

    public function testSanitizarTextoRemoveRiscoXss() {
        $entrada = '<script>alert("xss")</script>';
        $saida = SecurityHelper::sanitizarTexto($entrada);

        $this->assertStringNotContainsString('<script>', $saida);
        $this->assertStringContainsString('&lt;script&gt;', $saida);
    }

    public function testSanitizarCpfMantemApenasNumeros() {
        $this->assertEquals('12345678910', SecurityHelper::sanitizarCpf('123.456.789-10'));
    }

    public function testSanitizarEmail() {
        $this->assertEquals('admin@jamesbar.com', SecurityHelper::sanitizarEmail(' admin@jamesbar.com '));
    }

    public function testValidarNomeCorreto() {
        $this->assertTrue((bool) SecurityHelper::validarNome('André Komarcheuski'));
    }

    public function testRejeitarNomeComScript() {
        $this->assertFalse((bool) SecurityHelper::validarNome('<script>alert(1)</script>'));
    }

    public function testValidarEmailCorreto() {
        $this->assertTrue((bool) SecurityHelper::validarEmail('admin@jamesbar.com'));
    }

    public function testRejeitarEmailInvalido() {
        $this->assertFalse((bool) SecurityHelper::validarEmail('admin-jamesbar'));
    }

    public function testValidarCpfCorreto() {
        $this->assertTrue((bool) SecurityHelper::validarCpf('12345678910'));
    }

    public function testRejeitarCpfComLetras() {
        $this->assertFalse((bool) SecurityHelper::validarCpf('123abc78910'));
    }

    public function testValidarSenhaForte() {
        $this->assertTrue((bool) SecurityHelper::validarSenha('Senha@123'));
    }

    public function testRejeitarSenhaFraca() {
        $this->assertFalse((bool) SecurityHelper::validarSenha('123456'));
    }

    public function testValidarTelefone() {
        $this->assertTrue((bool) SecurityHelper::validarTelefone('(41) 99999-9999'));
    }

    public function testValidarMfa() {
        $this->assertTrue((bool) SecurityHelper::validarMfa('123456'));
    }

    public function testRejeitarMfaComLetra() {
        $this->assertFalse((bool) SecurityHelper::validarMfa('12345a'));
    }

    public function testValidarData() {
        $this->assertTrue((bool) SecurityHelper::validarData('2026-06-17'));
    }

    public function testRejeitarDataFormatoBr() {
        $this->assertFalse((bool) SecurityHelper::validarData('17/06/2026'));
    }

    public function testValidarDiasCorretos() {
        $this->assertTrue(jb_validate_dias(['quarta', 'sexta', 'sabado']));
    }

    public function testRejeitarDiaInvalido() {
        $this->assertFalse(jb_validate_dias(['segunda']));
    }

    public function testSanitizarPessoasLimitaQuantidade() {
        $pessoas = [];

        for ($i = 1; $i <= 10; $i++) {
            $pessoas[] = [
                'nome' => 'Pessoa ' . $i,
                'cpf' => '111.111.111-11'
            ];
        }

        $resultado = jb_sanitize_pessoas($pessoas, 5);

        $this->assertCount(5, $resultado);
        $this->assertEquals('11111111111', $resultado[0]['cpf']);
    }

    public function testCriptografiaMfaSecretFunciona() {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('Extensão OpenSSL não disponível.');
        }

        $secret = 'JBSWY3DPEHPK3PXP';

        $criptografado = SecurityHelper::criptografarMfaSecret($secret);

        $this->assertArrayHasKey('mfa_secret', $criptografado);
        $this->assertArrayHasKey('mfa_secret_key', $criptografado);
        $this->assertNotEquals($secret, $criptografado['mfa_secret']);

        $descriptografado = SecurityHelper::descriptografarMfaSecret(
            $criptografado['mfa_secret'],
            $criptografado['mfa_secret_key']
        );

        $this->assertEquals($secret, $descriptografado);
    }
}