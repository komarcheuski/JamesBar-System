<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: DocumentationSecurityRequirementsTest.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Teste unitário que valida regras do sistema, requisitos de segurança ou
| qualidade do código.
|
| SEGURANÇA APLICADA:
| - Testa se a documentação dos requisitos de segurança está presente.
*/
use PHPUnit\Framework\TestCase;

class DocumentationSecurityRequirementsTest extends TestCase {

    public function testArquivoDeRequisitosExiste() {
        $path = __DIR__ . '/../security/SECURITY_REQUIREMENTS.php';
        $this->assertFileExists($path);
    }

    public function testHaPeloMenosDezRequisitosDocumentados() {
        $path = __DIR__ . '/../security/SECURITY_REQUIREMENTS.php';
        $codigo = file_get_contents($path);
        preg_match_all('/REQUISITO DE SEGURANÇA:/u', $codigo, $matches);
        $this->assertGreaterThanOrEqual(10, count($matches[0]));
    }
}
