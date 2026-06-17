<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: session.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Inicializa e configura sessões PHP usadas pela autenticação e controle de
| acesso.
|
| SEGURANÇA APLICADA:
| - Configuração de cookie de sessão com HttpOnly, SameSite e Secure quando aplicável.
| - Controle centralizado de sessão autenticada.
| - Suporte ao token CSRF armazenado na sessão.
*/
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
}
