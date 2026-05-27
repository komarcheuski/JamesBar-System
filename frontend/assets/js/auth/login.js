document
    .getElementById('loginForm')
    .addEventListener('submit', async function (e) {

        e.preventDefault();

        const email = document.getElementById('email').value;
        const senha = document.getElementById('senha').value;

        try {
            const response = await fetch(
                '../../../backend/controllers/AuthController.php',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
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
            alert('Erro ao conectar com o servidor.');
        }
    });