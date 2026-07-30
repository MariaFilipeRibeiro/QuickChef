<?php
require_once("../includes/auth_admin.php");
require_once("../includes/ligacao.php");
$active_page = 'admin';

define('PROJ', '/25161/quickchefcodigo');

?>
<!DOCTYPE html>
<html lang="pt">
    <head>
        <title>Administração - Quick Chef</title>
        <link rel="stylesheet" href="../css/style-admin-index.css">
    </head>
    <body class="body-dashboard">

        <?php include("../includes/navbar.php"); ?>

        <div class="page-content">
            <h1 class="page-title">Painel de Administração</h1>

            <div class="admin-grid">

                <a class="admin-card" href="utilizadores.php">
                    <div class="admin-card-icon">
                        <img src="<?= PROJ ?>/imagens/icon_utilizadores.png" width="64" height="64" alt="Utilizadores">
                    </div>
                    <h2>Utilizadores</h2>
                    <p>Consultar e gerir os colaboradores registados.</p>
                </a>

                <a class="admin-card" href="ementas.php">
                    <div class="admin-card-icon">
                        <img src="<?= PROJ ?>/imagens/icon_ementa.png" width="64" height="64" alt="Ementas">
                    </div>
                    <h2>Ementas</h2>
                    <p>Adicionar, editar e remover refeições da ementa semanal.</p>
                </a>

                <a class="admin-card" href="ementa_semanal.php">
                    <div class="admin-card-icon">
                        <img src="<?= PROJ ?>/imagens/icon_ementa_semanal.png" width="64" height="64" alt="Ementa Semanal">
                    </div>
                    <h2>Ementa Semanal</h2>
                    <p>Definir as refeições de cada dia da semana.</p>
                </a>

                <a class="admin-card" href="marcacoes.php">
                    <div class="admin-card-icon">
                        <img src="<?= PROJ ?>/imagens/icon_marcacoes.png" width="64" height="64" alt="Marcações">
                    </div>
                    <h2>Marcações</h2>
                    <p>Consultar as marcações de almoço dos colaboradores.</p>
                </a>

                <a class="admin-card" href="feedback.php">
                    <div class="admin-card-icon">
                        <img src="<?= PROJ ?>/imagens/icon_feedback.png" width="64" height="64" alt="Feedback">
                    </div>
                    <h2>Feedback</h2>
                    <p>Consultar as avaliações e popularidade das refeições.</p>
                </a>

            </div>
        </div>

    </body>
</html>

