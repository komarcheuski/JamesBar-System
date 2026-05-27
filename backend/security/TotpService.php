<?php

class TotpService {

    public function gerarSecret($length = 16) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $secret;
    }

    public function gerarQrCodeUrl($email, $secret) {
        $issuer = 'JamesBar';
        $label = rawurlencode($issuer . ':' . $email);

        $otpauth = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";

        return "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($otpauth);
    }

    public function verificarCodigo($secret, $codigo) {
        $codigo = preg_replace('/\D/', '', $codigo);

        if (strlen($codigo) !== 6) {
            return false;
        }

        $tempoAtual = floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            if ($this->gerarCodigo($secret, $tempoAtual + $i) === $codigo) {
                return true;
            }
        }

        return false;
    }

    private function gerarCodigo($secret, $tempo) {
        $chave = $this->base32Decode($secret);
        $tempoBinario = pack('N*', 0) . pack('N*', $tempo);

        $hash = hash_hmac('sha1', $tempoBinario, $chave, true);
        $offset = ord(substr($hash, -1)) & 0x0F;

        $codigoBinario =
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF);

        return str_pad($codigoBinario % 1000000, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode($secret) {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $bits = '';
        $output = '';

        for ($i = 0; $i < strlen($secret); $i++) {
            $valor = strpos($base32chars, $secret[$i]);

            if ($valor === false) {
                continue;
            }

            $bits .= str_pad(decbin($valor), 5, '0', STR_PAD_LEFT);
        }

        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $output .= chr(bindec(substr($bits, $i, 8)));
        }

        return $output;
    }
}