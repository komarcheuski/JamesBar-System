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