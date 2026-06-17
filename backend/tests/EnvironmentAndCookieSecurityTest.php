<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: EnvironmentAndCookieSecurityTest.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Teste unitário que valida regras do sistema, requisitos de segurança ou
| qualidade do código.
|
| SEGURANÇA APLICADA:
| - Testa uso de .env e cookies seguros.
*/
use PHPUnit\Framework\TestCase;

class EnvironmentAndCookieSecurityTest extends TestCase {

    /**
     * O Database.php deve ler configuração por ambiente/.env, evitando credenciais fixas no código.
     */
    public function testDatabaseUsaVariaveisDeAmbiente(): void {
        $database = file_get_contents(__DIR__ . '/../config/Database.php');
        $envExample = file_get_contents(__DIR__ . '/../.env.example');

        $this->assertStringContainsString('DB_HOST', $database);
        $this->assertStringContainsString('DB_NAME_CORE', $database);
        $this->assertStringContainsString('DB_USER', $database);
        $this->assertStringContainsString('ATTR_EMULATE_PREPARES', $database);
        $this->assertStringContainsString('DB_HOST=', $envExample);
        $this->assertStringContainsString('DB_USER=', $envExample);
    }

    /**
     * Cookies devem ser HttpOnly/SameSite/Strict Mode para reduzir roubo de sessão.
     */
    public function testSessionPhpConfiguraCookiesSeguros(): void {
        $session = file_get_contents(__DIR__ . '/../config/session.php');

        $this->assertStringContainsString('session_set_cookie_params', $session);
        $this->assertStringContainsString('httponly', $session);
        $this->assertStringContainsString('samesite', $session);
        $this->assertStringContainsString('use_strict_mode', $session);
    }

    /**
     * .gitignore deve impedir envio de .env real e chaves privadas para o GitHub.
     */
    public function testGitignoreProtegeEnvEChaves(): void {
        $gitignore = file_get_contents(__DIR__ . '/../.gitignore');

        $this->assertStringContainsString('.env', $gitignore);
        $this->assertStringContainsString('security/keys/', $gitignore);
    }
}
