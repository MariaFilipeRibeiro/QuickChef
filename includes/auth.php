<?php
//  Proteção de páginas para utilizadores autenticados 
// Incluir este ficheiro no topo de qualquer página que exija login

// Iniciar sessão se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) session_start();

// Verificar se existe uma sessão de utilizador ativa
if (!isset($_SESSION['user_id'])) {
    // Calcular o caminho relativo até à pasta login
    // (funciona independentemente da profundidade da pasta atual)
    $script = $_SERVER['SCRIPT_FILENAME'];
    $root   = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $depth  = substr_count(str_replace($root, '', str_replace('\\', '/', $script)), '/') - 1;
    $prefix = str_repeat('../', $depth);

    // Redirecionar para a página de login
    header("Location: " . $prefix . "login/login.php");
    exit();
}
?>
