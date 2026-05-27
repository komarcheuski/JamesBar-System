let clienteAtualId = null;
let clienteAtualNome = '';
let clienteAtualTotalEntradas = 0;

document.addEventListener('DOMContentLoaded', () => {
    carregarContadores();

    document.getElementById('formPesquisarCpf')?.addEventListener('submit', pesquisarCpf);
    document.getElementById('formCadastroCliente')?.addEventListener('submit', cadastrarCliente);
    document.getElementById('btnLiberarEntrada')?.addEventListener('click', liberarEntrada);

    document.getElementById('btnPausarTurno')?.addEventListener('click', () => {
        document.getElementById('modalPausa').classList.add('ativo');
    });

    document.getElementById('btnEncerrarTurno')?.addEventListener('click', () => {
        document.getElementById('modalEncerrarTurno').classList.add('ativo');
    });

    document.getElementById('confirmarPausa')?.addEventListener('click', pausarTurno);
    document.getElementById('confirmarEncerramento')?.addEventListener('click', encerrarTurno);
});

async function pesquisarCpf(event) {
    event.preventDefault();

    const cpf = document.getElementById('cpf').value.trim();

    try {
        const response = await fetch('../../../backend/controllers/CaixaController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                acao: 'pesquisar_cpf',
                cpf: cpf
            })
        });

        const data = await response.json();

        if (data.success) {
            clienteAtualId = data.cliente.id;
            clienteAtualNome = data.cliente.nome;
            clienteAtualTotalEntradas = Number(data.cliente.total_entradas);

            abrirModalComanda();
        } else {
            document.getElementById('cpfCadastro').value = cpf;
            abrirModalCadastro();
        }

    } catch (error) {
        console.error(error);
        alert('Erro ao conectar ao servidor.');
    }
}

async function cadastrarCliente(event) {
    event.preventDefault();

    const nome = document.getElementById('nomeCliente').value.trim();
    const cpf = document.getElementById('cpfCadastro').value.trim();
    const dataAniversario = document.getElementById('dataAniversario').value;

    try {
        const response = await fetch('../../../backend/controllers/CaixaController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                acao: 'cadastrar_cliente',
                nome: nome,
                cpf: cpf,
                data_aniversario: dataAniversario
            })
        });

        const data = await response.json();

        if (data.success) {
            clienteAtualId = data.cliente.id;
            clienteAtualNome = data.cliente.nome;
            clienteAtualTotalEntradas = 0;

            fecharModais();
            abrirModalComanda();
        } else {
            alert(data.message);
        }

    } catch (error) {
        console.error(error);
        alert('Erro ao cadastrar cliente.');
    }
}

async function liberarEntrada() {
    if (!clienteAtualId) {
        alert('Nenhum cliente selecionado.');
        return;
    }

    try {
        const response = await fetch('../../../backend/controllers/CaixaController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                acao: 'liberar_entrada',
                cliente_id: clienteAtualId
            })
        });

        const data = await response.json();

        alert(data.message);

        if (data.success) {
            fecharModais();
            carregarContadores();

            document.getElementById('cpf').value = '';
            document.getElementById('formCadastroCliente')?.reset();

            clienteAtualId = null;
            clienteAtualNome = '';
            clienteAtualTotalEntradas = 0;
        }

    } catch (error) {
        console.error(error);
        alert('Erro ao liberar entrada.');
    }
}

async function pausarTurno() {
    try {
        const response = await fetch('../../../backend/controllers/CaixaController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                acao: 'pausar_turno'
            })
        });

        const data = await response.json();

        alert('Pausa acionada.\n\nVocê será desconectado, mas poderá retornar ao mesmo turno.');

        if (data.success) {
            window.location.href = data.redirect;
        }

    } catch (error) {
        console.error(error);
        alert('Erro ao pausar turno.');
    }
}

async function encerrarTurno() {
    try {
        const response = await fetch('../../../backend/controllers/CaixaController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                acao: 'encerrar_turno'
            })
        });

        const data = await response.json();

        alert('ATE A PROXIMA!! :))');

        if (data.success) {
            window.location.href = data.redirect;
        }

    } catch (error) {
        console.error(error);
        alert('Erro ao encerrar turno.');
    }
}

async function carregarContadores() {
    try {
        const response = await fetch('../../../backend/controllers/CaixaController.php?acao=contador');
        const data = await response.json();

        if (data.success) {
            document.getElementById('totalEntradas').innerText = data.total_entradas;
            document.getElementById('totalSaidas').innerText = data.total_saidas;
        }

    } catch (error) {
        console.error(error);
    }
}

function abrirModalCadastro() {
    document.getElementById('modalCadastro').classList.add('ativo');
}

function abrirModalComanda() {
    document.getElementById('nomeClienteComanda').innerText = clienteAtualNome;
    document.getElementById('vezClienteComanda').innerText = clienteAtualTotalEntradas + 1;
    document.getElementById('modalComanda').classList.add('ativo');
}

function fecharModais() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('ativo');
    });

    clienteAtualId = null;
    clienteAtualNome = '';
    clienteAtualTotalEntradas = 0;
}