/*
|--------------------------------------------------------------------------
| ARQUIVO: auth_check.js
|--------------------------------------------------------------------------
| FUNÇÃO:
| Script responsável por eventos da interface, chamadas fetch ao backend e
| validações do lado do cliente.
|
| SEGURANÇA APLICADA:
| - Bloqueia acesso direto a páginas protegidas sem sessão válida.
| - Redireciona usuário sem autenticação para login.
*/
// Comentário de documentação JamesBar
// Arquivo: frontend/assets/js/auth_check.js
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

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch('../../../backend/controllers/AuthController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                acao: 'verificar_sessao'
            })
        });

        const data = await response.json();

        if (!data.success) {
            window.location.href = '../auth/login.html';
            return;
        }

        const caminho = window.location.pathname.toLowerCase();
        const emMfa = caminho.endsWith('/mfa.html');
        const emTrocaSenha = caminho.endsWith('/trocar_senha.html');

        if (emMfa && !data.mfa_pendente && data.usuario_tipo !== 'adm') {
            window.location.href = '../auth/login.html';
            return;
        }

        if (emTrocaSenha && !data.troca_senha_pendente && !data.usuario_id) {
            window.location.href = '../auth/login.html';
            return;
        }

        const nomeSpan = document.getElementById('usuarioNome');
        if (nomeSpan && data.usuario_nome) {
            nomeSpan.textContent = data.usuario_nome;
        }
    } catch (err) {
        console.error(err);
        window.location.href = '../auth/login.html';
    }
});
