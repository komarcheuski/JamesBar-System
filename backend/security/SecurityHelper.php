<?php

class SecurityHelper {

    public static function sanitizarTexto($texto) {
        return htmlspecialchars(
            trim((string) $texto),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    public static function sanitizarCpf($cpf) {
        return preg_replace('/\D/', '', (string) $cpf);
    }

    public static function sanitizarEmail($email) {
        return filter_var(
            trim((string) $email),
            FILTER_SANITIZE_EMAIL
        );
    }

    public static function validarNome($nome) {
        return preg_match('/^[A-Za-zÀ-ÿ\s\'\.\-]{3,100}$/u', $nome);
    }

    public static function validarEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validarCpf($cpf) {
        return preg_match('/^\d{11}$/', $cpf);
    }

    public static function validarSenha($senha) {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $senha);
    }

    public static function validarTelefone($telefone) {
        return preg_match('/^\d{10,11}$/', preg_replace('/\D/', '', $telefone));
    }

    public static function validarMfa($codigo) {
        return preg_match('/^\d{6}$/', $codigo);
    }

    public static function validarData($data) {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data);
    }

    private static function caminhoChavePrivada() {
        return __DIR__ . '/keys/private.pem';
    }

    private static function caminhoChavePublica() {
        return __DIR__ . '/keys/public.pem';
    }

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

    private static function obterChavePublica() {
        self::garantirParDeChavesRSA();
        return file_get_contents(self::caminhoChavePublica());
    }

    private static function obterChavePrivada() {
        self::garantirParDeChavesRSA();
        return file_get_contents(self::caminhoChavePrivada());
    }

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

function jb_sanitize_text($texto, $max = 255) {
    $texto = SecurityHelper::sanitizarTexto($texto);
    return mb_substr($texto, 0, $max, 'UTF-8');
}

function jb_sanitize_cpf($cpf) {
    return SecurityHelper::sanitizarCpf($cpf);
}

function jb_email($email) {
    return SecurityHelper::sanitizarEmail($email);
}

function jb_validate_nome($nome) {
    return SecurityHelper::validarNome($nome);
}

function jb_validate_email($email) {
    return SecurityHelper::validarEmail($email);
}

function jb_validate_cpf($cpf) {
    return SecurityHelper::validarCpf($cpf);
}

function jb_validate_senha_forte($senha) {
    return SecurityHelper::validarSenha($senha);
}

function jb_validate_telefone($telefone) {
    return SecurityHelper::validarTelefone($telefone);
}

function jb_validate_date($data) {
    return SecurityHelper::validarData($data);
}

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

function jb_json_response($dados) {
    echo json_encode($dados);
    exit;
}

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