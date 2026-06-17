<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: PasswordSecurityUnitTest.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Teste unitário que valida regras do sistema, requisitos de segurança ou
| qualidade do código.
|
| SEGURANÇA APLICADA:
| - Testa hash seguro e verificação correta de senhas.
*/
use PHPUnit\Framework\TestCase;

class PasswordSecurityUnitTest extends TestCase {

    /**
     * Verifica se o helper do sistema gera bcrypt/Argon via password_hash,
     * em vez de MD5 ou hash simples. Esse teste apoia a defesa do requisito
     * de armazenamento seguro de credenciais.
     */
    public function testHashSeguroAceitaSenhaCorretaERejeitaSenhaErrada(): void {
        $hash = jb_hash_senha('Senha@123');

        $this->assertTrue(password_get_info($hash)['algo'] !== 0, 'O hash deve ser gerado pela API password_hash.');
        $this->assertTrue(jb_verify_senha('Senha@123', $hash));
        $this->assertFalse(jb_verify_senha('SenhaErrada@123', $hash));
        $this->assertNotEquals(md5('Senha@123'), $hash, 'O sistema não deve salvar senha em MD5.');
    }

    /**
     * Confere se os usuários iniciais do SQL já nascem com hash seguro e se
     * os hashes cadastrados aceitam as senhas padrão usadas na apresentação.
     */
    public function testUsuariosIniciaisDoBancoUsamPasswordHash(): void {
        $sql = file_get_contents(__DIR__ . '/../database/jamesbar.sql');

        $this->assertStringNotContainsString('MD5(', strtoupper($sql), 'O script do banco não deve criar senhas em MD5.');
        preg_match_all("/\('([^']+)','([^']+@jamesbar\.com)','([^']+)'/", $sql, $usuarios, PREG_SET_ORDER);

        $this->assertGreaterThanOrEqual(7, count($usuarios), 'O banco deve conter ADM e caixas iniciais.');

        foreach ($usuarios as $usuario) {
            $email = $usuario[2];
            $hash = $usuario[3];
            $senhaEsperada = $email === 'admin@jamesbar.com' ? 'admin123' : str_replace('@jamesbar.com', '', $email);

            $this->assertStringStartsWith('$2y$', $hash, "O usuário $email deve usar bcrypt.");
            $this->assertTrue(password_verify($senhaEsperada, $hash), "A senha padrão de $email deve validar no bcrypt.");
        }
    }

    /**
     * Garante que a regra de senha forte rejeita senhas fracas e aceita uma
     * senha com maiúscula, minúscula, número, caractere especial e 8+ chars.
     */
    public function testValidacaoDeForcaDeSenha(): void {
        $this->assertSame(0, jb_validate_senha_forte('123456'));
        $this->assertSame(0, jb_validate_senha_forte('senhafraca'));
        $this->assertSame(1, jb_validate_senha_forte('James@123'));
    }
}
