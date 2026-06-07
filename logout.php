<?php

    session_start();
    require_once __DIR__ . '/include/lembrar.php';

    apagarLembrar();
    session_destroy();
    header('Location: index.php');
    exit();

?>
