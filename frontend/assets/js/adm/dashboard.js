document.addEventListener('DOMContentLoaded', () => {
    carregarDiaSemanaAtual();
    preencherDataHoje();
    gerarCamposListas();

    carregarNomeAdm();
    listarCaixas();
    listarPromoters();
    carregarDiasFiltro();
    carregarSelectsListaPromoter();
    listarListasPromoters();
    listarListasAniversario();
    carregarStatusEnvio();

    document.getElementById('btnIniciarTurno').addEventListener('click', iniciarTurnoDia);
    document.getElementById('btnEncerrarTurnoDia').addEventListener('click', encerrarTurnoDia);
    document.getElementById('btnSair').addEventListener('click', sair);

    document.getElementById('btnAbrirCadastroCaixa').addEventListener('click', () => {
        document.getElementById('modalCadastroCaixa').classList.add('ativo');
    });

    document.getElementById('btnAbrirCadastroPromoter').addEventListener('click', abrirCadastroPromoter);
    document.getElementById('btnAbrirListaPromoter').addEventListener('click', abrirListaPromoter);
    document.getElementById('btnAbrirListaAniversario').addEventListener('click', abrirListaAniversario);

    document.getElementById('formCadastroCaixa').addEventListener('submit', cadastrarCaixa);
    document.getElementById('formPromoter').addEventListener('submit', salvarPromoter);
    document.getElementById('formListaPromoter').addEventListener('submit', cadastrarListaPromoter);
    document.getElementById('formListaAniversario').addEventListener('submit', cadastrarListaAniversario);

    document.getElementById('filtroDiaLista').addEventListener('change', listarListasPromoters);
    document.getElementById('dataStatusEnvio').addEventListener('change', carregarStatusEnvio);
});

function carregarDiaSemanaAtual() {
    const dias = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
    document.getElementById('diaSemanaAtual').innerText = dias[new Date().getDay()];
}

function preencherDataHoje() {
    const hoje = new Date().toISOString().split('T')[0];
    document.getElementById('dataStatusEnvio').value = hoje;
}

function gerarCamposListas() {
    gerarCampos('camposConvidadosPromoter', 5, 'convidadoPromoter');
    gerarCampos('camposVipsPromoter', 20, 'vipPromoter');
    gerarCampos('camposConvidadosAniversario', 20, 'convidadoAniversario');
}

function gerarCampos(containerId, quantidade, prefixo) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';

    for (let i = 1; i <= quantidade; i++) {
        const div = document.createElement('div');
        div.className = 'linha-nome-lista';

        div.innerHTML = `
            <input type="text" id="${prefixo}Nome${i}" placeholder="Nome ${i}">
            <input type="text" id="${prefixo}Cpf${i}" placeholder="CPF ${i}">
        `;

        container.appendChild(div);
    }
}

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
                    <button class="btn-ver" onclick="verDetalhesCaixa(${caixa.id})">Ver operações</button>
                    <button class="btn-excluir" onclick="excluirCaixa(${caixa.id})">Excluir</button>
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
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                acao: 'cadastrar_caixa',
                nome,
                email,
                senha
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
    if (!confirm('Deseja realmente excluir este caixa? Ele será desativado para preservar o histórico.')) return;

    try {
        const response = await fetch('../../../backend/controllers/AdmController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                acao: 'excluir_caixa',
                id
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

        alert(
            `Operações de ${data.caixa.nome}\n\n` +
            `Entradas: ${data.resumo.total_entradas}\n` +
            `Saídas: ${data.resumo.total_saidas}\n` +
            `Pausas: ${data.resumo.total_pausas}`
        );

    } catch (error) {
        console.error(error);
        alert('Erro ao carregar detalhes do caixa.');
    }
}

async function listarPromoters() {
    try {
        const response = await fetch('../../../backend/controllers/PromoterController.php?acao=listar_promoters');
        const data = await response.json();

        const lista = document.getElementById('listaPromoters');
        lista.innerHTML = '';

        if (!data.success || data.promoters.length === 0) {
            lista.innerHTML = '<p class="mensagem-vazia">Nenhum promoter cadastrado.</p>';
            return;
        }

        data.promoters.forEach(promoter => {
            const item = document.createElement('div');
            item.className = 'caixa-item';

            item.innerHTML = `
                <h3>${promoter.nome}</h3>
                <p>${promoter.telefone || 'Sem telefone'}</p>
                <p><strong>Dias:</strong> ${montarTextoDiasPromoter(promoter)}</p>

                <div class="caixa-botoes">
                    <button class="btn-ver" onclick='abrirEditarPromoter(${JSON.stringify(promoter)})'>Editar</button>
                    <button class="btn-excluir" onclick="excluirPromoter(${promoter.id})">Excluir</button>
                </div>
            `;

            lista.appendChild(item);
        });

    } catch (error) {
        console.error(error);
        alert('Erro ao carregar promoters.');
    }
}

function montarTextoDiasPromoter(promoter) {
    const dias = [];

    if (promoter.lista_quarta == 1) dias.push('quarta');
    if (promoter.lista_quinta == 1) dias.push('quinta');
    if (promoter.lista_sexta == 1) dias.push('sexta');
    if (promoter.lista_sabado == 1) dias.push('sábado');
    if (promoter.lista_domingo == 1) dias.push('domingo');

    return dias.length ? dias.join(', ') : 'nenhum';
}

function abrirCadastroPromoter() {
    document.getElementById('tituloModalPromoter').innerText = 'Cadastrar Promoter';
    document.getElementById('promoterId').value = '';
    document.getElementById('nomePromoter').value = '';
    document.getElementById('telefonePromoter').value = '';

    document.querySelectorAll('.dia-promoter').forEach(input => input.checked = false);

    document.getElementById('modalPromoter').classList.add('ativo');
}

function abrirEditarPromoter(promoter) {
    document.getElementById('tituloModalPromoter').innerText = 'Editar Promoter';
    document.getElementById('promoterId').value = promoter.id;
    document.getElementById('nomePromoter').value = promoter.nome;
    document.getElementById('telefonePromoter').value = promoter.telefone || '';

    document.querySelectorAll('.dia-promoter').forEach(input => input.checked = false);

    if (promoter.lista_quarta == 1) marcarDia('quarta');
    if (promoter.lista_quinta == 1) marcarDia('quinta');
    if (promoter.lista_sexta == 1) marcarDia('sexta');
    if (promoter.lista_sabado == 1) marcarDia('sabado');
    if (promoter.lista_domingo == 1) marcarDia('domingo');

    document.getElementById('modalPromoter').classList.add('ativo');
}

function marcarDia(dia) {
    const input = document.querySelector(`.dia-promoter[value="${dia}"]`);
    if (input) input.checked = true;
}

async function salvarPromoter(event) {
    event.preventDefault();

    const id = document.getElementById('promoterId').value;
    const nome = document.getElementById('nomePromoter').value.trim();
    const telefone = document.getElementById('telefonePromoter').value.trim();

    const dias = Array.from(document.querySelectorAll('.dia-promoter:checked')).map(input => input.value);
    const acao = id ? 'editar_promoter' : 'cadastrar_promoter';

    try {
        const response = await fetch('../../../backend/controllers/PromoterController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                acao,
                id,
                nome,
                telefone,
                dias
            })
        });

        const data = await response.json();
        alert(data.message);

        if (data.success) {
            fecharModais();
            listarPromoters();
            carregarSelectsListaPromoter();
            carregarStatusEnvio();
        }
    } catch (error) {
        console.error(error);
        alert('Erro ao salvar promoter.');
    }
}

async function excluirPromoter(id) {
    if (!confirm('Deseja excluir este promoter?')) return;

    try {
        const response = await fetch('../../../backend/controllers/PromoterController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                acao: 'excluir_promoter',
                id
            })
        });

        const data = await response.json();
        alert(data.message);

        if (data.success) {
            listarPromoters();
            carregarSelectsListaPromoter();
            listarListasPromoters();
            carregarStatusEnvio();
        }
    } catch (error) {
        console.error(error);
        alert('Erro ao excluir promoter.');
    }
}

async function carregarDiasFiltro() {
    try {
        const response = await fetch('../../../backend/controllers/PromoterController.php?acao=listar_dias');
        const data = await response.json();

        const filtro = document.getElementById('filtroDiaLista');
        const selectDiaLista = document.getElementById('selectDiaLista');

        filtro.innerHTML = '<option value="">Todos os dias</option>';
        selectDiaLista.innerHTML = '<option value="">Selecione o dia</option>';

        data.dias.forEach(dia => {
            filtro.innerHTML += `<option value="${dia.id}">${dia.nome}</option>`;
            selectDiaLista.innerHTML += `<option value="${dia.id}">${dia.nome}</option>`;
        });
    } catch (error) {
        console.error(error);
    }
}

async function carregarSelectsListaPromoter() {
    try {
        const response = await fetch('../../../backend/controllers/PromoterController.php?acao=listar_promoters');
        const data = await response.json();

        const select = document.getElementById('selectPromoterLista');
        select.innerHTML = '<option value="">Selecione o promoter</option>';

        if (data.success) {
            data.promoters.forEach(promoter => {
                select.innerHTML += `<option value="${promoter.id}">${promoter.nome}</option>`;
            });
        }
    } catch (error) {
        console.error(error);
    }
}

function abrirListaPromoter() {
    document.getElementById('formListaPromoter').reset();
    document.getElementById('modalListaPromoter').classList.add('ativo');
}

function abrirListaAniversario() {
    document.getElementById('formListaAniversario').reset();
    document.getElementById('modalListaAniversario').classList.add('ativo');
}

async function cadastrarListaPromoter(event) {
    event.preventDefault();

    const convidados = coletarPessoas('convidadoPromoter', 5);
    const vips = coletarPessoas('vipPromoter', 20);

    try {
        const response = await fetch('../../../backend/controllers/PromoterController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                acao: 'cadastrar_lista_promoter',
                promoter_id: document.getElementById('selectPromoterLista').value,
                dia_id: document.getElementById('selectDiaLista').value,
                data_lista: document.getElementById('dataListaPromoter').value,
                convidados,
                vips
            })
        });

        const data = await response.json();
        alert(data.message);

        if (data.success) {
            fecharModais();
            listarListasPromoters();
            carregarStatusEnvio();
        }
    } catch (error) {
        console.error(error);
        alert('Erro ao cadastrar lista do promoter.');
    }
}

async function cadastrarListaAniversario(event) {
    event.preventDefault();

    const convidados = coletarPessoas('convidadoAniversario', 20);

    try {
        const response = await fetch('../../../backend/controllers/PromoterController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                acao: 'cadastrar_lista_aniversario',
                aniversariante_nome: document.getElementById('aniversarianteNome').value.trim(),
                aniversariante_cpf: document.getElementById('aniversarianteCpf').value.trim(),
                data_evento: document.getElementById('dataEventoAniversario').value,
                convidados
            })
        });

        const data = await response.json();
        alert(data.message);

        if (data.success) {
            fecharModais();
            listarListasAniversario();
        }
    } catch (error) {
        console.error(error);
        alert('Erro ao cadastrar lista de aniversário.');
    }
}

function coletarPessoas(prefixo, quantidade) {
    const pessoas = [];

    for (let i = 1; i <= quantidade; i++) {
        const nome = document.getElementById(`${prefixo}Nome${i}`).value.trim();
        const cpf = document.getElementById(`${prefixo}Cpf${i}`).value.trim();

        if (nome !== '') {
            pessoas.push({ nome, cpf });
        }
    }

    return pessoas;
}

async function listarListasPromoters() {
    try {
        const diaId = document.getElementById('filtroDiaLista').value;

        let url = '../../../backend/controllers/PromoterController.php?acao=listar_listas_promoters';

        if (diaId) {
            url += `&dia_id=${diaId}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        const lista = document.getElementById('listaListasPromoters');
        lista.innerHTML = '';

        if (!data.success || data.listas.length === 0) {
            lista.innerHTML = '<p class="mensagem-vazia">Nenhuma lista encontrada.</p>';
            return;
        }

        data.listas.forEach(item => {
            const div = document.createElement('div');
            div.className = 'lista-promoter-item';

            div.innerHTML = `
                <div>
                    <h3>${item.promoter_nome}</h3>
                    <p><strong>Dia:</strong> ${item.dia_nome}</p>
                    <p><strong>Data:</strong> ${item.data_lista}</p>
                    <p><strong>Convidados grátis:</strong> ${item.total_convidados}/5</p>
                    <p><strong>VIPs:</strong> ${item.total_vips}/20</p>
                </div>
                <span class="tag-bloqueada">Lista enviada - bloqueada</span>
            `;

            lista.appendChild(div);
        });
    } catch (error) {
        console.error(error);
    }
}

async function listarListasAniversario() {
    try {
        const response = await fetch('../../../backend/controllers/PromoterController.php?acao=listar_listas_aniversario');
        const data = await response.json();

        const lista = document.getElementById('listaAniversarios');
        lista.innerHTML = '';

        if (!data.success || data.listas.length === 0) {
            lista.innerHTML = '<p class="mensagem-vazia">Nenhuma lista de aniversário cadastrada.</p>';
            return;
        }

        data.listas.forEach(item => {
            const div = document.createElement('div');
            div.className = 'lista-promoter-item';

            div.innerHTML = `
                <div>
                    <h3>${item.aniversariante_nome}</h3>
                    <p><strong>CPF:</strong> ${item.aniversariante_cpf || 'não informado'}</p>
                    <p><strong>Data:</strong> ${item.data_evento}</p>
                    <p><strong>Convidados:</strong> ${item.total_convidados}/20</p>
                </div>
                <span class="tag-bloqueada">Lista enviada - bloqueada</span>
            `;

            lista.appendChild(div);
        });
    } catch (error) {
        console.error(error);
    }
}

async function carregarStatusEnvio() {
    try {
        const dataEvento = document.getElementById('dataStatusEnvio').value;

        const response = await fetch(`../../../backend/controllers/PromoterController.php?acao=status_envio&data_evento=${dataEvento}`);
        const data = await response.json();

        const lista = document.getElementById('statusEnvioPromoters');
        lista.innerHTML = '';

        if (!data.success || data.status.length === 0) {
            lista.innerHTML = '<p class="mensagem-vazia">Nenhum promoter com dia liberado para essa data.</p>';
            return;
        }

        data.status.forEach(item => {
            const div = document.createElement('div');
            div.className = 'lista-promoter-item';

            const classe = item.status_envio === 'enviou' ? 'status-enviou' : 'status-nao-enviou';
            const texto = item.status_envio === 'enviou' ? 'Enviou lista' : 'Não enviou lista';

            div.innerHTML = `
                <div>
                    <h3>${item.nome}</h3>
                    <p><strong>Dia liberado:</strong> ${item.dia_nome}</p>
                    <p><strong>Telefone:</strong> ${item.telefone || 'não informado'}</p>
                </div>
                <span class="${classe}">${texto}</span>
            `;

            lista.appendChild(div);
        });
    } catch (error) {
        console.error(error);
    }
}

function iniciarTurnoDia() {
    alert('Turno do dia liberado para os caixas.');
}

function encerrarTurnoDia() {
    alert('Turno do dia encerrado.');
}

function sair() {
    window.location.href = '../auth/login.html';
}

function fecharModais() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('ativo');
    });
}