
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


document.addEventListener('DOMContentLoaded', () => {
    verificarMensagemUrl();

    const form = document.getElementById('loginForm');
    form?.addEventListener('submit', enviarLogin);
});

async function enviarLogin(e) {
    e.preventDefault();

    const email = sanitizeText(document.getElementById('email').value, 150).toLowerCase();
    const senha = String(document.getElementById('senha').value ?? '');

    if (email === '' || senha === '') {
        alert('Preencha e-mail e senha.');
        return;
    }

    if (!validarEmail(email)) {
        alert('E-mail inválido.');
        return;
    }

    if (senha.length > 72 || /[\u0000-\u001F\u007F]/.test(senha)) {
        alert('Senha inválida.');
        return;
    }

    try {
        const response = await fetch('../../../backend/controllers/AuthController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                acao: 'login',
                email,
                senha
            })
        });

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
}

function verificarMensagemUrl() {
    const params = new URLSearchParams(window.location.search);
    const motivo = params.get('motivo');

    if (motivo === 'inatividade') {
        alert('Você foi desconectado por inatividade.\n\nSe você era caixa, essa saída foi registrada como pausa automática.');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}
