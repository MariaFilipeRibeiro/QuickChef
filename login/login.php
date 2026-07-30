<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$erro = $_GET['erro'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt">
    <head>
        <title>Login - Quick Chef</title>
        <link rel="stylesheet" href="../css/style-login.css">
    </head>
    <body class="body-login">
        <div class="container">
            <div class="card">

                <h1 class="login-logo">Quick Chef</h1>
                <h2>A tua cantina mais saudável</h2>

                <?php if ($erro === 'credenciais'): ?>
                    <p class="msg-erro">Email ou palavra-passe incorretos.</p>
                <?php elseif ($erro === 'campos'): ?>
                    <p class="msg-erro">Preenche todos os campos.</p>
                <?php endif; ?>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab active" onclick="mostrarTab('login', this)">Entrar</button>
                    <button class="tab" onclick="mostrarTab('registar', this)">Criar conta</button>
                </div>

                <!-- Formulário Login -->
                <div id="form-login" class="form-section active">
                    <form method="post" action="autenticar.php">
                        <input type="hidden" name="acao" value="login">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="o_teu@email.com" required>
                        <label>Palavra-passe</label>
                        <input type="password" name="senha" placeholder="••••••••" required>
                        <input type="submit" value="Entrar">
                    </form>
                </div>

                <!-- Formulário Registar -->
                <div id="form-registar" class="form-section">
                    <form method="post" action="autenticar.php">
                        <input type="hidden" name="acao" value="registar">
                        <label>Nome</label>
                        <input type="text" name="nome" placeholder="O teu nome" required>
                        <label>Email</label>
                        <input type="email" name="email" placeholder="o_teu@email.com" required>
                        <label>Palavra-passe</label>
                        <input type="password" name="senha" placeholder="Mínimo 6 caracteres" required>
                        <label>Confirmar palavra-passe</label>
                        <input type="password" name="senha_conf" placeholder="Repete a palavra-passe" required>
                        <input type="submit" value="Criar conta">
                    </form>
                </div>

            </div>
        </div>

        <script>
            function mostrarTab(tab, btn) {
                document.querySelectorAll('.form-section').forEach(f => f.classList.remove('active'));
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.getElementById('form-' + tab).classList.add('active');
                btn.classList.add('active');
            }
            <?php if ($erro): ?>
            // Se houve erro no registo, abrir tab correspondente
            <?php if ($_GET['tab'] ?? '' === 'registar'): ?>
            mostrarTab('registar', document.querySelectorAll('.tab')[1]);
            <?php endif; ?>
            <?php endif; ?>
        </script>
    </body>
</html>

