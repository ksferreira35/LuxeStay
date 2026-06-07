<?php
const LEMBRAR_COOKIE = 'luxestay_lembrar';
const LEMBRAR_DIAS = 30;
const LEMBRAR_CHAVE = 'luxestay_pms_2026';

function lembrarAssinatura($usuario)
{
    $texto = $usuario['id_usuario'] . '|' . $usuario['email'] . '|' . $usuario['senha'] . '|' . $usuario['tipo'];

    return hash_hmac('sha256', $texto, LEMBRAR_CHAVE);
}

function salvarLembrar($usuario)
{
    $valor = $usuario['id_usuario'] . ':' . lembrarAssinatura($usuario);

    setcookie(LEMBRAR_COOKIE, $valor, [
        'expires' => time() + (60 * 60 * 24 * LEMBRAR_DIAS),
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function apagarLembrar()
{
    setcookie(LEMBRAR_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function preencherSessaoUsuario($usuario)
{
    $_SESSION['logado'] = true;
    $_SESSION['id'] = $usuario['id_usuario'] ?? null;
    $_SESSION['nome'] = $usuario['nome'] ?? $usuario['email'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['tipo'] = $usuario['tipo'];
}

function tentarLoginPorCookie($conexao)
{
    if (empty($_COOKIE[LEMBRAR_COOKIE])) {
        return false;
    }

    $partes = explode(':', $_COOKIE[LEMBRAR_COOKIE], 2);

    if (count($partes) !== 2) {
        apagarLembrar();
        return false;
    }

    [$idUsuario, $assinatura] = $partes;

    $stmt = $conexao->prepare("SELECT * FROM usuario WHERE id_usuario = :id_usuario LIMIT 1");
    $stmt->bindValue(':id_usuario', (int) $idUsuario, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !hash_equals(lembrarAssinatura($usuario), $assinatura)) {
        apagarLembrar();
        return false;
    }

    preencherSessaoUsuario($usuario);
    return true;
}
?>
