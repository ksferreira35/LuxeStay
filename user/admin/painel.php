<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('admin');
require_once __DIR__ . '/../../include/conexao.php';
require_once __DIR__ . '/../../include/funcoes.php';
atualizarReservasVencidas($conexao);

$andarSelecionado = $_GET['andar_visao'] ?? '';

try {
    $totalQuartos = (int) $conexao->query("SELECT COUNT(*) FROM quartos")->fetchColumn();
    $quartosDisponiveis = (int) $conexao->query("SELECT COUNT(*) FROM quartos WHERE LOWER(status) IN ('disponivel', 'disponível', 'livre')")->fetchColumn();
    $quartosOcupados = (int) $conexao->query("SELECT COUNT(*) FROM quartos WHERE LOWER(status) LIKE '%ocup%'")->fetchColumn();
    $quartosLimpeza = (int) $conexao->query("SELECT COUNT(*) FROM quartos WHERE LOWER(status) LIKE '%limpeza%' OR LOWER(status) LIKE '%manut%'")->fetchColumn();
    $checkinsHoje = (int) $conexao->query("SELECT COUNT(*) FROM reservas WHERE data_entrada = CURDATE()")->fetchColumn();
    $checkoutsHoje = (int) $conexao->query("SELECT COUNT(*) FROM reservas WHERE data_saida = CURDATE()")->fetchColumn();
    $altaPrioridade = (int) $conexao->query("SELECT COUNT(*) FROM solicitacoes_limpeza WHERE LOWER(status) NOT IN ('concluido', 'concluído', 'finalizado')")->fetchColumn();

    $sqlReservas = "SELECT reservas.id_reserva, hospedes.nome AS hospede, quartos.numero, quartos.tipo,
                           reservas.data_entrada, reservas.status, reservas.valor_total
                    FROM reservas
                    INNER JOIN hospedes ON hospedes.id_hospede = reservas.id_hospede
                    INNER JOIN quartos ON quartos.id_quarto = reservas.id_quarto
                    ORDER BY reservas.data_entrada DESC, reservas.id_reserva DESC
                    LIMIT 3";
    $reservasRecentes = $conexao->query($sqlReservas)->fetchAll(PDO::FETCH_ASSOC);

    $notificacoesAdmin = [];

    foreach ($reservasRecentes as $reserva) {
        $notificacoesAdmin[] = [
            'tipo' => 'reserva',
            'chave' => 'reserva-' . $reserva['id_reserva'],
            'ordem' => (int) $reserva['id_reserva'],
            'titulo' => 'Nova reserva criada por ' . $reserva['hospede'] . '.',
            'descricao' => 'Quarto ' . $reserva['numero'] . ' · Entrada ' . dataBrasil($reserva['data_entrada']),
        ];
    }

    $sqlSolicitacoes = "SELECT solicitacoes_limpeza.id_solicitacao, solicitacoes_limpeza.status, solicitacoes_limpeza.observacao,
                               solicitacoes_limpeza.data_solicitacao, quartos.numero
                        FROM solicitacoes_limpeza
                        INNER JOIN quartos ON quartos.id_quarto = solicitacoes_limpeza.id_quarto
                        WHERE LOWER(solicitacoes_limpeza.status) NOT IN ('concluido', 'concluído', 'finalizado')
                        ORDER BY solicitacoes_limpeza.data_solicitacao DESC
                        LIMIT 5";
    $solicitacoesRecentes = $conexao->query($sqlSolicitacoes)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($solicitacoesRecentes as $solicitacao) {
        $observacao = strtolower($solicitacao['observacao'] ?? '');
        $titulo = str_contains($observacao, 'serviço de quarto')
            ? 'Novo serviço de quarto solicitado.'
            : 'Nova solicitação de limpeza criada.';

        $notificacoesAdmin[] = [
            'tipo' => 'servico',
            'chave' => 'solicitacao-' . $solicitacao['id_solicitacao'] . '-' . strtolower($solicitacao['status']),
            'ordem' => 100000 + (int) $solicitacao['id_solicitacao'],
            'titulo' => $titulo,
            'descricao' => 'Quarto ' . $solicitacao['numero'] . ' · ' . dataBrasil($solicitacao['data_solicitacao']) . ' às ' . date('H:i', strtotime($solicitacao['data_solicitacao'])),
        ];
    }

    usort($notificacoesAdmin, function($a, $b) {
        return $b['ordem'] <=> $a['ordem'];
    });

    $notificacoesAdmin = array_slice($notificacoesAdmin, 0, 8);

    $limitesAndares = $conexao->query("SELECT MIN(andar) AS menor, MAX(andar) AS maior FROM quartos WHERE andar BETWEEN 1 AND 12")->fetch(PDO::FETCH_ASSOC);
    if ($limitesAndares && $limitesAndares['menor'] !== null) {
        $andarInicial = (int) $limitesAndares['menor'];
        $andarFinal = min(12, (int) $limitesAndares['maior']);
        $andaresDisponiveis = range($andarInicial, max($andarInicial, $andarFinal));
    } else {
        $andaresDisponiveis = [];
    }

    if ($andarSelecionado !== '') {
        $sqlQuartos = "SELECT numero, status
                       FROM quartos
                       WHERE andar = :andar
                       ORDER BY andar ASC, numero ASC";
        $stmt = $conexao->prepare($sqlQuartos);
        $stmt->bindValue(':andar', (int) $andarSelecionado, PDO::PARAM_INT);
        $stmt->execute();
        $quartosVisaoGeral = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sqlQuartos = "SELECT numero, status
                       FROM quartos
                       ORDER BY andar ASC, numero ASC";
        $quartosVisaoGeral = $conexao->query($sqlQuartos)->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $erro) {
    $totalQuartos = 0;
    $quartosDisponiveis = 0;
    $quartosOcupados = 0;
    $quartosLimpeza = 0;
    $checkinsHoje = 0;
    $checkoutsHoje = 0;
    $altaPrioridade = 0;
    $reservasRecentes = [];
    $notificacoesAdmin = [];
    $andaresDisponiveis = [];
    $quartosVisaoGeral = [];
}

$tituloVisaoGeral = $andarSelecionado !== ''
    ? 'Visão Geral do ' . (int) $andarSelecionado . 'º Andar'
    : 'Visão Geral de Todos os Andares';
$subtituloVisaoGeral = $andarSelecionado !== ''
    ? 'Status em tempo real dos quartos desse andar.'
    : 'Status em tempo real dos quartos cadastrados.';

$ocupacao = $totalQuartos > 0 ? round(($quartosOcupados / $totalQuartos) * 100) : 0;
$percentualDisponivel = $totalQuartos > 0 ? round(($quartosDisponiveis / $totalQuartos) * 100) : 0;
$percentualOcupado = $totalQuartos > 0 ? round(($quartosOcupados / $totalQuartos) * 100) : 0;
$percentualLimpeza = $totalQuartos > 0 ? round(($quartosLimpeza / $totalQuartos) * 100) : 0;
$fimDisponivel = $percentualDisponivel;
$fimOcupado = $percentualDisponivel + $percentualOcupado;
$graficoStatus = $totalQuartos > 0
    ? "conic-gradient(var(--green) 0 {$fimDisponivel}%, var(--red) {$fimDisponivel}% {$fimOcupado}%, var(--orange) {$fimOcupado}% 100%)"
    : "#e9eaee";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - LuxeStay PMS</title>
    <link rel="icon" type="image/png" href="/LuxeStay/assets/img/Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../../include/menu_admin.php'; ?>

    <header class="topo-admin">
        <h1>LuxeStay PMS</h1>

        <div class="d-flex flex-wrap align-items-center justify-content-end gap-3">
            <form class="busca-admin" method="GET" action="buscar.php">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" name="q" placeholder="Buscar hóspedes, quartos...">
                </div>
            </form>

            <details class="notificacoes-topo" data-storage-key="luxestay_notificacoes_admin">
                <summary class="btn btn-light position-relative" aria-label="Notificações">
                    <i class="bi bi-bell"></i>
                    <?php if (!empty($notificacoesAdmin)): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notificacoes-badge">
                            <?= count($notificacoesAdmin) ?>
                        </span>
                    <?php endif; ?>
                </summary>

                <div class="notificacoes-menu">
                    <strong>Notificações</strong>
                    <?php if (empty($notificacoesAdmin)): ?>
                        <div class="notificacao-item text-muted small">Nenhuma notificação recente.</div>
                    <?php else: ?>
                        <?php foreach ($notificacoesAdmin as $notificacao): ?>
                            <div class="notificacao-item" data-notificacao-chave="<?= htmlspecialchars($notificacao['chave']) ?>">
                                <div class="fw-bold small"><?= htmlspecialchars($notificacao['titulo']) ?></div>
                                <div class="mini-texto"><?= htmlspecialchars($notificacao['descricao']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </details>
            <a href="suporte.php" class="btn btn-light" aria-label="Suporte">
                <i class="bi bi-question-circle"></i>
            </a>
            <a href="configuracoes.php" class="btn btn-light" aria-label="Configurações">
                <i class="bi bi-gear"></i>
            </a>

            <div class="usuario-topo">
                <span>Logado: <?= htmlspecialchars($_SESSION['nome'] ?? 'Administrador') ?></span>
                <a class="btn btn-sm btn-outline-dark" href="../../logout.php">Sair</a>
            </div>
        </div>
    </header>

    <main class="conteudo-admin">
        <section class="mb-4">
            <h2 class="h5 mb-1">Bem-vindo de volta, <?= htmlspecialchars($_SESSION['nome'] ?? 'Administrador') ?></h2>
            <p class="text-muted mb-0">Aqui está o que está acontecendo na Sede Principal hoje, <?= date('d/m/Y') ?>.</p>
        </section>

        <section class="clima-widget cartao-admin mb-4" data-clima-widget>
            <div>
                <span class="mini-texto fw-bold">API PÚBLICA OPEN-METEO</span>
                <h2>Clima na Sede Principal</h2>
                <p data-clima-descricao>Carregando condições atuais...</p>
            </div>

            <div class="clima-dados">
                <i class="bi bi-cloud-sun"></i>
                <strong data-clima-temperatura>--°C</strong>
                <span data-clima-vento>Vento -- km/h</span>
            </div>
        </section>

        <section class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="cartao-admin indicador">
                    <div class="d-flex justify-content-between gap-3">
                        <div class="indicador-icone"><i class="bi bi-door-open"></i></div>
                        <span class="small fw-bold text-success">Base atualizada</span>
                    </div>
                    <h3>Ocupação Total</h3>
                    <strong><?= $ocupacao ?>%</strong>
                    <div class="progresso mt-3"><span style="width: <?= $ocupacao ?>%;"></span></div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="cartao-admin indicador">
                    <div class="indicador-icone amarelo"><i class="bi bi-box-arrow-in-right"></i></div>
                    <h3>Check-ins Hoje</h3>
                    <strong><?= $checkinsHoje ?></strong>
                    <span class="mini-texto">Reservas com entrada hoje</span>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="cartao-admin indicador">
                    <div class="indicador-icone cinza"><i class="bi bi-box-arrow-right"></i></div>
                    <h3>Check-outs Hoje</h3>
                    <strong><?= $checkoutsHoje ?></strong>
                    <span class="mini-texto">Reservas com saída hoje</span>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="cartao-admin indicador">
                    <div class="indicador-icone vermelho"><i class="bi bi-door-closed"></i></div>
                    <h3 class="text-danger">Alta Prioridade</h3>
                    <strong><?= $altaPrioridade ?></strong>
                    <span class="mini-texto">Solicitações de limpeza abertas</span>
                </div>
            </div>
        </section>

        <section class="row g-4 mb-4">
            <div class="col-xl-8">
                <div class="cartao-admin overflow-hidden">
                    <div class="d-flex align-items-center justify-content-between p-4">
                        <h2 class="h5 m-0">Reservas Recentes</h2>
                        <a href="reservas.php" class="small fw-bold text-decoration-none text-warning">Ver Tudo <i class="bi bi-arrow-right"></i></a>
                    </div>

                    <div class="table-responsive">
                        <table class="table tabela-reservas m-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Hóspede</th>
                                    <th>Quarto</th>
                                    <th>Entrada</th>
                                    <th>Status</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reservasRecentes)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            Nenhuma reserva cadastrada no banco.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reservasRecentes as $indice => $reserva): ?>
                                        <?php $classeAvatar = $indice === 1 ? 'amarelo' : ($indice === 2 ? 'escuro' : ''); ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <span class="avatar-letra <?= $classeAvatar ?>"><?= htmlspecialchars(iniciaisNome($reserva['hospede'])) ?></span>
                                                    <div>
                                                        <strong><?= htmlspecialchars($reserva['hospede']) ?></strong>
                                                        <div class="mini-texto">Reserva #<?= htmlspecialchars($reserva['id_reserva']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($reserva['tipo']) ?> <?= htmlspecialchars($reserva['numero']) ?></td>
                                            <td><?= htmlspecialchars(dataBrasil($reserva['data_entrada'])) ?></td>
                                            <td>
                                                <span class="status <?= htmlspecialchars(statusReservaClasse($reserva['status'])) ?>">
                                                    <?= htmlspecialchars($reserva['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold"><?= dinheiro($reserva['valor_total'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="cartao-admin p-4 h-100">
                    <h2 class="h5 mb-3">Distribuição de Status dos Quartos</h2>

                    <div
                        class="grafico-rosca"
                        style="background: <?= htmlspecialchars($graficoStatus) ?>;"
                        aria-label="<?= $totalQuartos ?> quartos: <?= $quartosDisponiveis ?> disponíveis, <?= $quartosOcupados ?> ocupados e <?= $quartosLimpeza ?> em limpeza"
                    >
                        <div class="grafico-centro">
                            <div>
                                <strong><?= $totalQuartos ?></strong>
                                <span class="mini-texto">Total de Quartos</span>
                            </div>
                        </div>
                    </div>

                    <div class="legenda-status">
                        <div class="d-flex align-items-center justify-content-between">
                            <span><span class="bolinha disponivel me-2"></span>Disponível</span>
                            <strong><?= $quartosDisponiveis ?> (<?= $percentualDisponivel ?>%)</strong>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span><span class="bolinha ocupado me-2"></span>Ocupado</span>
                            <strong><?= $quartosOcupados ?> (<?= $percentualOcupado ?>%)</strong>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span><span class="bolinha limpeza me-2"></span>Limpeza</span>
                            <strong><?= $quartosLimpeza ?> (<?= $percentualLimpeza ?>%)</strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1"><?= htmlspecialchars($tituloVisaoGeral) ?></h2>
                    <p class="text-muted mb-0"><?= htmlspecialchars($subtituloVisaoGeral) ?></p>
                </div>

                <form class="d-flex flex-wrap align-items-center gap-2" method="GET">
                    <label class="visually-hidden" for="andar_visao">Andar</label>
                    <select class="form-select form-select-sm seletor-andar" id="andar_visao" name="andar_visao" onchange="this.form.submit()">
                        <option value="">Todos os andares</option>
                        <?php foreach ($andaresDisponiveis as $andar): ?>
                            <option value="<?= (int) $andar ?>" <?= (string) $andarSelecionado === (string) $andar ? 'selected' : '' ?>>
                                <?= (int) $andar ?>º Andar
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-light btn-sm"><i class="bi bi-filter"></i></button>
                </form>
            </div>

            <?php if (empty($quartosVisaoGeral)): ?>
                <div class="estado-vazio">
                    Nenhum quarto cadastrado para exibir na visão geral.
                </div>
            <?php else: ?>
                <div class="quartos-grid">
                    <?php foreach ($quartosVisaoGeral as $quarto): ?>
                        <?php $classeQuarto = statusQuartoClasse($quarto['status']); ?>
                        <div class="quarto-card <?= htmlspecialchars($classeQuarto) ?>">
                            <span><?= htmlspecialchars($quarto['numero']) ?></span>
                            <div class="linha"></div>
                            <?= htmlspecialchars(strtoupper($quarto['status'])) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script src="../../assets/js/script.js?v=9"></script>
</body>
</html>
