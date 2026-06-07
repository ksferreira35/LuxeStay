<?php
    $envPath = __DIR__ . '/../.env';
    $env = file_exists($envPath) ? parse_ini_file($envPath) : [];

    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $user = $env['DB_USER'] ?? 'root';
    $porta = $env['DB_PORT'] ?? '3306';
    $password = $env['DB_PASSWORD'] ?? '';
    $db = $env['DB_NAME'] ?? 'luxestay';

    $conexao = new PDO(
        'mysql:host='.$host.';
        port='.$porta.';
        dbname='.$db,
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
?>
