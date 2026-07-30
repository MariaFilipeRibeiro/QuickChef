<?php
//  Terminar sessão 
session_start();

// Limpar todas as variáveis de sessão
session_unset();

// Destruir a sessão completamente
session_destroy();

// Redirecionar para a página inicial
header("Location: ../index.php");
exit();
?>
