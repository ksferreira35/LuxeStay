<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('cliente');
require_once __DIR__ . '/../../include/conexao.php';
require_once __DIR__ . '/../../include/funcoes.php';
atualizarReservasVencidas($conexao);

$itensCardapio = [
    [
        'id' => 'ovos-royale',
        'categoria' => 'Café da Manhã',
        'nome' => 'Ovos Royale',
        'preco' => 68,
        'descricao' => 'Ovos pochê, salmão defumado e hollandaise sobre brioche.',
        'imagem' => 'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=900&q=80',
    ],
    [
        'id' => 'bowl-energetico',
        'categoria' => 'Café da Manhã',
        'nome' => 'Bowl Energético',
        'preco' => 45,
        'descricao' => 'Iogurte grego, frutas vermelhas, granola artesanal e mel.',
        'imagem' => 'https://images.unsplash.com/photo-1511690743698-d9d85f2fbf38?auto=format&fit=crop&w=900&q=80',
    ],
    [
        'id' => 'panquecas-gold',
        'categoria' => 'Café da Manhã',
        'nome' => 'Panquecas Gold',
        'preco' => 52,
        'descricao' => 'Panquecas americanas com frutas, manteiga e xarope.',
        'imagem' => 'https://images.unsplash.com/photo-1528207776546-365bb710ee93?auto=format&fit=crop&w=900&q=80',
    ],
    [
        'id' => 'risoto-limao',
        'categoria' => 'Pratos Principais',
        'nome' => 'Risoto de Limão Siciliano',
        'preco' => 82,
        'descricao' => 'Arroz arbóreo cremoso, parmesão e toque cítrico.',
        'imagem' => 'https://images.unsplash.com/photo-1476124369491-e7addf5db371?auto=format&fit=crop&w=900&q=80',
    ],
    [
        'id' => 'brioche-baunilha',
        'categoria' => 'Sobremesas',
        'nome' => 'Brioche de Baunilha',
        'preco' => 48,
        'descricao' => 'Brioche tostado com creme de baunilha e calda quente.',
        'imagem' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=900&q=80',
    ],
    [
        'id' => 'suco-tropical',
        'categoria' => 'Bebidas',
        'nome' => 'Suco Tropical',
        'preco' => 24,
        'descricao' => 'Abacaxi, hortelã e limão batidos na hora.',
        'imagem' => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=900&q=80',
    ],
];

$reservaAtual = null;
$mensagemErro = '';

try {
    $email = $_SESSION['email'] ?? '';
    $sqlAtual = "SELECT reservas.id_reserva, quartos.id_quarto, quartos.numero
                 FROM reservas
                 INNER JOIN hospedes ON hospedes.id_hospede = reservas.id_hospede
                 INNER JOIN quartos ON quartos.id_quarto = reservas.id_quarto
                 WHERE hospedes.email = :email
                 AND LOWER(reservas.status) IN ('ocupado', 'confirmado', 'check-in realizado')
                 ORDER BY reservas.id_reserva DESC
                 LIMIT 1";
    $stmt = $conexao->prepare($sqlAtual);
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    $reservaAtual = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $erro) {
    $reservaAtual = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedidoJson = $_POST['pedido_json'] ?? '';
    $pedido = json_decode($pedidoJson, true);

    if (!$reservaAtual) {
        $mensagemErro = 'Você precisa ter uma reserva ativa para pedir serviço de quarto.';
    } elseif (!is_array($pedido) || empty($pedido)) {
        $mensagemErro = 'Adicione pelo menos um item na sacola.';
    } else {
        $mapaItens = [];
        foreach ($itensCardapio as $item) {
            $mapaItens[$item['id']] = $item;
        }

        $linhasPedido = [];
        $total = 0;

        foreach ($pedido as $itemPedido) {
            $idItem = $itemPedido['id'] ?? '';
            $quantidade = max(1, min(6, (int) ($itemPedido['quantidade'] ?? 1)));

            if (!isset($mapaItens[$idItem])) {
                continue;
            }

            $item = $mapaItens[$idItem];
            $subtotal = $item['preco'] * $quantidade;
            $total += $subtotal;
            $linhasPedido[] = $quantidade . 'x ' . $item['nome'] . ' (' . dinheiro($subtotal) . ')';
        }

        if (empty($linhasPedido)) {
            $mensagemErro = 'Adicione pelo menos um item válido na sacola.';
        } else {
            $observacao = 'Serviço de quarto: ' . implode(', ', $linhasPedido) . ' - Total ' . dinheiro($total);

            $stmt = $conexao->prepare(
                "INSERT INTO solicitacoes_limpeza (id_quarto, status, observacao)
                 VALUES (:id_quarto, :status, :observacao)"
            );
            $stmt->bindValue(':id_quarto', (int) $reservaAtual['id_quarto'], PDO::PARAM_INT);
            $stmt->bindValue(':status', 'Pendente');
            $stmt->bindValue(':observacao', $observacao);
            $stmt->execute();

            header('Location: gastronomia.php?pedido=ok');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gastronomia - LuxeStay PMS</title>
    <link rel="icon" type="image/png" href="/LuxeStay/assets/img/Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../../include/menu_cliente.php'; ?>

    <header class="topo-cliente">
        <h1 class="h4 fw-bold m-0">Gastronomia</h1>

        <div class="usuario-topo">
            <span>Logado: <?= htmlspecialchars($_SESSION['nome'] ?? 'Cliente') ?></span>
            <a class="btn btn-sm btn-outline-dark" href="../../logout.php">Sair</a>
        </div>
    </header>

    <main class="conteudo-cliente">
        <?php if (($_GET['pedido'] ?? '') === 'ok'): ?>
            <div class="alert alert-success">Pedido enviado para a equipe de gastronomia.</div>
        <?php endif; ?>

        <?php if ($mensagemErro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>

        <section class="gastronomia-hero mb-4">
            <div>
                <span class="badge text-bg-warning mb-3"><i class="bi bi-clock me-1"></i>30 - 45 minutos</span>
                <h1>Gastronomia & Serviço de Quarto</h1>
                <p>Escolha seu pedido e acompanhe o preparo pelas notificações do painel.</p>
            </div>
        </section>

        <section class="categorias-gastronomia d-flex flex-wrap gap-2 mb-4" aria-label="Categorias do cardápio">
            <?php foreach (['Café da Manhã', 'Pratos Principais', 'Sobremesas', 'Bebidas'] as $categoria): ?>
                <button type="button" class="btn btn-light categoria-btn <?= $categoria === 'Café da Manhã' ? 'ativo' : '' ?>" data-categoria="<?= htmlspecialchars($categoria) ?>">
                    <?= htmlspecialchars($categoria) ?>
                </button>
            <?php endforeach; ?>
        </section>

        <?php if (!$reservaAtual): ?>
            <div class="estado-vazio">
                Você precisa ter uma reserva ativa para pedir serviço de quarto.
            </div>
        <?php else: ?>
            <section class="row g-4">
                <div class="col-xl-8">
                    <div class="row g-4">
                        <?php foreach ($itensCardapio as $item): ?>
                            <div class="col-md-6 item-gastronomia" data-categoria="<?= htmlspecialchars($item['categoria']) ?>">
                                <article class="card-quarto h-100" data-id="<?= htmlspecialchars($item['id']) ?>" data-nome="<?= htmlspecialchars($item['nome']) ?>" data-preco="<?= htmlspecialchars($item['preco']) ?>">
                                    <img src="<?= htmlspecialchars($item['imagem']) ?>" alt="<?= htmlspecialchars($item['nome']) ?>">
                                    <div class="p-4">
                                        <div class="card-topo">
                                            <div>
                                                <span class="mini-texto fw-bold"><?= htmlspecialchars($item['categoria']) ?></span>
                                                <h3><?= htmlspecialchars($item['nome']) ?></h3>
                                            </div>
                                            <div class="preco">
                                                <strong><?= dinheiro($item['preco']) ?></strong>
                                            </div>
                                        </div>

                                        <p class="text-muted mt-3"><?= htmlspecialchars($item['descricao']) ?></p>

                                        <button type="button" class="btn btn-principal w-100 adicionar-sacola">
                                            <i class="bi bi-bag-plus me-2"></i>Adicionar à Sacola
                                        </button>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="cartao-admin sacola-pedido mb-4">
                        <div class="sacola-cabecalho">
                            <h2>Seu Pedido</h2>
                            <span id="sacola-contador">0 Itens</span>
                        </div>

                        <div class="sacola-corpo" id="sacola-corpo">
                            <div class="sacola-vazia">
                                <i class="bi bi-bag"></i>
                                <p>Seu carrinho está vazio.<br>Selecione itens para começar.</p>
                            </div>
                        </div>

                        <form method="POST" id="form-sacola" class="sacola-rodape">
                            <input type="hidden" name="pedido_json" id="pedido-json">
                            <div class="linha-valor mb-3">
                                <span>Total</span>
                                <strong id="sacola-total">R$ 0,00</strong>
                            </div>
                            <button type="submit" class="btn btn-principal w-100" disabled id="enviar-sacola">
                                Solicitar Pedido
                            </button>
                        </form>
                    </div>

                    <div class="cartao-admin p-4 bg-dark text-white">
                        <h2 class="h5 fw-bold mb-2">Quarto <?= htmlspecialchars($reservaAtual['numero']) ?></h2>
                        <i class="bi bi-telephone fs-4 text-warning"></i>
                        <h2 class="h5 fw-bold mt-3">Precisa de ajuda?</h2>
                        <p class="text-white-50">Fale com a recepção para alergias, restrições ou pedidos personalizados.</p>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <script src="../../assets/js/script.js?v=7"></script>
</body>
</html>
