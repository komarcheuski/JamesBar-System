<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: Database.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Cria conexões PDO com os bancos db_core e db_listas_vip, carregando
| configurações do ambiente.
|
| SEGURANÇA APLICADA:
| - Carregamento de credenciais pelo arquivo .env, evitando credenciais fixas no código.
| - Conexão PDO configurada com exceções para tratamento seguro de erros.
| - Uso de charset utf8mb4 para evitar problemas de codificação.
*/
class Database {
    private static $conn = null;

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    private static function carregarEnv() {
        $envPath = __DIR__ . '/../.env';

        if (!file_exists($envPath)) {
            return;
        }

        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'");

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    private static function env($key, $default = '') {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    public static function conectar() {
        if (self::$conn === null) {
            self::carregarEnv();

            $host = self::env('DB_HOST', 'localhost');
            $dbName = self::env('DB_NAME', 'db_core');
            $username = self::env('DB_USER', 'root');
            $password = self::env('DB_PASS', '');
            $charset = self::env('DB_CHARSET', 'utf8mb4');

            try {
                self::$conn = new PDO(
                    "mysql:host={$host};dbname={$dbName};charset={$charset}",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                error_log('Erro de conexão com banco JamesBar: ' . $e->getMessage());

                die(json_encode([
                    'success' => false,
                    'message' => 'Erro ao conectar com o banco.'
                ]));
            }
        }

        return self::$conn;
    }
}
