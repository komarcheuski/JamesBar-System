<?php

use PHPUnit\Framework\TestCase;

class GitHubActionsTest extends TestCase {

    private function workflowPath() {
        return dirname(__DIR__, 2) . '/.github/workflows/php-tests.yml';
    }

    public function testWorkflowExiste() {
        $this->assertFileExists($this->workflowPath());
    }

    public function testWorkflowRodaPhpUnit() {
        $workflow = file_get_contents($this->workflowPath());
        $this->assertStringContainsString('vendor/bin/phpunit', $workflow);
        $this->assertStringContainsString('composer install', $workflow);
    }

    public function testWorkflowTemExtensoesNecessarias() {
        $workflow = file_get_contents($this->workflowPath());
        foreach (['mbstring', 'openssl', 'pdo_mysql', 'zip', 'dom', 'xml', 'xmlwriter'] as $extensao) {
            $this->assertStringContainsString($extensao, $workflow);
        }
    }

    public function testWorkflowRodaNaMainEAndreBranch() {
        $workflow = file_get_contents($this->workflowPath());
        $this->assertStringContainsString('main', $workflow);
        $this->assertStringContainsString('andre-branch', $workflow);
    }
}
