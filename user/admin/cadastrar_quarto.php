<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('admin');
require_once __DIR__ . '/../../include/conexao.php';
require_once __DIR__ . '/../../include/funcoes.php';

$mensagemErro = '';
$valores = [
    'numero' => '',
    'andar' => '',
    'tipo' => '',
    'valor_diaria' => '',
    'status' => 'Disponível',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valores['numero'] = trim($_POST['numero'] ?? '');
    $valores['andar'] = trim($_POST['andar'] ?? '');
    $valores['tipo'] = trim($_POST['tipo'] ?? '');
    $valores['valor_diaria'] = trim($_POST['valor_diaria'] ?? '');
    $valores['status'] = trim($_POST['status'] ?? 'Disponível');

    $andar = (int) $valores['andar'];

    if (
        $valores['numero'] === '' ||
        $valores['andar'] === '' ||
        $valores['tipo'] === '' ||
        $valores['valor_diaria'] === '' ||
        $valores['status'] === ''
    ) {
        $mensagemErro = 'Preencha todos os campos.';
    } elseif ($andar < 1 || $andar > 12) {
        $mensagemErro = 'O LuxeStay permite cadastrar quartos somente do 1º ao 12º andar.';
    } else {
        try {
            $totalQuartos = (int) $conexao->query("SELECT COUNT(*) FROM quartos")->fetchColumn();

            if ($totalQuartos >= 101) {
                $mensagemErro = 'Limite de 101 quartos atingido.';
            } else {
                $sql = "INSERT INTO quartos (numero, andar, tipo, valor_diaria, status)
                        VALUES (:numero, :andar, :tipo, :valor_diaria, :status)";
                $stmt = $conexao->prepare($sql);
                $stmt->bindValue(':numero', $valores['numero']);
                $stmt->bindValue(':andar', $andar, PDO::PARAM_INT);
                $stmt->bindValue(':tipo', $valores['tipo']);
                $stmt->bindValue(':valor_diaria', normalizarDinheiro($valores['valor_diaria']));
                $stmt->bindValue(':status', $valores['status']);
                $stmt->execute();

                header('Location: matriz_quartos.php');
                exit;
            }
        } catch (PDOException $erro) {
            if ($erro->getCode() === '23000') {
                $mensagemErro = 'Já existe um quarto cadastrado com esse número.';
            } else {
                $mensagemErro = 'Não foi possível cadastrar o quarto.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Quarto - LuxeStay PMS</title>
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
                <div class="small text-muted mb-2">Gestão / <strong class="text-warning">Cadastrar Quarto</strong></div>
                <h2 class="display-6 fw-bold mb-1">Adicionar Novo Quarto</h2>
                <p class="text-muted mb-0">Cadastre um quarto para ele aparecer na matriz e nas reservas do cliente.</p>
            </div>

            <a href="matriz_quartos.php" class="btn btn-outline-dark px-4 py-3">
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
                        <label for="numero" class="form-label">NÚMERO DO QUARTO</label>
                        <input type="text" class="form-control" id="numero" name="numero" value="<?= htmlspecialchars($valores['numero']) ?>" placeholder="Ex: 101" required>
                    </div>

                    <div class="col-md-6">
                        <label for="andar" class="form-label">ANDAR</label>
                        <input type="number" class="form-control" id="andar" name="andar" value="<?= htmlspecialchars($valores['andar']) ?>" min="1" max="12" placeholder="Ex: 1" required>
                    </div>

                    <div class="col-md-6">
                        <label for="tipo" class="form-label">TIPO</label>
                        <input type="text" class="form-control" id="tipo" name="tipo" value="<?= htmlspecialchars($valores['tipo']) ?>" placeholder="Ex: Suíte, Luxo, Standard" required>
                    </div>

                    <div class="col-md-6">
                        <label for="valor_diaria" class="form-label">VALOR DA DIÁRIA</label>
                        <input type="text" class="form-control" id="valor_diaria" name="valor_diaria" value="<?= htmlspecialchars($valores['valor_diaria']) ?>" placeholder="Ex: 450.00" required>
                    </div>

                    <div class="col-md-12">
                        <label for="status" class="form-label">STATUS</label>
                        <select class="form-select" id="status" name="status" required>
                            <?php foreach (['Disponível', 'Ocupado', 'Limpeza', 'Manutenção'] as $status): ?>
                                <option value="<?= $status ?>" <?= $valores['status'] === $status ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                    <a href="matriz_quartos.php" class="btn btn-light px-4">Cancelar</a>
                    <button type="submit" class="btn btn-principal px-4">
                        <i class="bi bi-check-circle me-2"></i>Cadastrar Quarto
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
