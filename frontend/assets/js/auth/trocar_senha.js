/*
|--------------------------------------------------------------------------
| ARQUIVO: trocar_senha.js
|--------------------------------------------------------------------------
| FUNÇÃO:
| Script responsável por eventos da interface, chamadas fetch ao backend e
| validações do lado do cliente.
|
| SEGURANÇA APLICADA:
| - Validação visual de força de senha.
| - Confirmação de senha antes do envio.
*/
// Comentário de documentação JamesBar
// Arquivo: frontend/assets/js/auth/trocar_senha.js
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


const formTrocarSenha = document.getElementById('formTrocarSenha');
const novaSenhaInput = document.getElementById('novaSenha');
const confirmarSenhaInput = document.getElementById('confirmarSenha');
const btnAlterarSenha = document.getElementById('btnAlterarSenha');
const bolinhaFraca = document.getElementById('bolinhaFraca');
const bolinhaMedia = document.getElementById('bolinhaMedia');
const bolinhaForte = document.getElementById('bolinhaForte');
const mensagemForca = document.getElementById('mensagemForca');
const mensagemIgualdade = document.getElementById('mensagemIgualdade');

let senhaEhForte = false;
let senhasIguais = false;

novaSenhaInput?.addEventListener('input', validarTudo);
confirmarSenhaInput?.addEventListener('input', validarTudo);

formTrocarSenha?.addEventListener('submit', async function (event) {
    event.preventDefault();
    validarTudo();

    if (!senhaEhForte) {
        alert('A senha precisa ser forte para continuar.');
        return;
    }

    if (!senhasIguais) {
        alert('As senhas precisam ser iguais.');
        return;
    }

    const novaSenha = String(novaSenhaInput.value ?? '');
    const confirmarSenha = String(confirmarSenhaInput.value ?? '');

    if (!validarSenhaForte(novaSenha)) {
        alert('Senha inválida. Use no mínimo 8 caracteres, maiúscula, minúscula, número e símbolo.');
        return;
    }

    try {
        const response = await fetch('../../../backend/controllers/AuthController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                acao: 'trocar_senha_primeiro_acesso',
                nova_senha: novaSenha,
                confirmar_senha: confirmarSenha
            })
        });

        const data = await response.json();
        alert(data.message);

        if (data.success) {
            window.location.href = data.redirect;
        }
    } catch (error) {
        console.error(error);
        alert('Erro ao conectar com o servidor.');
    }
});

function validarTudo() {
    const senha = novaSenhaInput.value;
    const confirmarSenha = confirmarSenhaInput.value;
    const nivel = calcularNivelSenha(senha);

    atualizarForcaVisual(nivel);
    atualizarComparacaoSenha(senha, confirmarSenha);
    atualizarBotao();
}

function calcularNivelSenha(senha) {
    let pontos = 0;

    if (senha.length >= 8) pontos++;
    if (/[A-Z]/.test(senha)) pontos++;
    if (/[a-z]/.test(senha)) pontos++;
    if (/[0-9]/.test(senha)) pontos++;
    if (/[^A-Za-z0-9]/.test(senha)) pontos++;

    if (senha.length === 0) return 'vazia';
    if (pontos <= 2) return 'fraca';
    if (pontos <= 4) return 'media';
    return 'forte';
}

function atualizarForcaVisual(nivel) {
    limparBolinhas();
    senhaEhForte = false;

    if (nivel === 'vazia') {
        mensagemForca.innerText = 'Digite uma senha forte.';
        mensagemForca.className = 'mensagem-validacao';
        return;
    }

    if (nivel === 'fraca') {
        bolinhaFraca.classList.add('fraca');
        mensagemForca.innerText = 'Senha fraca: use no mínimo 8 caracteres, letras maiúsculas, números e símbolos.';
        mensagemForca.className = 'mensagem-validacao erro';
        return;
    }

    if (nivel === 'media') {
        bolinhaFraca.classList.add('fraca');
        bolinhaMedia.classList.add('media');
        mensagemForca.innerText = 'Senha média: adicione mais variedade para ficar forte.';
        mensagemForca.className = 'mensagem-validacao aviso';
        return;
    }

    bolinhaFraca.classList.add('fraca');
    bolinhaMedia.classList.add('media');
    bolinhaForte.classList.add('forte');
    mensagemForca.innerText = 'Senha forte.';
    mensagemForca.className = 'mensagem-validacao sucesso';
    senhaEhForte = true;
}

function atualizarComparacaoSenha(senha, confirmarSenha) {
    senhasIguais = false;

    if (confirmarSenha.length === 0) {
        mensagemIgualdade.innerText = 'As senhas ainda não coincidem.';
        mensagemIgualdade.className = 'mensagem-validacao';
        return;
    }

    if (senha !== confirmarSenha) {
        mensagemIgualdade.innerText = 'As senhas não coincidem.';
        mensagemIgualdade.className = 'mensagem-validacao erro';
        return;
    }

    mensagemIgualdade.innerText = 'As senhas coincidem.';
    mensagemIgualdade.className = 'mensagem-validacao sucesso';
    senhasIguais = true;
}

function atualizarBotao() {
    if (!btnAlterarSenha) return;
    btnAlterarSenha.disabled = !(senhaEhForte && senhasIguais);
}

function limparBolinhas() {
    bolinhaFraca.className = 'bolinha';
    bolinhaMedia.className = 'bolinha';
    bolinhaForte.className = 'bolinha';
}

validarTudo();
