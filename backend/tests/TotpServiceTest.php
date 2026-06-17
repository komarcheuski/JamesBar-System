<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: TotpServiceTest.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Teste unitário que valida regras do sistema, requisitos de segurança ou
| qualidade do código.
|
| SEGURANÇA APLICADA:
| - Geração e validação de códigos TOTP para MFA do administrador.
| - Uso de segredo MFA protegido antes de ser armazenado no banco.
| - Testa funcionalidades relacionadas ao MFA/TOTP.
*/
use PHPUnit\Framework\TestCase;

class TotpServiceTest extends TestCase {

    public function testGerarSecretRetornaStringCom16Caracteres() {
        $totp = new TotpService();
        $secret = $totp->gerarSecret();

        $this->assertIsString($secret);
        $this->assertEquals(16, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function testGerarQrCodeUrlContemDadosEsperados() {
        $totp = new TotpService();

        $url = $totp->gerarQrCodeUrl(
            'admin@jamesbar.com',
            'JBSWY3DPEHPK3PXP'
        );

        $urlDecodificada = urldecode($url);

        $this->assertStringContainsString('api.qrserver.com', $url);
        $this->assertStringContainsString('otpauth://totp', $urlDecodificada);
        $this->assertStringContainsString('JamesBar', $urlDecodificada);
        $this->assertStringContainsString('admin@jamesbar.com', $urlDecodificada);
    }

    public function testCodigoComLetrasRetornaFalse() {
        $totp = new TotpService();

        $this->assertFalse(
            $totp->verificarCodigo('JBSWY3DPEHPK3PXP', 'abc123')
        );
    }

    public function testCodigoComMenosDeSeisDigitosRetornaFalse() {
        $totp = new TotpService();

        $this->assertFalse(
            $totp->verificarCodigo('JBSWY3DPEHPK3PXP', '12345')
        );
    }

    public function testCodigoAtualGeradoPorReflexaoValida() {
        $totp = new TotpService();
        $secret = 'JBSWY3DPEHPK3PXP';

        $metodo = new ReflectionMethod(TotpService::class, 'gerarCodigo');
        $metodo->setAccessible(true);

        $tempoAtual = floor(time() / 30);
        $codigo = $metodo->invoke($totp, $secret, $tempoAtual);

        $this->assertTrue($totp->verificarCodigo($secret, $codigo));
    }
}