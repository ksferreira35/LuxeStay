<?php
session_start();

require_once __DIR__ . '/include/conexao.php';
require_once __DIR__ . '/include/lembrar.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = $_POST['email'];
$senha = md5($_POST['senha']);

$sql = "SELECT * FROM usuario 
        WHERE email = :email 
        AND senha = :senha";

$stmt = $conexao->prepare($sql);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':senha', $senha);
$stmt->execute();

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario) {
    preencherSessaoUsuario($usuario);

    if (!empty($_POST['lembrar'])) {
        salvarLembrar($usuario);
    } else {
        apagarLembrar();
    }

    if ($usuario['tipo'] === 'admin') {
        header('Location: user/admin/painel.php');
    } else {
        header('Location: user/client/painel.php');
    }

    exit;
} else {
    $_SESSION['logado'] = false;
    header('Location: index.php?erro=login');
    exit;
}
?>
