<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('admin');
require_once __DIR__ . '/../../include/conexao.php';
require_once __DIR__ . '/../../include/funcoes.php';
atualizarReservasVencidas($conexao);

$busca = trim($_GET['q'] ?? '');
$hospedes = [];
$reservas = [];

if ($busca !== '') {
    $termo = '%' . $busca . '%';

    try {
        $stmt = $conexao->prepare(
            "SELECT nome, email, telefone, cpf
             FROM hospedes
             WHERE nome LIKE :busca OR email LIKE :busca OR telefone LIKE :busca OR cpf LIKE :busca
             ORDER BY nome ASC
             LIMIT 8"
        );
        $stmt->bindValue(':busca', $termo);
        $stmt->execute();
        $hospedes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conexao->prepare(
            "SELECT reservas.id_reserva, hospedes.nome AS hospede, quartos.numero, quartos.tipo,
                    reservas.data_entrada, reservas.data_saida, reservas.status, reservas.valor_total
             FROM reservas
             INNER JOIN hospedes ON hospedes.id_hospede = reservas.id_hospede
             INNER JOIN quartos ON quartos.id_quarto = reservas.id_quarto
             WHERE hospedes.nome LIKE :busca OR quartos.numero LIKE :busca OR quartos.tipo LIKE :busca OR reservas.status LIKE :busca
             ORDER BY reservas.id_reserva DESC
             LIMIT 10"
        );
        $stmt->bindValue(':busca', $termo);
        $stmt->execute();
        $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $erro) {
        $hospedes = [];
        $reservas = [];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Busca - LuxeStay PMS</title>
    <link rel="icon" type="image/png" href="/LuxeStay/assets/img/Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../../include/menu_admin.php'; ?>

    <header class="topo-admin">
        <h1>Busca</h1>
        <form class="busca-admin" method="GET">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" name="q" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar hóspedes ou reservas">
            </div>
        </form>
    </header>

    <main class="conteudo-admin">
        <section class="mb-4">
            <h2 class="display-6 fw-bold">Resultados da Busca</h2>
            <p class="text-muted mb-0">Pesquise por hóspede, status ou reserva.</p>
        </section>

        <?php if ($busca === ''): ?>
            <div class="estado-vazio">Digite algo para buscar.</div>
        <?php else: ?>
            <section class="row g-4">
                <div class="col-xl-6">
                    <div class="cartao-admin p-4 h-100">
                        <h3 class="h5 fw-bold mb-3">Hóspedes</h3>
                        <?php if (empty($hospedes)): ?>
                            <p class="text-muted mb-0">Nenhum hóspede encontrado.</p>
                        <?php else: ?>
                            <?php foreach ($hospedes as $hospede): ?>
                                <div class="notificacao-item">
                                    <strong><?= htmlspecialchars($hospede['nome']) ?></strong>
                                    <div class="mini-texto"><?= htmlspecialchars($hospede['email'] ?? '') ?> · <?= htmlspecialchars($hospede['telefone'] ?? '') ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="cartao-admin p-4 h-100">
                        <h3 class="h5 fw-bold mb-3">Reservas</h3>
                        <?php if (empty($reservas)): ?>
                            <p class="text-muted mb-0">Nenhuma reserva encontrada.</p>
                        <?php else: ?>
                            <?php foreach ($reservas as $reserva): ?>
                                <div class="notificacao-item">
                                    <strong>#<?= (int) $reserva['id_reserva'] ?> · <?= htmlspecialchars($reserva['hospede']) ?></strong>
                                    <div class="mini-texto">Quarto <?= htmlspecialchars($reserva['numero']) ?> · <?= dataBrasil($reserva['data_entrada']) ?> até <?= dataBrasil($reserva['data_saida']) ?></div>
                                    <span class="status <?= htmlspecialchars(statusReservaClasse($reserva['status'])) ?>"><?= htmlspecialchars($reserva['status']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
