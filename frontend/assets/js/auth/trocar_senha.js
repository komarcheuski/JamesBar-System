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

novaSenhaInput.addEventListener('input', validarTudo);
confirmarSenhaInput.addEventListener('input', validarTudo);

formTrocarSenha.addEventListener('submit', async function (event) {
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

    const novaSenha = novaSenhaInput.value.trim();
    const confirmarSenha = confirmarSenhaInput.value.trim();

    try {
        const response = await fetch(
            '../../../backend/controllers/AuthController.php',
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    acao: 'trocar_senha_primeiro_acesso',
                    nova_senha: novaSenha,
                    confirmar_senha: confirmarSenha
                })
            }
        );

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

    if (senha.length >= 8) {
        pontos++;
    }

    if (/[A-Z]/.test(senha)) {
        pontos++;
    }

    if (/[a-z]/.test(senha)) {
        pontos++;
    }

    if (/[0-9]/.test(senha)) {
        pontos++;
    }

    if (/[^A-Za-z0-9]/.test(senha)) {
        pontos++;
    }

    if (senha.length === 0) {
        return 'vazia';
    }

    if (pontos <= 2) {
        return 'fraca';
    }

    if (pontos <= 4) {
        return 'media';
    }

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

        mensagemForca.innerText =
            'Senha fraca: use no mínimo 8 caracteres, letras maiúsculas, números e símbolos.';

        mensagemForca.className = 'mensagem-validacao erro';
        return;
    }

    if (nivel === 'media') {
        bolinhaFraca.classList.add('fraca');
        bolinhaMedia.classList.add('media');

        mensagemForca.innerText =
            'Senha média: adicione mais variedade para ficar forte.';

        mensagemForca.className = 'mensagem-validacao aviso';
        return;
    }

    if (nivel === 'forte') {
        bolinhaFraca.classList.add('fraca');
        bolinhaMedia.classList.add('media');
        bolinhaForte.classList.add('forte');

        mensagemForca.innerText = 'Senha forte.';
        mensagemForca.className = 'mensagem-validacao sucesso';

        senhaEhForte = true;
    }
}

function atualizarComparacaoSenha(senha, confirmarSenha) {
    senhasIguais = false;

    if (confirmarSenha.length === 0) {
        mensagemIgualdade.innerText = 'Confirme sua senha.';
        mensagemIgualdade.className = 'mensagem-validacao';
        return;
    }

    if (senha === confirmarSenha) {
        mensagemIgualdade.innerText = 'As senhas coincidem.';
        mensagemIgualdade.className = 'mensagem-validacao sucesso';
        senhasIguais = true;
    } else {
        mensagemIgualdade.innerText = 'As senhas não coincidem.';
        mensagemIgualdade.className = 'mensagem-validacao erro';
    }
}

function atualizarBotao() {
    btnAlterarSenha.disabled = !(senhaEhForte && senhasIguais);
}

function limparBolinhas() {
    bolinhaFraca.className = 'bolinha';
    bolinhaMedia.className = 'bolinha';
    bolinhaForte.className = 'bolinha';
}