<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: ValidationRulesUnitTest.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Teste unitário que valida regras do sistema, requisitos de segurança ou
| qualidade do código.
*/
use PHPUnit\Framework\TestCase;

class ValidationRulesUnitTest extends TestCase {

    /**
     * CPF é a chave operacional de busca/cadastro de cliente. O teste garante
     * que o sistema remove máscara e só aceita 11 dígitos antes de consultar o banco.
     */
    public function testCpfSanitizadoEValidado(): void {
        $this->assertSame('11111111111', jb_sanitize_cpf('111.111.111-11'));
        $this->assertSame(1, jb_validate_cpf('11111111111'));
        $this->assertSame(0, jb_validate_cpf('111'));
        $this->assertSame(0, jb_validate_cpf('abc11111111'));
    }

    /**
     * Validações simples reduzem bypass de entrada no frontend e no backend.
     */
    public function testEmailMfaDataENome(): void {
        $this->assertNotFalse(jb_validate_email('admin@jamesbar.com'));
        $this->assertFalse(jb_validate_email('email-invalido'));
        $this->assertSame(1, SecurityHelper::validarMfa('123456'));
        $this->assertSame(0, SecurityHelper::validarMfa('12345a'));
        $this->assertSame(1, jb_validate_date('2026-06-17'));
        $this->assertSame(0, jb_validate_date('17/06/2026'));
        $this->assertSame(1, jb_validate_nome('João da Silva'));
    }

    /**
     * A sanitização de texto deve neutralizar tags HTML/JS antes de exibir dados.
     */
    public function testSanitizacaoTextoEvitaHtmlCru(): void {
        $texto = jb_sanitize_text('<script>alert(1)</script> André', 200);

        $this->assertStringNotContainsString('<script>', $texto);
        $this->assertStringContainsString('&lt;script&gt;', $texto);
    }
}
