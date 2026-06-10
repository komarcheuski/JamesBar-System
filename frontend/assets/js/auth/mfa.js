document.addEventListener('DOMContentLoaded', () => {
    carregarStatusMfa();

    document
        .getElementById('mfaForm')
        .addEventListener('submit', verificarMfa);
});

async function carregarStatusMfa() {
    try {
        const response = await fetch(
            '../../../backend/controllers/AuthController.php?acao=mfa_status'
        );

        const data = await response.json();

        if (!data.success) {
            alert(data.message);
            window.location.href = 'login.html';
            return;
        }

        if (!data.mfa_ativo) {
            document.getElementById('setupMfaArea').classList.remove('escondido');
            document.getElementById('qrCodeMfa').src = data.qr_code_url;
            document.getElementById('secretMfa').innerText = data.secret;

            document.getElementById('mfaDescricao').innerText =
                'Primeiro acesso ADM: configure o Google Authenticator e confirme o código.';
        }

    } catch (error) {
        console.error(error);
        alert('Erro ao carregar MFA.');
        window.location.href = 'login.html';
    }
}

async function verificarMfa(event) {
    event.preventDefault();

    const codigo = document.getElementById('codigoMfa').value.trim();

    try {
        const response = await fetch(
            '../../../backend/controllers/AuthController.php',
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    acao: 'verificar_mfa',
                    codigo: codigo
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
        alert('Erro ao verificar MFA.');
    }
}