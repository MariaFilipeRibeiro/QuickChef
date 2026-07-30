<?php
//  Proteção de páginas exclusivas para administradores ─
// Incluir este ficheiro no topo de qualquer página da área de admin

// Iniciar sessão se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) session_start();

// Verificar se há sessão ativa E se o utilizador é admin
// Caso contrário, redirecionar para o login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Calcular caminho relativo até à pasta login
    $script = $_SERVER['SCRIPT_FILENAME'];
    $root   = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $depth  = substr_count(str_replace($root, '', str_replace('\\', '/', $script)), '/') - 1;
    $prefix = str_repeat('../', $depth);

    header("Location: " . $prefix . "login/login.php");
    exit();
}
?>
