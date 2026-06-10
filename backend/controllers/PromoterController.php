<?php



session_start();



header('Content-Type: application/json');



require_once __DIR__ . '/../dao/PromoterDAO.php';



$promoterDAO = new PromoterDAO();



if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $acao = $_GET['acao'] ?? '';



    if ($acao === 'listar_promoters') {

        echo json_encode([

            'success' => true,

            'promoters' => $promoterDAO->listarTodos()

        ]);

        exit;

    }



    if ($acao === 'listar_dias') {

        echo json_encode([

            'success' => true,

            'dias' => $promoterDAO->listarDias()

        ]);

        exit;

    }



    if ($acao === 'listar_listas_promoters') {

        $diaId = $_GET['dia_id'] ?? null;



        echo json_encode([

            'success' => true,

            'listas' => $promoterDAO->listarListasPromoters($diaId)

        ]);

        exit;

    }



    if ($acao === 'listar_listas_aniversario') {

        echo json_encode([

            'success' => true,

            'listas' => $promoterDAO->listarListasAniversario()

        ]);

        exit;

    }



    if ($acao === 'status_envio') {

        $dataEvento = $_GET['data_evento'] ?? date('Y-m-d');



        echo json_encode([

            'success' => true,

            'status' => $promoterDAO->statusEnvioPromoters($dataEvento)

        ]);

        exit;

    }

}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dados = json_decode(file_get_contents("php://input"), true);

    $acao = $dados['acao'] ?? '';



    if ($acao === 'cadastrar_promoter') {

        $nome = trim($dados['nome'] ?? '');

        $telefone = trim($dados['telefone'] ?? '');

        $dias = $dados['dias'] ?? [];



        if ($nome === '') {

            echo json_encode([

                'success' => false,

                'message' => 'Nome obrigatório.'

            ]);

            exit;

        }



        $promoterDAO->cadastrarPromoter($nome, $telefone, $dias);



        echo json_encode([

            'success' => true,

            'message' => 'Promoter cadastrado com sucesso.'

        ]);

        exit;

    }



    if ($acao === 'editar_promoter') {

        $id = intval($dados['id'] ?? 0);

        $nome = trim($dados['nome'] ?? '');

        $telefone = trim($dados['telefone'] ?? '');

        $dias = $dados['dias'] ?? [];



        if ($id <= 0 || $nome === '') {

            echo json_encode([

                'success' => false,

                'message' => 'Dados inválidos.'

            ]);

            exit;

        }



        $promoterDAO->editarPromoter($id, $nome, $telefone, $dias);



        echo json_encode([

            'success' => true,

            'message' => 'Promoter atualizado com sucesso.'

        ]);

        exit;

    }



    if ($acao === 'excluir_promoter') {

        $id = intval($dados['id'] ?? 0);



        if ($id <= 0) {

            echo json_encode([

                'success' => false,

                'message' => 'Promoter inválido.'

            ]);

            exit;

        }



        $promoterDAO->excluirPromoter($id);



        echo json_encode([

            'success' => true,

            'message' => 'Promoter excluído com sucesso.'

        ]);

        exit;

    }



    if ($acao === 'cadastrar_lista_promoter') {

        $promoterId = intval($dados['promoter_id'] ?? 0);

        $diaId = intval($dados['dia_id'] ?? 0);

        $dataLista = trim($dados['data_lista'] ?? '');

        $convidados = $dados['convidados'] ?? [];

        $vips = $dados['vips'] ?? [];



        if ($promoterId <= 0 || $diaId <= 0 || $dataLista === '') {

            echo json_encode([

                'success' => false,

                'message' => 'Preencha promoter, dia e data da lista.'

            ]);

            exit;

        }



        echo json_encode(

            $promoterDAO->cadastrarListaPromoter(

                $promoterId,

                $diaId,

                $dataLista,

                $convidados,

                $vips

            )

        );

        exit;

    }



    if ($acao === 'cadastrar_lista_aniversario') {

        $nome = trim($dados['aniversariante_nome'] ?? '');

        $cpf = trim($dados['aniversariante_cpf'] ?? '');

        $dataEvento = trim($dados['data_evento'] ?? '');

        $convidados = $dados['convidados'] ?? [];



        if ($nome === '' || $dataEvento === '') {

            echo json_encode([

                'success' => false,

                'message' => 'Nome do aniversariante e data do evento são obrigatórios.'

            ]);

            exit;

        }



        echo json_encode(

            $promoterDAO->cadastrarListaAniversario(

                $nome,

                $cpf,

                $dataEvento,

                $convidados

            )

        );

        exit;

    }

}



echo json_encode([

    'success' => false,

    'message' => 'Ação inválida.'

]);