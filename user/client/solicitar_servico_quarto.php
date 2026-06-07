<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('cliente');
require_once __DIR__ . '/../../include/conexao.php';

$idQuarto = (int) ($_POST['id_quarto'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idQuarto > 0) {
    $stmt = $conexao->prepare(
        "INSERT INTO solicitacoes_limpeza (id_quarto, status, observacao)
         VALUES (:id_quarto, :status, :observacao)"
    );
    $stmt->bindValue(':id_quarto', $idQuarto, PDO::PARAM_INT);
    $stmt->bindValue(':status', 'Pendente');
    $stmt->bindValue(':observacao', 'Serviço de quarto solicitado pelo hóspede.');
    $stmt->execute();

    header('Location: painel.php?servico=ok');
    exit;
}

header('Location: painel.php?erro=servico');
exit;
?>
