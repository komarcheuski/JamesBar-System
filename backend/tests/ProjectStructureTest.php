<?php

use PHPUnit\Framework\TestCase;

/**
 * Testes de estrutura do projeto.
 * Validam MVC, arquivos principais e workflow de CI.
 */
class ProjectStructureTest extends TestCase {

    private function rootPath($arquivo) {
        return __DIR__ . '/../../' . $arquivo;
    }

    public function testModelsPrincipaisExistem() {
        foreach (['Usuario.php', 'Cliente.php', 'Promoter.php', 'Lista.php'] as $model) {
            $this->assertFileExists($this->rootPath('backend/models/' . $model));
        }
    }

    public function testModelsImplementamJsonSerializableEArrayAccess() {
        foreach (['Usuario.php', 'Cliente.php', 'Promoter.php', 'Lista.php'] as $model) {
            $codigo = file_get_contents($this->rootPath('backend/models/' . $model));
            $this->assertStringContainsString('JsonSerializable', $codigo);
            $this->assertStringContainsString('ArrayAccess', $codigo);
        }
    }

    public function testGitHubActionsExiste() {
        $this->assertFileExists($this->rootPath('.github/workflows/php-tests.yml'));
    }

    public function testComposerTemPhpUnit() {
        $codigo = file_get_contents($this->rootPath('backend/composer.json'));
        $this->assertStringContainsString('phpunit/phpunit', $codigo);
    }

    public function testGitignoreIgnoraChavesPrivadas() {
        $codigo = file_get_contents($this->rootPath('.gitignore'));
        $this->assertStringContainsString('backend/security/keys/', $codigo);
    }
}
