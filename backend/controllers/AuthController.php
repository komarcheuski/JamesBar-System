<?php

session_start();

header("Content-Type: application/json");

require_once __DIR__ . '/../dao/UsuarioDAO.php';

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

/*
    Seu banco atual usa MD5:
    MD5('admin123'), MD5('caixa01'), etc.
*/
if (md5($senha) !== $usuario["senha_hash"]) {
    echo json_encode([
        "success" => false,
        "message" => "Senha incorreta."
    ]);
    exit;
}

$_SESSION["usuario_id"] = $usuario["id"];
$_SESSION["usuario_nome"] = $usuario["nome"];
$_SESSION["usuario_email"] = $usuario["email"];
$_SESSION["usuario_tipo"] = $usuario["tipo"];

echo json_encode([
    "success" => true,
    "message" => "Login realizado com sucesso.",
    "usuario" => [
        "id" => $usuario["id"],
        "nome" => $usuario["nome"],
        "tipo" => $usuario["tipo"]
    ]
]);