<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('cliente');

$busca = trim($_GET['busca'] ?? '');
$termo = strtolower($busca);
$resultados = [];

if ($busca !== '') {
    if (str_contains($termo, 'gastronomia') || str_contains($termo, 'comida') || str_contains($termo, 'café') || str_contains($termo, 'serviço')) {
        $resultados[] = ['titulo' => 'Gastronomia', 'descricao' => 'Pedir serviço de quarto e acompanhar pedidos.', 'link' => 'gastronomia.php'];
    }

    if (str_contains($termo, 'reserva') || str_contains($termo, 'quarto') || str_contains($termo, 'diária')) {
        $resultados[] = ['titulo' => 'Reservas', 'descricao' => 'Buscar quartos disponíveis para reservar.', 'link' => 'reservas.php?busca=' . urlencode($busca)];
    }

    if (str_contains($termo, 'limpeza')) {
        $resultados[] = ['titulo' => 'Solicitar Limpeza', 'descricao' => 'Voltar ao painel para solicitar limpeza do quarto atual.', 'link' => 'painel.php#servicos'];
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
    <?php require_once __DIR__ . '/../../include/menu_cliente.php'; ?>

    <header class="topo-cliente">
        <form class="campo-busca w-100" method="GET">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar serviços...">
            </div>
        </form>
    </header>

    <main class="conteudo-cliente">
        <section class="mb-4">
            <h1 class="display-6 fw-bold">Busca</h1>
            <p class="text-muted mb-0">Encontre serviços disponíveis para o hóspede.</p>
        </section>

        <?php if ($busca === ''): ?>
            <div class="estado-vazio">Digite algo para buscar.</div>
        <?php elseif (empty($resultados)): ?>
            <div class="estado-vazio">Nenhum serviço encontrado para “<?= htmlspecialchars($busca) ?>”.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($resultados as $resultado): ?>
                    <div class="col-md-4">
                        <a href="<?= htmlspecialchars($resultado['link']) ?>" class="cartao-admin p-4 d-block text-decoration-none text-reset h-100">
                            <h2 class="h5 fw-bold"><?= htmlspecialchars($resultado['titulo']) ?></h2>
                            <p class="text-muted mb-0"><?= htmlspecialchars($resultado['descricao']) ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
