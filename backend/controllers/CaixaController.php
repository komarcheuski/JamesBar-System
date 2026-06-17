<?php

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../dao/ClienteDAO.php';
require_once __DIR__ . '/../dao/MovimentacaoDAO.php';
require_once __DIR__ . '/../dao/TurnoDAO.php';
require_once __DIR__ . '/../security/SecurityHelper.php';

$clienteDAO = new ClienteDAO();
$movimentacaoDAO = new MovimentacaoDAO();
$turnoDAO = new TurnoDAO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    jb_require_login('caixa');

    $dados = json_decode(file_get_contents('php://input'), true);

    if (!is_array($dados)) {
        jb_json_response([
            'success' => false,
            'message' => 'Dados inválidos.'
        ]);
    }

    $acao = jb_sanitize_text($dados['acao'] ?? '', 50);

    if ($acao === 'pesquisar_cpf') {
        $cpf = jb_sanitize_cpf($dados['cpf'] ?? '');

        if (!jb_validate_cpf($cpf)) {
            jb_json_response([
                'success' => false,
                'message' => 'CPF inválido. Use 000.000.000-00.'
            ]);
        }

        $cliente = $clienteDAO->buscarPorCpf($cpf);

        if ($cliente) {
            jb_json_response([
                'success' => true,
                'message' => 'Cliente encontrado.',
                'cliente' => $cliente
            ]);
        }

        jb_json_response([
            'success' => false,
            'message' => 'Cliente não cadastrado.'
        ]);
    }

    if ($acao === 'cadastrar_cliente') {
        $nome = jb_sanitize_text($dados['nome'] ?? '', 100);
        $cpf = jb_sanitize_cpf($dados['cpf'] ?? '');
        $dataAniversario = jb_sanitize_text($dados['data_aniversario'] ?? '', 10);

        if ($nome === '' || $cpf === '' || $dataAniversario === '') {
            jb_json_response([
                'success' => false,
                'message' => 'Preencha todos os campos.'
            ]);
        }

        if (!jb_validate_nome($nome)) {
            jb_json_response([
                'success' => false,
                'message' => 'Nome inválido.'
            ]);
        }

        if (!jb_validate_cpf($cpf)) {
            jb_json_response([
                'success' => false,
                'message' => 'CPF inválido. Use 000.000.000-00.'
            ]);
        }

        if (!jb_validate_date($dataAniversario)) {
            jb_json_response([
                'success' => false,
                'message' => 'Data de aniversário inválida.'
            ]);
        }

        if ($clienteDAO->buscarPorCpf($cpf)) {
            jb_json_response([
                'success' => false,
                'message' => 'CPF já cadastrado.'
            ]);
        }

        $clienteId = $clienteDAO->cadastrar($nome, $cpf, $dataAniversario);

        jb_json_response([
            'success' => true,
            'message' => 'Cliente cadastrado com sucesso.',
            'cliente' => [
                'id' => (int) $clienteId,
                'nome' => $nome,
                'cpf' => $cpf
            ]
        ]);
    }

    if ($acao === 'liberar_entrada') {
        $clienteId = filter_var($dados['cliente_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;

        if ($clienteId <= 0) {
            jb_json_response([
                'success' => false,
                'message' => 'Cliente inválido.'
            ]);
        }

        $caixaId = (int) $_SESSION['usuario_id'];
        $movimentacaoDAO->registrarEntrada($clienteId, $caixaId);

        jb_json_response([
            'success' => true,
            'message' => 'Entrada liberada com sucesso!'
        ]);
    }

    if ($acao === 'pausar_turno') {
        $caixaId = (int) $_SESSION['usuario_id'];
        $turnoDAO->pausarTurno($caixaId);

        session_unset();
        session_destroy();

        jb_json_response([
            'success' => true,
            'message' => 'Pausa acionada com sucesso.',
            'redirect' => '../auth/login.html'
        ]);
    }

    if ($acao === 'encerrar_turno') {
        $caixaId = (int) $_SESSION['usuario_id'];
        $turnoDAO->fecharTurno($caixaId);

        session_unset();
        session_destroy();

        jb_json_response([
            'success' => true,
            'message' => 'ATE A PROXIMA!! :))',
            'redirect' => '../auth/login.html'
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jb_require_login('caixa');

    $acao = jb_sanitize_text($_GET['acao'] ?? '', 50);

    if ($acao === 'contador') {
        $caixaId = (int) $_SESSION['usuario_id'];
        $contador = $movimentacaoDAO->buscarContadores($caixaId);

        jb_json_response([
            'success' => true,
            'total_entradas' => (int) ($contador['total_entradas'] ?? 0),
            'total_saidas' => (int) ($contador['total_saidas'] ?? 0)
        ]);
    }
}

jb_json_response([
    'success' => false,
    'message' => 'Ação inválida.'
]);
