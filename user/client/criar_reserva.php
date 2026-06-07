<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('cliente');
require_once __DIR__ . '/../../include/conexao.php';
require_once __DIR__ . '/../../include/funcoes.php';
atualizarReservasVencidas($conexao);

function responder($sucesso, $mensagem, $dados = [])
{
    $ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'sucesso' => $sucesso,
            'mensagem' => $mensagem,
        ], $dados));
        exit;
    }

    if ($sucesso) {
        header('Location: painel.php?reserva=ok&id=' . urlencode($dados['id_reserva'] ?? ''));
        exit;
    }

    header('Location: reservas.php?erro=' . urlencode($mensagem));
    exit;
}

function dataBanco($data)
{
    $partes = explode('/', $data);

    if (count($partes) !== 3) {
        return null;
    }

    [$dia, $mes, $ano] = $partes;

    if (!checkdate((int) $mes, (int) $dia, (int) $ano)) {
        return null;
    }

    return $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dia, 2, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Método inválido.');
}

$idQuarto = (int) ($_POST['id_quarto'] ?? 0);
$dataEntrada = dataBanco($_POST['data_entrada'] ?? '');
$dataSaida = dataBanco($_POST['data_saida'] ?? '');

if ($idQuarto <= 0 || !$dataEntrada || !$dataSaida) {
    responder(false, 'Dados da reserva incompletos.');
}

$entrada = new DateTime($dataEntrada);
$saida = new DateTime($dataSaida);

if ($saida <= $entrada) {
    responder(false, 'A data de saída precisa ser depois da entrada.');
}

try {
    $conexao->beginTransaction();

    $stmt = $conexao->prepare("SELECT id_quarto, valor_diaria, status FROM quartos WHERE id_quarto = :id_quarto FOR UPDATE");
    $stmt->bindValue(':id_quarto', $idQuarto, PDO::PARAM_INT);
    $stmt->execute();
    $quarto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quarto) {
        $conexao->rollBack();
        responder(false, 'Quarto não encontrado.');
    }

    $statusQuarto = strtolower($quarto['status']);

    if (!in_array($statusQuarto, ['disponivel', 'disponível', 'livre'])) {
        $conexao->rollBack();
        responder(false, 'Esse quarto não está mais disponível para reserva.');
    }

    $nomeHospede = $_SESSION['nome'] ?? 'Cliente';
    $emailHospede = $_SESSION['email'] ?? null;
    $idHospede = null;

    if (!$emailHospede && !empty($_SESSION['id'])) {
        $stmt = $conexao->prepare("SELECT nome, email FROM usuario WHERE id_usuario = :id_usuario LIMIT 1");
        $stmt->bindValue(':id_usuario', (int) $_SESSION['id'], PDO::PARAM_INT);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $nomeHospede = $usuario['nome'] ?: $nomeHospede;
            $emailHospede = $usuario['email'] ?: null;
            $_SESSION['email'] = $emailHospede;
        }
    }

    if ($emailHospede) {
        $stmt = $conexao->prepare("SELECT id_hospede FROM hospedes WHERE email = :email LIMIT 1");
        $stmt->bindValue(':email', $emailHospede);
        $stmt->execute();
        $idHospede = $stmt->fetchColumn();
    }

    if (!$idHospede) {
        $stmt = $conexao->prepare("INSERT INTO hospedes (nome, email) VALUES (:nome, :email)");
        $stmt->bindValue(':nome', $nomeHospede);
        $stmt->bindValue(':email', $emailHospede);
        $stmt->execute();
        $idHospede = $conexao->lastInsertId();
    }

    $stmt = $conexao->prepare("SELECT COUNT(*) FROM reservas WHERE id_hospede = :id_hospede AND LOWER(status) IN ('ocupado', 'confirmado', 'check-in realizado')");
    $stmt->bindValue(':id_hospede', $idHospede, PDO::PARAM_INT);
    $stmt->execute();

    if ((int) $stmt->fetchColumn() > 0) {
        $conexao->rollBack();
        responder(false, 'Você já possui uma reserva ativa.');
    }

    $noites = $entrada->diff($saida)->days;
    $subtotal = (float) $quarto['valor_diaria'] * $noites;
    $valorTotal = ($subtotal * 1.12) + 45;

    $stmt = $conexao->prepare(
        "INSERT INTO reservas (id_hospede, id_quarto, data_entrada, data_saida, status, valor_total)
         VALUES (:id_hospede, :id_quarto, :data_entrada, :data_saida, :status, :valor_total)"
    );
    $stmt->bindValue(':id_hospede', $idHospede, PDO::PARAM_INT);
    $stmt->bindValue(':id_quarto', $idQuarto, PDO::PARAM_INT);
    $stmt->bindValue(':data_entrada', $dataEntrada);
    $stmt->bindValue(':data_saida', $dataSaida);
    $stmt->bindValue(':status', 'Ocupado');
    $stmt->bindValue(':valor_total', $valorTotal);
    $stmt->execute();
    $idReserva = $conexao->lastInsertId();

    $stmt = $conexao->prepare("UPDATE quartos SET status = 'Ocupado' WHERE id_quarto = :id_quarto");
    $stmt->bindValue(':id_quarto', $idQuarto, PDO::PARAM_INT);
    $stmt->execute();

    $conexao->commit();

    responder(true, 'Reserva criada com sucesso.', [
        'id_reserva' => $idReserva,
    ]);
} catch (PDOException $erro) {
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }

    responder(false, 'Não foi possível criar a reserva: ' . $erro->getMessage());
}
?>
