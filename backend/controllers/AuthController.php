<?php

session_start();

header("Content-Type: application/json");

require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../dao/TurnoDAO.php';

$dados = json_decode(file_get_contents("php://input"), true);

if (!$dados) {
    echo json_encode([
        "success" => false,
        "message" => "Dados inválidos."
    ]);
    exit;
}

$email = trim($dados["email"] ?? "");
$senha = trim($dados["senha"] ?? "");

if (empty($email) || empty($senha)) {
    echo json_encode([
        "success" => false,
        "message" => "E-mail e senha são obrigatórios."
    ]);
    exit;
}

$usuarioDAO = new UsuarioDAO();
$turnoDAO = new TurnoDAO();

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

if ($usuario["tipo"] === "adm") {
    $redirect = "../adm/dashboardAdm.html";
} else {
    $redirect = "../caixa/dashboardCaixa.html";
}

echo json_encode([
    "success" => true,
    "message" => "Login realizado com sucesso.",
    "redirect" => $redirect,
    "usuario" => [
        "id" => $usuario["id"],
        "nome" => $usuario["nome"],
        "tipo" => $usuario["tipo"]
    ]
]);