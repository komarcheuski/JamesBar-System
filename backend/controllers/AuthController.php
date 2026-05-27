<?php

session_start();

header("Content-Type: application/json");

require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../dao/TurnoDAO.php';
require_once __DIR__ . '/../security/TotpService.php';

$usuarioDAO = new UsuarioDAO();
$turnoDAO = new TurnoDAO();
$totpService = new TotpService();

$limiteTentativas = 5;
$minutosBloqueio = 10;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $acao = $_GET['acao'] ?? '';

    if ($acao === 'mfa_status') {
        if (!isset($_SESSION['mfa_usuario_id'])) {
            echo json_encode([
                "success" => false,
                "message" => "Sessão MFA não encontrada."
            ]);
            exit;
        }

        $usuario = $usuarioDAO->buscarPorId($_SESSION['mfa_usuario_id']);

        if (!$usuario) {
            echo json_encode([
                "success" => false,
                "message" => "Usuário não encontrado."
            ]);
            exit;
        }

        if (empty($usuario['mfa_secret'])) {
            $secret = $totpService->gerarSecret();
            $usuarioDAO->salvarMfaSecret($usuario['id'], $secret);
            $usuario['mfa_secret'] = $secret;
        }

        echo json_encode([
            "success" => true,
            "mfa_ativo" => (bool) $usuario['mfa_ativo'],
            "qr_code_url" => $usuario['mfa_ativo'] ? null : $totpService->gerarQrCodeUrl($usuario['email'], $usuario['mfa_secret']),
            "secret" => $usuario['mfa_ativo'] ? null : $usuario['mfa_secret'],
            "email" => $usuario['email']
        ]);
        exit;
    }
}

$dados = json_decode(file_get_contents("php://input"), true);

if (!$dados) {
    echo json_encode([
        "success" => false,
        "message" => "Dados inválidos."
    ]);
    exit;
}

$acao = $dados['acao'] ?? 'login';

if ($acao === 'login') {
    $email = trim($dados["email"] ?? "");
    $senha = trim($dados["senha"] ?? "");

    if (empty($email) || empty($senha)) {
        echo json_encode([
            "success" => false,
            "message" => "E-mail e senha são obrigatórios."
        ]);
        exit;
    }

    $usuario = $usuarioDAO->buscarPorEmail($email);

    if (!$usuario) {
        echo json_encode([
            "success" => false,
            "message" => "Usuário não encontrado."
        ]);
        exit;
    }

    if (!$usuario["ativo"]) {
        echo json_encode([
            "success" => false,
            "message" => "Usuário inativo."
        ]);
        exit;
    }

    if (!empty($usuario["bloqueio_login_until"])) {
        $bloqueioAte = strtotime($usuario["bloqueio_login_until"]);

        if ($bloqueioAte > time()) {
            $segundosRestantes = $bloqueioAte - time();
            $minutosRestantes = ceil($segundosRestantes / 60);

            echo json_encode([
                "success" => false,
                "message" => "Conta bloqueada temporariamente. Tente novamente em aproximadamente {$minutosRestantes} minuto(s)."
            ]);
            exit;
        }
    }

    if (md5($senha) !== $usuario["senha_hash"]) {
        $tentativasAtuais = (int) ($usuario["tentativas_login"] ?? 0);
        $novasTentativas = $tentativasAtuais + 1;

        if ($novasTentativas >= $limiteTentativas) {
            $bloqueioAte = date(
                'Y-m-d H:i:s',
                time() + ($minutosBloqueio * 60)
            );

            $usuarioDAO->bloquearLoginTemporariamente(
                $usuario["id"],
                $bloqueioAte
            );

            echo json_encode([
                "success" => false,
                "message" => "Senha incorreta. A conta foi bloqueada por {$minutosBloqueio} minutos após {$limiteTentativas} tentativas inválidas."
            ]);
            exit;
        }

        $usuarioDAO->atualizarTentativasLogin(
            $usuario["id"],
            $novasTentativas
        );

        $tentativasRestantes = $limiteTentativas - $novasTentativas;

        echo json_encode([
            "success" => false,
            "message" => "Senha incorreta. Tentativas restantes: {$tentativasRestantes}."
        ]);
        exit;
    }

    $usuarioDAO->limparTentativasLogin($usuario["id"]);

    if ((int) $usuario["primeiro_acesso"] === 1) {
        $_SESSION["troca_senha_usuario_id"] = $usuario["id"];
        $_SESSION["troca_senha_usuario_tipo"] = $usuario["tipo"];

        echo json_encode([
            "success" => true,
            "primeiro_acesso" => true,
            "message" => "Primeiro acesso detectado. Troque sua senha para continuar.",
            "redirect" => "trocar_senha.html"
        ]);
        exit;
    }

    if ($usuario["tipo"] === "adm") {
        $_SESSION["mfa_usuario_id"] = $usuario["id"];

        echo json_encode([
            "success" => true,
            "require_mfa" => true,
            "redirect" => "mfa.html",
            "message" => "Confirme o MFA para continuar."
        ]);
        exit;
    }

    if ($usuario["tipo"] === "caixa") {
        $turnoHoje = $turnoDAO->buscarTurnoHoje($usuario["id"]);

        if ($turnoHoje && $turnoHoje["status"] === "fechado") {
            echo json_encode([
                "success" => false,
                "message" => "Seu turno já foi encerrado hoje. Novo acesso somente no próximo dia."
            ]);
            exit;
        }

        $turnoDAO->abrirOuRetomarTurno($usuario["id"]);
    }

    $_SESSION["usuario_id"] = $usuario["id"];
    $_SESSION["usuario_nome"] = $usuario["nome"];
    $_SESSION["usuario_email"] = $usuario["email"];
    $_SESSION["usuario_tipo"] = $usuario["tipo"];
    $_SESSION["last_activity"] = time();

    echo json_encode([
        "success" => true,
        "message" => "Login realizado com sucesso.",
        "redirect" => "../caixa/dashboardCaixa.html",
        "usuario" => [
            "id" => $usuario["id"],
            "nome" => $usuario["nome"],
            "tipo" => $usuario["tipo"]
        ]
    ]);
    exit;
}

if ($acao === 'trocar_senha_primeiro_acesso') {
    if (!isset($_SESSION['troca_senha_usuario_id'])) {
        echo json_encode([
            "success" => false,
            "message" => "Sessão expirada. Faça login novamente."
        ]);
        exit;
    }

    $novaSenha = trim($dados['nova_senha'] ?? '');
    $confirmarSenha = trim($dados['confirmar_senha'] ?? '');

    if (empty($novaSenha) || empty($confirmarSenha)) {
        echo json_encode([
            "success" => false,
            "message" => "Preencha todos os campos."
        ]);
        exit;
    }

    if ($novaSenha !== $confirmarSenha) {
        echo json_encode([
            "success" => false,
            "message" => "As senhas não coincidem."
        ]);
        exit;
    }

    if (strlen($novaSenha) < 6) {
        echo json_encode([
            "success" => false,
            "message" => "A nova senha deve ter no mínimo 6 caracteres."
        ]);
        exit;
    }

    $usuarioId = $_SESSION['troca_senha_usuario_id'];

    $usuarioDAO->trocarSenhaPrimeiroAcesso($usuarioId, $novaSenha);

    unset($_SESSION['troca_senha_usuario_id']);
    unset($_SESSION['troca_senha_usuario_tipo']);

    session_destroy();

    echo json_encode([
        "success" => true,
        "message" => "Senha alterada com sucesso. Faça login novamente.",
        "redirect" => "login.html"
    ]);
    exit;
}

if ($acao === 'verificar_mfa') {
    if (!isset($_SESSION['mfa_usuario_id'])) {
        echo json_encode([
            "success" => false,
            "message" => "Sessão MFA expirada. Faça login novamente."
        ]);
        exit;
    }

    $codigo = trim($dados['codigo'] ?? '');
    $usuario = $usuarioDAO->buscarPorId($_SESSION['mfa_usuario_id']);

    if (!$usuario || $usuario['tipo'] !== 'adm') {
        echo json_encode([
            "success" => false,
            "message" => "Usuário inválido para MFA."
        ]);
        exit;
    }

    if (!$totpService->verificarCodigo($usuario['mfa_secret'], $codigo)) {
        echo json_encode([
            "success" => false,
            "message" => "Código MFA inválido."
        ]);
        exit;
    }

    if (!$usuario['mfa_ativo']) {
        $usuarioDAO->ativarMfa($usuario['id']);
    }

    $_SESSION["usuario_id"] = $usuario["id"];
    $_SESSION["usuario_nome"] = $usuario["nome"];
    $_SESSION["usuario_email"] = $usuario["email"];
    $_SESSION["usuario_tipo"] = $usuario["tipo"];
    $_SESSION["last_activity"] = time();

    unset($_SESSION['mfa_usuario_id']);

    echo json_encode([
        "success" => true,
        "message" => "MFA validado com sucesso.",
        "redirect" => "../adm/dashboardAdm.html"
    ]);
    exit;
}

if ($acao === 'logout_inatividade') {
    if (isset($_SESSION["usuario_id"]) && ($_SESSION["usuario_tipo"] ?? '') === 'caixa') {
        $turnoDAO->pausarTurno($_SESSION["usuario_id"]);
    }

    session_unset();
    session_destroy();

    echo json_encode([
        "success" => true,
        "message" => "Sessão encerrada por inatividade.",
        "redirect" => "../auth/login.html?motivo=inatividade"
    ]);
    exit;
}

if ($acao === 'logout') {
    session_unset();
    session_destroy();

    echo json_encode([
        "success" => true,
        "message" => "Logout realizado com sucesso.",
        "redirect" => "../auth/login.html"
    ]);
    exit;
}

echo json_encode([
    "success" => false,
    "message" => "Ação inválida."
]);