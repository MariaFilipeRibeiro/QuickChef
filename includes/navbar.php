<?php
//  Barra de navegação global 
// Incluída em todas as páginas após o auth.php

// Iniciar sessão se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) session_start();

// Página ativa (para destacar o item no menu)
$active_page = $active_page ?? '';

// Gerar iniciais do nome do utilizador para o avatar circular
$nome    = $_SESSION['user_nome'] ?? 'U';
$partes  = explode(' ', trim($nome));
$iniciais = strtoupper(substr($partes[0], 0, 1));
if (count($partes) >= 2) {
    // Adicionar inicial do apelido se existir
    $iniciais .= strtoupper(substr($partes[1], 0, 1));
}

// Caminho base do projeto (usado em todos os links e imagens)
define('PROJETO', '/25161/quickchefcodigo');
?>
<nav>
    <!-- Logo / nome da aplicação -->
    <a href="<?= PROJETO ?>/index.php" class="nav-logo">Quick Chef</a>

    <!-- Avatar com dropdown de opções -->
    <div class="avatar-wrap">
        <button class="avatar-btn" aria-label="Menu utilizador"><?= $iniciais ?></button>

        <div class="dropdown" role="menu">

            <!-- Link para a ementa (dashboard) -->
            <a href="<?= PROJETO ?>/dashboard.php" role="menuitem"
               <?= $active_page === 'dashboard' ? 'class="active"' : '' ?>>
                <img src="<?= PROJETO ?>/imagens/icon_categorias.png" width="24" height="24" alt="">
                Ementa
            </a>

            <!-- Link para o perfil do utilizador -->
            <a href="<?= PROJETO ?>/perfil.php" role="menuitem"
               <?= $active_page === 'perfil' ? 'class="active"' : '' ?>>
                <img src="<?= PROJETO ?>/imagens/icon_utilizador.png" width="24" height="24" alt="">
                Perfil
            </a>

            <!-- Link para administração (só visível para admins) -->
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                <a href="<?= PROJETO ?>/admin/index.php" role="menuitem">
                    <img src="<?= PROJETO ?>/imagens/icon_admin.png" width="24" height="24" alt="">
                    Administração
                </a>
            <?php endif; ?>

            <div class="dropdown-divider"></div>

            <!-- Link para terminar sessão -->
            <a href="<?= PROJETO ?>/login/logout.php" role="menuitem" class="logout">
                <img src="<?= PROJETO ?>/imagens/icon_sair.png" width="24" height="24" alt="">
                Sair
            </a>

        </div>
    </div>
</nav>
