<?php
require_once("../includes/auth_admin.php");
require_once("../includes/ligacao.php");
$active_page = 'admin';

$resultado = $conn->query("SELECT * FROM v_popularidade_refeicoes");

?>
<!DOCTYPE html>
<html lang="pt">
    <head>
        <title>Feedback - Quick Chef Admin</title>
        <link rel="stylesheet" href="../css/style-admin-feedback.css">
    </head>
    <body class="body-dashboard">

        <?php include("../includes/navbar.php"); ?>

        <div class="page-content">
            <div class="page-header">
                <h1 class="page-title">Feedback das Refeições</h1>
                <a href="index.php" class="btn-secondary">← Voltar</a>
            </div>

            <?php if ($resultado->num_rows === 0): ?>
                <p class="msg-vazio">Ainda não há avaliações registadas.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Refeição</th>
                            <th>Tipo</th>
                            <th>Média</th>
                            <th>Total</th>
                            <th> Bom</th>
                            <th> Ok</th>
                            <th> Mau</th>
                            <th>Popularidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $resultado->fetch_assoc()):
                            $media = $r['rating_media'] ?? 0;
                            $pct = $r['rating_total'] > 0 ? round((($media - 1) / 2) * 100) : 0;
                            $cor = $pct >= 66 ? '#3B6D11' : ($pct >= 33 ? '#854F0B' : '#993C1D');
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['nome']) ?></strong></td>
                                <td>
                                    <span class="badge badge-tipo">
                                        <?= ucfirst(str_replace('_', ' ', $r['tipo'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($r['rating_total'] > 0): ?>
                                        <strong><?= number_format($media, 1) ?></strong> / 3
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $r['rating_total'] ?> avaliações</td>
                                <td class="td-center"> <?= $r['total_bom'] ?></td>
                                <td class="td-center"> <?= $r['total_ok'] ?></td>
                                <td class="td-center"> <?= $r['total_mau'] ?></td>
                                <td>
                                    <?php if ($r['rating_total'] > 0): ?>
                                        <div class="barra-wrap">
                                            <div class="barra-fill" style="width:<?= $pct ?>%; background:<?= $cor ?>"></div>
                                        </div>
                                        <span class="barra-pct"><?= $pct ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted">Sem dados</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </body>
</html>
