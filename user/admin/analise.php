<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('admin');
require_once __DIR__ . '/../../include/conexao.php';
require_once __DIR__ . '/../../include/funcoes.php';
atualizarReservasVencidas($conexao);

$dataInicio = $_GET['inicio'] ?? date('Y-m-01');
$dataFim = $_GET['fim'] ?? date('Y-m-d');

if ($dataInicio > $dataFim) {
    $temporaria = $dataInicio;
    $dataInicio = $dataFim;
    $dataFim = $temporaria;
}

try {
    $quartosDisponiveis = (int) $conexao->query("SELECT COUNT(*) FROM quartos WHERE LOWER(status) IN ('disponivel', 'disponível', 'livre')")->fetchColumn();
    $quartosOcupados = (int) $conexao->query("SELECT COUNT(*) FROM quartos WHERE LOWER(status) LIKE '%ocup%'")->fetchColumn();

    $stmt = $conexao->prepare(
        "SELECT COALESCE(SUM(valor_total), 0)
         FROM reservas
         WHERE data_entrada BETWEEN :inicio AND :fim"
    );
    $stmt->bindValue(':inicio', $dataInicio);
    $stmt->bindValue(':fim', $dataFim);
    $stmt->execute();
    $receita = (float) $stmt->fetchColumn();

    $stmt = $conexao->prepare(
        "SELECT COUNT(*) AS total_reservas,
                COALESCE(SUM(GREATEST(DATEDIFF(data_saida, data_entrada), 1)), 0) AS total_noites
         FROM reservas
         WHERE data_entrada BETWEEN :inicio AND :fim"
    );
    $stmt->bindValue(':inicio', $dataInicio);
    $stmt->bindValue(':fim', $dataFim);
    $stmt->execute();
    $resumoPeriodo = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalReservas = (int) ($resumoPeriodo['total_reservas'] ?? 0);
    $totalNoites = (int) ($resumoPeriodo['total_noites'] ?? 0);

    $stmt = $conexao->prepare(
        "SELECT
            SUM(CASE WHEN LOWER(observacao) LIKE '%serviço de quarto%' THEN 1 ELSE 0 END) AS servicos_quarto,
            SUM(CASE WHEN LOWER(observacao) NOT LIKE '%serviço de quarto%' OR observacao IS NULL THEN 1 ELSE 0 END) AS limpezas
         FROM solicitacoes_limpeza
         WHERE DATE(data_solicitacao) BETWEEN :inicio AND :fim"
    );
    $stmt->bindValue(':inicio', $dataInicio);
    $stmt->bindValue(':fim', $dataFim);
    $stmt->execute();
    $servicosPeriodo = $stmt->fetch(PDO::FETCH_ASSOC);
    $servicosQuarto = (int) ($servicosPeriodo['servicos_quarto'] ?? 0);
    $pedidosLimpeza = (int) ($servicosPeriodo['limpezas'] ?? 0);

    $sqlReservas = "SELECT reservas.id_reserva, hospedes.nome AS hospede, quartos.numero,
                           quartos.tipo, reservas.data_entrada, reservas.data_saida,
                           GREATEST(DATEDIFF(reservas.data_saida, reservas.data_entrada), 1) AS noites,
                           reservas.status, reservas.valor_total,
                           (
                               SELECT solicitacoes_limpeza.status
                               FROM solicitacoes_limpeza
                               WHERE solicitacoes_limpeza.id_quarto = quartos.id_quarto
                               ORDER BY solicitacoes_limpeza.data_solicitacao DESC
                               LIMIT 1
                           ) AS status_limpeza
                    FROM reservas
                    INNER JOIN hospedes ON hospedes.id_hospede = reservas.id_hospede
                    INNER JOIN quartos ON quartos.id_quarto = reservas.id_quarto
                    WHERE reservas.data_entrada BETWEEN :inicio AND :fim
                    ORDER BY reservas.data_entrada DESC, reservas.id_reserva DESC
                    LIMIT 8";
    $stmt = $conexao->prepare($sqlReservas);
    $stmt->bindValue(':inicio', $dataInicio);
    $stmt->bindValue(':fim', $dataFim);
    $stmt->execute();
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $erro) {
    $quartosDisponiveis = 0;
    $quartosOcupados = 0;
    $receita = 0;
    $totalReservas = 0;
    $totalNoites = 0;
    $servicosQuarto = 0;
    $pedidosLimpeza = 0;
    $reservas = [];
}

$paramsPdf = http_build_query([
    'inicio' => $dataInicio,
    'fim' => $dataFim,
]);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise - LuxeStay PMS</title>
    <link rel="icon" type="image/png" href="/LuxeStay/assets/img/Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../../include/menu_admin.php'; ?>

    <header class="topo-admin">
        <h1>Relatórios</h1>

        <div class="d-flex flex-wrap align-items-center justify-content-end gap-3">
            <div class="usuario-topo">
                <span>Logado: <?= htmlspecialchars($_SESSION['nome'] ?? 'Administrador') ?></span>
                <a class="btn btn-sm btn-outline-dark" href="../../logout.php">Sair</a>
            </div>
        </div>
    </header>

    <main class="conteudo-admin">
        <section class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
            <div>
                <div class="small text-muted mb-2">Gestão / <strong class="text-warning">Análise</strong></div>
                <h2 class="display-6 fw-bold mb-1">Análise e Relatórios</h2>
                <p class="text-muted mb-0">Acompanhe ocupação, receita e reservas no período selecionado.</p>
            </div>

            <a href="relatorio_pdf.php?<?= htmlspecialchars($paramsPdf) ?>" class="btn btn-principal px-4 py-3">
                <i class="bi bi-download me-2"></i>Baixar Relatório PDF
            </a>
        </section>

        <form class="cartao-admin p-4 mb-4" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="inicio" class="form-label fw-bold small">DATA INÍCIO</label>
                    <input type="date" class="form-control" id="inicio" name="inicio" value="<?= htmlspecialchars($dataInicio) ?>">
                </div>

                <div class="col-md-4">
                    <label for="fim" class="form-label fw-bold small">DATA FIM</label>
                    <input type="date" class="form-control" id="fim" name="fim" value="<?= htmlspecialchars($dataFim) ?>">
                </div>

                <div class="col-md-4">
                    <button type="submit" class="btn btn-outline-dark w-100">
                        <i class="bi bi-funnel me-2"></i>Filtrar
                    </button>
                </div>
            </div>
        </form>

        <section class="analise-grid mb-4">
            <div class="cartao-admin indicador analise-metrica">
                <span class="mini-texto fw-bold">QUARTOS DISPONÍVEIS</span>
                <strong><?= $quartosDisponiveis ?></strong>
                <div class="analise-marca"><i class="bi bi-door-open"></i></div>
            </div>

            <div class="cartao-admin indicador analise-metrica">
                <span class="mini-texto fw-bold">QUARTOS OCUPADOS</span>
                <strong class="text-warning"><?= $quartosOcupados ?></strong>
                <div class="analise-marca"><i class="bi bi-door-closed"></i></div>
            </div>

            <div class="cartao-admin analise-receita">
                <div>
                    <span class="mini-texto fw-bold text-white-50">RECEITA TOTAL ESTIMADA</span>
                    <strong><?= dinheiro($receita) ?></strong>
                    <p class="mb-0">Baseada nas reservas criadas entre <?= dataBrasil($dataInicio) ?> e <?= dataBrasil($dataFim) ?>.</p>
                </div>
                <i class="bi bi-graph-up-arrow"></i>
            </div>
        </section>

        <section class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="cartao-admin p-4 h-100">
                    <span class="mini-texto fw-bold">RESERVAS NO PERÍODO</span>
                    <strong class="d-block fs-2 mt-2"><?= $totalReservas ?></strong>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="cartao-admin p-4 h-100">
                    <span class="mini-texto fw-bold">NOITES RESERVADAS</span>
                    <strong class="d-block fs-2 mt-2"><?= $totalNoites ?></strong>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="cartao-admin p-4 h-100">
                    <span class="mini-texto fw-bold">SERVIÇOS DE QUARTO</span>
                    <strong class="d-block fs-2 mt-2"><?= $servicosQuarto ?></strong>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="cartao-admin p-4 h-100">
                    <span class="mini-texto fw-bold">PEDIDOS DE LIMPEZA</span>
                    <strong class="d-block fs-2 mt-2"><?= $pedidosLimpeza ?></strong>
                </div>
            </div>
        </section>

        <section class="cartao-admin overflow-hidden">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-4">
                <h3 class="h5 m-0">Prévia dos Dados</h3>
                <span class="mini-texto">Mostrando <?= count($reservas) ?> reserva(s)</span>
            </div>

            <div class="table-responsive">
                <table class="table tabela-reservas m-0">
                    <thead class="table-light">
                        <tr>
                            <th>Número</th>
                            <th>Hóspede</th>
                            <th>Entrada/Saída</th>
                            <th>Noites</th>
                            <th>Arrecadado</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Limpeza</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservas)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Nenhuma reserva encontrada nesse período.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reservas as $reserva): ?>
                                <?php $limpeza = $reserva['status_limpeza'] ?: 'Sem solicitação'; ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($reserva['numero']) ?></td>
                                    <td><?= htmlspecialchars($reserva['hospede']) ?></td>
                                    <td><?= dataBrasil($reserva['data_entrada']) ?> - <?= dataBrasil($reserva['data_saida']) ?></td>
                                    <td><?= (int) $reserva['noites'] ?></td>
                                    <td class="fw-bold"><?= dinheiro($reserva['valor_total']) ?></td>
                                    <td><span class="badge text-bg-light"><?= htmlspecialchars($reserva['tipo']) ?></span></td>
                                    <td><span class="status <?= htmlspecialchars(statusReservaClasse($reserva['status'])) ?>"><?= htmlspecialchars($reserva['status']) ?></span></td>
                                    <td><span class="status <?= str_contains(strtolower($limpeza), 'concl') ? 'confirmado' : 'pendente' ?>"><?= htmlspecialchars($limpeza) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
