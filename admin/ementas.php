<?php
require_once("../includes/auth_admin.php");   // Só admins podem aceder
require_once("../includes/ligacao.php");
$active_page = 'admin';

$erro    = '';
$sucesso = '';

//  Apagar refeição 
// Ativado pelo link ?apagar=ID na tabela
if (isset($_GET['apagar']) && is_numeric($_GET['apagar'])) {
    $id_apagar = (int)$_GET['apagar'];
    $stmt = $conn->prepare("DELETE FROM refeicoes WHERE id = ?");
    $stmt->bind_param("i", $id_apagar);
    $stmt->execute();
    // Redirecionar com mensagem de sucesso
    header("Location: ementas.php?msg=apagado");
    exit();
}

//  ADICIONAR / EDITAR refeição 
// Ativado pelo submit do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se id > 0 é edição, se id = 0 é inserção nova
    $id = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : 0;

    // Recolher dados do formulário
    $nome         = trim($_POST['nome']         ?? '');
    $tipo         = $_POST['tipo']              ?? '';
    $descricao    = trim($_POST['descricao']    ?? '');
    $ingredientes = trim($_POST['ingredientes'] ?? '');

    // Guardar valores numéricos em variáveis separadas
    // (bind_param não aceita casts inline em PHP 8.3+)
    $cal = (float)($_POST['calorias']  ?? 0);
    $pro = (float)($_POST['proteinas'] ?? 0);
    $hid = (float)($_POST['hidratos']  ?? 0);
    $lip = (float)($_POST['lipidos']   ?? 0);
    $fib = (float)($_POST['fibra']     ?? 0);

    // Checkboxes: 1 se marcado, 0 se não
    $sg  = isset($_POST['sem_gluten'])  ? 1 : 0;
    $sl  = isset($_POST['sem_lactose']) ? 1 : 0;
    $veg = isset($_POST['vegetariano']) ? 1 : 0;
    $vgn = isset($_POST['vegan'])       ? 1 : 0;

    // Validar campos obrigatórios
    if (empty($nome) || empty($tipo)) {
        $erro = 'Nome e tipo são obrigatórios.';
    } else {
        if ($id > 0) {
            //  Editar refeição existente
            // 14 parâmetros: 4 strings + 5 decimais + 4 inteiros + 1 inteiro (id)
            $stmt = $conn->prepare("
                UPDATE refeicoes
                SET nome=?, tipo=?, descricao=?, ingredientes=?,
                    calorias=?, proteinas=?, hidratos=?, lipidos=?, fibra=?,
                    sem_gluten=?, sem_lactose=?, vegetariano=?, vegan=?
                WHERE id=?
            ");
            $stmt->bind_param("ssssdddddiiiii",
                $nome, $tipo, $descricao, $ingredientes,
                $cal, $pro, $hid, $lip, $fib,
                $sg, $sl, $veg, $vgn,
                $id
            );
        } else {
            //  Inserir nova refeição
            // 13 parâmetros: 4 strings + 5 decimais + 4 inteiros
            $stmt = $conn->prepare("
                INSERT INTO refeicoes
                    (nome, tipo, descricao, ingredientes,
                     calorias, proteinas, hidratos, lipidos, fibra,
                     sem_gluten, sem_lactose, vegetariano, vegan)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param("ssssdddddiiii",
                $nome, $tipo, $descricao, $ingredientes,
                $cal, $pro, $hid, $lip, $fib,
                $sg, $sl, $veg, $vgn
            );
        }

        if ($stmt->execute()) {
            $sucesso = $id > 0 ? 'Refeição atualizada com sucesso.' : 'Refeição adicionada com sucesso.';
        } else {
            $erro = 'Erro ao guardar: ' . $conn->error;
        }
    }
}

//  Carregar refeição para editar ─
// Ativado pelo link ?editar=ID na tabela
$editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM refeicoes WHERE id = ?");
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

//  Listar todas as refeições 
$refeicoes = $conn->query("
    SELECT id, nome, tipo, calorias, sem_gluten, sem_lactose, vegetariano, vegan
    FROM refeicoes
    ORDER BY tipo, nome
");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <title>Ementas - Quick Chef Admin</title>
    <link rel="stylesheet" href="../css/style-admin-ementa.css">
</head>
<body class="body-dashboard">

    <?php include("../includes/navbar.php"); ?>

    <div class="page-content">
        <div class="page-header">
            <h1 class="page-title">Ementas</h1>
            <a href="index.php" class="btn-secondary">← Voltar</a>
        </div>

        <!-- Mensagens de erro e sucesso -->
        <?php if ($erro):    ?><p class="msg-erro"><?= htmlspecialchars($erro) ?></p><?php endif; ?>
        <?php if ($sucesso): ?><p class="msg-sucesso"><?= htmlspecialchars($sucesso) ?></p><?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'apagado'): ?>
            <p class="msg-sucesso">Refeição apagada com sucesso.</p>
        <?php endif; ?>

        <!-- Formulário: muda de título e comportamento conforme adicionar ou editar -->
        <div class="form-card">
            <h2><?= $editar ? 'Editar Refeição' : 'Nova Refeição' ?></h2>
            <form method="POST" action="ementas.php">
                <!-- Campo oculto com o ID (só presente no modo edição) -->
                <?php if ($editar): ?>
                    <input type="hidden" name="id" value="<?= $editar['id'] ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" name="nome" required
                               value="<?= htmlspecialchars($editar['nome'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="tipo" required>
                            <?php
                            // Apenas 3 tipos válidos conforme o ENUM da BD
                            $tipos  = ['sopa','prato_principal','sobremesa'];
                            $labels = ['Sopa','Prato Principal','Sobremesa'];
                            foreach ($tipos as $i => $t):
                                $sel = ($editar['tipo'] ?? '') === $t ? 'selected' : '';
                            ?>
                            <option value="<?= $t ?>" <?= $sel ?>><?= $labels[$i] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descrição</label>
                    <input type="text" name="descricao"
                           value="<?= htmlspecialchars($editar['descricao'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Ingredientes</label>
                    <input type="text" name="ingredientes"
                           value="<?= htmlspecialchars($editar['ingredientes'] ?? '') ?>">
                </div>

                <!-- Campos nutricionais: step="0.01" permite decimais -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Calorias (kcal)</label>
                        <input type="number" step="0.01" name="calorias"
                               value="<?= $editar['calorias'] ?? 0 ?>">
                    </div>
                    <div class="form-group">
                        <label>Proteínas (g)</label>
                        <input type="number" step="0.01" name="proteinas"
                               value="<?= $editar['proteinas'] ?? 0 ?>">
                    </div>
                    <div class="form-group">
                        <label>Hidratos (g)</label>
                        <input type="number" step="0.01" name="hidratos"
                               value="<?= $editar['hidratos'] ?? 0 ?>">
                    </div>
                    <div class="form-group">
                        <label>Lípidos (g)</label>
                        <input type="number" step="0.01" name="lipidos"
                               value="<?= $editar['lipidos'] ?? 0 ?>">
                    </div>
                    <div class="form-group">
                        <label>Fibra (g)</label>
                        <input type="number" step="0.01" name="fibra"
                               value="<?= $editar['fibra'] ?? 0 ?>">
                    </div>
                </div>

                <!-- Checkboxes para restrições alimentares -->
                <div class="form-checkboxes">
                    <label class="checkbox-label">
                        <input type="checkbox" name="sem_gluten"
                               <?= !empty($editar['sem_gluten']) ? 'checked' : '' ?>>
                        Sem Glúten
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="sem_lactose"
                               <?= !empty($editar['sem_lactose']) ? 'checked' : '' ?>>
                        Sem Lactose
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="vegetariano"
                               <?= !empty($editar['vegetariano']) ? 'checked' : '' ?>>
                        Vegetariano
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="vegan"
                               <?= !empty($editar['vegan']) ? 'checked' : '' ?>>
                        Vegan
                    </label>
                </div>

                <div class="form-actions">
                    <input type="submit" value="<?= $editar ? 'Guardar alterações' : 'Adicionar refeição' ?>">
                    <?php if ($editar): ?>
                        <!-- Cancelar edição volta ao formulário vazio -->
                        <a href="ementas.php" class="btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabela com todas as refeições existentes -->
        <h2 class="section-title">Refeições existentes</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Calorias</th>
                    <th>Dietética</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($r = $refeicoes->fetch_assoc()): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= htmlspecialchars($r['nome']) ?></td>
                    <td>
                        <span class="badge badge-<?= $r['tipo'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $r['tipo'])) ?>
                        </span>
                    </td>
                    <td><?= $r['calorias'] ?> kcal</td>
                    <td>
                        <!-- Badges de restrições alimentares baseados nos booleans -->
                        <?php if ($r['sem_gluten']):  ?><span class="badge badge-sem-gluten">SG</span><?php endif; ?>
                        <?php if ($r['sem_lactose']): ?><span class="badge badge-sem-lactose">SL</span><?php endif; ?>
                        <?php if ($r['vegetariano']): ?><span class="badge badge-vegetariano">V</span><?php endif; ?>
                        <?php if ($r['vegan']):       ?><span class="badge badge-vegan">VG</span><?php endif; ?>
                    </td>
                    <td class="td-acoes">
                        <!-- Link editar: carrega dados no formulário acima -->
                        <a href="ementas.php?editar=<?= $r['id'] ?>" class="btn-editar">Editar</a>
                        <!-- Link apagar: confirmação no browser antes de apagar -->
                        <a href="ementas.php?apagar=<?= $r['id'] ?>"
                           class="btn-apagar"
                           onclick="return confirm('Apagar esta refeição?')">Apagar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
