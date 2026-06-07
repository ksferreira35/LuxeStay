<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('admin');
require_once __DIR__ . '/../../include/conexao.php';

$mensagemErro = '';
$valores = [
    'nome' => '',
    'cargo' => '',
    'telefone' => '',
    'email' => '',
    'status' => 'Ativo',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valores['nome'] = trim($_POST['nome'] ?? '');
    $valores['cargo'] = trim($_POST['cargo'] ?? '');
    $valores['telefone'] = trim($_POST['telefone'] ?? '');
    $valores['email'] = trim($_POST['email'] ?? '');
    $valores['status'] = trim($_POST['status'] ?? 'Ativo');

    if ($valores['nome'] === '' || $valores['cargo'] === '' || $valores['status'] === '') {
        $mensagemErro = 'Preencha nome, cargo e status.';
    } else {
        try {
            $sql = "INSERT INTO funcionarios (nome, cargo, telefone, email, status)
                    VALUES (:nome, :cargo, :telefone, :email, :status)";
            $stmt = $conexao->prepare($sql);
            $stmt->bindValue(':nome', $valores['nome']);
            $stmt->bindValue(':cargo', $valores['cargo']);
            $stmt->bindValue(':telefone', $valores['telefone']);
            $stmt->bindValue(':email', $valores['email']);
            $stmt->bindValue(':status', $valores['status']);
            $stmt->execute();

            header('Location: governanca.php?funcionario=ok');
            exit;
        } catch (PDOException $erro) {
            $mensagemErro = 'Não foi possível cadastrar o funcionário.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Funcionário - LuxeStay PMS</title>
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
                <div class="small text-muted mb-2">Governança / <strong class="text-warning">Equipe</strong></div>
                <h2 class="display-6 fw-bold mb-1">Adicionar Funcionário</h2>
                <p class="text-muted mb-0">Cadastre colaboradores para aparecerem na escala da Governança.</p>
            </div>

            <a href="governanca.php" class="btn btn-outline-dark px-4 py-3">
                <i class="bi bi-arrow-left me-2"></i>Voltar
            </a>
        </section>

        <section class="cartao-admin formulario-admin p-4">
            <?php if ($mensagemErro): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($mensagemErro) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome" class="form-label">NOME</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($valores['nome']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="cargo" class="form-label">CARGO</label>
                        <input type="text" class="form-control" id="cargo" name="cargo" value="<?= htmlspecialchars($valores['cargo']) ?>" placeholder="Ex: Camareira, Governança" required>
                    </div>

                    <div class="col-md-6">
                        <label for="telefone" class="form-label">TELEFONE</label>
                        <input type="text" class="form-control" id="telefone" name="telefone" value="<?= htmlspecialchars($valores['telefone']) ?>" placeholder="Ex: (11) 99999-9999">
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">E-MAIL</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($valores['email']) ?>" placeholder="nome@luxestay.com">
                    </div>

                    <div class="col-md-12">
                        <label for="status" class="form-label">STATUS</label>
                        <select class="form-select" id="status" name="status" required>
                            <?php foreach (['Ativo', 'Em atendimento', 'Folga', 'Inativo'] as $status): ?>
                                <option value="<?= $status ?>" <?= $valores['status'] === $status ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                    <a href="governanca.php" class="btn btn-light px-4">Cancelar</a>
                    <button type="submit" class="btn btn-principal px-4">
                        <i class="bi bi-person-plus me-2"></i>Cadastrar Funcionário
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
