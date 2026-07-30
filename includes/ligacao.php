<?php
//  Configuração da ligação à base de dados 
// Credenciais para ambiente local (WAMP)
$servername = "localhost";       // Servidor MySQL (local)
$username   = "root";            // Utilizador da BD
$password   = "";                // Password (vazia no WAMP por defeito)
$dbname     = "quickchef";       // Nome da base de dados

/*
// Credenciais para servidor de produção (descomentar se necessário)
$servername = "";
$username   = "";
$password   = "";
$dbname     = "";
*/

// Criar ligação à BD usando MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Definir charset utf8mb4 para suportar caracteres especiais e emojis
mysqli_set_charset($conn, "utf8mb4");

// Verificar se a ligação foi bem-sucedida
if ($conn->connect_error) {
    // Registar erro no log do servidor (não mostra ao utilizador)
    error_log("Erro de ligação BD: " . $conn->connect_error);
    // Mostrar mensagem genérica ao utilizador
    die("Erro interno do servidor. Tenta novamente mais tarde.");
}
?>
