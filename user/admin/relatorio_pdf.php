<?php
require_once __DIR__ . '/../../include/sessao.php';
exigirTipo('admin');
require_once __DIR__ . '/../../include/conexao.php';
require_once __DIR__ . '/../../include/funcoes.php';
atualizarReservasVencidas($conexao);

$dataInicio = $_GET['inicio'] ?? date('Y-m-01');
$dataFim = $_GET['fim'] ?? date('Y-m-d');

if ($dataInicio > $dataFim) {
    $temporaria = $dataInicio;
    $dataInicio = $dataFim;
    $dataFim = $temporaria;
}

$stmt = $conexao->prepare(
    "SELECT reservas.id_reserva, hospedes.nome AS hospede, quartos.numero,
            quartos.tipo, reservas.data_entrada, reservas.data_saida,
            GREATEST(DATEDIFF(reservas.data_saida, reservas.data_entrada), 1) AS noites,
            reservas.status, reservas.valor_total
     FROM reservas
     INNER JOIN hospedes ON hospedes.id_hospede = reservas.id_hospede
     INNER JOIN quartos ON quartos.id_quarto = reservas.id_quarto
     WHERE reservas.data_entrada BETWEEN :inicio AND :fim
     ORDER BY reservas.data_entrada DESC, reservas.id_reserva DESC
     LIMIT 18"
);
$stmt->bindValue(':inicio', $dataInicio);
$stmt->bindValue(':fim', $dataFim);
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conexao->prepare(
    "SELECT COUNT(*) AS total_reservas,
            COALESCE(SUM(valor_total), 0) AS receita,
            COALESCE(SUM(GREATEST(DATEDIFF(data_saida, data_entrada), 1)), 0) AS noites
     FROM reservas
     WHERE data_entrada BETWEEN :inicio AND :fim"
);
$stmt->bindValue(':inicio', $dataInicio);
$stmt->bindValue(':fim', $dataFim);
$stmt->execute();
$resumo = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conexao->prepare(
    "SELECT
        SUM(CASE WHEN LOWER(observacao) LIKE '%serviço de quarto%' THEN 1 ELSE 0 END) AS servicos_quarto,
        SUM(CASE WHEN LOWER(observacao) NOT LIKE '%serviço de quarto%' OR observacao IS NULL THEN 1 ELSE 0 END) AS limpezas,
        SUM(CASE WHEN LOWER(status) IN ('pendente', 'sujo', 'solicitado') THEN 1 ELSE 0 END) AS pendentes,
        SUM(CASE WHEN LOWER(status) IN ('concluido', 'concluído', 'finalizado', 'limpo') THEN 1 ELSE 0 END) AS concluidos
     FROM solicitacoes_limpeza
     WHERE DATE(data_solicitacao) BETWEEN :inicio AND :fim"
);
$stmt->bindValue(':inicio', $dataInicio);
$stmt->bindValue(':fim', $dataFim);
$stmt->execute();
$servicos = $stmt->fetch(PDO::FETCH_ASSOC);

$totalReservas = (int) ($resumo['total_reservas'] ?? 0);
$receita = (float) ($resumo['receita'] ?? 0);
$totalNoites = (int) ($resumo['noites'] ?? 0);
$servicosQuarto = (int) ($servicos['servicos_quarto'] ?? 0);
$pedidosLimpeza = (int) ($servicos['limpezas'] ?? 0);
$pendentes = (int) ($servicos['pendentes'] ?? 0);
$concluidos = (int) ($servicos['concluidos'] ?? 0);
$ticketMedio = $totalReservas > 0 ? $receita / $totalReservas : 0;
$diariaMedia = $totalNoites > 0 ? $receita / $totalNoites : 0;

function pdfTexto($texto)
{
    $texto = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto);
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $texto);
}

function pdfText(&$conteudo, $texto, $x, $y, $size = 10, $font = 'F1', $color = '0.05 0.09 0.16')
{
    $conteudo .= "{$color} rg\n";
    $conteudo .= "BT\n/{$font} {$size} Tf\n1 0 0 1 {$x} {$y} Tm\n(" . pdfTexto($texto) . ") Tj\nET\n";
}

function pdfRect(&$conteudo, $x, $y, $w, $h, $color, $stroke = '')
{
    $conteudo .= "{$color} rg\n{$x} {$y} {$w} {$h} re f\n";

    if ($stroke !== '') {
        $conteudo .= "{$stroke} RG\n{$x} {$y} {$w} {$h} re S\n";
    }
}

function pdfLine(&$conteudo, $x1, $y1, $x2, $y2, $color = '0.86 0.84 0.84')
{
    $conteudo .= "{$color} RG\n0.7 w\n{$x1} {$y1} m\n{$x2} {$y2} l\nS\n";
}

$conteudo = "";

pdfRect($conteudo, 0, 0, 595, 842, '0.98 0.97 0.97');
pdfRect($conteudo, 0, 760, 595, 82, '0.03 0.08 0.15');
pdfRect($conteudo, 42, 709, 511, 92, '1 1 1', '0.86 0.84 0.84');
pdfText($conteudo, 'LuxeStay PMS', 58, 775, 21, 'F2', '1 1 1');
pdfText($conteudo, 'Relatorio Executivo de Reservas', 58, 752, 13, 'F1', '0.87 0.90 0.95');
pdfText($conteudo, 'Periodo analisado: ' . dataBrasil($dataInicio) . ' a ' . dataBrasil($dataFim), 58, 724, 11, 'F1');
pdfText($conteudo, 'Gerado em ' . date('d/m/Y H:i'), 420, 724, 9, 'F1', '0.35 0.39 0.46');

$cards = [
    ['Reservas', (string) $totalReservas, '0.03 0.08 0.15'],
    ['Noites reservadas', (string) $totalNoites, '0.54 0.42 0.07'],
    ['Servicos de quarto', (string) $servicosQuarto, '0.08 0.33 0.58'],
    ['Pedidos de limpeza', (string) $pedidosLimpeza, '0.72 0.25 0.15'],
];

$x = 42;
foreach ($cards as $card) {
    pdfRect($conteudo, $x, 628, 118, 64, '1 1 1', '0.86 0.84 0.84');
    pdfText($conteudo, strtoupper($card[0]), $x + 12, 671, 7, 'F2', '0.38 0.42 0.49');
    pdfText($conteudo, $card[1], $x + 12, 645, 22, 'F2', $card[2]);
    $x += 131;
}

pdfRect($conteudo, 42, 540, 248, 66, '0.03 0.08 0.15');
pdfText($conteudo, 'Receita estimada', 58, 580, 9, 'F2', '0.76 0.80 0.87');
pdfText($conteudo, dinheiro($receita), 58, 555, 22, 'F2', '1 1 1');

pdfRect($conteudo, 305, 540, 248, 66, '1 1 1', '0.86 0.84 0.84');
pdfText($conteudo, 'Indicadores financeiros', 321, 580, 9, 'F2', '0.38 0.42 0.49');
pdfText($conteudo, 'Ticket medio: ' . dinheiro($ticketMedio), 321, 560, 10, 'F1');
pdfText($conteudo, 'Diaria media: ' . dinheiro($diariaMedia), 321, 544, 10, 'F1');

pdfText($conteudo, 'Governanca no periodo', 42, 506, 12, 'F2');
pdfText($conteudo, 'Solicitacoes pendentes: ' . $pendentes, 42, 486, 10, 'F1');
pdfText($conteudo, 'Solicitacoes concluidas: ' . $concluidos, 218, 486, 10, 'F1');

pdfText($conteudo, 'Reservas detalhadas', 42, 448, 13, 'F2');
pdfRect($conteudo, 42, 421, 511, 22, '0.94 0.94 0.95');
pdfText($conteudo, 'Quarto', 54, 428, 8, 'F2', '0.28 0.31 0.37');
pdfText($conteudo, 'Hospede', 104, 428, 8, 'F2', '0.28 0.31 0.37');
pdfText($conteudo, 'Periodo', 238, 428, 8, 'F2', '0.28 0.31 0.37');
pdfText($conteudo, 'Noites', 340, 428, 8, 'F2', '0.28 0.31 0.37');
pdfText($conteudo, 'Valor', 398, 428, 8, 'F2', '0.28 0.31 0.37');
pdfText($conteudo, 'Status', 484, 428, 8, 'F2', '0.28 0.31 0.37');

$y = 396;
if (empty($reservas)) {
    pdfText($conteudo, 'Nenhuma reserva encontrada no periodo selecionado.', 54, $y, 10, 'F1', '0.38 0.42 0.49');
} else {
    foreach ($reservas as $reserva) {
        pdfLine($conteudo, 42, $y - 8, 553, $y - 8);
        pdfText($conteudo, $reserva['numero'], 54, $y, 9, 'F2');
        pdfText($conteudo, substr($reserva['hospede'], 0, 22), 104, $y, 8);
        pdfText($conteudo, dataBrasil($reserva['data_entrada']) . ' - ' . dataBrasil($reserva['data_saida']), 238, $y, 8);
        pdfText($conteudo, (string) $reserva['noites'], 348, $y, 8);
        pdfText($conteudo, dinheiro($reserva['valor_total']), 398, $y, 8);
        pdfText($conteudo, substr($reserva['status'], 0, 12), 484, $y, 8);
        $y -= 24;

        if ($y < 92) {
            pdfText($conteudo, 'A lista foi limitada para caber em uma pagina. Use a tela de Analise para ver mais detalhes.', 42, 70, 8, 'F1', '0.48 0.52 0.58');
            break;
        }
    }
}

pdfLine($conteudo, 42, 54, 553, 54, '0.80 0.78 0.75');
pdfText($conteudo, 'LuxeStay PMS - Suite de Gestao de Propriedades', 42, 36, 8, 'F1', '0.42 0.45 0.50');
pdfText($conteudo, 'Relatorio PDF gerado automaticamente pelo painel administrativo.', 338, 36, 8, 'F1', '0.42 0.45 0.50');

$objetos = [];
$objetos[] = "<< /Type /Catalog /Pages 2 0 R >>";
$objetos[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
$objetos[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>";
$objetos[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
$objetos[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";
$objetos[] = "<< /Length " . strlen($conteudo) . " >>\nstream\n{$conteudo}endstream";

$pdf = "%PDF-1.4\n";
$offsets = [0];
foreach ($objetos as $indice => $objeto) {
    $offsets[] = strlen($pdf);
    $numero = $indice + 1;
    $pdf .= "{$numero} 0 obj\n{$objeto}\nendobj\n";
}

$xref = strlen($pdf);
$pdf .= "xref\n0 " . (count($objetos) + 1) . "\n";
$pdf .= "0000000000 65535 f \n";
for ($i = 1; $i <= count($objetos); $i++) {
    $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
}
$pdf .= "trailer\n<< /Size " . (count($objetos) + 1) . " /Root 1 0 R >>\n";
$pdf .= "startxref\n{$xref}\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="relatorio-luxestay.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit;
