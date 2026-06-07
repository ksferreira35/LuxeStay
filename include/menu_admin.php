<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);
$matrizAtiva = in_array($paginaAtual, ['matriz_quartos.php', 'cadastrar_quarto.php', 'editar_quarto.php']);
$reservasAtiva = $paginaAtual === 'reservas.php';
$governancaAtiva = in_array($paginaAtual, ['governanca.php', 'cadastrar_funcionario.php']);
$analiseAtiva = in_array($paginaAtual, ['analise.php', 'relatorio_pdf.php']);
$configAtiva = $paginaAtual === 'configuracoes.php';
$suporteAtivo = $paginaAtual === 'suporte.php';
?>

<aside class="menu-lateral">
    <div class="logo">
        <div>
            <h2>Sede Principal</h2>
            <span>Acesso Administrativo</span>
        </div>
    </div>

    <nav aria-label="Menu principal">
        <ul>
            <li>
                <a href="painel.php" class="<?= $paginaAtual === 'painel.php' ? 'ativo' : '' ?>">
                    <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
                    <span>Painel</span>
                </a>
            </li>
            <li>
                <a href="matriz_quartos.php" class="<?= $matrizAtiva ? 'ativo' : '' ?>">
                    <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    <span>Matriz de Quartos</span>
                </a>
            </li>
            <li>
                <a href="reservas.php" class="<?= $reservasAtiva ? 'ativo' : '' ?>">
                    <i class="bi bi-calendar-check" aria-hidden="true"></i>
                    <span>Reservas</span>
                </a>
            </li>
            <li>
                <a href="governanca.php" class="<?= $governancaAtiva ? 'ativo' : '' ?>">
                    <i class="bi bi-wrench-adjustable" aria-hidden="true"></i>
                    <span>Governança</span>
                </a>
            </li>
            <li>
                <a href="analise.php" class="<?= $analiseAtiva ? 'ativo' : '' ?>">
                    <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                    <span>Análise</span>
                </a>
            </li>
        </ul>

        <div class="menu-rodape">
            <a href="reservas.php" class="menu-nova-reserva">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Nova Reserva</span>
            </a>

            <ul>
                <li>
                    <a href="configuracoes.php" class="<?= $configAtiva ? 'ativo' : '' ?>">
                        <i class="bi bi-gear" aria-hidden="true"></i>
                        <span>Configurações</span>
                    </a>
                </li>
                <li>
                    <a href="suporte.php" class="<?= $suporteAtivo ? 'ativo' : '' ?>">
                        <i class="bi bi-question-circle" aria-hidden="true"></i>
                        <span>Suporte</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>
