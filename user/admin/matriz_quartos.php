<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('admin');
require_once __DIR__ . '/../../include/conexao.php';
require_once __DIR__ . '/../../include/funcoes.php';
atualizarReservasVencidas($conexao);

$andarFiltro = $_GET['andar'] ?? '';
$tipoFiltro = $_GET['tipo'] ?? '';

try {
    $filtros = [];
    $parametros = [];

    if ($andarFiltro !== '') {
        $filtros[] = 'andar = :andar';
        $parametros[':andar'] = (int) $andarFiltro;
    }

    if ($tipoFiltro !== '') {
        $filtros[] = 'tipo = :tipo';
        $parametros[':tipo'] = $tipoFiltro;
    }

    $where = $filtros ? 'WHERE ' . implode(' AND ', $filtros) : '';

    $sqlQuartos = "SELECT id_quarto, numero, andar, tipo, valor_diaria, status
                   FROM quartos
                   {$where}
                   ORDER BY andar ASC, numero ASC";
    $stmt = $conexao->prepare($sqlQuartos);

    foreach ($parametros as $campo => $valor) {
        $tipoParametro = $campo === ':andar' ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($campo, $valor, $tipoParametro);
    }

    $stmt->execute();
    $quartos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalQuartos = (int) $conexao->query("SELECT COUNT(*) FROM quartos")->fetchColumn();
    $disponiveis = (int) $conexao->query("SELECT COUNT(*) FROM quartos WHERE LOWER(status) IN ('disponivel', 'disponível', 'livre')")->fetchColumn();
    $ocupados = (int) $conexao->query("SELECT COUNT(*) FROM quartos WHERE LOWER(status) LIKE '%ocup%'")->fetchColumn();
    $limpeza = (int) $conexao->query("SELECT COUNT(*) FROM quartos WHERE LOWER(status) LIKE '%limpeza%'")->fetchColumn();
    $manutencao = (int) $conexao->query("SELECT COUNT(*) FROM quartos WHERE LOWER(status) LIKE '%manut%'")->fetchColumn();
    $andares = $conexao->query("SELECT DISTINCT andar FROM quartos ORDER BY andar ASC")->fetchAll(PDO::FETCH_COLUMN);
    $tiposFiltro = $conexao->query("SELECT DISTINCT tipo FROM quartos ORDER BY tipo ASC")->fetchAll(PDO::FETCH_COLUMN);

    $sqlTipos = "SELECT tipo, COUNT(*) AS total
                 FROM quartos
                 GROUP BY tipo
                 ORDER BY total DESC
                 LIMIT 3";
    $tipos = $conexao->query($sqlTipos)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $erro) {
    $quartos = [];
    $totalQuartos = 0;
    $disponiveis = 0;
    $ocupados = 0;
    $limpeza = 0;
    $manutencao = 0;
    $andares = [];
    $tiposFiltro = [];
    $tipos = [];
}

$ocupacao = $totalQuartos > 0 ? round(($ocupados / $totalQuartos) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matriz de Quartos - LuxeStay PMS</title>
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
                    <input type="search" class="form-control" name="q" placeholder="Buscar quartos, tipos...">
                </div>
            </form>

            <div class="usuario-topo">
                <span>Logado: <?= htmlspecialchars($_SESSION['nome'] ?? 'Administrador') ?></span>
                <a class="btn btn-sm btn-outline-dark" href="../../logout.php">Sair</a>
            </div>
        </div>
    </header>

    <main class="conteudo-admin">
        <?php if (($_GET['editado'] ?? '') === 'ok'): ?>
            <div class="alert alert-success">Quarto atualizado com sucesso.</div>
        <?php endif; ?>

        <section class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
            <div>
                <div class="small text-muted mb-2">Gestão / <strong class="text-warning">Matriz de Quartos</strong></div>
                <h2 class="display-6 fw-bold mb-1">Gestão de Quartos</h2>
                <p class="text-muted mb-0">Monitore e gerencie o inventário da propriedade.</p>
            </div>

            <a href="cadastrar_quarto.php" class="btn btn-principal px-4 py-3">
                <i class="bi bi-plus-circle me-2"></i>Adicionar Novo Quarto
            </a>
        </section>

        <section class="resumo-matriz mb-4">
            <div class="cartao-admin indicador">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="mini-texto fw-bold">Taxa de Ocupação</span>
                        <strong class="display-6"><?= number_format($ocupacao, 1, ',', '.') ?>%</strong>
                        <span class="small fw-bold text-success">Dados do banco</span>
                    </div>
                    <i class="bi bi-graph-up-arrow text-warning fs-4"></i>
                </div>
                <div class="progresso mt-3"><span style="width: <?= $ocupacao ?>%;"></span></div>
            </div>

            <div class="cartao-admin p-4">
                <div class="metricas-quartos">
                    <div class="metrica-quarto ativo">
                        <span>Todos os Quartos</span>
                        <strong><?= $totalQuartos ?></strong>
                    </div>
                    <div class="metrica-quarto disponivel">
                        <span>Disponíveis</span>
                        <strong><?= $disponiveis ?></strong>
                    </div>
                    <div class="metrica-quarto ocupado">
                        <span>Ocupados</span>
                        <strong><?= $ocupados ?></strong>
                    </div>
                    <div class="metrica-quarto limpeza">
                        <span>Limpeza</span>
                        <strong><?= $limpeza ?></strong>
                    </div>
                </div>

                <div class="metrica-quarto manutencao mt-3">
                    <span>Manutenção</span>
                    <strong><?= $manutencao ?></strong>
                </div>
            </div>
        </section>

        <section class="cartao-admin overflow-hidden mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-4">
                <form class="d-flex flex-wrap gap-2" method="GET">
                    <select class="form-select form-select-sm seletor-andar" name="andar" onchange="this.form.submit()">
                        <option value="">Todos os Andares</option>
                        <?php foreach ($andares as $andar): ?>
                            <option value="<?= (int) $andar ?>" <?= (string) $andarFiltro === (string) $andar ? 'selected' : '' ?>>
                                <?= (int) $andar ?>º Andar
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select class="form-select form-select-sm seletor-andar" name="tipo" onchange="this.form.submit()">
                        <option value="">Todos os Tipos</option>
                        <?php foreach ($tiposFiltro as $tipo): ?>
                            <option value="<?= htmlspecialchars($tipo) ?>" <?= $tipoFiltro === $tipo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tipo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <span class="mini-texto">Mostrando <?= count($quartos) ?> de <?= $totalQuartos ?> quartos</span>
            </div>

            <div class="table-responsive">
                <table class="table tabela-reservas m-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nº do Quarto</th>
                            <th>Tipo</th>
                            <th>Andar</th>
                            <th>Status</th>
                            <th>Tarifa</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($quartos)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Nenhum quarto cadastrado no banco.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($quartos as $quarto): ?>
                                <?php $classeStatus = statusQuartoClasse($quarto['status']); ?>
                                <?php
                                    $podeEditar = in_array($classeStatus, ['disponivel', 'manutencao'], true);
                                    $classeBadge = $classeStatus === 'ocupado'
                                        ? 'ocupado'
                                        : ($classeStatus === 'manutencao' ? 'manutencao' : ($classeStatus === 'limpeza' ? 'pendente' : 'confirmado'));
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="bolinha <?= htmlspecialchars($classeStatus) ?>"></span>
                                            <strong><?= htmlspecialchars($quarto['numero']) ?></strong>
                                        </div>
                                    </td>
                                    <td><span class="badge text-bg-warning"><?= htmlspecialchars(strtoupper($quarto['tipo'])) ?></span></td>
                                    <td><?= (int) $quarto['andar'] ?>º Andar</td>
                                    <td><span class="status <?= htmlspecialchars($classeBadge) ?>"><?= htmlspecialchars($quarto['status']) ?></span></td>
                                    <td><?= dinheiro($quarto['valor_diaria']) ?></td>
                                    <td class="text-end">
                                        <?php if ($podeEditar): ?>
                                            <a href="editar_quarto.php?id=<?= (int) $quarto['id_quarto'] ?>" class="btn btn-sm btn-link text-warning" title="Editar quarto">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-link text-muted" title="Apenas quartos disponíveis ou em manutenção podem ser editados" disabled>
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="row g-4">
            <div class="col-lg-6">
                <div class="cartao-admin p-4 h-100">
                    <h3 class="h5 mb-3">Alertas de Manutenção</h3>
                    <div class="alerta-manutencao vermelho mb-3">
                        <strong>Quarto com status de manutenção</strong>
                        <div class="small">Use a tabela acima para acompanhar quartos indisponíveis.</div>
                    </div>
                    <div class="alerta-manutencao azul">
                        <strong>Solicitações de limpeza</strong>
                        <div class="small">As solicitações aparecem no painel administrativo.</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="cartao-admin p-4 h-100">
                    <h3 class="h5 mb-3">Distribuição por Tipo de Quarto</h3>
                    <?php if (empty($tipos)): ?>
                        <div class="estado-vazio">Nenhum tipo de quarto cadastrado.</div>
                    <?php else: ?>
                        <div class="barra-tipo">
                            <?php foreach ($tipos as $tipo): ?>
                                <?php $altura = max(32, min(140, (int) $tipo['total'] * 28)); ?>
                                <div>
                                    <div class="barra" style="height: <?= $altura ?>px;"></div>
                                    <div class="small mt-2"><?= htmlspecialchars($tipo['tipo']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
