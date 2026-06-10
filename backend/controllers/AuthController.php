<?php

session_start();

header("Content-Type: application/json");

require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../dao/TurnoDAO.php';
require_once __DIR__ . '/../security/TotpService.php';

$usuarioDAO = new UsuarioDAO();
$turnoDAO = new TurnoDAO();
$totpService = new TotpService();

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

    if (md5($senha) !== $usuario["senha_hash"]) {
        echo json_encode([
            "success" => false,
            "message" => "Senha incorreta."
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

    unset($_SESSION['mfa_usuario_id']);

    echo json_encode([
        "success" => true,
        "message" => "MFA validado com sucesso.",
        "redirect" => "../adm/dashboardAdm.html"
    ]);
    exit;
}

echo json_encode([
    "success" => false,
    "message" => "Ação inválida."
]);