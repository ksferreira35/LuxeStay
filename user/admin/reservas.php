<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('admin');
require_once __DIR__ . '/../../include/conexao.php';
require_once __DIR__ . '/../../include/funcoes.php';
atualizarReservasVencidas($conexao);

try {
    $sqlReservas = "SELECT reservas.id_reserva, hospedes.nome AS hospede, hospedes.email,
                           quartos.numero, quartos.tipo, reservas.data_entrada,
                           reservas.data_saida, reservas.status, reservas.valor_total
                    FROM reservas
                    INNER JOIN hospedes ON hospedes.id_hospede = reservas.id_hospede
                    INNER JOIN quartos ON quartos.id_quarto = reservas.id_quarto
                    ORDER BY reservas.data_entrada DESC, reservas.id_reserva DESC";
    $reservas = $conexao->query($sqlReservas)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $erro) {
    $reservas = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas - LuxeStay PMS</title>
    <link rel="icon" type="image/png" href="/LuxeStay/assets/img/Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../../include/menu_admin.php'; ?>

    <header class="topo-admin">
        <h1>LuxeStay PMS</h1>

        <div class="usuario-topo">
            <span>Logado: <?= htmlspecialchars($_SESSION['nome'] ?? 'Administrador') ?></span>
            <a class="btn btn-sm btn-outline-dark" href="../../logout.php">Sair</a>
        </div>
    </header>

    <main class="conteudo-admin">
        <section class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
            <div>
                <div class="small text-muted mb-2">Gestão / <strong class="text-warning">Reservas</strong></div>
                <h2 class="display-6 fw-bold mb-1">Todas as Reservas</h2>
                <p class="text-muted mb-0">Acompanhe as reservas criadas pelos clientes.</p>
            </div>

            <a href="painel.php" class="btn btn-outline-dark px-4 py-3">
                <i class="bi bi-arrow-left me-2"></i>Voltar ao Painel
            </a>
        </section>

        <section class="cartao-admin overflow-hidden">
            <div class="d-flex align-items-center justify-content-between p-4">
                <h3 class="h5 m-0">Reservas Cadastradas</h3>
                <span class="mini-texto"><?= count($reservas) ?> reserva(s)</span>
            </div>

            <div class="table-responsive">
                <table class="table tabela-reservas m-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Hóspede</th>
                            <th>Quarto</th>
                            <th>Entrada</th>
                            <th>Saída</th>
                            <th>Status</th>
                            <th class="text-end">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservas)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Nenhuma reserva cadastrada no banco.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reservas as $indice => $reserva): ?>
                                <?php $classeAvatar = $indice % 3 === 1 ? 'amarelo' : ($indice % 3 === 2 ? 'escuro' : ''); ?>
                                <tr>
                                    <td class="fw-bold">#<?= htmlspecialchars($reserva['id_reserva']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="avatar-letra <?= $classeAvatar ?>"><?= htmlspecialchars(iniciaisNome($reserva['hospede'])) ?></span>
                                            <div>
                                                <strong><?= htmlspecialchars($reserva['hospede']) ?></strong>
                                                <div class="mini-texto"><?= htmlspecialchars($reserva['email'] ?? '') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($reserva['tipo']) ?> <?= htmlspecialchars($reserva['numero']) ?></td>
                                    <td><?= htmlspecialchars(dataBrasil($reserva['data_entrada'])) ?></td>
                                    <td><?= htmlspecialchars(dataBrasil($reserva['data_saida'])) ?></td>
                                    <td>
                                        <span class="status <?= htmlspecialchars(statusReservaClasse($reserva['status'])) ?>">
                                            <?= htmlspecialchars($reserva['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold"><?= dinheiro($reserva['valor_total']) ?></td>
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
