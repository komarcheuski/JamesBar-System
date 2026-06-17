<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: TotpService.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Implementa o serviço de autenticação multifator baseado em TOTP,
| utilizado no MFA dos usuários administradores.
|
| SEGURANÇA APLICADA:
| - Geração de segredo TOTP compatível com Google Authenticator.
| - Validação de códigos temporários de 6 dígitos.
| - Janela de tolerância curta para evitar falhas por diferença de horário.
| - Apoia o requisito de MFA para acesso administrativo.
*/
class TotpService {

    /**
     * FUNÇÃO:
     * Gera um segredo Base32 usado pelo Google Authenticator.
     *
     * SEGURANÇA:
     * Usa random_int(), que é adequado para geração aleatória segura.
     */
    public function gerarSecret($length = 16) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $secret;
    }

    /**
     * FUNÇÃO:
     * Gera a URL do QR Code para cadastro do MFA no Google Authenticator.
     *
     * SEGURANÇA:
     * Usa o padrão otpauth://totp com issuer, segredo, algoritmo SHA1,
     * 6 dígitos e período de 30 segundos.
     *
     * OBSERVAÇÃO:
     * O label não é codificado antes da montagem do otpauth, para evitar
     * dupla codificação do e-mail. A URL completa é codificada apenas no
     * parâmetro data do serviço de QR Code.
     */
    public function gerarQrCodeUrl($email, $secret) {
        $issuer = 'JamesBar';
        $label = $issuer . ':' . $email;

        $otpauth = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";

        return "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($otpauth);
    }

    /**
     * FUNÇÃO:
     * Verifica se o código digitado pelo usuário é válido.
     *
     * SEGURANÇA:
     * Aceita apenas códigos numéricos com 6 dígitos e utiliza uma janela
     * curta de tolerância para compensar pequenas diferenças de horário.
     */
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

    /**
     * FUNÇÃO:
     * Gera o código TOTP de 6 dígitos a partir do segredo e do intervalo
     * de tempo atual.
     *
     * SEGURANÇA:
     * Implementa HMAC-SHA1 conforme o padrão TOTP usado por aplicativos
     * autenticadores.
     */
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

    /**
     * FUNÇÃO:
     * Decodifica o segredo Base32 para bytes, permitindo gerar o HMAC.
     */
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