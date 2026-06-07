<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('cliente');
require_once __DIR__ . '/../../include/conexao.php';
require_once __DIR__ . '/../../include/funcoes.php';
atualizarReservasVencidas($conexao);

$busca = trim($_GET['busca'] ?? '');

try {
    $sql = "SELECT id_quarto, numero, andar, tipo, valor_diaria, status
            FROM quartos
            WHERE LOWER(status) IN ('disponivel', 'disponível', 'livre')";

    if ($busca !== '') {
        $sql .= " AND (numero LIKE :busca OR tipo LIKE :busca OR CAST(andar AS CHAR) LIKE :busca)";
    }

    $sql .= " ORDER BY valor_diaria ASC, numero ASC";
    $stmt = $conexao->prepare($sql);

    if ($busca !== '') {
        $stmt->bindValue(':busca', '%' . $busca . '%');
    }

    $stmt->execute();
    $quartosDisponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $erro) {
    $quartosDisponiveis = [];
}

$primeiroQuarto = $quartosDisponiveis[0] ?? null;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas - Cliente</title>
    <link rel="icon" type="image/png" href="/LuxeStay/assets/img/Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../../include/menu_cliente.php'; ?>

    <header class="topo-cliente">
        <form class="campo-busca w-100" method="GET">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar quartos disponíveis...">
            </div>
        </form>

        <div class="d-flex align-items-center justify-content-between gap-3">
            <span class="small text-muted">
                Logado: <?= htmlspecialchars($_SESSION['nome'] ?? 'Cliente') ?>
            </span>
            <a class="btn btn-sm btn-outline-dark" href="../../logout.php">Sair</a>
        </div>
    </header>

    <main class="conteudo-cliente">
        <?php if (($_GET['reserva'] ?? '') === 'ok'): ?>
            <div class="alert alert-success">
                Reserva criada com sucesso!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['erro'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_GET['erro']) ?>
            </div>
        <?php endif; ?>

        <form class="filtro-reserva p-3 mb-4" id="form-reserva">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="checkin" class="form-label small fw-bold">Check-in</label>
                    <input type="text" class="form-control" id="checkin" value="<?= date('d/m/Y') ?>" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric">
                    <div class="invalid-feedback">Informe uma data válida no formato dd/mm/aaaa.</div>
                </div>

                <div class="col-md-3">
                    <label for="checkout" class="form-label small fw-bold">Check-out</label>
                    <input type="text" class="form-control" id="checkout" value="<?= date('d/m/Y', strtotime('+1 day')) ?>" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric">
                    <div class="invalid-feedback">O check-out precisa ser depois do check-in.</div>
                </div>

                <div class="col-md-3">
                    <label for="hospedes" class="form-label small fw-bold">Hóspedes</label>
                    <select class="form-select" id="hospedes">
                        <option value="1">1 Pessoa</option>
                        <option value="2" selected>2 Pessoas</option>
                        <option value="3">3 Pessoas</option>
                        <option value="4">4 Pessoas</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-principal w-100 py-2">Selecionar</button>
                </div>
            </div>
        </form>

        <div class="row g-4">
            <section class="col-xl-8">
                <h1 class="fw-bold mb-4">Tipos de Quartos Disponíveis</h1>
                <?php if ($busca !== ''): ?>
                    <p class="text-muted">Resultado para: <strong><?= htmlspecialchars($busca) ?></strong></p>
                <?php endif; ?>

                <?php if (empty($quartosDisponiveis)): ?>
                    <div class="estado-vazio">
                        Nenhum quarto disponível foi encontrado. Cadastre quartos no painel administrativo para eles aparecerem aqui.
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($quartosDisponiveis as $indice => $quarto): ?>
                            <?php
                                $imagem = imagemQuarto($quarto['tipo']);
                                $detalhes = detalhesQuarto($quarto);
                            ?>
                            <div class="col-md-6">
                                <article
                                    class="card-quarto <?= $indice === 0 ? 'selecionado' : '' ?>"
                                    data-id="<?= (int) $quarto['id_quarto'] ?>"
                                    data-nome="<?= htmlspecialchars($quarto['tipo']) ?>"
                                    data-preco="<?= htmlspecialchars((float) $quarto['valor_diaria']) ?>"
                                    data-img="<?= htmlspecialchars($imagem) ?>"
                                    data-detalhes="<?= htmlspecialchars($detalhes) ?>"
                                >
                                    <img src="<?= htmlspecialchars($imagem) ?>" alt="<?= htmlspecialchars($quarto['tipo']) ?>">

                                    <div class="p-4">
                                        <div class="card-topo mb-2">
                                            <h3><?= htmlspecialchars($quarto['tipo']) ?></h3>
                                            <div class="preco">
                                                <strong><?= dinheiro($quarto['valor_diaria']) ?></strong>
                                                <span>por noite</span>
                                            </div>
                                        </div>

                                        <p class="text-muted mb-3"><?= htmlspecialchars($detalhes) ?></p>

                                        <div class="tags mb-3">
                                            <span><i class="bi bi-door-open"></i> Quarto <?= htmlspecialchars($quarto['numero']) ?></span>
                                            <span><i class="bi bi-building"></i> <?= (int) $quarto['andar'] ?>º andar</span>
                                            <span><i class="bi bi-check-circle"></i> <?= htmlspecialchars($quarto['status']) ?></span>
                                        </div>

                                        <p class="text-muted"><?= htmlspecialchars(descricaoQuarto($quarto['tipo'])) ?></p>
                                        <button type="button" class="btn btn-contorno w-100 selecionar-quarto">Selecionar Quarto</button>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="col-xl-4">
                <div class="resumo-reserva mb-4">
                    <div class="resumo-cabecalho">
                        <h2 class="h4 m-0 fw-bold">Resumo da Reserva</h2>
                    </div>

                    <div class="p-4">
                        <?php if ($primeiroQuarto): ?>
                            <div class="d-flex gap-3 mb-4">
                                <img class="resumo-img" id="resumo-img" src="<?= htmlspecialchars(imagemQuarto($primeiroQuarto['tipo'])) ?>" alt="Quarto selecionado">
                                <div>
                                    <h3 class="h6 fw-bold mb-1" id="resumo-nome"><?= htmlspecialchars($primeiroQuarto['tipo']) ?></h3>
                                    <p class="small text-muted mb-0" id="resumo-detalhes"><?= htmlspecialchars(detalhesQuarto($primeiroQuarto)) ?></p>
                                    <p class="small text-muted mb-0" id="resumo-periodo">1 noite, 2 hóspedes</p>
                                </div>
                            </div>

                            <div class="border-top border-bottom py-3 mb-3">
                                <div class="linha-valor mb-2">
                                    <span id="linha-diarias"><?= dinheiro($primeiroQuarto['valor_diaria']) ?> x 1 noite</span>
                                    <strong id="subtotal"><?= dinheiro($primeiroQuarto['valor_diaria']) ?></strong>
                                </div>
                                <div class="linha-valor mb-2">
                                    <span>Taxas e impostos (12%)</span>
                                    <strong id="impostos"><?= dinheiro($primeiroQuarto['valor_diaria'] * 0.12) ?></strong>
                                </div>
                                <div class="linha-valor">
                                    <span>Taxa de Serviço</span>
                                    <strong id="servico"><?= dinheiro(45) ?></strong>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <h3 class="h6 fw-bold m-0">Valor Total</h3>
                                <strong class="total-reserva" id="total"><?= dinheiro($primeiroQuarto['valor_diaria'] * 1.12 + 45) ?></strong>
                            </div>

                            <form action="criar_reserva.php" method="POST" id="confirmar-reserva-form">
                                <input type="hidden" name="id_quarto" id="reserva-id-quarto" value="<?= (int) $primeiroQuarto['id_quarto'] ?>">
                                <input type="hidden" name="data_entrada" id="reserva-data-entrada" value="<?= date('d/m/Y') ?>">
                                <input type="hidden" name="data_saida" id="reserva-data-saida" value="<?= date('d/m/Y', strtotime('+1 day')) ?>">

                                <button type="submit" class="btn btn-principal w-100 py-3" id="confirmar-reserva">
                                    Confirmar Reserva
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="estado-vazio">
                                Não há quartos disponíveis para montar uma reserva.
                            </div>
                        <?php endif; ?>

                        <div class="bg-light rounded-3 p-3 mt-4">
                            <h4 class="h6 fw-bold mb-1"><i class="bi bi-shield-check text-warning"></i> Melhor Tarifa Garantida</h4>
                            <p class="small text-muted mb-0">Cancelamento flexível disponível até 48 horas antes da chegada.</p>
                        </div>
                    </div>
                </div>

                <div class="ajuda-reserva p-4">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <h3 class="h6 fw-bold mb-1">Precisa de ajuda?</h3>
                            <p class="small text-muted mb-0">Ligue para nosso concierge 24/7.</p>
                        </div>
                        <a class="btn btn-sm btn-outline-warning fw-bold" href="#">Contato</a>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <script src="../../assets/js/script.js?v=2"></script>
</body>
</html>
