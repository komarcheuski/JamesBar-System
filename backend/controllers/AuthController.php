<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: AuthController.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Controla login, logout, MFA, troca obrigatória de senha, verificação de sessão
| e respostas JSON de autenticação.
|
| SEGURANÇA APLICADA:
| - Autenticação com hash seguro de senha usando password_verify/password_hash.
| - Bloqueio temporário após tentativas inválidas de login.
| - Regeneração do ID da sessão após autenticação para reduzir risco de Session Fixation.
| - Integração com MFA/TOTP para perfil administrador.
| - Registro de eventos sensíveis em logs_sistema.
| - Validação de CSRF nas ações POST.
*/
require_once __DIR__ . '/../config/session.php';

header("Content-Type: application/json");

require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../dao/TurnoDAO.php';
require_once __DIR__ . '/../dao/LogDAO.php';
require_once __DIR__ . '/../security/TotpService.php';
require_once __DIR__ . '/../security/SecurityHelper.php';

$usuarioDAO = new UsuarioDAO();
$turnoDAO = new TurnoDAO();
$totpService = new TotpService();
$logDAO = new LogDAO();

$limiteTentativas = 5;
$minutosBloqueio = 10;

/**
 * FUNÇÃO: Retorna resposta JSON padronizada e encerra a execução do controller.
 */
function resposta_json($dados) {
    echo json_encode($dados);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $acao = $_GET['acao'] ?? '';

    if ($acao === 'csrf_token') {
        resposta_json([
            'success' => true,
            'csrf_token' => jb_csrf_token()
        ]);
    }

    if ($acao === 'mfa_status') {
        if (!isset($_SESSION['mfa_usuario_id'])) {
            resposta_json([
                "success" => false,
                "message" => "Sessão MFA não encontrada."
            ]);
        }

        $usuario = $usuarioDAO->buscarPorId($_SESSION['mfa_usuario_id']);

        if (!$usuario) {
            resposta_json([
                "success" => false,
                "message" => "Usuário não encontrado."
            ]);
        }

        if (empty($usuario['mfa_secret'])) {
            $secret = $totpService->gerarSecret();

            $criptografia = SecurityHelper::criptografarMfaSecret($secret);

            $usuarioDAO->salvarMfaSecretCriptografado(
                $usuario['id'],
                $criptografia['mfa_secret'],
                $criptografia['mfa_secret_key']
            );

            $secretParaExibir = $secret;
        } else {
            $secretParaExibir = SecurityHelper::descriptografarMfaSecret(
                $usuario['mfa_secret'],
                $usuario['mfa_secret_key'] ?? null
            );

            if ($secretParaExibir === '') {
                resposta_json([
                    "success" => false,
                    "message" => "Erro ao descriptografar MFA."
                ]);
            }

            if (empty($usuario['mfa_secret_key'])) {
                $criptografia = SecurityHelper::criptografarMfaSecret($secretParaExibir);

                $usuarioDAO->salvarMfaSecretCriptografado(
                    $usuario['id'],
                    $criptografia['mfa_secret'],
                    $criptografia['mfa_secret_key']
                );
            }
        }

        resposta_json([
            "success" => true,
            "mfa_ativo" => (bool) $usuario['mfa_ativo'],
            "qr_code_url" => $usuario['mfa_ativo'] ? null : $totpService->gerarQrCodeUrl($usuario['email'], $secretParaExibir),
            "secret" => $usuario['mfa_ativo'] ? null : $secretParaExibir,
            "email" => $usuario['email']
        ]);
    }
}

$dados = json_decode(file_get_contents("php://input"), true);

if (!is_array($dados)) {
    resposta_json([
        "success" => false,
        "message" => "Dados inválidos."
    ]);
}

$acao = trim($dados['acao'] ?? 'login');

if ($acao === 'verificar_sessao') {
    if (isset($_SESSION['usuario_id'])) {
        resposta_json([
            "success" => true,
            "usuario_id" => $_SESSION['usuario_id'],
            "usuario_tipo" => $_SESSION['usuario_tipo'],
            "usuario_nome" => $_SESSION['usuario_nome'] ?? '',
            "csrf_token" => jb_csrf_token()
        ]);
    }

    if (isset($_SESSION['mfa_usuario_id'])) {
        resposta_json([
            "success" => true,
            "mfa_pendente" => true,
            "message" => "Sessão MFA ativa.",
            "csrf_token" => jb_csrf_token()
        ]);
    }

    if (isset($_SESSION['troca_senha_usuario_id'])) {
        resposta_json([
            "success" => true,
            "troca_senha_pendente" => true,
            "message" => "Sessão de troca de senha ativa.",
            "csrf_token" => jb_csrf_token()
        ]);
    }

    resposta_json([
        "success" => false,
        "message" => "Usuário não logado."
    ]);
}

if ($acao === 'login') {
    $email = filter_var(trim($dados["email"] ?? ""), FILTER_SANITIZE_EMAIL);
    $senha = trim($dados["senha"] ?? "");

    if ($email === '' || $senha === '') {
        resposta_json([
            "success" => false,
            "message" => "E-mail e senha são obrigatórios."
        ]);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        resposta_json([
            "success" => false,
            "message" => "E-mail inválido."
        ]);
    }

    $usuario = $usuarioDAO->buscarPorEmail($email);

    if (!$usuario) {
        $logDAO->registrar(null, 'LOGIN_FALHA', 'E-mail não encontrado: ' . $email);
        resposta_json([
            "success" => false,
            "message" => "Usuário não encontrado."
        ]);
    }

    if (!$usuario["ativo"]) {
        $logDAO->registrar($usuario["id"], 'LOGIN_FALHA', 'Usuário inativo tentou acessar.');
        resposta_json([
            "success" => false,
            "message" => "Usuário inativo."
        ]);
    }

    if (!empty($usuario["bloqueio_login_until"])) {
        $bloqueioAte = strtotime($usuario["bloqueio_login_until"]);

        if ($bloqueioAte > time()) {
            $minutosRestantes = ceil(($bloqueioAte - time()) / 60);

            resposta_json([
                "success" => false,
                "message" => "Conta bloqueada temporariamente. Tente novamente em aproximadamente {$minutosRestantes} minuto(s)."
            ]);
        }
    }

    if (!jb_verify_senha($senha, $usuario["senha_hash"])) {
        $tentativasAtuais = (int) ($usuario["tentativas_login"] ?? 0);
        $novasTentativas = $tentativasAtuais + 1;

        if ($novasTentativas >= $limiteTentativas) {
            $bloqueioAte = date('Y-m-d H:i:s', time() + ($minutosBloqueio * 60));

            $usuarioDAO->bloquearLoginTemporariamente($usuario["id"], $bloqueioAte);
            $logDAO->registrar($usuario["id"], 'LOGIN_BLOQUEIO', 'Conta bloqueada após tentativas inválidas.');

            resposta_json([
                "success" => false,
                "message" => "Senha incorreta. A conta foi bloqueada por {$minutosBloqueio} minutos após {$limiteTentativas} tentativas inválidas."
            ]);
        }

        $usuarioDAO->atualizarTentativasLogin($usuario["id"], $novasTentativas);
        $logDAO->registrar($usuario["id"], 'LOGIN_FALHA', 'Senha incorreta. Tentativa ' . $novasTentativas . ' de ' . $limiteTentativas . '.');

        $tentativasRestantes = $limiteTentativas - $novasTentativas;

        resposta_json([
            "success" => false,
            "message" => "Senha incorreta. Tentativas restantes: {$tentativasRestantes}."
        ]);
    }

    $usuarioDAO->limparTentativasLogin($usuario["id"]);

    if (jb_hash_precisa_rehash($usuario["senha_hash"])) {
        $usuarioDAO->atualizarSenhaHash($usuario["id"], jb_hash_senha($senha));
    }

    if ((int) $usuario["primeiro_acesso"] === 1) {
        jb_regenerate_session();
        $_SESSION["troca_senha_usuario_id"] = $usuario["id"];
        $_SESSION["troca_senha_usuario_tipo"] = $usuario["tipo"];

        resposta_json([
            "success" => true,
            "primeiro_acesso" => true,
            "message" => "Primeiro acesso detectado. Troque sua senha para continuar.",
            "redirect" => "trocar_senha.html"
        ]);
    }

    if ($usuario["tipo"] === "adm") {
        jb_regenerate_session();
        $_SESSION["mfa_usuario_id"] = $usuario["id"];

        resposta_json([
            "success" => true,
            "require_mfa" => true,
            "redirect" => "mfa.html",
            "message" => "Confirme o MFA para continuar."
        ]);
    }

    if ($usuario["tipo"] === "caixa") {
        $turnoHoje = $turnoDAO->buscarTurnoHoje($usuario["id"]);

        if ($turnoHoje && $turnoHoje["status"] === "fechado") {
            resposta_json([
                "success" => false,
                "message" => "Seu turno já foi encerrado hoje. Novo acesso somente no próximo dia."
            ]);
        }

        $turnoDAO->abrirOuRetomarTurno($usuario["id"]);
    }

    jb_regenerate_session();
    $_SESSION["usuario_id"] = $usuario["id"];
    $_SESSION["usuario_nome"] = htmlspecialchars($usuario["nome"], ENT_QUOTES, 'UTF-8');
    $_SESSION["usuario_email"] = htmlspecialchars($usuario["email"], ENT_QUOTES, 'UTF-8');
    $_SESSION["usuario_tipo"] = $usuario["tipo"];
    $_SESSION["last_activity"] = time();

    $redirect = $usuario["tipo"] === "adm"
        ? "../adm/dashboardAdm.html"
        : "../caixa/dashboardCaixa.html";

    $logDAO->registrar($usuario["id"], 'LOGIN_SUCESSO', 'Login validado para perfil ' . $usuario["tipo"] . '.');

    resposta_json([
        "success" => true,
        "message" => "Login realizado com sucesso.",
        "redirect" => $redirect,
        "usuario" => [
            "id" => $usuario["id"],
            "nome" => htmlspecialchars($usuario["nome"], ENT_QUOTES, 'UTF-8'),
            "tipo" => $usuario["tipo"]
        ],
        "csrf_token" => jb_csrf_token()
    ]);
}

if ($acao === 'trocar_senha_primeiro_acesso') {
    if (!isset($_SESSION['troca_senha_usuario_id'])) {
        resposta_json([
            "success" => false,
            "message" => "Sessão expirada. Faça login novamente."
        ]);
    }

    $novaSenha = trim($dados['nova_senha'] ?? '');
    $confirmarSenha = trim($dados['confirmar_senha'] ?? '');

    if ($novaSenha === '' || $confirmarSenha === '') {
        resposta_json([
            "success" => false,
            "message" => "Preencha todos os campos."
        ]);
    }

    if ($novaSenha !== $confirmarSenha) {
        resposta_json([
            "success" => false,
            "message" => "As senhas não coincidem."
        ]);
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $novaSenha)) {
        resposta_json([
            "success" => false,
            "message" => "A senha precisa ser forte: mínimo 8 caracteres, maiúscula, minúscula, número e símbolo."
        ]);
    }

    $usuarioId = $_SESSION['troca_senha_usuario_id'];

    $usuarioDAO->trocarSenhaPrimeiroAcesso($usuarioId, $novaSenha);

    unset($_SESSION['troca_senha_usuario_id']);
    unset($_SESSION['troca_senha_usuario_tipo']);

    session_destroy();

    resposta_json([
        "success" => true,
        "message" => "Senha alterada com sucesso. Faça login novamente.",
        "redirect" => "login.html"
    ]);
}

if ($acao === 'verificar_mfa') {
    if (!isset($_SESSION['mfa_usuario_id'])) {
        resposta_json([
            "success" => false,
            "message" => "Sessão MFA expirada. Faça login novamente."
        ]);
    }

    $codigo = trim($dados['codigo'] ?? '');

    if (!preg_match('/^\d{6}$/', $codigo)) {
        resposta_json([
            "success" => false,
            "message" => "Código MFA inválido. Digite os 6 números."
        ]);
    }

    $usuario = $usuarioDAO->buscarPorId($_SESSION['mfa_usuario_id']);

    if (!$usuario || $usuario['tipo'] !== 'adm') {
        resposta_json([
            "success" => false,
            "message" => "Usuário inválido para MFA."
        ]);
    }

    $secretDescriptografado = SecurityHelper::descriptografarMfaSecret(
        $usuario['mfa_secret'],
        $usuario['mfa_secret_key'] ?? null
    );

    if ($secretDescriptografado === '') {
        resposta_json([
            "success" => false,
            "message" => "Erro ao descriptografar MFA."
        ]);
    }

    if (!$totpService->verificarCodigo($secretDescriptografado, $codigo)) {
        resposta_json([
            "success" => false,
            "message" => "Código MFA inválido."
        ]);
    }

    if (!$usuario['mfa_ativo']) {
        $usuarioDAO->ativarMfa($usuario['id']);
    }

    jb_regenerate_session();
    $_SESSION["usuario_id"] = $usuario["id"];
    $_SESSION["usuario_nome"] = htmlspecialchars($usuario["nome"], ENT_QUOTES, 'UTF-8');
    $_SESSION["usuario_email"] = htmlspecialchars($usuario["email"], ENT_QUOTES, 'UTF-8');
    $_SESSION["usuario_tipo"] = $usuario["tipo"];
    $_SESSION["last_activity"] = time();

    unset($_SESSION['mfa_usuario_id']);

    $logDAO->registrar($usuario["id"], 'MFA_SUCESSO', 'MFA validado para ADM.');

    resposta_json([
        "success" => true,
        "message" => "MFA validado com sucesso.",
        "redirect" => "../adm/dashboardAdm.html",
        "csrf_token" => jb_csrf_token()
    ]);
}

if ($acao === 'logout_inatividade') {
    if (isset($_SESSION["usuario_id"]) && ($_SESSION["usuario_tipo"] ?? '') === 'caixa') {
        $turnoDAO->pausarTurno($_SESSION["usuario_id"]);
    }

    session_unset();
    session_destroy();

    resposta_json([
        "success" => true,
        "message" => "Sessão encerrada por inatividade.",
        "redirect" => "../auth/login.html?motivo=inatividade"
    ]);
}

if ($acao === 'logout') {
    session_unset();
    session_destroy();

    resposta_json([
        "success" => true,
        "message" => "Logout realizado com sucesso.",
        "redirect" => "../auth/login.html"
    ]);
}

resposta_json([
    "success" => false,
    "message" => "Ação inválida."
]);
