<?php
//  Painel admin: consulta de marcações ─
require_once("../includes/auth_admin.php");
require_once("../includes/ligacao.php");
$active_page = 'admin';

//  Calcular semana a visualizar 
// offset=0 é a semana atual, -1 é a anterior, +1 é a próxima
$offset  = isset($_GET['semana']) ? (int)$_GET['semana'] : 0;
$segunda = new DateTime();
$segunda->modify('monday this week');
// Ajustar pela quantidade de semanas de offset
$segunda->modify(($offset >= 0 ? '+' : '') . $offset . ' week');
$sexta = clone $segunda;
$sexta->modify('+4 days');

$data_ini     = $segunda->format('Y-m-d');
$data_fim     = $sexta->format('Y-m-d');
$semana_label = $segunda->format('d M') . ' a ' . $sexta->format('d M Y');

//  Procurar marcações da semana selecionada 
// JOIN com utilizadores e refeicoes para obter os nomes
$stmt = $conn->prepare("
    SELECT
        m.data,
        u.nome  AS utilizador,
        s.nome  AS sopa,
        p.nome  AS prato,
        sb.nome AS sobremesa,
        m.total_kcal
    FROM marcacoes m
    JOIN utilizadores u  ON u.id  = m.user_id
    LEFT JOIN refeicoes s  ON s.id  = m.sopa_id       -- sopa pode ser NULL
    LEFT JOIN refeicoes p  ON p.id  = m.prato_id      -- prato pode ser NULL
    LEFT JOIN refeicoes sb ON sb.id = m.sobremesa_id  -- sobremesa pode ser NULL
    WHERE m.data BETWEEN ? AND ?
    ORDER BY m.data, u.nome
");
$stmt->bind_param("ss", $data_ini, $data_fim);
$stmt->execute();
$resultado = $stmt->get_result();

//  Estatísticas da semana ─
$stmt2 = $conn->prepare("
    SELECT
        COUNT(*)              AS total_marcacoes,
        ROUND(AVG(total_kcal), 0) AS media_kcal,
        COUNT(DISTINCT user_id)   AS total_utilizadores
    FROM marcacoes
    WHERE data BETWEEN ? AND ?
");
$stmt2->bind_param("ss", $data_ini, $data_fim);
$stmt2->execute();
$stats = $stmt2->get_result()->fetch_assoc();

$semana_ant = $offset - 1;
$semana_seg = $offset + 1;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <title>Marcações - Quick Chef Admin</title>
    <link rel="stylesheet" href="../css/style-admin-marcacoes.css">
</head>
<body class="body-dashboard">

<?php include("../includes/navbar.php"); ?>

<div class="page-content">
    <div class="page-header">
        <h1 class="page-title"> Marcações</h1>
        <a href="index.php" class="btn-secondary">← Voltar</a>
    </div>

    <!-- Navegação entre semanas -->
    <div class="semana-nav">
        <a href="marcacoes.php?semana=<?= $semana_ant ?>" class="dia-btn">&#8592;</a>
        <span class="semana-label">Semana de <?= $semana_label ?></span>
        <a href="marcacoes.php?semana=<?= $semana_seg ?>" class="dia-btn">&#8594;</a>
    </div>

    <!-- Cards com resumo estatístico da semana -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-valor"><?= $stats['total_marcacoes'] ?? 0 ?></div>
            <div class="stat-label">Marcações</div>
        </div>
        <div class="stat-card">
            <div class="stat-valor"><?= $stats['total_utilizadores'] ?? 0 ?></div>
            <div class="stat-label">Colaboradores</div>
        </div>
        <div class="stat-card">
            <!-- Média de kcal por refeição marcada -->
            <div class="stat-valor"><?= $stats['media_kcal'] ?? 0 ?> kcal</div>
            <div class="stat-label">Média de calorias</div>
        </div>
    </div>

    <!-- Tabela de marcações -->
    <?php if ($resultado->num_rows === 0): ?>
        <p class="msg-vazio">Não há marcações para esta semana.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Colaborador</th>
                <th>Sopa</th>
                <th>Prato Principal</th>
                <th>Sobremesa</th>
                <th>Total kcal</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($r = $resultado->fetch_assoc()): ?>
            <tr>
                <td><?= date('d/m', strtotime($r['data'])) ?></td>
                <td><strong><?= htmlspecialchars($r['utilizador']) ?></strong></td>
                <!-- Mostrar "—" se não escolheu sopa/prato/sobremesa -->
                <td><?= $r['sopa']      ? htmlspecialchars($r['sopa'])      : '<span class="text-muted">—</span>' ?></td>
                <td><?= $r['prato']     ? htmlspecialchars($r['prato'])     : '<span class="text-muted">—</span>' ?></td>
                <td><?= $r['sobremesa'] ? htmlspecialchars($r['sobremesa']) : '<span class="text-muted">—</span>' ?></td>
                <td><strong><?= number_format($r['total_kcal'], 0) ?> kcal</strong></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</body>
</html>
