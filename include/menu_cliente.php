<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);
$gastronomiaAtiva = $paginaAtual === 'gastronomia.php';
$configAtiva = $paginaAtual === 'configuracoes.php';
$suporteAtivo = $paginaAtual === 'suporte.php';
?>

<aside class="menu-lateral">
    <div class="logo">
        <div>
            <h2>LuxeStay PMS</h2>
            <span>Unidade Principal</span>
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
                <a href="reservas.php" class="<?= $paginaAtual === 'reservas.php' ? 'ativo' : '' ?>">
                    <i class="bi bi-calendar-check" aria-hidden="true"></i>
                    <span>Reservas</span>
                </a>
            </li>
            <li>
                <a href="gastronomia.php" class="<?= $gastronomiaAtiva ? 'ativo' : '' ?>">
                    <i class="bi bi-cup-hot" aria-hidden="true"></i>
                    <span>Gastronomia</span>
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
