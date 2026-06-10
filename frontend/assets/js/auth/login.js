document.addEventListener('DOMContentLoaded', () => {
    verificarMensagemUrl();
});

document
    .getElementById('loginForm')
    .addEventListener('submit', async function (e) {
        e.preventDefault();

        const email = document.getElementById('email').value.trim();
        const senha = document.getElementById('senha').value.trim();

        if (email === '' || senha === '') {
            alert('Preencha e-mail e senha.');
            return;
        }

        try {
            const response = await fetch(
                '../../../backend/controllers/AuthController.php',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        acao: 'login',
                        email: email,
                        senha: senha
                    })
                }
            );

            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirect;
            } else {
                alert(data.message);
            }

        } catch (error) {
            console.error(error);
            alert('Erro ao conectar com o servidor.');
        }
    });

function verificarMensagemUrl() {
    const params = new URLSearchParams(window.location.search);
    const motivo = params.get('motivo');

    if (motivo === 'inatividade') {
        alert(
            'Você foi desconectado por inatividade.\n\nSe você era caixa, essa saída foi registrada como pausa automática.'
        );

        window.history.replaceState(
            {},
            document.title,
            window.location.pathname
        );
    }
}