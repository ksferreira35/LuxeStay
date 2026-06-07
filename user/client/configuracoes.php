<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('cliente');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - LuxeStay PMS</title>
    <link rel="icon" type="image/png" href="/LuxeStay/assets/img/Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../../include/menu_cliente.php'; ?>

    <header class="topo-cliente">
        <h1 class="h4 fw-bold m-0">Configurações</h1>
        <div class="usuario-topo">
            <span>Logado: <?= htmlspecialchars($_SESSION['nome'] ?? 'Cliente') ?></span>
            <a class="btn btn-sm btn-outline-dark" href="../../logout.php">Sair</a>
        </div>
    </header>

    <main class="conteudo-cliente">
        <section class="row g-4">
            <div class="col-lg-6">
                <div class="cartao-admin p-4 h-100">
                    <h2 class="h5 fw-bold">Preferências</h2>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input config-toggle" type="checkbox" id="avisos" data-config-chave="luxestay_notificacoes_ativas" checked>
                        <label class="form-check-label" for="avisos">Receber notificações da estadia</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input config-toggle" type="checkbox" id="clima" data-config-chave="luxestay_clima_ativo" checked>
                        <label class="form-check-label" for="clima">Mostrar clima na página inicial</label>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="cartao-admin p-4 h-100">
                    <h2 class="h5 fw-bold">Minha Conta</h2>
                    <p class="mb-2"><strong>Nome:</strong> <?= htmlspecialchars($_SESSION['nome'] ?? 'Cliente') ?></p>
                    <p class="mb-0"><strong>Tipo:</strong> Hóspede</p>
                </div>
            </div>
        </section>
    </main>
    <script src="../../assets/js/script.js?v=9"></script>
</body>
</html>
