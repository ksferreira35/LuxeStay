<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('cliente');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suporte - LuxeStay PMS</title>
    <link rel="icon" type="image/png" href="/LuxeStay/assets/img/Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../../include/menu_cliente.php'; ?>

    <header class="topo-cliente">
        <h1 class="h4 fw-bold m-0">Suporte</h1>
        <div class="usuario-topo">
            <span>Logado: <?= htmlspecialchars($_SESSION['nome'] ?? 'Cliente') ?></span>
            <a class="btn btn-sm btn-outline-dark" href="../../logout.php">Sair</a>
        </div>
    </header>

    <main class="conteudo-cliente">
        <section class="row g-4">
            <div class="col-lg-4">
                <div class="cartao-admin p-4 h-100">
                    <i class="bi bi-cup-hot fs-3 text-warning"></i>
                    <h2 class="h5 fw-bold mt-3">Gastronomia</h2>
                    <p class="text-muted mb-0">Use a tela de Gastronomia para montar sua sacola e pedir serviço de quarto.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="cartao-admin p-4 h-100">
                    <i class="bi bi-brush fs-3 text-warning"></i>
                    <h2 class="h5 fw-bold mt-3">Limpeza</h2>
                    <p class="text-muted mb-0">Solicite limpeza no painel e confirme quando o atendimento terminar.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="cartao-admin p-4 h-100">
                    <i class="bi bi-bell fs-3 text-warning"></i>
                    <h2 class="h5 fw-bold mt-3">Notificações</h2>
                    <p class="text-muted mb-0">Acompanhe preparo, limpeza e conclusão pelo sino no topo.</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
