<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: SecurityHelper.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Agrupa funções auxiliares de segurança: CSRF, senha forte, criptografia,
| validação e sanitização.
|
| SEGURANÇA APLICADA:
| - Validação de força de senha.
| - Geração e validação de token CSRF.
| - Criptografia e descriptografia de dados sensíveis com chave configurada.
| - Sanitização e validação de entradas utilizadas pelo sistema.
*/
class SecurityHelper {

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    public static function sanitizarTexto($texto) {
        return htmlspecialchars(
            trim((string) $texto),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    public static function sanitizarCpf($cpf) {
        return preg_replace('/\D/', '', (string) $cpf);
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    public static function sanitizarEmail($email) {
        return filter_var(
            trim((string) $email),
            FILTER_SANITIZE_EMAIL
        );
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    public static function validarNome($nome) {
        return preg_match('/^[A-Za-zÀ-ÿ\s\'\.\-]{3,100}$/u', $nome);
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    public static function validarEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Requisito de segurança: valida o CPF sanitizado com 11 dígitos antes de usar em cadastro ou busca.
     */
    public static function validarCpf($cpf) {
        return preg_match('/^\d{11}$/', $cpf);
    }

    /**
     * Requisito de segurança: exige senha forte no primeiro acesso/troca de senha.
     */
    public static function validarSenha($senha) {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $senha);
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    public static function validarTelefone($telefone) {
        return preg_match('/^\d{10,11}$/', preg_replace('/\D/', '', $telefone));
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    public static function validarMfa($codigo) {
        return preg_match('/^\d{6}$/', $codigo);
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    public static function validarData($data) {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data);
    }

    /**
     * Requisito de segurança: gera hash seguro de senha usando API nativa do PHP, evitando MD5.
     */
    public static function hashSenha($senha) {
        return password_hash((string) $senha, PASSWORD_DEFAULT);
    }

    /**
     * Requisito de segurança: verifica senha com password_verify e mantém compatibilidade temporária com MD5 legado.
     */
    public static function verificarSenha($senha, $hash) {
        $hash = (string) $hash;

        if (password_verify((string) $senha, $hash)) {
            return true;
        }

        // Compatibilidade temporária com senhas antigas em MD5 já gravadas no banco.
        return preg_match('/^[a-f0-9]{32}$/i', $hash) && hash_equals(strtolower($hash), md5((string) $senha));
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    public static function hashPrecisaRehash($hash) {
        $hash = (string) $hash;
        return preg_match('/^[a-f0-9]{32}$/i', $hash) || password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /**
     * Requisito de segurança: cria token CSRF salvo na sessão para proteger requisições POST.
     */
    public static function gerarCsrfToken() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Requisito de segurança: compara token CSRF com hash_equals para evitar comparação insegura.
     */
    public static function validarCsrfToken($token) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    private static function caminhoChavePrivada() {
        return __DIR__ . '/keys/private.pem';
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    private static function caminhoChavePublica() {
        return __DIR__ . '/keys/public.pem';
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    private static function garantirParDeChavesRSA() {
        $dir = __DIR__ . '/keys';

        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $privatePath = self::caminhoChavePrivada();
        $publicPath = self::caminhoChavePublica();

        if (file_exists($privatePath) && file_exists($publicPath)) {
            return;
        }

        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA
        ];

        $res = openssl_pkey_new($config);

        if (!$res) {
            throw new Exception('Erro ao gerar chaves RSA.');
        }

        openssl_pkey_export($res, $privateKey);

        $publicKeyData = openssl_pkey_get_details($res);
        $publicKey = $publicKeyData['key'];

        file_put_contents($privatePath, $privateKey);
        file_put_contents($publicPath, $publicKey);

        chmod($privatePath, 0600);
        chmod($publicPath, 0644);
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    private static function obterChavePublica() {
        self::garantirParDeChavesRSA();
        return file_get_contents(self::caminhoChavePublica());
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    private static function obterChavePrivada() {
        self::garantirParDeChavesRSA();
        return file_get_contents(self::caminhoChavePrivada());
    }

    /**
     * Requisito de criptografia: protege o segredo MFA com AES-256-GCM e chave AES cifrada por RSA.
     */
    public static function criptografarMfaSecret($secret) {
        $aesKey = random_bytes(32);
        $iv = random_bytes(12);
        $tag = '';

        $cipherText = openssl_encrypt(
            $secret,
            'aes-256-gcm',
            $aesKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($cipherText === false) {
            throw new Exception('Erro ao criptografar MFA.');
        }

        $publicKey = self::obterChavePublica();

        $ok = openssl_public_encrypt(
            $aesKey,
            $encryptedAesKey,
            $publicKey,
            OPENSSL_PKCS1_OAEP_PADDING
        );

        if (!$ok) {
            throw new Exception('Erro ao criptografar chave AES.');
        }

        return [
            'mfa_secret' => base64_encode(json_encode([
                'iv' => base64_encode($iv),
                'tag' => base64_encode($tag),
                'ciphertext' => base64_encode($cipherText)
            ])),
            'mfa_secret_key' => base64_encode($encryptedAesKey)
        ];
    }

    /**
     * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
     */
    public static function descriptografarMfaSecret($mfaSecret, $mfaSecretKey) {
        if (empty($mfaSecret)) {
            return '';
        }

        if (empty($mfaSecretKey)) {
            return $mfaSecret;
        }

        $privateKey = self::obterChavePrivada();

        $ok = openssl_private_decrypt(
            base64_decode($mfaSecretKey),
            $aesKey,
            $privateKey,
            OPENSSL_PKCS1_OAEP_PADDING
        );

        if (!$ok) {
            return '';
        }

        $payloadJson = base64_decode($mfaSecret);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            return '';
        }

        $iv = base64_decode($payload['iv'] ?? '');
        $tag = base64_decode($payload['tag'] ?? '');
        $cipherText = base64_decode($payload['ciphertext'] ?? '');

        $secret = openssl_decrypt(
            $cipherText,
            'aes-256-gcm',
            $aesKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $secret ?: '';
    }
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_sanitize_text($texto, $max = 255) {
    $texto = SecurityHelper::sanitizarTexto($texto);
    return mb_substr($texto, 0, $max, 'UTF-8');
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_sanitize_cpf($cpf) {
    return SecurityHelper::sanitizarCpf($cpf);
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_email($email) {
    return SecurityHelper::sanitizarEmail($email);
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_validate_nome($nome) {
    return SecurityHelper::validarNome($nome);
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_validate_email($email) {
    return SecurityHelper::validarEmail($email);
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_validate_cpf($cpf) {
    return SecurityHelper::validarCpf($cpf);
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_validate_senha_forte($senha) {
    return SecurityHelper::validarSenha($senha);
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_validate_telefone($telefone) {
    return SecurityHelper::validarTelefone($telefone);
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_validate_date($data) {
    return SecurityHelper::validarData($data);
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_hash_senha($senha) {
    return SecurityHelper::hashSenha($senha);
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_verify_senha($senha, $hash) {
    return SecurityHelper::verificarSenha($senha, $hash);
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_hash_precisa_rehash($hash) {
    return SecurityHelper::hashPrecisaRehash($hash);
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_csrf_token() {
    return SecurityHelper::gerarCsrfToken();
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_validate_csrf($token) {
    return SecurityHelper::validarCsrfToken($token);
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_validate_dias($dias) {
    if (!is_array($dias)) {
        return false;
    }

    $permitidos = ['quarta', 'quinta', 'sexta', 'sabado', 'domingo'];

    foreach ($dias as $dia) {
        if (!in_array($dia, $permitidos, true)) {
            return false;
        }
    }

    return true;
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_sanitize_pessoas($pessoas, $limite) {
    $resultado = [];

    foreach (array_slice($pessoas, 0, $limite) as $pessoa) {
        $nome = jb_sanitize_text($pessoa['nome'] ?? '', 100);
        $cpf = jb_sanitize_cpf($pessoa['cpf'] ?? '');

        if ($nome !== '') {
            $resultado[] = [
                'nome' => $nome,
                'cpf' => $cpf
            ];
        }
    }

    return $resultado;
}

/**
 * FUNÇÃO: Executa uma regra específica deste arquivo mantendo a responsabilidade organizada.
 */
function jb_json_response($dados) {
    echo json_encode($dados);
    exit;
}

/**
 * Middleware simples usado pelos controllers para bloquear POST sem token CSRF válido.
 */
function jb_require_csrf($dados = null) {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if ($token === '' && is_array($dados)) {
        $token = $dados['csrf_token'] ?? '';
    }

    if (!jb_validate_csrf($token)) {
        jb_json_response([
            'success' => false,
            'message' => 'Token CSRF inválido ou expirado.'
        ]);
    }
}

/**
 * Requisito de segurança: regenera o ID da sessão após autenticação para mitigar Session Fixation.
 */
function jb_regenerate_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Controle de autorização: exige usuário logado e, quando informado, perfil correto.
 */
function jb_require_login($tipo = null) {
    if (!isset($_SESSION['usuario_id'])) {
        jb_json_response([
            'success' => false,
            'message' => 'Usuário não autenticado.'
        ]);
    }

    if ($tipo !== null && ($_SESSION['usuario_tipo'] ?? '') !== $tipo) {
        jb_json_response([
            'success' => false,
            'message' => 'Acesso não autorizado.'
        ]);
    }
}
