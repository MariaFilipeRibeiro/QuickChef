<?php
session_start();

// Se já tiver sessão, vai direto para o dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="pt">
    <head>
        <title>Quick Chef</title>
        <link rel="stylesheet" href="css/style-index.css">
    </head>
    <body>
        <div class="container">
            <h1>Quick Chef</h1>
            <h2>Bem-vindo ao Quick Chef!</h2>
            <a class="btn-comecar" href="login/login.php">Começar</a>
        </div>
    </body>
</html>

