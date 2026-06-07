<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('admin');
require_once __DIR__ . '/../../include/conexao.php';
require_once __DIR__ . '/../../include/funcoes.php';

$mensagem = '';
$erroMensagem = '';
$andarFiltro = $_GET['andar'] ?? '';
$tipoFiltro = $_GET['tipo'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idSolicitacao = (int) ($_POST['id_solicitacao'] ?? 0);
    $novoStatus = $_POST['status'] ?? '';
    $statusPermitidos = ['Em andamento', 'Concluido', 'Pendente'];

    if ($idSolicitacao > 0 && in_array($novoStatus, $statusPermitidos, true)) {
        try {
            $stmt = $conexao->prepare(
                "UPDATE solicitacoes_limpeza
                 SET status = :status
                 WHERE id_solicitacao = :id_solicitacao"
            );
            $stmt->bindValue(':status', $novoStatus);
            $stmt->bindValue(':id_solicitacao', $idSolicitacao, PDO::PARAM_INT);
            $stmt->execute();

            header('Location: governanca.php?status=ok');
            exit;
        } catch (PDOException $erro) {
            $erroMensagem = 'Não foi possível atualizar a solicitação.';
        }
    }
}

try {
    $totalQuartos = (int) $conexao->query("SELECT COUNT(*) FROM quartos")->fetchColumn();
    $totalSujos = (int) $conexao->query("SELECT COUNT(*) FROM solicitacoes_limpeza WHERE LOWER(status) IN ('pendente', 'sujo', 'solicitado')")->fetchColumn();
    $emAndamento = (int) $conexao->query("SELECT COUNT(*) FROM solicitacoes_limpeza WHERE LOWER(status) IN ('em progresso', 'em andamento', 'limpando')")->fetchColumn();
    $limpos = (int) $conexao->query("SELECT COUNT(*) FROM solicitacoes_limpeza WHERE LOWER(status) IN ('concluido', 'concluído', 'finalizado', 'limpo')")->fetchColumn();

    $limitesAndar = $conexao->query("SELECT MIN(andar) AS menor, MAX(andar) AS maior FROM quartos WHERE andar BETWEEN 1 AND 12")->fetch(PDO::FETCH_ASSOC);
    if ($limitesAndar && $limitesAndar['menor'] !== null) {
        $andarInicial = (int) $limitesAndar['menor'];
        $andarFinal = min(12, (int) $limitesAndar['maior']);
        $andares = range($andarInicial, max($andarInicial, $andarFinal));
    } else {
        $andares = [];
    }
    $tiposQuarto = $conexao->query("SELECT DISTINCT tipo FROM quartos ORDER BY tipo ASC")->fetchAll(PDO::FETCH_COLUMN);

    $filtros = [];
    $parametros = [];

    if ($andarFiltro !== '') {
        $filtros[] = 'quartos.andar = :andar';
        $parametros[':andar'] = (int) $andarFiltro;
    }

    if ($tipoFiltro !== '') {
        $filtros[] = 'quartos.tipo = :tipo';
        $parametros[':tipo'] = $tipoFiltro;
    }

    $whereSolicitacoes = $filtros ? 'WHERE ' . implode(' AND ', $filtros) : '';

    $sqlSolicitacoes = "SELECT solicitacoes_limpeza.id_solicitacao, solicitacoes_limpeza.status,
                               solicitacoes_limpeza.observacao, solicitacoes_limpeza.data_solicitacao,
                               quartos.numero, quartos.andar, quartos.tipo,
                               funcionarios.nome AS funcionario
                        FROM solicitacoes_limpeza
                        INNER JOIN quartos ON quartos.id_quarto = solicitacoes_limpeza.id_quarto
                        LEFT JOIN funcionarios ON funcionarios.id_funcionario = solicitacoes_limpeza.id_funcionario
                        {$whereSolicitacoes}
                        ORDER BY solicitacoes_limpeza.data_solicitacao DESC";
    $stmtSolicitacoes = $conexao->prepare($sqlSolicitacoes);

    foreach ($parametros as $campo => $valor) {
        $tipoParametro = $campo === ':andar' ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmtSolicitacoes->bindValue($campo, $valor, $tipoParametro);
    }

    $stmtSolicitacoes->execute();
    $solicitacoes = $stmtSolicitacoes->fetchAll(PDO::FETCH_ASSOC);

    $sqlNotas = "SELECT solicitacoes_limpeza.observacao, solicitacoes_limpeza.data_solicitacao,
                        quartos.numero
                 FROM solicitacoes_limpeza
                 INNER JOIN quartos ON quartos.id_quarto = solicitacoes_limpeza.id_quarto
                 ORDER BY solicitacoes_limpeza.data_solicitacao DESC
                 LIMIT 4";
    $notas = $conexao->query($sqlNotas)->fetchAll(PDO::FETCH_ASSOC);

    $equipe = $conexao->query("SELECT nome, cargo, status FROM funcionarios ORDER BY nome ASC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $erro) {
    $totalQuartos = 0;
    $totalSujos = 0;
    $emAndamento = 0;
    $limpos = 0;
    $solicitacoes = [];
    $notas = [];
    $equipe = [];
    $andares = [];
    $tiposQuarto = [];
}

$totalSolicitacoes = $totalSujos + $emAndamento + $limpos;
$produtividade = $totalSolicitacoes > 0 ? round(($limpos / $totalSolicitacoes) * 100) : 0;

if (($_GET['status'] ?? '') === 'ok') {
    $mensagem = 'Status da solicitação atualizado.';
}

if (($_GET['funcionario'] ?? '') === 'ok') {
    $mensagem = 'Funcionário cadastrado com sucesso.';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Governança - LuxeStay PMS</title>
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
                    <input type="search" class="form-control" name="q" placeholder="Buscar quarto ou hóspede...">
                </div>
            </form>

            <div class="usuario-topo">
                <span>Logado: <?= htmlspecialchars($_SESSION['nome'] ?? 'Administrador') ?></span>
                <a class="btn btn-sm btn-outline-dark" href="../../logout.php">Sair</a>
            </div>
        </div>
    </header>

    <main class="conteudo-admin">
        <?php if ($mensagem): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <?php if ($erroMensagem): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erroMensagem) ?></div>
        <?php endif; ?>

        <section class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
            <div>
                <h2 class="h4 fw-bold mb-1">Governança & Limpeza</h2>
                <p class="text-muted mb-0">Gerencie solicitações de limpeza e serviço dos quartos ativos.</p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="governanca.php" class="btn btn-outline-warning px-4">
                    <i class="bi bi-arrow-clockwise me-2"></i>Atualizar Status
                </a>
            </div>
        </section>

        <section class="row g-4 mb-4">
            <div class="col-xl-8">
                <div class="cartao-admin p-4">
                    <div class="governanca-resumo">
                        <div>
                            <span class="mini-texto fw-bold">TOTAL SUJOS</span>
                            <strong class="d-block fs-2 text-danger"><?= $totalSujos ?></strong>
                        </div>
                        <div>
                            <span class="mini-texto fw-bold">EM PROGRESSO</span>
                            <strong class="d-block fs-2 text-warning"><?= $emAndamento ?></strong>
                        </div>
                        <div>
                            <span class="mini-texto fw-bold">LIMPOS</span>
                            <strong class="d-block fs-2 text-primary"><?= $limpos ?></strong>
                        </div>
                        <div>
                            <span class="mini-texto fw-bold">PRODUTIVIDADE HOJE</span>
                            <div class="progresso mt-2"><span style="width: <?= $produtividade ?>%;"></span></div>
                            <strong class="d-block mt-2"><?= $produtividade ?>%</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <form class="cartao-admin p-4" method="GET">
                    <h3 class="h6 fw-bold mb-3"><i class="bi bi-funnel me-2"></i>Filtros Rápidos</h3>
                    <select class="form-select mb-3" name="andar" onchange="this.form.submit()">
                        <option value="">Todos os Andares</option>
                        <?php foreach ($andares as $andar): ?>
                            <option value="<?= (int) $andar ?>" <?= (string) $andarFiltro === (string) $andar ? 'selected' : '' ?>>
                                <?= (int) $andar ?>º Andar
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="form-select" name="tipo" onchange="this.form.submit()">
                        <option value="">Todos os Tipos</option>
                        <?php foreach ($tiposQuarto as $tipo): ?>
                            <option value="<?= htmlspecialchars($tipo) ?>" <?= $tipoFiltro === $tipo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tipo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </section>

        <section class="row g-4 mb-4">
            <?php if (empty($solicitacoes)): ?>
                <div class="col-12">
                    <div class="estado-vazio">
                        Nenhuma solicitação de limpeza ou serviço de quarto registrada.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($solicitacoes as $solicitacao): ?>
                    <?php
                        $status = strtolower($solicitacao['status']);
                        $classe = str_contains($status, 'andamento') || str_contains($status, 'progresso') || str_contains($status, 'limpando')
                            ? 'andamento'
                            : (str_contains($status, 'concl') || str_contains($status, 'limpo') ? 'limpo' : 'sujo');
                        $botao = $classe === 'andamento' ? 'Finalizar' : ($classe === 'limpo' ? 'Reverter Status' : 'Iniciar');
                        $proximoStatus = $classe === 'andamento' ? 'Concluido' : ($classe === 'limpo' ? 'Pendente' : 'Em andamento');
                        $observacao = $solicitacao['observacao'] ?: 'Solicitação feita pelo hóspede.';
                        $tipoSolicitacao = str_contains(strtolower($observacao), 'serviço de quarto') ? 'Serviço de Quarto' : 'Limpeza';
                    ?>
                    <div class="col-sm-6 col-xl-3">
                        <article class="cartao-admin governanca-card <?= htmlspecialchars($classe) ?> p-4">
                            <div class="d-flex justify-content-between gap-2 mb-3">
                                <div>
                                    <span class="mini-texto"><?= (int) $solicitacao['andar'] ?>º Andar</span>
                                    <div class="quarto-numero">Quarto <?= htmlspecialchars($solicitacao['numero']) ?></div>
                                    <div class="mini-texto"><?= htmlspecialchars($solicitacao['tipo']) ?></div>
                                </div>
                                <span class="servico-badge <?= htmlspecialchars($classe) ?>">
                                    <?= htmlspecialchars($tipoSolicitacao) ?>
                                </span>
                            </div>

                            <p class="small mb-3">
                                <i class="bi bi-clock me-2"></i><?= htmlspecialchars(dataBrasil($solicitacao['data_solicitacao'])) ?> às <?= date('H:i', strtotime($solicitacao['data_solicitacao'])) ?>
                            </p>

                            <p class="small text-muted">
                                <span class="status <?= htmlspecialchars($classe === 'sujo' ? 'ocupado' : ($classe === 'andamento' ? 'pendente' : 'confirmado')) ?>">
                                    <?= htmlspecialchars($solicitacao['status']) ?>
                                </span>
                                <span class="d-block mt-2"><?= htmlspecialchars($observacao) ?></span>
                            </p>

                            <?php if (!empty($solicitacao['funcionario'])): ?>
                                <p class="small mb-3"><i class="bi bi-person me-2"></i>Resp: <?= htmlspecialchars($solicitacao['funcionario']) ?></p>
                            <?php endif; ?>

                            <form method="POST">
                                <input type="hidden" name="id_solicitacao" value="<?= (int) $solicitacao['id_solicitacao'] ?>">
                                <input type="hidden" name="status" value="<?= htmlspecialchars($proximoStatus) ?>">
                                <button class="btn btn-principal btn-sm w-100" type="submit"><?= $botao ?></button>
                            </form>
                        </article>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="row g-4">
            <div class="col-xl-8">
                <div class="cartao-admin p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 m-0">Notas de Governança Recentes</h3>
                        <a href="#" class="small text-warning text-decoration-none fw-bold">Ver Todas</a>
                    </div>

                    <?php if (empty($notas)): ?>
                        <div class="estado-vazio">Nenhuma nota registrada.</div>
                    <?php else: ?>
                        <?php foreach ($notas as $nota): ?>
                            <div class="nota-governanca">
                                <div class="nota-icone"><i class="bi bi-brush"></i></div>
                                <div class="flex-grow-1">
                                    <strong>Quarto <?= htmlspecialchars($nota['numero']) ?></strong>
                                    <div class="text-muted small"><?= htmlspecialchars($nota['observacao'] ?: 'Solicitação feita pelo hóspede.') ?></div>
                                </div>
                                <span class="mini-texto"><?= date('H:i', strtotime($nota['data_solicitacao'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="cartao-admin p-4 bg-dark text-white h-100">
                    <h3 class="h5 mb-2">Escala da Equipe</h3>
                    <p class="small text-white-50">Equipe ativa no turno atual.</p>

                    <?php if (empty($equipe)): ?>
                        <p class="text-white-50 mb-4">Nenhum funcionário cadastrado.</p>
                    <?php else: ?>
                        <?php foreach ($equipe as $funcionario): ?>
                            <div class="d-flex align-items-center justify-content-between border-bottom border-secondary py-2">
                                <div>
                                    <strong><?= htmlspecialchars($funcionario['nome']) ?></strong>
                                    <div class="small text-white-50"><?= htmlspecialchars($funcionario['cargo']) ?></div>
                                </div>
                                <span class="bolinha <?= strtolower($funcionario['status']) === 'ativo' ? 'disponivel' : 'limpeza' ?>"></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <a href="cadastrar_funcionario.php" class="btn btn-light w-100 mt-4">
                        <i class="bi bi-person-plus me-2"></i>Gerenciar Equipe
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
