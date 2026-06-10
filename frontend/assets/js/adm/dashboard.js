document.addEventListener('DOMContentLoaded', () => {
    carregarNomeAdm();
    listarCaixas();

    document.getElementById('btnIniciarTurno').addEventListener('click', iniciarTurnoDia);
    document.getElementById('btnEncerrarTurnoDia').addEventListener('click', encerrarTurnoDia);
    document.getElementById('btnSair').addEventListener('click', sair);

    document.getElementById('btnAbrirCadastroCaixa').addEventListener('click', () => {
        document.getElementById('modalCadastroCaixa').classList.add('ativo');
    });

    document.getElementById('formCadastroCaixa').addEventListener('submit', cadastrarCaixa);
});

async function carregarNomeAdm() {
    try {
        const response = await fetch('../../../backend/controllers/AdmController.php?acao=usuario_logado');
        const data = await response.json();

        if (data.success) {
            document.getElementById('nomeAdm').innerText = data.nome;
        }

    } catch (error) {
        console.error(error);
    }
}

async function listarCaixas() {
    try {
        const response = await fetch('../../../backend/controllers/AdmController.php?acao=listar_caixas');
        const data = await response.json();

        const lista = document.getElementById('listaCaixas');
        lista.innerHTML = '';

        if (!data.success || data.caixas.length === 0) {
            lista.innerHTML = '<p class="mensagem-vazia">Nenhum caixa cadastrado.</p>';
            return;
        }

        data.caixas.forEach(caixa => {
            const statusClasse = caixa.ativo == 1 ? 'status-ativo' : 'status-inativo';
            const statusTexto = caixa.ativo == 1 ? 'Ativo' : 'Inativo';

            const item = document.createElement('div');
            item.className = 'caixa-item';

            item.innerHTML = `
                <h3>${caixa.nome}</h3>
                <p>${caixa.email}</p>
                <span class="status-caixa ${statusClasse}">${statusTexto}</span>

                <div class="caixa-botoes">
                    <button class="btn-ver" onclick="verDetalhesCaixa(${caixa.id})">
                        Ver operações
                    </button>

                    <button class="btn-excluir" onclick="excluirCaixa(${caixa.id})">
                        Excluir
                    </button>
                </div>
            `;

            lista.appendChild(item);
        });

    } catch (error) {
        console.error(error);
        alert('Erro ao carregar caixas.');
    }
}

async function cadastrarCaixa(event) {
    event.preventDefault();

    const nome = document.getElementById('nomeCaixa').value.trim();
    const email = document.getElementById('emailCaixa').value.trim();
    const senha = document.getElementById('senhaCaixa').value.trim();

    try {
        const response = await fetch('../../../backend/controllers/AdmController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                acao: 'cadastrar_caixa',
                nome: nome,
                email: email,
                senha: senha
            })
        });

        const data = await response.json();

        alert(data.message);

        if (data.success) {
            document.getElementById('formCadastroCaixa').reset();
            fecharModais();
            listarCaixas();
        }

    } catch (error) {
        console.error(error);
        alert('Erro ao cadastrar caixa.');
    }
}

async function excluirCaixa(id) {
    const confirmar = confirm('Deseja realmente excluir este caixa? Ele será desativado para preservar o histórico.');

    if (!confirmar) return;

    try {
        const response = await fetch('../../../backend/controllers/AdmController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                acao: 'excluir_caixa',
                id: id
            })
        });

        const data = await response.json();

        alert(data.message);

        if (data.success) {
            listarCaixas();
        }

    } catch (error) {
        console.error(error);
        alert('Erro ao excluir caixa.');
    }
}

async function verDetalhesCaixa(id) {
    try {
        const response = await fetch(`../../../backend/controllers/AdmController.php?acao=detalhes_caixa&caixa_id=${id}`);
        const data = await response.json();

        if (!data.success) {
            alert(data.message);
            return;
        }

        document.getElementById('detalheNomeCaixa').innerText = `Operações de ${data.caixa.nome}`;
        document.getElementById('detalheEntradas').innerText = data.resumo.total_entradas;
        document.getElementById('detalheSaidas').innerText = data.resumo.total_saidas;
        document.getElementById('detalhePausas').innerText = data.resumo.total_pausas;

        document.getElementById('detalheInicioTurno').innerText = data.turno.aberto_em ?? '-';
        document.getElementById('detalheFimTurno').innerText = data.turno.fechado_em ?? '-';
        document.getElementById('detalheStatusTurno').innerText = data.turno.status ?? 'sem turno';

        const listaPausas = document.getElementById('listaPausas');
        listaPausas.innerHTML = '';

        if (data.pausas.length === 0) {
            listaPausas.innerHTML = '<div class="pausa-item">Nenhuma pausa registrada neste turno.</div>';
        } else {
            data.pausas.forEach(pausa => {
                const item = document.createElement('div');
                item.className = 'pausa-item';
                item.innerHTML = `
                    <strong>Início:</strong> ${pausa.inicio_pausa}
                    <br>
                    <strong>Fim:</strong> ${pausa.fim_pausa ?? 'Em andamento'}
                `;
                listaPausas.appendChild(item);
            });
        }

        document.getElementById('modalDetalhesCaixa').classList.add('ativo');

    } catch (error) {
        console.error(error);
        alert('Erro ao carregar detalhes do caixa.');
    }
}

function iniciarTurnoDia() {
    alert('Turno do dia liberado para os caixas.');
}

function encerrarTurnoDia() {
    alert('Turno do dia encerrado. Os caixas serão bloqueados quando conectarmos essa regra ao backend geral.');
}

function sair() {
    window.location.href = '../auth/login.html';
}

function fecharModais() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('ativo');
    });
}