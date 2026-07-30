<?php
require_once("includes/auth.php");
require_once("includes/ligacao.php");
$active_page = 'dashboard';

// Validar id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = (int)$_GET['id'];
$dia_sel = isset($_GET['dia']) && is_numeric($_GET['dia']) ? (int)$_GET['dia'] : 0;

// Procurar refeição + avaliação do utilizador
$stmt = $conn->prepare("
SELECT r.*,
a.nota AS minha_nota
FROM refeicoes r
LEFT JOIN avaliacoes a ON a.refeicao_id = r.id AND a.user_id = ?
WHERE r.id = ?
");
$stmt->bind_param("ii", $_SESSION['user_id'], $id);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();

if (!$r) {
    header("Location: dashboard.php");
    exit();
}

$tipo_labels = [
'sopa' => 'Sopa',
'prato_principal' => 'Prato Principal',
'sobremesa' => 'Sobremesa',
];

?>
<!DOCTYPE html>
<html lang="pt">
    <head>
        <title><?= htmlspecialchars($r['nome']) ?> - Quick Chef</title>
        <link rel="stylesheet" href="css/style-dashboard.css">
    </head>
    <body class="body-dashboard">

        <?php include("includes/navbar.php"); ?>

        <div class="page-content">

            <!-- Voltar -->
            <div style="margin-bottom: 16px;">
                <a href="dashboard.php<?= $dia_sel ? '?dia='.$dia_sel : '' ?>" class="btn-secondary">
                    ← Voltar à ementa
                </a>
            </div>

            <div class="refeicao-detalhe">

                <!-- Cabeçalho -->
                <div>
                    <span class="badge badge-tipo">
                        <?= $tipo_labels[$r['tipo']] ?? $r['tipo'] ?>
                    </span>
                    <?php if ($r['sem_gluten']): ?><span class="badge badge-sem-gluten">Sem Glúten</span><?php endif; ?>
                    <?php if ($r['sem_lactose']): ?><span class="badge badge-sem-lactose">Sem Lactose</span><?php endif; ?>
                    <?php if ($r['vegetariano']): ?><span class="badge badge-vegetariano">Vegetariano</span><?php endif; ?>
                    <?php if ($r['vegan']): ?><span class="badge badge-vegan">Vegan</span><?php endif; ?>
                </div>

                <h2 style="margin-top: 12px;"><?= htmlspecialchars($r['nome']) ?></h2>

                <?php if ($r['descricao']): ?>
                    <p class="descricao"><?= htmlspecialchars($r['descricao']) ?></p>
                <?php endif; ?>

                <!-- Grid de nutrientes -->
                <div class="nutrientes-grid">
                    <div class="nut-box">
                        <span class="nut-valor"><?= $r['calorias'] ?></span>
                        <span class="nut-nome">kcal</span>
                    </div>
                    <div class="nut-box">
                        <span class="nut-valor"><?= $r['proteinas'] ?>g</span>
                        <span class="nut-nome">Proteínas</span>
                    </div>
                    <div class="nut-box">
                        <span class="nut-valor"><?= $r['hidratos'] ?>g</span>
                        <span class="nut-nome">Hidratos</span>
                    </div>
                    <div class="nut-box">
                        <span class="nut-valor"><?= $r['lipidos'] ?>g</span>
                        <span class="nut-nome">Lípidos</span>
                    </div>
                    <div class="nut-box">
                        <span class="nut-valor"><?= $r['fibra'] ?>g</span>
                        <span class="nut-nome">Fibra</span>
                    </div>
                </div>

                <!-- Ingredientes -->
                <?php if ($r['ingredientes']): ?>
                    <div class="ingredientes-lista">
                        <strong> Ingredientes</strong>
                        <?= htmlspecialchars($r['ingredientes']) ?>
                    </div>
                <?php endif; ?>

                <!-- Avaliação -->
                <div class="refeicao-avaliar">
                    <h3>A tua avaliação</h3>

                    <?php if ($r['minha_nota']): ?>
                        <!-- Já avaliou -->
                        <p class="avaliacao-feita">
                            Já avaliaste esta refeição com
                            <?php
                            $emojis = [1 => '😡 Mau', 2 => '😑 Ok', 3 => '😊 Bom'];
                            echo $emojis[$r['minha_nota']] ?? '';
                            ?>
                        </p>
                    <?php else: ?>
                        <!-- Ainda não avaliou -->
                        <div class="carinhas">
                            <button class="carinha-btn" onclick="avaliar(<?= $r['id'] ?>, 3, this)">😊</button>
                            <button class="carinha-btn" onclick="avaliar(<?= $r['id'] ?>, 2, this)">😑</button>
                            <button class="carinha-btn" onclick="avaliar(<?= $r['id'] ?>, 1, this)">😡</button>
                        </div>
                        <p class="avaliacao-label">Bom &nbsp;|&nbsp; Ok &nbsp;|&nbsp; Mau</p>
                    <?php endif; ?>

                    <?php if ($r['rating_total'] > 0): ?>
                        <p class="rating-global">
                            Média geral: <strong> <?= number_format($r['rating_media'], 1) ?></strong>
                            (<?= $r['rating_total'] ?> avaliações)
                        </p>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <script>
            function avaliar(refeicao_id, nota, btn) {
                const carinhas = btn.closest('.carinhas').querySelectorAll('.carinha-btn');
                carinhas.forEach(b => b.disabled = true);
                btn.classList.add('selected');

                fetch('/25161/quickchefcodigo/api/avaliar.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({refeicao_id, nota})
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        // Mostrar confirmação
                        btn.closest('.carinhas').insertAdjacentHTML('afterend',
                        '<p class="avaliacao-feita">Avaliação guardada! Obrigado </p>'
                        );
                    } else {
                        carinhas.forEach(b => b.disabled = false);
                        btn.classList.remove('selected');
                        alert('Erro ao guardar avaliação.');
                    }
                });
            }
        </script>

    </body>
</html>

