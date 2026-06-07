<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('admin');
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
    <?php require_once __DIR__ . '/../../include/menu_admin.php'; ?>

    <header class="topo-admin">
        <h1>Suporte</h1>
        <div class="usuario-topo">
            <span>Logado: <?= htmlspecialchars($_SESSION['nome'] ?? 'Administrador') ?></span>
            <a class="btn btn-sm btn-outline-dark" href="../../logout.php">Sair</a>
        </div>
    </header>

    <main class="conteudo-admin">
        <section class="row g-4">
            <div class="col-lg-4">
                <div class="cartao-admin p-4 h-100">
                    <i class="bi bi-database-check fs-3 text-warning"></i>
                    <h2 class="h5 fw-bold mt-3">Banco de Dados</h2>
                    <p class="text-muted mb-0">Verifique quartos, reservas e hóspedes caso algum dado não apareça.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="cartao-admin p-4 h-100">
                    <i class="bi bi-bell fs-3 text-warning"></i>
                    <h2 class="h5 fw-bold mt-3">Notificações</h2>
                    <p class="text-muted mb-0">Abra o sino para marcar notificações como visualizadas.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="cartao-admin p-4 h-100">
                    <i class="bi bi-cloud-sun fs-3 text-warning"></i>
                    <h2 class="h5 fw-bold mt-3">API Pública</h2>
                    <p class="text-muted mb-0">O clima usa Open-Meteo via JavaScript fetch.</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
