(() => {
    document.documentElement.style.visibility = 'hidden';

    const paginaAtual = window.location.pathname.toLowerCase();

    const LOGIN_URL = '../auth/login.html';
    const AUTH_CONTROLLER = '../../../backend/controllers/AuthController.php';

    function redirecionarLogin() {
        window.location.replace(LOGIN_URL);
    }

    function tipoNecessario() {
        if (paginaAtual.includes('/adm/')) {
            return 'adm';
        }

        if (paginaAtual.includes('/caixa/')) {
            return 'caixa';
        }

        if (paginaAtual.includes('mfa.html')) {
            return 'mfa';
        }

        if (paginaAtual.includes('trocar_senha.html')) {
            return 'troca_senha';
        }

        return null;
    }

    async function verificarAcesso() {
        try {
            const response = await fetch(AUTH_CONTROLLER, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({
                    acao: 'verificar_sessao'
                })
            });

            const data = await response.json();
            const tipo = tipoNecessario();

            if (!data.success) {
                redirecionarLogin();
                return;
            }

            if (tipo === 'adm' && data.usuario_tipo !== 'adm') {
                redirecionarLogin();
                return;
            }

            if (tipo === 'caixa' && data.usuario_tipo !== 'caixa') {
                redirecionarLogin();
                return;
            }

            if (tipo === 'mfa' && data.mfa_pendente !== true) {
                redirecionarLogin();
                return;
            }

            if (tipo === 'troca_senha' && data.troca_senha_pendente !== true) {
                redirecionarLogin();
                return;
            }

            document.documentElement.style.visibility = 'visible';

        } catch (error) {
            redirecionarLogin();
        }
    }

    verificarAcesso();
})();