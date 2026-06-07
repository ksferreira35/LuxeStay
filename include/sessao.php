<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['logado'])) {
    require_once __DIR__ . '/conexao.php';
    require_once __DIR__ . '/lembrar.php';

    if (!tentarLoginPorCookie($conexao)) {
        header('Location: ../../index.php');
        exit();
    }
}

function exigirTipo($tipo)
{
    if (($_SESSION['tipo'] ?? '') !== $tipo) {
        header('Location: ../../index.php');
        exit();
    }
}
?>
