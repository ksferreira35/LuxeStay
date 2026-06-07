const checkin = document.getElementById('checkin');
const checkout = document.getElementById('checkout');
const hospedes = document.getElementById('hospedes');
const cards = document.querySelectorAll('.card-quarto');
let quartoSelecionado = document.querySelector('.card-quarto.selecionado');
const toggleSenha = document.getElementById('toggle-senha');
const campoSenha = document.getElementById('senha');
const iconeSenha = document.getElementById('icone-senha');

if (toggleSenha && campoSenha && iconeSenha) {
    toggleSenha.addEventListener('click', function() {
        const senhaVisivel = campoSenha.type === 'text';

        campoSenha.type = senhaVisivel ? 'password' : 'text';
        iconeSenha.className = senhaVisivel ? 'bi bi-eye' : 'bi bi-eye-slash';
        toggleSenha.setAttribute('aria-label', senhaVisivel ? 'Mostrar senha' : 'Ocultar senha');
    });
}

function formatarMoeda(valor) {
    return valor.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

function parseDataBrasileira(valor) {
    const partes = valor.split('/');

    if (partes.length !== 3) {
        return null;
    }

    const dia = Number(partes[0]);
    const mes = Number(partes[1]);
    const ano = Number(partes[2]);
    const data = new Date(ano, mes - 1, dia);

    if (
        data.getFullYear() !== ano ||
        data.getMonth() !== mes - 1 ||
        data.getDate() !== dia
    ) {
        return null;
    }

    return data;
}

function formatarDataBrasileira(data) {
    const dia = String(data.getDate()).padStart(2, '0');
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const ano = data.getFullYear();

    return dia + '/' + mes + '/' + ano;
}

function somarDias(data, dias) {
    const novaData = new Date(data);
    novaData.setDate(novaData.getDate() + dias);

    return novaData;
}

function mascaraData(campo) {
    let valor = campo.value.replace(/\D/g, '');

    if (valor.length > 2) {
        valor = valor.slice(0, 2) + '/' + valor.slice(2);
    }

    if (valor.length > 5) {
        valor = valor.slice(0, 5) + '/' + valor.slice(5, 9);
    }

    campo.value = valor;
}

function validarDatas() {
    const entrada = parseDataBrasileira(checkin.value);
    const saida = parseDataBrasileira(checkout.value);

    checkin.classList.remove('is-invalid');
    checkout.classList.remove('is-invalid');

    if (!entrada) {
        checkin.classList.add('is-invalid');
        return false;
    }

    if (!saida || saida <= entrada) {
        checkout.value = formatarDataBrasileira(somarDias(entrada, 1));
        checkout.classList.add('is-invalid');
        return false;
    }

    return true;
}

function calcularNoites() {
    const entrada = parseDataBrasileira(checkin.value);
    const saida = parseDataBrasileira(checkout.value);

    if (!entrada || !saida || saida <= entrada) {
        return 1;
    }

    return (saida - entrada) / 1000 / 60 / 60 / 24;
}

function atualizarResumo() {
    if (!quartoSelecionado) {
        return;
    }

    const nome = quartoSelecionado.dataset.nome;
    const preco = Number(quartoSelecionado.dataset.preco);
    const imagem = quartoSelecionado.dataset.img;
    const detalhes = quartoSelecionado.dataset.detalhes;

    validarDatas();

    const noites = calcularNoites();
    const subtotal = preco * noites;
    const impostos = subtotal * 0.12;
    const servico = 45;
    const total = subtotal + impostos + servico;

    document.getElementById('resumo-img').src = imagem;
    document.getElementById('resumo-nome').textContent = nome;
    document.getElementById('resumo-detalhes').textContent = detalhes;
    document.getElementById('resumo-periodo').textContent = noites + ' noites, ' + hospedes.value + ' hóspedes';
    document.getElementById('linha-diarias').textContent = formatarMoeda(preco) + ' x ' + noites + ' noites';
    document.getElementById('subtotal').textContent = formatarMoeda(subtotal);
    document.getElementById('impostos').textContent = formatarMoeda(impostos);
    document.getElementById('servico').textContent = formatarMoeda(servico);
    document.getElementById('total').textContent = formatarMoeda(total);

    const campoIdQuarto = document.getElementById('reserva-id-quarto');
    const campoDataEntrada = document.getElementById('reserva-data-entrada');
    const campoDataSaida = document.getElementById('reserva-data-saida');

    if (campoIdQuarto && campoDataEntrada && campoDataSaida) {
        campoIdQuarto.value = quartoSelecionado.dataset.id;
        campoDataEntrada.value = checkin.value;
        campoDataSaida.value = checkout.value;
    }
}

if (checkin && checkout && hospedes) {
    cards.forEach(function(card) {
        card.querySelector('.selecionar-quarto').addEventListener('click', function() {
            cards.forEach(function(item) {
                item.classList.remove('selecionado');
            });

            card.classList.add('selecionado');
            quartoSelecionado = card;
            atualizarResumo();
        });
    });

    document.getElementById('form-reserva').addEventListener('submit', function(event) {
        event.preventDefault();

        if (validarDatas()) {
            atualizarResumo();
        }
    });

    checkin.addEventListener('input', function() {
        mascaraData(checkin);
    });

    checkout.addEventListener('input', function() {
        mascaraData(checkout);
    });

    checkin.addEventListener('change', atualizarResumo);
    checkout.addEventListener('change', atualizarResumo);
    hospedes.addEventListener('change', atualizarResumo);

    const formConfirmar = document.getElementById('confirmar-reserva-form');

    if (formConfirmar) {
        formConfirmar.addEventListener('submit', function(event) {
            if (!quartoSelecionado) {
                event.preventDefault();
                alert('Nenhum quarto disponível para confirmar.');
                return;
            }

            if (!validarDatas()) {
                event.preventDefault();
                alert('Confira as datas antes de confirmar a reserva.');
                return;
            }

            atualizarResumo();
        });
    }

    atualizarResumo();
}

document.querySelectorAll('.notificacoes-topo').forEach(function(container) {
    const badge = container.querySelector('.notificacoes-badge');
    const itens = Array.from(container.querySelectorAll('[data-notificacao-chave]'));
    const storageKey = container.dataset.storageKey || 'luxestay_notificacoes_vistas';
    const chavesAtuais = itens.map(function(item) {
        return item.dataset.notificacaoChave;
    });

    function buscarVistas() {
        try {
            return JSON.parse(localStorage.getItem(storageKey)) || [];
        } catch (erro) {
            return [];
        }
    }

    function atualizarBadge() {
        const vistas = buscarVistas();
        const novas = chavesAtuais.filter(function(chave) {
            return !vistas.includes(chave);
        });

        if (!badge || novas.length === 0) {
            if (badge) {
                badge.hidden = true;
            }
            return;
        }

        badge.hidden = false;
        badge.textContent = novas.length;
    }

    container.addEventListener('toggle', function() {
        if (container.open) {
            localStorage.setItem(storageKey, JSON.stringify(chavesAtuais));
            atualizarBadge();
        }
    });

    atualizarBadge();
});

document.addEventListener('click', function(event) {
    document.querySelectorAll('.notificacoes-topo[open]').forEach(function(container) {
        if (!container.contains(event.target)) {
            container.removeAttribute('open');
        }
    });
});

const categoriaBotoes = document.querySelectorAll('.categoria-btn');
const itensGastronomia = document.querySelectorAll('.item-gastronomia');

if (categoriaBotoes.length && itensGastronomia.length) {
    categoriaBotoes.forEach(function(botao) {
        botao.addEventListener('click', function() {
            const categoria = botao.dataset.categoria;

            categoriaBotoes.forEach(function(item) {
                item.classList.remove('ativo');
            });

            botao.classList.add('ativo');

            itensGastronomia.forEach(function(item) {
                item.hidden = item.dataset.categoria !== categoria;
            });
        });
    });

    itensGastronomia.forEach(function(item) {
        item.hidden = item.dataset.categoria !== 'Café da Manhã';
    });
}

const sacolaCorpo = document.getElementById('sacola-corpo');
const sacolaContador = document.getElementById('sacola-contador');
const sacolaTotal = document.getElementById('sacola-total');
const pedidoJson = document.getElementById('pedido-json');
const enviarSacola = document.getElementById('enviar-sacola');
const sacola = [];

function atualizarSacola() {
    if (!sacolaCorpo || !sacolaContador || !sacolaTotal || !pedidoJson || !enviarSacola) {
        return;
    }

    const totalItens = sacola.reduce(function(total, item) {
        return total + item.quantidade;
    }, 0);
    const totalValor = sacola.reduce(function(total, item) {
        return total + item.preco * item.quantidade;
    }, 0);

    sacolaContador.textContent = totalItens + (totalItens === 1 ? ' Item' : ' Itens');
    sacolaTotal.textContent = formatarMoeda(totalValor);
    pedidoJson.value = JSON.stringify(sacola.map(function(item) {
        return {
            id: item.id,
            quantidade: item.quantidade
        };
    }));
    enviarSacola.disabled = totalItens === 0;

    if (totalItens === 0) {
        sacolaCorpo.innerHTML = '<div class="sacola-vazia"><i class="bi bi-bag"></i><p>Seu carrinho está vazio.<br>Selecione itens para começar.</p></div>';
        return;
    }

    sacolaCorpo.innerHTML = sacola.map(function(item, indice) {
        return '<div class="sacola-item">'
            + '<div><strong>' + item.nome + '</strong><span>' + item.quantidade + ' x ' + formatarMoeda(item.preco) + '</span></div>'
            + '<button type="button" class="btn btn-sm btn-light remover-sacola" data-indice="' + indice + '"><i class="bi bi-x-lg"></i></button>'
            + '</div>';
    }).join('');
}

document.querySelectorAll('.adicionar-sacola').forEach(function(botao) {
    botao.addEventListener('click', function() {
        const card = botao.closest('.card-quarto');
        const id = card.dataset.id;
        const itemExistente = sacola.find(function(item) {
            return item.id === id;
        });

        if (itemExistente) {
            itemExistente.quantidade = Math.min(itemExistente.quantidade + 1, 6);
        } else {
            sacola.push({
                id: id,
                nome: card.dataset.nome,
                preco: Number(card.dataset.preco),
                quantidade: 1
            });
        }

        atualizarSacola();
    });
});

if (sacolaCorpo) {
    sacolaCorpo.addEventListener('click', function(event) {
        const botao = event.target.closest('.remover-sacola');

        if (!botao) {
            return;
        }

        sacola.splice(Number(botao.dataset.indice), 1);
        atualizarSacola();
    });
}

atualizarSacola();

const configToggles = document.querySelectorAll('.config-toggle');

configToggles.forEach(function(toggle) {
    const chave = toggle.dataset.configChave;
    const valorSalvo = localStorage.getItem(chave);

    if (valorSalvo !== null) {
        toggle.checked = valorSalvo === '1';
    }

    toggle.addEventListener('change', function() {
        localStorage.setItem(chave, toggle.checked ? '1' : '0');
    });
});

if (localStorage.getItem('luxestay_notificacoes_ativas') === '0') {
    document.querySelectorAll('.notificacoes-topo').forEach(function(item) {
        item.hidden = true;
    });
}

const climaWidgets = document.querySelectorAll('[data-clima-widget]');

function descricaoClima(codigo) {
    const descricoes = {
        0: 'Céu limpo',
        1: 'Principalmente limpo',
        2: 'Parcialmente nublado',
        3: 'Nublado',
        45: 'Neblina',
        48: 'Neblina com geada',
        51: 'Garoa leve',
        53: 'Garoa moderada',
        55: 'Garoa intensa',
        61: 'Chuva fraca',
        63: 'Chuva moderada',
        65: 'Chuva forte',
        80: 'Pancadas de chuva',
        95: 'Tempestade'
    };

    return descricoes[codigo] || 'Condição climática atual';
}

if (climaWidgets.length && localStorage.getItem('luxestay_clima_ativo') !== '0') {
    const urlClima = 'https://api.open-meteo.com/v1/forecast?latitude=-15.79&longitude=-47.88&current=temperature_2m,wind_speed_10m,weather_code&timezone=America%2FSao_Paulo';

    fetch(urlClima)
        .then(function(resposta) {
            if (!resposta.ok) {
                throw new Error('Falha ao consultar clima');
            }

            return resposta.json();
        })
        .then(function(dados) {
            const atual = dados.current;
            const temperatura = Math.round(atual.temperature_2m);
            const vento = Math.round(atual.wind_speed_10m);
            const descricao = descricaoClima(atual.weather_code);

            climaWidgets.forEach(function(widget) {
                widget.querySelector('[data-clima-temperatura]').textContent = temperatura + '°C';
                widget.querySelector('[data-clima-vento]').textContent = 'Vento ' + vento + ' km/h';
                widget.querySelector('[data-clima-descricao]').textContent = descricao + ' em Brasília, atualizado pela Open-Meteo.';
            });
        })
        .catch(function() {
            climaWidgets.forEach(function(widget) {
                widget.querySelector('[data-clima-descricao]').textContent = 'Não foi possível carregar o clima agora.';
            });
        });
}

if (climaWidgets.length && localStorage.getItem('luxestay_clima_ativo') === '0') {
    climaWidgets.forEach(function(widget) {
        widget.hidden = true;
    });
}
