<?php
require_once("includes/auth.php");
require_once("includes/ligacao.php");
$active_page = 'dashboard';

$dias_nomes = [1=>'Segunda-feira',2=>'Terça-feira',3=>'Quarta-feira',4=>'Quinta-feira',5=>'Sexta-feira',6=>'Sábado',7=>'Domingo'];

$hoje = min(max((int)date('N'), 1), 5);
$dia_sel = isset($_GET['dia']) && is_numeric($_GET['dia']) ? (int)$_GET['dia'] : $hoje;
$dia_sel = min(max($dia_sel, 1), 5);

$segunda = new DateTime();
$segunda->modify('monday this week');
$dt = clone $segunda;
$dt->modify('+' . ($dia_sel - 1) . ' days');
$data_sel = $dt->format('Y-m-d');

$sucesso = '';
$erro = '';

//  GUARDAR MARCAÇÃO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'] ?? '';
    $sopa_id = !empty($_POST['sopa_id']) ? (int)$_POST['sopa_id'] : null;
    $prato_id = !empty($_POST['prato_id']) ? (int)$_POST['prato_id'] : null;
    $sobremesa_id = !empty($_POST['sobremesa_id']) ? (int)$_POST['sobremesa_id'] : null;

    if (empty($data)) {
        $erro = 'Data inválida.';
    } elseif (empty($prato_id) && empty($sopa_id)) {
        $erro = 'Tens de escolher pelo menos uma sopa ou um prato principal.';
    } else {
        // Calcular total kcal
        $ids = array_filter([$sopa_id, $prato_id, $sobremesa_id]);
        $total_kcal = 0;
        if (!empty($ids)) {
            $ids = array_values($ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("SELECT SUM(calorias) as total FROM refeicoes WHERE id IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
            $stmt->execute();
            $total_kcal = (float)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        }

        // INSERT ou UPDATE
        $uid = $_SESSION['user_id'];
        $stmt = $conn->prepare("
        INSERT INTO marcacoes (user_id, data, sopa_id, prato_id, sobremesa_id, total_kcal)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        sopa_id = VALUES(sopa_id),
        prato_id = VALUES(prato_id),
        sobremesa_id = VALUES(sobremesa_id),
        total_kcal = VALUES(total_kcal)
        ");
        $stmt->bind_param("isiiid", $uid, $data, $sopa_id, $prato_id, $sobremesa_id, $total_kcal);
        $stmt->execute();
        $sucesso = 'Marcação guardada com sucesso!';
        $data_sel = $data;
        $dia_sel = min(max((int)date('N', strtotime($data)), 1), 5);
    }
}

// Buscar perfil do utilizador para aplicar filtros
$stmt_perfil = $conn->prepare("
    SELECT u.meta_calorica, p.sem_gluten, p.sem_lactose, p.vegetariano, p.vegan
    FROM utilizadores u
    LEFT JOIN perfil_utilizador p ON p.user_id = u.id
    WHERE u.id = ?
");
$stmt_perfil->bind_param("i", $_SESSION['user_id']);
$stmt_perfil->execute();
$perfil = $stmt_perfil->get_result()->fetch_assoc();

// Construir filtros com base no perfil
$filtros = "";
$params  = [$data_sel];
$tipos   = "s";

if (!empty($perfil['sem_gluten'])) {
    $filtros .= " AND r.sem_gluten = 1";
}
if (!empty($perfil['sem_lactose'])) {
    $filtros .= " AND r.sem_lactose = 1";
}
if (!empty($perfil['vegan'])) {
    $filtros .= " AND r.vegan = 1";
} elseif (!empty($perfil['vegetariano'])) {
    $filtros .= " AND (r.vegetariano = 1 OR r.vegan = 1)";
}
if (!empty($perfil['meta_calorica'])) {
    $filtros .= " AND r.calorias <= ?";
    $params[] = $perfil['meta_calorica'];
    $tipos   .= "i";
}

// Buscar ementa do dia com filtros aplicados
$stmt = $conn->prepare("
    SELECT r.id, r.nome, r.tipo, r.calorias, r.sem_gluten, r.sem_lactose, r.vegetariano, r.vegan
    FROM ementa_diaria e
    JOIN refeicoes r ON r.id = e.refeicao_id
    WHERE e.data = ?
      $filtros
    ORDER BY FIELD(r.tipo, 'sopa','prato_principal','sobremesa'), r.calorias ASC
");
$stmt->bind_param($tipos, ...$params);
$stmt->execute();
$ementa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$sopas = array_values(array_filter($ementa, fn($r) => $r['tipo'] === 'sopa'));
$pratos = array_values(array_filter($ementa, fn($r) => $r['tipo'] === 'prato_principal'));
$sobremesas = array_values(array_filter($ementa, fn($r) => $r['tipo'] === 'sobremesa'));

//  Procurar marcação existente
$uid = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM marcacoes WHERE user_id = ? AND data = ?");
$stmt->bind_param("is", $uid, $data_sel);
$stmt->execute();
$marcacao = $stmt->get_result()->fetch_assoc();

//  Meta calórica
$stmt = $conn->prepare("SELECT meta_calorica FROM utilizadores WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$meta = $stmt->get_result()->fetch_assoc()['meta_calorica'] ?? null;

$dia_ant = $dia_sel > 1 ? $dia_sel - 1 : null;
$dia_seg = $dia_sel < 7 ? $dia_sel + 1 : null;

?>
<!DOCTYPE html>
<html lang="pt">
    <head>
        <title>Marcar Refeição - Quick Chef</title>
        <link rel="stylesheet" href="css/style-marcar.css">
    </head>
    <body class="body-dashboard">

        <?php include("includes/navbar.php"); ?>

        <div class="page-content">
            <div class="page-header">
                <h1 class="page-title"> Marcar Refeição</h1>
                <a href="dashboard.php" class="btn-secondary">← Voltar</a>
            </div>

            <?php if ($sucesso): ?>
                <p class="msg-sucesso"><?= htmlspecialchars($sucesso) ?></p>
            <?php endif; ?>
            <?php if ($erro): ?>
                <p class="msg-erro"><?= htmlspecialchars($erro) ?></p>
            <?php endif; ?>

            <!-- Navegação de dias -->
            <div class="dia-nav">
                <?php if ($dia_ant): ?>
                    <a href="marcar.php?dia=<?= $dia_ant ?>" class="dia-btn">&#8592;</a>
                <?php else: ?>
                    <span class="dia-btn dia-btn--disabled">&#8592;</span>
                <?php endif; ?>
                <span class="dia-label"><?= $dias_nomes[$dia_sel] ?> - <?= date('d/m', strtotime($data_sel)) ?></span>
                <?php if ($dia_seg): ?>
                    <a href="marcar.php?dia=<?= $dia_seg ?>" class="dia-btn">&#8594;</a>
                <?php else: ?>
                    <span class="dia-btn dia-btn--disabled">&#8594;</span>
                <?php endif; ?>
            </div>

            <?php if (empty($ementa)): ?>
                <div class="aviso-vazio"><p> Não há ementa disponível para este dia.</p></div>
            <?php else: ?>

                <form method="POST" action="marcar.php">
                    <input type="hidden" name="data" value="<?= $data_sel ?>">

                    <!-- Total kcal -->
                    <div class="total-kcal-card">
                        <span class="total-label">Total da refeição:</span>
                        <span class="total-valor" id="total-kcal">0 kcal</span>
                        <?php if ($meta): ?>
                            <span class="meta-label">Meta: <?= $meta ?> kcal</span>
                            <div class="meta-barra-wrap">
                                <div class="meta-barra-fill" id="meta-barra" style="width:0%"></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- SOPAS -->
                    <?php if (!empty($sopas)): ?>
                        <div class="grupo-card">
                            <div class="grupo-titulo"> Sopa <span class="grupo-hint">(opcional)</span></div>
                            <div class="grupo-opcoes">
                                <label class="opcao-item <?= empty($marcacao['sopa_id']) ? 'selecionada' : '' ?>">
                                    <input type="radio" name="sopa_id" value=""
                                    <?= empty($marcacao['sopa_id']) ? 'checked' : '' ?>
                                    onchange="calcTotal()">
                                    <span class="opcao-none">Sem sopa</span>
                                </label>
                                <?php foreach ($sopas as $s): ?>
                                    <label class="opcao-item <?= ($marcacao['sopa_id'] ?? null) == $s['id'] ? 'selecionada' : '' ?>">
                                        <input type="radio" name="sopa_id" value="<?= $s['id'] ?>"
                                        data-kcal="<?= $s['calorias'] ?>"
                                        <?= ($marcacao['sopa_id'] ?? null) == $s['id'] ? 'checked' : '' ?>
                                        onchange="calcTotal()">
                                        <span class="opcao-nome"><?= htmlspecialchars($s['nome']) ?></span>
                                        <span class="opcao-kcal"><?= $s['calorias'] ?> kcal</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- PRATOS PRINCIPAIS -->
                    <div class="grupo-card">
                        <div class="grupo-titulo"> Prato Principal <span class="grupo-hint">(Escolhe um prato ou a segunda dose de sopa)</span></div>
                        <div class="grupo-opcoes">
                            <label class="opcao-item <?= empty($marcacao['prato_id']) ? 'selecionada' : '' ?>">
                                <input type="radio" name="prato_id" value=""
                                <?= empty($marcacao['prato_id']) ? 'checked' : '' ?>
                                onchange="calcTotal(); toggleSegundaSopa()">
                                <span class="opcao-none">Sem prato / 2ª sopa</span>
                            </label>
                            <?php foreach ($pratos as $p): ?>
                                <label class="opcao-item <?= ($marcacao['prato_id'] ?? null) == $p['id'] ? 'selecionada' : '' ?>">
                                    <input type="radio" name="prato_id" value="<?= $p['id'] ?>"
                                    data-kcal="<?= $p['calorias'] ?>"
                                    <?= ($marcacao['prato_id'] ?? null) == $p['id'] ? 'checked' : '' ?>
                                    onchange="calcTotal(); toggleSegundaSopa()">
                                    <span class="opcao-nome"><?= htmlspecialchars($p['nome']) ?></span>
                                    <?php if ($p['vegetariano']): ?><span class="badge badge-vegetariano">V</span><?php endif; ?>
                                    <?php if ($p['vegan']): ?><span class="badge badge-vegan">VG</span><?php endif; ?>
                                    <?php if ($p['sem_gluten']): ?><span class="badge badge-sem-gluten">SG</span><?php endif; ?>
                                    <span class="opcao-kcal"><?= $p['calorias'] ?> kcal</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 2ª SOPA -->
                    <?php if (!empty($sopas)): ?>
                        <div class="grupo-card" id="grupo-segunda-sopa" style="display:<?= empty($marcacao['prato_id']) ? 'block' : 'none' ?>">
                            <div class="grupo-titulo"> 2ª Sopa <span class="grupo-hint">(só disponível sem prato principal)</span></div>
                            <div class="grupo-opcoes">
                                <label class="opcao-item selecionada">
                                    <input type="radio" name="segunda_sopa_id" value="" checked onchange="calcTotal()">
                                    <span class="opcao-none">Sem 2ª sopa</span>
                                </label>
                                <?php foreach ($sopas as $s): ?>
                                    <label class="opcao-item">
                                        <input type="radio" name="segunda_sopa_id" value="<?= $s['id'] ?>"
                                            data-kcal="<?= $s['calorias'] ?>"
                                            onchange="calcTotal()">
                                        <span class="opcao-nome"><?= htmlspecialchars($s['nome']) ?></span>
                                        <span class="opcao-kcal"><?= $s['calorias'] ?> kcal</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- SOBREMESAS -->
                    <?php if (!empty($sobremesas)): ?>
                        <div class="grupo-card">
                            <div class="grupo-titulo"> Sobremesa <span class="grupo-hint">(opcional)</span></div>
                            <div class="grupo-opcoes">
                                <label class="opcao-item <?= empty($marcacao['sobremesa_id']) ? 'selecionada' : '' ?>">
                                    <input type="radio" name="sobremesa_id" value=""
                                    <?= empty($marcacao['sobremesa_id']) ? 'checked' : '' ?>
                                    onchange="calcTotal()">
                                    <span class="opcao-none">Sem sobremesa</span>
                                </label>
                                <?php foreach ($sobremesas as $s): ?>
                                    <label class="opcao-item <?= ($marcacao['sobremesa_id'] ?? null) == $s['id'] ? 'selecionada' : '' ?>">
                                        <input type="radio" name="sobremesa_id" value="<?= $s['id'] ?>"
                                        data-kcal="<?= $s['calorias'] ?>"
                                        <?= ($marcacao['sobremesa_id'] ?? null) == $s['id'] ? 'checked' : '' ?>
                                        onchange="calcTotal()">
                                        <span class="opcao-nome"><?= htmlspecialchars($s['nome']) ?></span>
                                        <span class="opcao-kcal"><?= $s['calorias'] ?> kcal</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions-bottom">
                        <input type="submit" value=" Guardar marcação">
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <script>
            const META_KCAL = <?= $meta ? (int)$meta : 'null' ?>;

            function getCheckedKcal(name) {
                const el = document.querySelector(`input[name="${name}"]:checked`);
                return el ? parseFloat(el.dataset.kcal || 0) : 0;
            }

            function calcTotal() {
                const semPrato = !document.querySelector('input[name="prato_id"]:checked')?.value;
                const kcal = getCheckedKcal('sopa_id')
                + getCheckedKcal('prato_id')
                + (semPrato ? getCheckedKcal('segunda_sopa_id') : 0)
                + getCheckedKcal('sobremesa_id');

                document.getElementById('total-kcal').textContent = kcal.toFixed(0) + ' kcal';

                if (META_KCAL) {
                    const pct = Math.min((kcal / META_KCAL) * 100, 100);
                    const barra = document.getElementById('meta-barra');
                    if (barra) {
                        barra.style.width = pct + '%';
                        barra.style.background = pct > 100 ? '#dc2626' : pct > 80 ? '#854F0B' : '#2d5a1b';
                    }
                }

                document.querySelectorAll('.opcao-item').forEach(label => {
                    const input = label.querySelector('input');
                    label.classList.toggle('selecionada', input.checked && input.value !== '');
                });
            }

            function toggleSegundaSopa() {
                const temPrato = !!document.querySelector('input[name="prato_id"]:checked')?.value;
                const grupo = document.getElementById('grupo-segunda-sopa');
                if (grupo) {
                    grupo.style.display = temPrato ? 'none' : 'block';
                    if (temPrato) {
                        const sem = grupo.querySelector('input[value=""]');
                        if (sem) sem.checked = true;
                    }
                }
                calcTotal();
            }

            document.addEventListener('DOMContentLoaded', calcTotal);
        </script>

    </body>
</html>
