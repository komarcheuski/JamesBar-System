<?php

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../dao/ClienteDAO.php';
require_once __DIR__ . '/../dao/MovimentacaoDAO.php';
require_once __DIR__ . '/../dao/TurnoDAO.php';

$clienteDAO = new ClienteDAO();
$movimentacaoDAO = new MovimentacaoDAO();
$turnoDAO = new TurnoDAO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = json_decode(file_get_contents("php://input"), true);

    $acao = $dados['acao'] ?? '';

    if ($acao === 'pesquisar_cpf') {
        $cpf = trim($dados['cpf'] ?? '');

        $cliente = $clienteDAO->buscarPorCpf($cpf);

        if ($cliente) {
            echo json_encode([
                'success' => true,
                'message' => 'Cliente encontrado.',
                'cliente' => $cliente
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Cliente não cadastrado.'
            ]);
        }

        exit;
    }

    if ($acao === 'cadastrar_cliente') {
        $nome = trim($dados['nome'] ?? '');
        $cpf = trim($dados['cpf'] ?? '');
        $dataAniversario = trim($dados['data_aniversario'] ?? '');

        if (empty($nome) || empty($cpf) || empty($dataAniversario)) {
            echo json_encode([
                'success' => false,
                'message' => 'Preencha todos os campos.'
            ]);
            exit;
        }

        $clienteExistente = $clienteDAO->buscarPorCpf($cpf);

        if ($clienteExistente) {
            echo json_encode([
                'success' => false,
                'message' => 'CPF já cadastrado.'
            ]);
            exit;
        }

        $clienteId = $clienteDAO->cadastrar($nome, $cpf, $dataAniversario);

        echo json_encode([
            'success' => true,
            'message' => 'Cliente cadastrado com sucesso.',
            'cliente' => [
                'id' => $clienteId,
                'nome' => $nome,
                'cpf' => $cpf
            ]
        ]);

        exit;
    }

    if ($acao === 'liberar_entrada') {
        $clienteId = intval($dados['cliente_id'] ?? 0);

        if ($clienteId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Cliente inválido.'
            ]);
            exit;
        }

        $caixaId = $_SESSION['usuario_id'] ?? 2;

        $movimentacaoDAO->registrarEntrada($clienteId, $caixaId);

        echo json_encode([
            'success' => true,
            'message' => 'Entrada liberada com sucesso!'
        ]);

        exit;
    }

    if ($acao === 'pausar_turno') {
        $caixaId = $_SESSION['usuario_id'] ?? 2;

        $turnoDAO->pausarTurno($caixaId);

        session_destroy();

        echo json_encode([
            'success' => true,
            'message' => 'Pausa acionada com sucesso.',
            'redirect' => '../auth/login.html'
        ]);

        exit;
    }

    if ($acao === 'encerrar_turno') {
        $caixaId = $_SESSION['usuario_id'] ?? 2;

        $turnoDAO->fecharTurno($caixaId);

        session_destroy();

        echo json_encode([
            'success' => true,
            'message' => 'ATE A PROXIMA!! :))',
            'redirect' => '../auth/login.html'
        ]);

        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $acao = $_GET['acao'] ?? '';

    if ($acao === 'contador') {
        $caixaId = $_SESSION['usuario_id'] ?? 2;

        $contador = $movimentacaoDAO->buscarContadores($caixaId);

        echo json_encode([
            'success' => true,
            'total_entradas' => $contador['total_entradas'] ?? 0,
            'total_saidas' => $contador['total_saidas'] ?? 0
        ]);

        exit;
    }
}

echo json_encode([
    'success' => false,
    'message' => 'Ação inválida.'
]);