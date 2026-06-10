<?php

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../dao/TurnoDAO.php';

$usuarioDAO = new UsuarioDAO();
$turnoDAO = new TurnoDAO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $acao = $_GET['acao'] ?? '';

    if ($acao === 'usuario_logado') {
        echo json_encode([
            'success' => true,
            'nome' => $_SESSION['usuario_nome'] ?? 'Administrador'
        ]);
        exit;
    }

    if ($acao === 'listar_caixas') {
        echo json_encode([
            'success' => true,
            'caixas' => $usuarioDAO->listarCaixas()
        ]);
        exit;
    }

    if ($acao === 'detalhes_caixa') {
        $caixaId = intval($_GET['caixa_id'] ?? 0);

        if ($caixaId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Caixa inválido.'
            ]);
            exit;
        }

        $caixa = $usuarioDAO->buscarPorId($caixaId);
        $turno = $turnoDAO->buscarTurnoHoje($caixaId);
        $resumo = $turnoDAO->buscarResumoOperacional($caixaId);
        $pausas = $turno ? $turnoDAO->listarPausasDoTurno($turno['id']) : [];

        echo json_encode([
            'success' => true,
            'caixa' => $caixa,
            'turno' => $turno ?? [
                'status' => 'sem turno',
                'aberto_em' => null,
                'fechado_em' => null
            ],
            'resumo' => $resumo,
            'pausas' => $pausas
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = json_decode(file_get_contents("php://input"), true);
    $acao = $dados['acao'] ?? '';

    if ($acao === 'cadastrar_caixa') {
        $nome = trim($dados['nome'] ?? '');
        $email = trim($dados['email'] ?? '');
        $senha = trim($dados['senha'] ?? '');

        if (empty($nome) || empty($email) || empty($senha)) {
            echo json_encode([
                'success' => false,
                'message' => 'Preencha todos os campos.'
            ]);
            exit;
        }

        if ($usuarioDAO->buscarPorEmail($email)) {
            echo json_encode([
                'success' => false,
                'message' => 'E-mail já cadastrado.'
            ]);
            exit;
        }

        $usuarioDAO->cadastrarCaixa($nome, $email, $senha);

        echo json_encode([
            'success' => true,
            'message' => 'Caixa cadastrado com sucesso.'
        ]);
        exit;
    }

    if ($acao === 'excluir_caixa') {
        $id = intval($dados['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Caixa inválido.'
            ]);
            exit;
        }

        $usuarioDAO->desativarCaixa($id);

        echo json_encode([
            'success' => true,
            'message' => 'Caixa desativado com sucesso.'
        ]);
        exit;
    }
}

echo json_encode([
    'success' => false,
    'message' => 'Ação inválida.'
]);