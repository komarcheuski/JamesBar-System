<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: AdmController.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Controla ações do administrador, como gestão de caixas, abertura/encerramento
| de turno do dia e consulta de status.
|
| SEGURANÇA APLICADA:
| - Verificação de sessão e perfil administrador antes das ações.
| - Validação de CSRF em requisições que alteram dados.
| - Auditoria de ações administrativas em logs_sistema.
*/
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../dao/TurnoDAO.php';
require_once __DIR__ . '/../dao/LogDAO.php';
require_once __DIR__ . '/../security/SecurityHelper.php';

$usuarioDAO = new UsuarioDAO();
$turnoDAO = new TurnoDAO();
$logDAO = new LogDAO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $acao = jb_sanitize_text($_GET['acao'] ?? '', 50);

    if ($acao === 'usuario_logado') {
        jb_require_login('adm');

        jb_json_response([
            'success' => true,
            'nome' => $_SESSION['usuario_nome'] ?? 'Administrador'
        ]);
    }

    if ($acao === 'listar_caixas') {
        jb_require_login('adm');

        jb_json_response([
            'success' => true,
            'caixas' => $usuarioDAO->listarCaixas()
        ]);
    }

    if ($acao === 'detalhes_caixa') {
        jb_require_login('adm');

        $caixaId = filter_input(INPUT_GET, 'caixa_id', FILTER_VALIDATE_INT) ?: 0;

        if ($caixaId <= 0) {
            jb_json_response([
                'success' => false,
                'message' => 'Caixa inválido.'
            ]);
        }

        $caixa = $usuarioDAO->buscarPorId($caixaId);

        if (!$caixa || ($caixa['tipo'] ?? '') !== 'caixa') {
            jb_json_response([
                'success' => false,
                'message' => 'Caixa não encontrado.'
            ]);
        }

        $turno = $turnoDAO->buscarTurnoHoje($caixaId);
        $resumo = $turnoDAO->buscarResumoOperacional($caixaId);
        $pausas = $turno ? $turnoDAO->listarPausasDoTurno((int) $turno['id']) : [];

        jb_json_response([
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
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    jb_require_login('adm');

    $dados = json_decode(file_get_contents('php://input'), true);

    if (!is_array($dados)) {
        jb_json_response([
            'success' => false,
            'message' => 'Dados inválidos.'
        ]);
    }

    jb_require_csrf($dados);

    $acao = jb_sanitize_text($dados['acao'] ?? '', 50);

    if ($acao === 'cadastrar_caixa') {
        $nome = jb_sanitize_text($dados['nome'] ?? '', 100);
        $email = jb_email($dados['email'] ?? '');
        $senha = (string) ($dados['senha'] ?? '');

        if ($nome === '' || $email === '' || $senha === '') {
            jb_json_response([
                'success' => false,
                'message' => 'Preencha todos os campos.'
            ]);
        }

        if (!jb_validate_nome($nome)) {
            jb_json_response([
                'success' => false,
                'message' => 'Nome inválido. Use apenas letras, espaços, apóstrofo, ponto ou hífen.'
            ]);
        }

        if (!jb_validate_email($email)) {
            jb_json_response([
                'success' => false,
                'message' => 'E-mail inválido.'
            ]);
        }

        if (!jb_validate_senha_forte($senha)) {
            jb_json_response([
                'success' => false,
                'message' => 'Senha inválida. Use no mínimo 8 caracteres com maiúscula, minúscula, número e símbolo.'
            ]);
        }

        if ($usuarioDAO->buscarPorEmail($email)) {
            jb_json_response([
                'success' => false,
                'message' => 'E-mail já cadastrado.'
            ]);
        }

        $usuarioDAO->cadastrarCaixa($nome, $email, $senha);
        $logDAO->registrar($_SESSION['usuario_id'] ?? null, 'CADASTRAR_CAIXA', 'Caixa cadastrado: ' . $email);

        jb_json_response([
            'success' => true,
            'message' => 'Caixa cadastrado com sucesso.'
        ]);
    }

    if ($acao === 'excluir_caixa') {
        $id = filter_var($dados['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;

        if ($id <= 0) {
            jb_json_response([
                'success' => false,
                'message' => 'Caixa inválido.'
            ]);
        }

        $usuarioDAO->desativarCaixa($id);
        $logDAO->registrar($_SESSION['usuario_id'] ?? null, 'DESATIVAR_CAIXA', 'Caixa ID desativado: ' . $id);

        jb_json_response([
            'success' => true,
            'message' => 'Caixa desativado com sucesso.'
        ]);
    }
}

jb_json_response([
    'success' => false,
    'message' => 'Ação inválida.'
]);
