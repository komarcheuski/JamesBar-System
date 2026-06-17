(() => {
    document.documentElement.style.visibility = 'hidden';

    const path = window.location.pathname.toLowerCase();
    let tipoNecessario = null;
    let loginUrl = '../auth/login.html';

    if (path.includes('/views/adm/')) {
        tipoNecessario = 'adm';
    }

    if (path.includes('/views/caixa/')) {
        tipoNecessario = 'caixa';
    }

    if (path.includes('/views/auth/mfa.html')) {
        tipoNecessario = 'mfa';
        loginUrl = 'login.html';
    }

    if (path.includes('/views/auth/trocar_senha.html')) {
        tipoNecessario = 'trocar_senha';
        loginUrl = 'login.html';
    }

    async function bloquear() {
        window.location.replace(loginUrl);
    }

    async function verificar() {
        try {
            const response = await fetch('../../../backend/controllers/AuthController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                cache: 'no-store',
                body: JSON.stringify({
                    acao: 'verificar_sessao'
                })
            });

            const data = await response.json();

            if (tipoNecessario === 'adm') {
                if (data.success === true && data.usuario_tipo === 'adm') {
                    document.documentElement.style.visibility = 'visible';
                    return;
                }

                bloquear();
                return;
            }

            if (tipoNecessario === 'caixa') {
                if (data.success === true && data.usuario_tipo === 'caixa') {
                    document.documentElement.style.visibility = 'visible';
                    return;
                }

                bloquear();
                return;
            }

            document.documentElement.style.visibility = 'visible';

        } catch (error) {
            bloquear();
        }
    }

    verificar();
})();