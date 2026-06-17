/*
|--------------------------------------------------------------------------
| ARQUIVO: dashboard.js
|--------------------------------------------------------------------------
| FUNÇÃO:
| Script responsável por eventos da interface, chamadas fetch ao backend e
| validações do lado do cliente.
|
| SEGURANÇA APLICADA:
| - Busca token CSRF e envia em ações administrativas.
| - Validação de dados antes de ações administrativas.
*/
// Comentário de documentação JamesBar
// Arquivo: frontend/assets/js/adm/dashboard.js
// Função: Script JavaScript do JamesBar. Controla interação da tela, chamadas fetch e validações do frontend.

async function jbGetCsrfToken() {
    if (window.__jbCsrfToken) return window.__jbCsrfToken;

    try {
        const response = await window.__jbOriginalFetch('../../../backend/controllers/AuthController.php?acao=csrf_token', {
            credentials: 'same-origin'
        });
        const data = await response.json();

        if (data.success && data.csrf_token) {
            window.__jbCsrfToken = data.csrf_token;
            return data.csrf_token;
        }
    } catch (error) {
        console.error('Erro ao obter CSRF token:', error);
    }

    return '';
}

if (!window.__jbOriginalFetch) {
    window.__jbOriginalFetch = window.fetch.bind(window);

    window.fetch = async (resource, options = {}) => {
        const method = String(options.method || 'GET').toUpperCase();

        if (method === 'POST') {
            const headers = new Headers(options.headers || {});
            const token = await jbGetCsrfToken();

            if (token) {
                headers.set('X-CSRF-Token', token);

                if (typeof options.body === 'string' && headers.get('Content-Type')?.includes('application/json')) {
                    try {
                        const body = JSON.parse(options.body);
                        body.csrf_token = token;
                        options = { ...options, body: JSON.stringify(body) };
                    } catch (error) {
                        console.error('Erro ao anexar CSRF no corpo JSON:', error);
                    }
                }
            }

            options = { ...options, headers, credentials: 'same-origin' };
        }

        return window.__jbOriginalFetch(resource, options);
    };
}

function sanitizeText(value, maxLength = 255) {
    return String(value ?? '')
        .trim()
        .replace(/<[^>]*>/g, '')
        .replace(/[\u0000-\u001F\u007F]/g, '')
        .slice(0, maxLength);
}

function escapeHTML(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function validarEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email) && email.length <= 150;
}

function validarNome(nome) {
    return /^[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ\s'.-]{1,99}$/.test(nome);
}

function apenasDigitos(value) {
    return String(value ?? '').replace(/\D/g, '');
}

function formatarCpf(value) {
    const d = apenasDigitos(value).slice(0, 11);
    if (d.length <= 3) return d;
    if (d.length <= 6) return `${d.slice(0, 3)}.${d.slice(3)}`;
    if (d.length <= 9) return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6)}`;
    return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6, 9)}-${d.slice(9, 11)}`;
}

function validarCpf(cpf) {
    return /^\d{3}\.\d{3}\.\d{3}-\d{2}$/.test(cpf);
}

function validarDataISO(data) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(data)) return false;
    const parsed = new Date(`${data}T00:00:00`);
    return !Number.isNaN(parsed.getTime()) && parsed.toISOString().slice(0, 10) === data;
}

function validarSenhaForte(senha) {
    return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,72}$/.test(senha);
}

function validarTelefone(telefone) {
    return telefone === '' || /^[0-9()\s+\-]{8,20}$/.test(telefone);
}

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

    document.getElementById('btnIniciarTurno')?.addEventListener('click', iniciarTurnoDia);
    document.getElementById('btnEncerrarTurnoDia')?.addEventListener('click', encerrarTurnoDia);
    document.getElementById('btnSair')?.addEventListener('click', sair);

    document.getElementById('btnAbrirCadastroCaixa')?.addEventListener('click', () => {
        document.getElementById('modalCadastroCaixa')?.classList.add('ativo');
    });

    document.getElementById('btnAbrirCadastroPromoter')?.addEventListener('click', abrirCadastroPromoter);
    document.getElementById('btnAbrirListaPromoter')?.addEventListener('click', abrirListaPromoter);
    document.getElementById('btnAbrirListaAniversario')?.addEventListener('click', abrirListaAniversario);

    document.getElementById('formCadastroCaixa')?.addEventListener('submit', cadastrarCaixa);
    document.getElementById('formPromoter')?.addEventListener('submit', salvarPromoter);
    document.getElementById('formListaPromoter')?.addEventListener('submit', cadastrarListaPromoter);
    document.getElementById('formListaAniversario')?.addEventListener('submit', cadastrarListaAniversario);

    document.getElementById('filtroDiaLista')?.addEventListener('change', listarListasPromoters);
    document.getElementById('dataStatusEnvio')?.addEventListener('change', carregarStatusEnvio);

    aplicarMascaraCpf('aniversarianteCpf');
});

function aplicarMascaraCpf(id) {
    const input = document.getElementById(id);
    input?.addEventListener('input', () => {
        input.value = formatarCpf(input.value);
    });
}

function carregarDiaSemanaAtual() {
    const dias = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
    const el = document.getElementById('diaSemanaAtual');
    if (el) el.textContent = dias[new Date().getDay()];
}

function preencherDataHoje() {
    const input = document.getElementById('dataStatusEnvio');
    if (input) input.value = new Date().toISOString().split('T')[0];
}

function gerarCamposListas() {
    gerarCampos('camposConvidadosPromoter', 5, 'convidadoPromoter');
    gerarCampos('camposVipsPromoter', 20, 'vipPromoter');
    gerarCampos('camposConvidadosAniversario', 20, 'convidadoAniversario');
}

function gerarCampos(containerId, quantidade, prefixo) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';

    for (let i = 1; i <= quantidade; i++) {
        const div = document.createElement('div');
        div.className = 'linha-nome-lista';
        div.innerHTML = `
            <input type="text" id="${prefixo}Nome${i}" placeholder="Nome ${i}" maxlength="100">
            <input type="text" id="${prefixo}Cpf${i}" placeholder="CPF ${i}" maxlength="14">
        `;
        container.appendChild(div);
        aplicarMascaraCpf(`${prefixo}Cpf${i}`);
    }
}

async function carregarNomeAdm() {
    try {
        const response = await fetch('../../../backend/controllers/AdmController.php?acao=usuario_logado');
        const data = await response.json();
        if (data.success) document.getElementById('nomeAdm').textContent = data.nome;
    } catch (error) {
        console.error(error);
    }
}

async function listarCaixas() {
    try {
        const response = await fetch('../../../backend/controllers/AdmController.php?acao=listar_caixas');
        const data = await response.json();
        const lista = document.getElementById('listaCaixas');
        if (!lista) return;
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
                <h3>${escapeHTML(caixa.nome)}</h3>
                <p>${escapeHTML(caixa.email)}</p>
                <span class="status-caixa ${statusClasse}">${statusTexto}</span>
                <div class="caixa-botoes">
                    <button class="btn-ver" onclick="verDetalhesCaixa(${Number(caixa.id)})">Ver operações</button>
                    <button class="btn-excluir" onclick="excluirCaixa(${Number(caixa.id)})">Excluir</button>
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

    const nome = sanitizeText(document.getElementById('nomeCaixa').value, 100);
    const email = sanitizeText(document.getElementById('emailCaixa').value, 150).toLowerCase();
    const senha = String(document.getElementById('senhaCaixa').value ?? '');

    if (!validarNome(nome)) {
        alert('Nome do caixa inválido.');
        return;
    }

    if (!validarEmail(email)) {
        alert('E-mail inválido.');
        return;
    }

    if (!validarSenhaForte(senha)) {
        alert('Senha inválida. Use no mínimo 8 caracteres, maiúscula, minúscula, número e símbolo.');
        return;
    }

    try {
        const response = await fetch('../../../backend/controllers/AdmController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'cadastrar_caixa', nome, email, senha})
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
    if (!Number.isInteger(Number(id)) || Number(id) <= 0) return;
    if (!confirm('Deseja realmente excluir este caixa? Ele será desativado para preservar o histórico.')) return;

    try {
        const response = await fetch('../../../backend/controllers/AdmController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'excluir_caixa', id: Number(id)})
        });
        const data = await response.json();
        alert(data.message);
        if (data.success) listarCaixas();
    } catch (error) {
        console.error(error);
        alert('Erro ao excluir caixa.');
    }
}

async function verDetalhesCaixa(id) {
    if (!Number.isInteger(Number(id)) || Number(id) <= 0) return;
    try {
        const response = await fetch(`../../../backend/controllers/AdmController.php?acao=detalhes_caixa&caixa_id=${Number(id)}`);
        const data = await response.json();
        if (!data.success) {
            alert(data.message);
            return;
        }

        const nome = escapeHTML(data.caixa.nome);
        document.getElementById('detalheNomeCaixa').innerHTML = `Operações de ${nome}`;
        document.getElementById('detalheEntradas').textContent = data.resumo.total_entradas;
        document.getElementById('detalheSaidas').textContent = data.resumo.total_saidas;
        document.getElementById('detalhePausas').textContent = data.resumo.total_pausas;
        document.getElementById('detalheInicioTurno').textContent = data.turno.aberto_em ?? '-';
        document.getElementById('detalheFimTurno').textContent = data.turno.fechado_em ?? '-';
        document.getElementById('detalheStatusTurno').textContent = data.turno.status ?? 'sem turno';

        const listaPausas = document.getElementById('listaPausas');
        listaPausas.innerHTML = '';

        if (!data.pausas || data.pausas.length === 0) {
            listaPausas.innerHTML = '<div class="pausa-item">Nenhuma pausa registrada neste turno.</div>';
        } else {
            data.pausas.forEach(pausa => {
                const item = document.createElement('div');
                item.className = 'pausa-item';
                item.innerHTML = `<strong>Início:</strong> ${escapeHTML(pausa.inicio_pausa)}<br><strong>Fim:</strong> ${escapeHTML(pausa.fim_pausa ?? 'Em andamento')}`;
                listaPausas.appendChild(item);
            });
        }

        document.getElementById('modalDetalhesCaixa').classList.add('ativo');
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
        if (!lista) return;
        lista.innerHTML = '';

        if (!data.success || data.promoters.length === 0) {
            lista.innerHTML = '<p class="mensagem-vazia">Nenhum promoter cadastrado.</p>';
            return;
        }

        window.promotersCache = data.promoters;

        data.promoters.forEach(promoter => {
            const item = document.createElement('div');
            item.className = 'caixa-item';
            item.innerHTML = `
                <h3>${escapeHTML(promoter.nome)}</h3>
                <p>${escapeHTML(promoter.telefone || 'Sem telefone')}</p>
                <p><strong>Dias:</strong> ${escapeHTML(montarTextoDiasPromoter(promoter))}</p>
                <div class="caixa-botoes">
                    <button class="btn-ver" onclick="abrirEditarPromoterPorId(${Number(promoter.id)})">Editar</button>
                    <button class="btn-excluir" onclick="excluirPromoter(${Number(promoter.id)})">Excluir</button>
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
    document.getElementById('tituloModalPromoter').textContent = 'Cadastrar Promoter';
    document.getElementById('promoterId').value = '';
    document.getElementById('nomePromoter').value = '';
    document.getElementById('telefonePromoter').value = '';
    document.querySelectorAll('.dia-promoter').forEach(input => input.checked = false);
    document.getElementById('modalPromoter').classList.add('ativo');
}

function abrirEditarPromoterPorId(id) {
    const promoter = (window.promotersCache || []).find(item => Number(item.id) === Number(id));
    if (promoter) abrirEditarPromoter(promoter);
}

function abrirEditarPromoter(promoter) {
    document.getElementById('tituloModalPromoter').textContent = 'Editar Promoter';
    document.getElementById('promoterId').value = Number(promoter.id);
    document.getElementById('nomePromoter').value = sanitizeText(promoter.nome, 100);
    document.getElementById('telefonePromoter').value = sanitizeText(promoter.telefone || '', 20);
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

    const id = Number(document.getElementById('promoterId').value || 0);
    const nome = sanitizeText(document.getElementById('nomePromoter').value, 100);
    const telefone = sanitizeText(document.getElementById('telefonePromoter').value, 20);
    const dias = Array.from(document.querySelectorAll('.dia-promoter:checked')).map(input => input.value);
    const acao = id ? 'editar_promoter' : 'cadastrar_promoter';

    if (!validarNome(nome)) {
        alert('Nome do promoter inválido.');
        return;
    }

    if (!validarTelefone(telefone)) {
        alert('Telefone inválido.');
        return;
    }

    try {
        const response = await fetch('../../../backend/controllers/PromoterController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao, id, nome, telefone, dias})
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
    if (!Number.isInteger(Number(id)) || Number(id) <= 0) return;
    if (!confirm('Deseja excluir este promoter? As listas relacionadas também serão removidas.')) return;

    try {
        const response = await fetch('../../../backend/controllers/PromoterController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'excluir_promoter', id: Number(id)})
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
        if (filtro) filtro.innerHTML = '<option value="">Todos os dias</option>';
        if (selectDiaLista) selectDiaLista.innerHTML = '<option value="">Selecione o dia</option>';
        if (!data.success) return;
        data.dias.forEach(dia => {
            const id = Number(dia.id);
            const nome = escapeHTML(dia.nome);
            if (filtro) filtro.innerHTML += `<option value="${id}">${nome}</option>`;
            if (selectDiaLista) selectDiaLista.innerHTML += `<option value="${id}">${nome}</option>`;
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
        if (!select) return;
        select.innerHTML = '<option value="">Selecione o promoter</option>';
        if (data.success) {
            data.promoters.forEach(promoter => {
                select.innerHTML += `<option value="${Number(promoter.id)}">${escapeHTML(promoter.nome)}</option>`;
            });
        }
    } catch (error) {
        console.error(error);
    }
}

function abrirListaPromoter() {
    document.getElementById('formListaPromoter')?.reset();
    document.getElementById('modalListaPromoter')?.classList.add('ativo');
}

function abrirListaAniversario() {
    document.getElementById('formListaAniversario')?.reset();
    document.getElementById('modalListaAniversario')?.classList.add('ativo');
}

async function cadastrarListaPromoter(event) {
    event.preventDefault();

    const promoterId = Number(document.getElementById('selectPromoterLista').value);
    const diaId = Number(document.getElementById('selectDiaLista').value);
    const dataLista = sanitizeText(document.getElementById('dataListaPromoter').value, 10);
    const convidados = coletarPessoas('convidadoPromoter', 5);
    const vips = coletarPessoas('vipPromoter', 20);

    if (!promoterId || !diaId || !validarDataISO(dataLista)) {
        alert('Selecione promoter, dia e uma data válida.');
        return;
    }

    try {
        const response = await fetch('../../../backend/controllers/PromoterController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'cadastrar_lista_promoter', promoter_id: promoterId, dia_id: diaId, data_lista: dataLista, convidados, vips})
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

    const nome = sanitizeText(document.getElementById('aniversarianteNome').value, 100);
    const cpf = formatarCpf(document.getElementById('aniversarianteCpf').value);
    const dataEvento = sanitizeText(document.getElementById('dataEventoAniversario').value, 10);
    const convidados = coletarPessoas('convidadoAniversario', 20);

    if (!validarNome(nome)) {
        alert('Nome do aniversariante inválido.');
        return;
    }

    if (cpf !== '' && !validarCpf(cpf)) {
        alert('CPF do aniversariante inválido.');
        return;
    }

    if (!validarDataISO(dataEvento)) {
        alert('Data do evento inválida.');
        return;
    }

    try {
        const response = await fetch('../../../backend/controllers/PromoterController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'cadastrar_lista_aniversario', aniversariante_nome: nome, aniversariante_cpf: cpf, data_evento: dataEvento, convidados})
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
        const nomeInput = document.getElementById(`${prefixo}Nome${i}`);
        const cpfInput = document.getElementById(`${prefixo}Cpf${i}`);
        if (!nomeInput || !cpfInput) continue;
        const nome = sanitizeText(nomeInput.value, 100);
        const cpf = formatarCpf(cpfInput.value);
        if (nome === '') continue;
        if (!validarNome(nome)) {
            alert(`Nome inválido na linha ${i}.`);
            return [];
        }
        if (cpf !== '' && !validarCpf(cpf)) {
            alert(`CPF inválido na linha ${i}.`);
            return [];
        }
        pessoas.push({nome, cpf});
    }
    return pessoas;
}

async function listarListasPromoters() {
    try {
        const filtro = document.getElementById('filtroDiaLista');
        const diaId = filtro ? Number(filtro.value) : 0;
        let url = '../../../backend/controllers/PromoterController.php?acao=listar_listas_promoters';
        if (diaId) url += `&dia_id=${diaId}`;
        const response = await fetch(url);
        const data = await response.json();
        const lista = document.getElementById('listaListasPromoters');
        if (!lista) return;
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
                    <h3>${escapeHTML(item.promoter_nome)}</h3>
                    <p><strong>Dia:</strong> ${escapeHTML(item.dia_nome)}</p>
                    <p><strong>Data:</strong> ${escapeHTML(item.data_lista)}</p>
                    <p><strong>Convidados grátis:</strong> ${Number(item.total_convidados)}/5</p>
                    <p><strong>VIPs:</strong> ${Number(item.total_vips)}/20</p>
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
        if (!lista) return;
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
                    <h3>${escapeHTML(item.aniversariante_nome)}</h3>
                    <p><strong>CPF:</strong> ${escapeHTML(item.aniversariante_cpf || 'não informado')}</p>
                    <p><strong>Data:</strong> ${escapeHTML(item.data_evento)}</p>
                    <p><strong>Convidados:</strong> ${Number(item.total_convidados)}/20</p>
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
        const inputData = document.getElementById('dataStatusEnvio');
        const dataEvento = inputData ? sanitizeText(inputData.value, 10) : '';
        if (dataEvento && !validarDataISO(dataEvento)) return;
        const response = await fetch(`../../../backend/controllers/PromoterController.php?acao=status_envio&data_evento=${encodeURIComponent(dataEvento)}`);
        const data = await response.json();
        const lista = document.getElementById('statusEnvioPromoters');
        if (!lista) return;
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
                    <h3>${escapeHTML(item.nome)}</h3>
                    <p><strong>Dia liberado:</strong> ${escapeHTML(item.dia_nome)}</p>
                    <p><strong>Telefone:</strong> ${escapeHTML(item.telefone || 'não informado')}</p>
                </div>
                <span class="${classe}">${texto}</span>
            `;
            lista.appendChild(div);
        });
    } catch (error) {
        console.error(error);
    }
}

function iniciarTurnoDia() { alert('Turno do dia liberado para os caixas.'); }
function encerrarTurnoDia() { alert('Turno do dia encerrado.'); }
function sair() { window.location.href = '../auth/login.html'; }
function fecharModais() { document.querySelectorAll('.modal').forEach(modal => modal.classList.remove('ativo')); }
