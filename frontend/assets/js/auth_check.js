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
