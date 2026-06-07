<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('cliente');
require_once __DIR__ . '/../../include/conexao.php';
require_once __DIR__ . '/../../include/funcoes.php';
atualizarReservasVencidas($conexao);

$reservaAtual = null;
$reservasAnteriores = [];
$notificacoesCliente = [];
$consumoQuarto = 0;
$pedidosConsumo = [];

try {
    $email = $_SESSION['email'] ?? '';

    $sqlAtual = "SELECT reservas.id_reserva, reservas.data_entrada, reservas.data_saida,
                        reservas.status, reservas.valor_total, quartos.id_quarto,
                        quartos.numero, quartos.tipo, quartos.andar
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

    $sqlHistorico = "SELECT reservas.id_reserva, reservas.data_entrada, reservas.data_saida,
                            reservas.status, reservas.valor_total, quartos.numero, quartos.tipo
                     FROM reservas
                     INNER JOIN hospedes ON hospedes.id_hospede = reservas.id_hospede
                     INNER JOIN quartos ON quartos.id_quarto = reservas.id_quarto
                     WHERE hospedes.email = :email
                     AND LOWER(reservas.status) NOT IN ('ocupado', 'confirmado', 'check-in realizado')
                     ORDER BY reservas.data_entrada DESC
                     LIMIT 5";
    $stmt = $conexao->prepare($sqlHistorico);
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    $reservasAnteriores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($reservaAtual) {
        $sqlNotificacoes = "SELECT id_solicitacao, status, observacao, data_solicitacao
                            FROM solicitacoes_limpeza
                            WHERE id_quarto = :id_quarto
                            ORDER BY data_solicitacao DESC
                            LIMIT 5";
        $stmt = $conexao->prepare($sqlNotificacoes);
        $stmt->bindValue(':id_quarto', (int) $reservaAtual['id_quarto'], PDO::PARAM_INT);
        $stmt->execute();
        $notificacoesCliente = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlConsumo = "SELECT observacao, data_solicitacao
                       FROM solicitacoes_limpeza
                       WHERE id_quarto = :id_quarto
                       AND LOWER(observacao) LIKE '%serviço de quarto%'
                       ORDER BY data_solicitacao DESC";
        $stmt = $conexao->prepare($sqlConsumo);
        $stmt->bindValue(':id_quarto', (int) $reservaAtual['id_quarto'], PDO::PARAM_INT);
        $stmt->execute();
        $pedidosConsumo = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pedidosConsumo as $pedidoConsumo) {
            $consumoQuarto += valorTotalObservacao($pedidoConsumo['observacao']);
        }
    }
} catch (PDOException $erro) {
    $reservaAtual = null;
    $reservasAnteriores = [];
    $notificacoesCliente = [];
    $consumoQuarto = 0;
    $pedidosConsumo = [];
}

$totalFaturaAtual = (float) ($reservaAtual['valor_total'] ?? 0) + $consumoQuarto;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Cliente - LuxeStay PMS</title>
    <link rel="icon" type="image/png" href="/LuxeStay/assets/img/Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../../include/menu_cliente.php'; ?>

    <header class="topo-cliente">
        <form class="campo-busca w-100" method="GET" action="buscar.php">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" name="busca" placeholder="Buscar serviços...">
            </div>
        </form>

        <div class="d-flex align-items-center gap-3">
            <details class="notificacoes-topo" data-storage-key="luxestay_notificacoes_cliente_<?= htmlspecialchars(md5($_SESSION['email'] ?? $_SESSION['nome'] ?? 'cliente')) ?>">
                <summary class="btn btn-light position-relative" aria-label="Notificações">
                    <i class="bi bi-bell"></i>
                    <?php if (!empty($notificacoesCliente)): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notificacoes-badge">
                            <?= count($notificacoesCliente) ?>
                        </span>
                    <?php endif; ?>
                </summary>

                <div class="notificacoes-menu">
                    <strong>Notificações</strong>
                    <?php if (empty($notificacoesCliente)): ?>
                        <div class="notificacao-item text-muted small">Nenhuma atualização do quarto ainda.</div>
                    <?php else: ?>
                        <?php foreach ($notificacoesCliente as $notificacao): ?>
                            <?php
                                $observacao = strtolower($notificacao['observacao'] ?? '');
                                $status = strtolower($notificacao['status'] ?? '');
                                if (str_contains($observacao, 'serviço de quarto')) {
                                    if (str_contains($status, 'concl')) {
                                        $texto = 'Seu serviço de quarto foi concluído.';
                                    } elseif (str_contains($status, 'andamento') || str_contains($status, 'progresso')) {
                                        $texto = 'Seu pedido está sendo preparado.';
                                    } else {
                                        $texto = 'Recebemos seu pedido de gastronomia.';
                                    }
                                } else {
                                    $texto = str_contains($status, 'concl')
                                        ? 'Seu quarto foi limpo.'
                                        : 'Sua solicitação de limpeza está em andamento.';
                                }
                            ?>
                            <div class="notificacao-item" data-notificacao-chave="solicitacao-<?= (int) $notificacao['id_solicitacao'] ?>-<?= htmlspecialchars(strtolower($notificacao['status'])) ?>">
                                <div class="fw-bold small"><?= htmlspecialchars($texto) ?></div>
                                <div class="mini-texto"><?= htmlspecialchars(dataBrasil($notificacao['data_solicitacao'])) ?> às <?= date('H:i', strtotime($notificacao['data_solicitacao'])) ?></div>
                                <?php if ((str_contains($status, 'andamento') || str_contains($status, 'progresso')) && !str_contains($status, 'concl')): ?>
                                    <form method="POST" action="confirmar_solicitacao.php" class="mt-2">
                                        <input type="hidden" name="id_solicitacao" value="<?= (int) $notificacao['id_solicitacao'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success w-100">Confirmar conclusão</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </details>

            <div class="usuario-topo">
            <span>Logado: <?= htmlspecialchars($_SESSION['nome'] ?? 'Cliente') ?></span>
            <a class="btn btn-sm btn-outline-dark" href="../../logout.php">Sair</a>
            </div>
        </div>
    </header>

    <main class="conteudo-cliente">
        <?php if (($_GET['limpeza'] ?? '') === 'ok'): ?>
            <div class="alert alert-success">Solicitação de limpeza enviada.</div>
        <?php endif; ?>

        <?php if (($_GET['servico'] ?? '') === 'ok'): ?>
            <div class="alert alert-success">Serviço de quarto solicitado.</div>
        <?php endif; ?>

        <?php if (($_GET['confirmado'] ?? '') === 'ok'): ?>
            <div class="alert alert-success">Serviço confirmado com sucesso.</div>
        <?php endif; ?>

        <?php if (($_GET['reserva'] ?? '') === 'ok'): ?>
            <div class="alert alert-success">Reserva criada com sucesso.</div>
        <?php endif; ?>

        <section class="mb-4">
            <h1 class="display-6 fw-bold mb-1">Bem-vindo de volta, <?= htmlspecialchars($_SESSION['nome'] ?? 'Cliente') ?>.</h1>
            <p class="text-muted mb-0">Gerencie sua estadia atual e solicite serviços.</p>
        </section>

        <section class="clima-widget cartao-admin mb-4" data-clima-widget>
            <div>
                <span class="mini-texto fw-bold">CLIMA PARA SUA ESTADIA</span>
                <h2>Condições na Sede Principal</h2>
                <p data-clima-descricao>Consultando previsão pela API pública...</p>
            </div>

            <div class="clima-dados">
                <i class="bi bi-cloud-sun"></i>
                <strong data-clima-temperatura>--°C</strong>
                <span data-clima-vento>Vento -- km/h</span>
            </div>
        </section>

        <?php if (!$reservaAtual): ?>
            <div class="estado-vazio mb-4">
                Você ainda não possui uma reserva ativa. Acesse Reservas para escolher um quarto.
            </div>
            <a href="reservas.php" class="btn btn-principal px-4 py-3">Ver Quartos Disponíveis</a>
        <?php else: ?>
            <section class="row g-4 mb-4" id="servicos">
                <div class="col-xl-8">
                    <div class="cartao-admin overflow-hidden">
                        <div class="row g-0">
                            <div class="col-md-5">
                                <img src="<?= htmlspecialchars(imagemQuarto($reservaAtual['tipo'])) ?>" alt="Quarto atual" class="w-100 h-100 object-fit-cover">
                            </div>
                            <div class="col-md-7 p-4">
                                <div class="d-flex justify-content-between gap-3 mb-3">
                                    <div>
                                        <span class="mini-texto fw-bold">MINHA RESERVA ATUAL</span>
                                        <h2 class="fw-bold mt-2"><?= htmlspecialchars($reservaAtual['tipo']) ?></h2>
                                    </div>
                                    <div class="text-end">
                                        <span class="mini-texto">Número do Quarto</span>
                                        <strong class="d-block fs-4 text-warning"><?= htmlspecialchars($reservaAtual['numero']) ?></strong>
                                    </div>
                                </div>

                                <div class="row border-top border-bottom py-3 mb-3">
                                    <div class="col">
                                        <span class="mini-texto">Entrada</span>
                                        <strong class="d-block"><?= htmlspecialchars(dataBrasil($reservaAtual['data_entrada'])) ?></strong>
                                    </div>
                                    <div class="col">
                                        <span class="mini-texto">Saída</span>
                                        <strong class="d-block"><?= htmlspecialchars(dataBrasil($reservaAtual['data_saida'])) ?></strong>
                                    </div>
                                </div>

                                <p class="mb-0"><i class="bi bi-wifi text-warning me-2"></i>WiFi Ultra-Rápido de Cortesia Incluso</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="d-grid gap-3">
                        <a class="cartao-admin p-4 text-decoration-none text-reset" href="gastronomia.php">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="bi bi-cup-hot fs-3 text-warning"></i>
                                    <h3 class="h5 fw-bold mt-3">Serviço de Quarto</h3>
                                    <p class="text-muted mb-0">Gastronomia entregue na suíte.</p>
                                </div>
                                <span class="btn btn-light"><i class="bi bi-chevron-right"></i></span>
                            </div>
                        </a>

                        <form class="cartao-admin p-4" method="POST" action="solicitar_limpeza.php">
                            <input type="hidden" name="id_quarto" value="<?= (int) $reservaAtual['id_quarto'] ?>">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="bi bi-brush fs-3 text-warning"></i>
                                    <h3 class="h5 fw-bold mt-3">Solicitar Limpeza</h3>
                                    <p class="text-muted mb-0">Agende uma renovação para sua suíte.</p>
                                </div>
                                <button type="submit" class="btn btn-light"><i class="bi bi-chevron-right"></i></button>
                            </div>
                        </form>

                        <div class="cartao-admin p-4">
                            <i class="bi bi-receipt fs-3 text-warning"></i>
                            <h3 class="h5 fw-bold mt-3">Ver Fatura</h3>
                            <div class="linha-valor mt-3">
                                <span>Reserva</span>
                                <strong><?= dinheiro($reservaAtual['valor_total']) ?></strong>
                            </div>
                            <div class="linha-valor mt-2">
                                <span>Consumo no quarto</span>
                                <strong><?= dinheiro($consumoQuarto) ?></strong>
                            </div>
                            <hr>
                            <div class="linha-valor">
                                <span>Total atual</span>
                                <strong><?= dinheiro($totalFaturaAtual) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="cartao-admin overflow-hidden">
            <div class="d-flex align-items-center justify-content-between p-4">
                <h2 class="h5 m-0">Estadias Anteriores</h2>
            </div>
            <div class="table-responsive">
                <table class="table tabela-reservas m-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reserva</th>
                            <th>Datas</th>
                            <th>Tipo de Quarto</th>
                            <th>Status</th>
                            <th class="text-end">Fatura</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservasAnteriores)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Nenhuma estadia anterior encontrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reservasAnteriores as $reserva): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($reserva['id_reserva']) ?></td>
                                    <td><?= dataBrasil($reserva['data_entrada']) ?> - <?= dataBrasil($reserva['data_saida']) ?></td>
                                    <td><?= htmlspecialchars($reserva['tipo']) ?> <?= htmlspecialchars($reserva['numero']) ?></td>
                                    <td><span class="status confirmado"><?= htmlspecialchars($reserva['status']) ?></span></td>
                                    <td class="text-end fw-bold"><?= dinheiro($reserva['valor_total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <script src="../../assets/js/script.js?v=9"></script>
</body>
</html>
