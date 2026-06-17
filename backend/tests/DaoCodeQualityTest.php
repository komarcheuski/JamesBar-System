<?php

use PHPUnit\Framework\TestCase;

class DaoCodeQualityTest extends TestCase {

    private function daoPath($arquivo) {
        return __DIR__ . '/../dao/' . $arquivo;
    }

    public function testDaosPrincipaisExistem() {
        $arquivos = [
            'UsuarioDAO.php',
            'ClienteDAO.php',
            'MovimentacaoDAO.php',
            'TurnoDAO.php',
            'PromoterDAO.php',
            'ListaPromoterDAO.php',
            'ListaAniversarioDAO.php'
        ];

        foreach ($arquivos as $arquivo) {
            $this->assertFileExists($this->daoPath($arquivo));
        }
    }

    public function testDaosUsamPreparedStatements() {
        $arquivos = [
            'UsuarioDAO.php',
            'ClienteDAO.php',
            'MovimentacaoDAO.php',
            'TurnoDAO.php',
            'PromoterDAO.php',
            'ListaPromoterDAO.php',
            'ListaAniversarioDAO.php'
        ];

        foreach ($arquivos as $arquivo) {
            $codigo = file_get_contents($this->daoPath($arquivo));

            $this->assertStringContainsString(
                'prepare(',
                $codigo,
                "{$arquivo} deve usar prepare() contra SQL Injection."
            );
        }
    }

    public function testDaosNaoAcessamGetOuPostDiretamente() {
        $arquivos = glob(__DIR__ . '/../dao/*.php');

        foreach ($arquivos as $arquivo) {
            $codigo = file_get_contents($arquivo);

            $this->assertStringNotContainsString('$_GET', $codigo);
            $this->assertStringNotContainsString('$_POST', $codigo);
            $this->assertStringNotContainsString('$_REQUEST', $codigo);
        }
    }

    public function testDaosIncluemModelsQuandoNecessario() {
        $usuarioDao = file_get_contents($this->daoPath('UsuarioDAO.php'));
        $clienteDao = file_get_contents($this->daoPath('ClienteDAO.php'));
        $promoterDao = file_get_contents($this->daoPath('PromoterDAO.php'));

        $this->assertStringContainsString('../models/Usuario.php', $usuarioDao);
        $this->assertStringContainsString('../models/Cliente.php', $clienteDao);
        $this->assertStringContainsString('../models/Promoter.php', $promoterDao);
    }
}