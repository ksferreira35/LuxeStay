<?php
function dinheiro($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function normalizarDinheiro($valor)
{
    $valor = trim((string) $valor);
    $valor = str_replace(['R$', ' '], '', $valor);

    if (str_contains($valor, ',') && str_contains($valor, '.')) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    } else {
        $valor = str_replace(',', '.', $valor);
    }

    return (float) $valor;
}

function valorTotalObservacao($observacao)
{
    if (!preg_match('/Total\s+R\$\s*([0-9\.\,]+)/i', (string) $observacao, $resultado)) {
        return 0;
    }

    return normalizarDinheiro($resultado[1]);
}

function dataBrasil($data)
{
    if (empty($data)) {
        return '-';
    }

    return date('d/m/Y', strtotime($data));
}

function imagemQuarto($tipo)
{
    $tipo = strtolower($tipo);

    if (str_contains($tipo, 'mar')) {
        return '../../assets/img/quarto-mar-real.jpg';
    }

    if (str_contains($tipo, 'presidencial') || str_contains($tipo, 'cobertura')) {
        return '../../assets/img/quarto-executivo-real.jpg';
    }

    return '../../assets/img/quarto-jardim-real.jpg';
}

function descricaoQuarto($tipo)
{
    $tipo = strtolower($tipo);

    if (str_contains($tipo, 'mar')) {
        return 'Vista ampla para o mar, com ambiente elegante e muito conforto.';
    }

    if (str_contains($tipo, 'familiar')) {
        return 'Amplo espaço para toda a família com quartos confortáveis.';
    }

    if (str_contains($tipo, 'presidencial') || str_contains($tipo, 'cobertura')) {
        return 'Experiência premium do LuxeStay, com acabamento superior.';
    }

    return 'Quarto confortável para uma estadia tranquila na LuxeStay.';
}

function detalhesQuarto($quarto)
{
    return 'Quarto ' . $quarto['numero'] . ' • ' . $quarto['tipo'] . ' • ' . $quarto['andar'] . 'º andar';
}

function statusReservaClasse($status)
{
    $status = strtolower($status);

    if (str_contains($status, 'concl') || str_contains($status, 'final')) {
        return 'concluido';
    }

    if (str_contains($status, 'pendente')) {
        return 'pendente';
    }

    if (str_contains($status, 'ocup')) {
        return 'ocupado';
    }

    if (str_contains($status, 'check')) {
        return 'checkin';
    }

    return 'confirmado';
}

function atualizarReservasVencidas($conexao)
{
    $statusAtivos = "('ocupado', 'confirmado', 'check-in realizado', 'pendente')";

    try {
        $conexao->beginTransaction();

        $sqlLiberarQuartos = "UPDATE quartos
                              SET status = 'Disponível'
                              WHERE id_quarto IN (
                                  SELECT id_quarto
                                  FROM reservas
                                  WHERE data_saida < CURDATE()
                                  AND LOWER(status) IN {$statusAtivos}
                              )
                              AND NOT EXISTS (
                                  SELECT 1
                                  FROM reservas reserva_ativa
                                  WHERE reserva_ativa.id_quarto = quartos.id_quarto
                                  AND reserva_ativa.data_saida >= CURDATE()
                                  AND LOWER(reserva_ativa.status) IN {$statusAtivos}
                              )
                              AND LOWER(status) LIKE '%ocup%'";
        $conexao->exec($sqlLiberarQuartos);

        $sqlConcluirReservas = "UPDATE reservas
                                SET status = 'Concluido'
                                WHERE data_saida < CURDATE()
                                AND LOWER(status) IN {$statusAtivos}";
        $conexao->exec($sqlConcluirReservas);

        $conexao->commit();
    } catch (PDOException $erro) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
    }
}

function statusQuartoClasse($status)
{
    $status = strtolower($status);

    if (str_contains($status, 'ocup')) {
        return 'ocupado';
    }

    if (str_contains($status, 'manut')) {
        return 'manutencao';
    }

    if (str_contains($status, 'limpeza')) {
        return 'limpeza';
    }

    return 'disponivel';
}

function iniciaisNome($nome)
{
    $partes = explode(' ', trim($nome));
    $primeira = substr($partes[0] ?? 'H', 0, 1);
    $segunda = substr($partes[1] ?? $partes[0] ?? 'P', 0, 1);

    return strtoupper($primeira . $segunda);
}
?>
