<?php
require_once("../includes/auth_admin.php");
require_once("../includes/ligacao.php");
$active_page = 'admin';

//  APAGAR utilizador
if (isset($_GET['apagar']) && is_numeric($_GET['apagar'])) {
    $id = (int)$_GET['apagar'];
    $stmt = $conn->prepare("DELETE FROM utilizadores WHERE id = ? AND role != 'admin'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: utilizadores.php?msg=apagado");
    exit();
}

//  Listar todos os utilizadores
$resultado = $conn->query("
SELECT id, nome, email, role, meta_calorica
FROM utilizadores
ORDER BY role DESC, nome ASC
");

?>
<!DOCTYPE html>
<html lang="pt">
    <head>
        <title>Utilizadores - Quick Chef Admin</title>
        <link rel="stylesheet" href="../css/style-admin-utilizadores.css">
    </head>
    <body class="body-dashboard">

        <?php include("../includes/navbar.php"); ?>

        <div class="page-content">
            <div class="page-header">
                <h1 class="page-title">Utilizadores</h1>
                <a href="index.php" class="btn-secondary">← Voltar</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'apagado'): ?>
                <p class="msg-sucesso">Utilizador apagado com sucesso.</p>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Meta Cal. (kcal)</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['nome']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                    <?= $u['role'] === 'admin' ? 'Admin' : 'Utilizador' ?>
                                </span>
                            </td>
                            <td><?= $u['meta_calorica'] ?? '—' ?> kcal</td>
                            <td>
                                <?php if ($u['role'] !== 'admin'): ?>
                                    <a href="utilizadores.php?apagar=<?= $u['id'] ?>"
                                        class="btn-apagar"
                                        onclick="return confirm('Tens a certeza que queres apagar este utilizador?')">Apagar
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </body>
</html>
