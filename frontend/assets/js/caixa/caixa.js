/*
|--------------------------------------------------------------------------
| ARQUIVO: caixa.js
|--------------------------------------------------------------------------
| FUNÇÃO:
| Script responsável por eventos da interface, chamadas fetch ao backend e
| validações do lado do cliente.
|
| SEGURANÇA APLICADA:
| - Busca token CSRF e envia em ações sensíveis.
| - Controle de inatividade para pausa/logout automático.
| - Validação de campos antes de chamar o backend.
*/
// Comentário de documentação JamesBar
// Arquivo: frontend/assets/js/caixa/caixa.js
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

let clienteAtualId = null;
let clienteAtualNome = '';
let clienteAtualTotalEntradas = 0;
let tempoLimiteInatividade = 5 * 60 * 1000;
let temporizadorInatividade = null;

document.addEventListener('DOMContentLoaded', () => {
    iniciarControleInatividade();
    carregarContadores();

    document.getElementById('formPesquisarCpf')?.addEventListener('submit', pesquisarCpf);
    document.getElementById('formCadastroCliente')?.addEventListener('submit', cadastrarCliente);
    document.getElementById('btnLiberarEntrada')?.addEventListener('click', liberarEntrada);

    document.getElementById('cpf')?.addEventListener('input', (event) => {
        event.target.value = formatarCpf(event.target.value);
    });

    document.getElementById('cpfCadastro')?.addEventListener('input', (event) => {
        event.target.value = formatarCpf(event.target.value);
    });

    document.getElementById('btnPausarTurno')?.addEventListener('click', () => {
        reiniciarTemporizadorInatividade();
        document.getElementById('modalPausa').classList.add('ativo');
    });

    document.getElementById('btnEncerrarTurno')?.addEventListener('click', () => {
        reiniciarTemporizadorInatividade();
        document.getElementById('modalEncerrarTurno').classList.add('ativo');
    });

    document.getElementById('confirmarPausa')?.addEventListener('click', pausarTurno);
    document.getElementById('confirmarEncerramento')?.addEventListener('click', encerrarTurno);
});

function iniciarControleInatividade() {
    ['mousemove', 'mousedown', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evento => {
        document.addEventListener(evento, reiniciarTemporizadorInatividade);
    });
    reiniciarTemporizadorInatividade();
}

function reiniciarTemporizadorInatividade() {
    clearTimeout(temporizadorInatividade);
    temporizadorInatividade = setTimeout(logoutPorInatividade, tempoLimiteInatividade);
}

async function logoutPorInatividade() {
    try {
        const response = await fetch('../../../backend/controllers/AuthController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'logout_inatividade'})
        });
        const data = await response.json();
        alert('Você foi desconectado por inatividade.\n\nComo você é caixa, isso foi registrado como pausa automática.');
        window.location.href = data.success ? data.redirect : '../auth/login.html?motivo=inatividade';
    } catch (error) {
        console.error(error);
        alert('Sessão encerrada por inatividade.');
        window.location.href = '../auth/login.html?motivo=inatividade';
    }
}

async function pesquisarCpf(event) {
    event.preventDefault();
    reiniciarTemporizadorInatividade();

    const cpf = formatarCpf(document.getElementById('cpf').value);

    if (!validarCpf(cpf)) {
        alert('CPF inválido. Use 000.000.000-00.');
        return;
    }

    try {
        const response = await fetch('../../../backend/controllers/CaixaController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'pesquisar_cpf', cpf})
        });
        const data = await response.json();

        if (data.success) {
            clienteAtualId = Number(data.cliente.id);
            clienteAtualNome = sanitizeText(data.cliente.nome, 100);
            clienteAtualTotalEntradas = Number(data.cliente.total_entradas);
            abrirModalComanda();
        } else {
            document.getElementById('cpfCadastro').value = cpf;
            abrirModalCadastro();
        }
    } catch (error) {
        console.error(error);
        alert('Erro ao conectar ao servidor.');
    }
}

async function cadastrarCliente(event) {
    event.preventDefault();
    reiniciarTemporizadorInatividade();

    const nome = sanitizeText(document.getElementById('nomeCliente').value, 100);
    const cpf = formatarCpf(document.getElementById('cpfCadastro').value);
    const dataAniversario = sanitizeText(document.getElementById('dataAniversario').value, 10);

    if (!validarNome(nome)) {
        alert('Nome inválido.');
        return;
    }

    if (!validarCpf(cpf)) {
        alert('CPF inválido. Use 000.000.000-00.');
        return;
    }

    if (!validarDataISO(dataAniversario)) {
        alert('Data de aniversário inválida.');
        return;
    }

    try {
        const response = await fetch('../../../backend/controllers/CaixaController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'cadastrar_cliente', nome, cpf, data_aniversario: dataAniversario})
        });
        const data = await response.json();

        if (data.success) {
            clienteAtualId = Number(data.cliente.id);
            clienteAtualNome = sanitizeText(data.cliente.nome, 100);
            clienteAtualTotalEntradas = 0;
            fecharModais();
            abrirModalComanda();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error(error);
        alert('Erro ao cadastrar cliente.');
    }
}

async function liberarEntrada() {
    reiniciarTemporizadorInatividade();

    if (!clienteAtualId || !Number.isInteger(Number(clienteAtualId))) {
        alert('Nenhum cliente selecionado.');
        return;
    }

    try {
        const response = await fetch('../../../backend/controllers/CaixaController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'liberar_entrada', cliente_id: Number(clienteAtualId)})
        });
        const data = await response.json();
        alert(data.message);
        if (data.success) {
            fecharModais();
            carregarContadores();
            document.getElementById('cpf').value = '';
            document.getElementById('formCadastroCliente')?.reset();
            clienteAtualId = null;
            clienteAtualNome = '';
            clienteAtualTotalEntradas = 0;
        }
    } catch (error) {
        console.error(error);
        alert('Erro ao liberar entrada.');
    }
}

async function pausarTurno() {
    reiniciarTemporizadorInatividade();
    try {
        const response = await fetch('../../../backend/controllers/CaixaController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'pausar_turno'})
        });
        const data = await response.json();
        alert('Pausa acionada.\n\nVocê será desconectado, mas poderá retornar ao mesmo turno.');
        if (data.success) window.location.href = data.redirect;
    } catch (error) {
        console.error(error);
        alert('Erro ao pausar turno.');
    }
}

async function encerrarTurno() {
    reiniciarTemporizadorInatividade();
    try {
        const response = await fetch('../../../backend/controllers/CaixaController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'encerrar_turno'})
        });
        const data = await response.json();
        alert('ATE A PROXIMA!! :))');
        if (data.success) window.location.href = data.redirect;
    } catch (error) {
        console.error(error);
        alert('Erro ao encerrar turno.');
    }
}

async function carregarContadores() {
    try {
        const response = await fetch('../../../backend/controllers/CaixaController.php?acao=contador');
        const data = await response.json();
        if (data.success) {
            document.getElementById('totalEntradas').textContent = Number(data.total_entradas);
            document.getElementById('totalSaidas').textContent = Number(data.total_saidas);
        }
    } catch (error) {
        console.error(error);
    }
}

function abrirModalCadastro() {
    reiniciarTemporizadorInatividade();
    document.getElementById('modalCadastro').classList.add('ativo');
}

function abrirModalComanda() {
    reiniciarTemporizadorInatividade();
    document.getElementById('nomeClienteComanda').textContent = clienteAtualNome;
    document.getElementById('vezClienteComanda').textContent = clienteAtualTotalEntradas + 1;
    document.getElementById('modalComanda').classList.add('ativo');
}

function fecharModais() {
    reiniciarTemporizadorInatividade();
    document.querySelectorAll('.modal').forEach(modal => modal.classList.remove('ativo'));
    clienteAtualId = null;
    clienteAtualNome = '';
    clienteAtualTotalEntradas = 0;
}
