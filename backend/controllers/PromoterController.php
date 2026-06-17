<?php

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../dao/PromoterDAO.php';
require_once __DIR__ . '/../security/SecurityHelper.php';

$promoterDAO = new PromoterDAO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jb_require_login('adm');

    $acao = jb_sanitize_text($_GET['acao'] ?? '', 50);

    if ($acao === 'listar_promoters') {
        jb_json_response([
            'success' => true,
            'promoters' => $promoterDAO->listarTodos()
        ]);
    }

    if ($acao === 'listar_dias') {
        jb_json_response([
            'success' => true,
            'dias' => $promoterDAO->listarDias()
        ]);
    }

    if ($acao === 'listar_listas_promoters') {
        $diaId = filter_input(INPUT_GET, 'dia_id', FILTER_VALIDATE_INT) ?: null;

        jb_json_response([
            'success' => true,
            'listas' => $promoterDAO->listarListasPromoters($diaId)
        ]);
    }

    if ($acao === 'listar_listas_aniversario') {
        jb_json_response([
            'success' => true,
            'listas' => $promoterDAO->listarListasAniversario()
        ]);
    }

    if ($acao === 'status_envio') {
        $dataEvento = jb_sanitize_text($_GET['data_evento'] ?? date('Y-m-d'), 10);

        if (!jb_validate_date($dataEvento)) {
            $dataEvento = date('Y-m-d');
        }

        jb_json_response([
            'success' => true,
            'status' => $promoterDAO->statusEnvioPromoters($dataEvento)
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

    $acao = jb_sanitize_text($dados['acao'] ?? '', 50);

    if ($acao === 'cadastrar_promoter') {
        $nome = jb_sanitize_text($dados['nome'] ?? '', 100);
        $telefone = jb_sanitize_text($dados['telefone'] ?? '', 20);
        $dias = is_array($dados['dias'] ?? null) ? $dados['dias'] : [];

        if ($nome === '') {
            jb_json_response([
                'success' => false,
                'message' => 'Nome obrigatório.'
            ]);
        }

        if (!jb_validate_nome($nome)) {
            jb_json_response([
                'success' => false,
                'message' => 'Nome inválido.'
            ]);
        }

        if (!jb_validate_telefone($telefone)) {
            jb_json_response([
                'success' => false,
                'message' => 'Telefone inválido.'
            ]);
        }

        if (!jb_validate_dias($dias)) {
            jb_json_response([
                'success' => false,
                'message' => 'Dias inválidos.'
            ]);
        }

        $promoterDAO->cadastrarPromoter($nome, $telefone, $dias);

        jb_json_response([
            'success' => true,
            'message' => 'Promoter cadastrado com sucesso.'
        ]);
    }

    if ($acao === 'editar_promoter') {
        $id = filter_var($dados['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $nome = jb_sanitize_text($dados['nome'] ?? '', 100);
        $telefone = jb_sanitize_text($dados['telefone'] ?? '', 20);
        $dias = is_array($dados['dias'] ?? null) ? $dados['dias'] : [];

        if ($id <= 0 || $nome === '') {
            jb_json_response([
                'success' => false,
                'message' => 'Dados inválidos.'
            ]);
        }

        if (!jb_validate_nome($nome)) {
            jb_json_response([
                'success' => false,
                'message' => 'Nome inválido.'
            ]);
        }

        if (!jb_validate_telefone($telefone)) {
            jb_json_response([
                'success' => false,
                'message' => 'Telefone inválido.'
            ]);
        }

        if (!jb_validate_dias($dias)) {
            jb_json_response([
                'success' => false,
                'message' => 'Dias inválidos.'
            ]);
        }

        $promoterDAO->editarPromoter($id, $nome, $telefone, $dias);

        jb_json_response([
            'success' => true,
            'message' => 'Promoter atualizado com sucesso.'
        ]);
    }

    if ($acao === 'excluir_promoter') {
        $id = filter_var($dados['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;

        if ($id <= 0) {
            jb_json_response([
                'success' => false,
                'message' => 'Promoter inválido.'
            ]);
        }

        $promoterDAO->excluirPromoter($id);

        jb_json_response([
            'success' => true,
            'message' => 'Promoter excluído com sucesso.'
        ]);
    }

    if ($acao === 'cadastrar_lista_promoter') {
        $promoterId = filter_var($dados['promoter_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $diaId = filter_var($dados['dia_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $dataLista = jb_sanitize_text($dados['data_lista'] ?? '', 10);
        $convidados = is_array($dados['convidados'] ?? null) ? jb_sanitize_pessoas($dados['convidados'], 5) : [];
        $vips = is_array($dados['vips'] ?? null) ? jb_sanitize_pessoas($dados['vips'], 20) : [];

        if ($promoterId <= 0 || $diaId <= 0 || $dataLista === '') {
            jb_json_response([
                'success' => false,
                'message' => 'Preencha promoter, dia e data da lista.'
            ]);
        }

        if (!jb_validate_date($dataLista)) {
            jb_json_response([
                'success' => false,
                'message' => 'Data da lista inválida.'
            ]);
        }

        jb_json_response(
            $promoterDAO->cadastrarListaPromoter(
                $promoterId,
                $diaId,
                $dataLista,
                $convidados,
                $vips
            )
        );
    }

    if ($acao === 'cadastrar_lista_aniversario') {
        $nome = jb_sanitize_text($dados['aniversariante_nome'] ?? '', 100);
        $cpf = jb_sanitize_cpf($dados['aniversariante_cpf'] ?? '');
        $dataEvento = jb_sanitize_text($dados['data_evento'] ?? '', 10);
        $convidados = is_array($dados['convidados'] ?? null) ? jb_sanitize_pessoas($dados['convidados'], 20) : [];

        if ($nome === '' || $dataEvento === '') {
            jb_json_response([
                'success' => false,
                'message' => 'Nome do aniversariante e data do evento são obrigatórios.'
            ]);
        }

        if (!jb_validate_nome($nome)) {
            jb_json_response([
                'success' => false,
                'message' => 'Nome do aniversariante inválido.'
            ]);
        }

        if ($cpf !== '' && !jb_validate_cpf($cpf)) {
            jb_json_response([
                'success' => false,
                'message' => 'CPF do aniversariante inválido.'
            ]);
        }

        if (!jb_validate_date($dataEvento)) {
            jb_json_response([
                'success' => false,
                'message' => 'Data do evento inválida.'
            ]);
        }

        jb_json_response(
            $promoterDAO->cadastrarListaAniversario(
                $nome,
                $cpf,
                $dataEvento,
                $convidados
            )
        );
    }
}

jb_json_response([
    'success' => false,
    'message' => 'Ação inválida.'
]);
