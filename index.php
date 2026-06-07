<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LuxeStay PMS</title>
    <link rel="icon" type="image/png" href="/LuxeStay/assets/img/Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <main class="login-area">
        <form class="login-card" action="login.php" method="POST">
            <div class="login-marca">LS</div>

            <h1>LuxeStay PMS</h1>
            <p class="subtitulo mb-4">Suíte de Gestão de Propriedades</p>

            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-danger py-2 small">
                    E-mail ou senha inválidos.
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="email" class="form-label">ENDEREÇO DE E-MAIL</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" placeholder="admin@luxestay.com" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="senha" class="form-label">SENHA</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="••••••••••••" required>
                    <button class="input-group-text bg-white border-start-0" type="button" id="toggle-senha" aria-label="Mostrar senha">
                        <i class="bi bi-eye" id="icone-senha"></i>
                    </button>
                </div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="lembrar" name="lembrar">
                <label class="form-check-label fw-normal" for="lembrar">Lembrar-me</label>
            </div>

            <button type="submit" class="btn btn-principal w-100 py-3">
                Entrar <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="login-status px-2">
            <span>Sistema Operacional</span>
            <span>v2.4.0-Stable</span>
        </div>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>
