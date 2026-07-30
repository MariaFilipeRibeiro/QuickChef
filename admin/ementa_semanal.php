<?php
require_once("../includes/auth_admin.php");
require_once("../includes/ligacao.php");
$active_page = 'admin';

$dias_nomes = [
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
];

// Calcular semana selecionada
$offset = isset($_GET['semana']) ? (int)$_GET['semana'] : 0;
// Segunda-feira da semana atual + offset em semanas
$segunda = new DateTime();
$segunda->modify('monday this week');
$segunda->modify(($offset > 0 ? '+' : '') . $offset . ' week');

$sexta = clone $segunda;
$sexta->modify('+4 days');

$semana_label = $segunda->format('d M') . ' a ' . $sexta->format('d M Y');

// Datas de cada dia
$datas = [];
for ($d = 1; $d <= 5; $d++) {
    $dt = clone $segunda;
    $dt->modify('+' . ($d - 1) . ' days');
    $datas[$d] = $dt->format('Y-m-d');
}

$sucesso = '';
$erro = '';

// Guardar ementa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Para cada dia, apagar e reinserir
    foreach ($datas as $dia_num => $data) {
        // Apagar ementas deste dia
        $stmt = $conn->prepare("DELETE FROM ementa_diaria WHERE data = ?");
        $stmt->bind_param("s", $data);
        $stmt->execute();

        // Inserir as selecionadas
        $selecionadas = $_POST['dia_' . $dia_num] ?? [];
        foreach ($selecionadas as $refeicao_id) {
            $rid = (int)$refeicao_id;
            $stmt = $conn->prepare("INSERT INTO ementa_diaria (refeicao_id, dia_semana, data) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $rid, $dia_num, $data);
            $stmt->execute();
        }
    }
    $sucesso = 'Ementa da semana guardada com sucesso.';
}

// Procurar todas as refeições agrupadas por tipo
$todas = $conn->query("SELECT id, nome, tipo, vegetariano, vegan FROM refeicoes ORDER BY FIELD(tipo, 'sopa', 'prato_principal', 'sobremesa'), nome");
$refeicoes = [];
while ($r = $todas->fetch_assoc()) {
    $refeicoes[$r['tipo']][] = $r;
}

// Procurar ementas já definidas para esta semana
$ementas_semana = [];
foreach ($datas as $dia_num => $data) {
    $stmt = $conn->prepare("SELECT refeicao_id FROM ementa_diaria WHERE data = ?");
    $stmt->bind_param("s", $data);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ementas_semana[$dia_num][] = $row['refeicao_id'];
    }
}

$tipo_labels = [
    'sopa'            => 'Sopas',
    'prato_principal' => 'Pratos Principais',
    'vegetariano'     => 'Vegetariano',
    'vegan'           => 'Vegan',
    'sobremesa'       => 'Sobremesas',
];

$semana_ant = $offset - 1;
$semana_seg = $offset + 1;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <title>Ementa Semanal - Quick Chef Admin</title>
    <link rel="stylesheet" href="../css/style-ementa-semanal.css">
</head>
<body class="body-dashboard">

    <?php include("../includes/navbar.php"); ?>

    <div class="page-content">
        <div class="page-header">
            <h1 class="page-title">Ementa Semanal</h1>
            <a href="index.php" class="btn-secondary">← Voltar</a>
        </div>

        <?php if ($sucesso): ?>
            <p class="msg-sucesso"><?= htmlspecialchars($sucesso) ?></p>
        <?php endif; ?>
        <?php if ($erro): ?>
            <p class="msg-erro"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <!-- Navegação de semana -->
        <div class="semana-nav">
            <a href="ementa_semanal.php?semana=<?= $semana_ant ?>" class="dia-btn">&#8592;</a>
            <span class="semana-label">Semana de <?= $semana_label ?></span>
            <a href="ementa_semanal.php?semana=<?= $semana_seg ?>" class="dia-btn">&#8594;</a>
        </div>

        <form method="POST" action="ementa_semanal.php?semana=<?= $offset ?>">

            <div class="dias-grid">
                <?php foreach ($datas as $dia_num => $data): ?>
                    <div class="dia-card">
                        <div class="dia-header">
                            <h2><?= $dias_nomes[$dia_num] ?></h2>
                            <span class="dia-data"><?= date('d/m', strtotime($data)) ?></span>
                        </div>

                        <?php foreach ($refeicoes as $tipo => $lista): ?>
                            <div class="tipo-grupo">
                                <div class="tipo-label"><?= $tipo_labels[$tipo] ?? $tipo ?></div>
                                <?php foreach ($lista as $ref):
                                    $checked = in_array($ref['id'], $ementas_semana[$dia_num] ?? []) ? 'checked' : '';
                                ?>
                                    <label class="refeicao-check <?= $checked ? 'selecionada' : '' ?>">
                                        <input type="checkbox"
                                               name="dia_<?= $dia_num ?>[]"
                                               value="<?= $ref['id'] ?>"
                                               <?= $checked ?>
                                               onchange="this.closest('label').classList.toggle('selecionada', this.checked)">
                                        <?= htmlspecialchars($ref['nome']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>

                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-actions-bottom">
                <input type="submit" value="Guardar ementa da semana">
            </div>

        </form>
    </div>

</body>
</html>
