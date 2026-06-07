<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('cliente');
require_once __DIR__ . '/../../include/conexao.php';

$idSolicitacao = (int) ($_POST['id_solicitacao'] ?? 0);
$email = $_SESSION['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idSolicitacao > 0 && $email !== '') {
    $sql = "UPDATE solicitacoes_limpeza
            INNER JOIN quartos ON quartos.id_quarto = solicitacoes_limpeza.id_quarto
            INNER JOIN reservas ON reservas.id_quarto = quartos.id_quarto
            INNER JOIN hospedes ON hospedes.id_hospede = reservas.id_hospede
            SET solicitacoes_limpeza.status = 'Concluido'
            WHERE solicitacoes_limpeza.id_solicitacao = :id_solicitacao
            AND hospedes.email = :email
            AND (LOWER(solicitacoes_limpeza.status) LIKE '%andamento%' OR LOWER(solicitacoes_limpeza.status) LIKE '%progresso%')
            AND LOWER(reservas.status) IN ('ocupado', 'confirmado', 'check-in realizado')";
    $stmt = $conexao->prepare($sql);
    $stmt->bindValue(':id_solicitacao', $idSolicitacao, PDO::PARAM_INT);
    $stmt->bindValue(':email', $email);
    $stmt->execute();

    header('Location: painel.php?confirmado=ok');
    exit;
}

header('Location: painel.php?erro=confirmar');
exit;
?>
