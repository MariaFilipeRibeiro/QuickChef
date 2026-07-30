<?php
require_once("includes/auth.php");     // Verificar autenticação
require_once("includes/ligacao.php"); // Ligação à BD
$active_page = 'perfil';

$sucesso = '';

//  Procurar dados do utilizador e perfil alimentar ─
// LEFT JOIN garante que mesmo sem perfil_utilizador a query não falha
$stmt = $conn->prepare("
SELECT u.id, u.nome, u.email, u.role, u.meta_calorica,
p.sem_gluten, p.sem_lactose, p.vegetariano, p.vegan
FROM utilizadores u
LEFT JOIN perfil_utilizador p ON p.user_id = u.id
WHERE u.id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

//  Criar perfil vazio se não existir ─
// Acontece quando o utilizador ainda não tem registo em perfil_utilizador
if ($u && is_null($u['sem_gluten'])) {
    $uid = $_SESSION['user_id'];
    $conn->query("INSERT IGNORE INTO perfil_utilizador (user_id) VALUES ($uid)");
    // Inicializar valores por defeito
    $u['sem_gluten']  = 0;
    $u['sem_lactose'] = 0;
    $u['vegetariano'] = 0;
    $u['vegan']       = 0;
}

//  Guardar alterações do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meta_calorica = (int)($_POST['meta_calorica'] ?? 700);
    $sem_gluten    = isset($_POST['sem_gluten'])  ? 1 : 0;
    $sem_lactose   = isset($_POST['sem_lactose']) ? 1 : 0;
    $vegetariano   = isset($_POST['vegetariano']) ? 1 : 0;
    $vegan         = isset($_POST['vegan'])       ? 1 : 0;

    // Atualizar meta calórica na tabela utilizadores
    $stmt = $conn->prepare("UPDATE utilizadores SET meta_calorica = ? WHERE id = ?");
    $stmt->bind_param("ii", $meta_calorica, $_SESSION['user_id']);
    $stmt->execute();

    // Inserir ou atualizar perfil alimentar
    // ON DUPLICATE KEY UPDATE: se já existe registo, atualiza; senão insere
    $stmt = $conn->prepare("
    INSERT INTO perfil_utilizador (user_id, sem_gluten, sem_lactose, vegetariano, vegan)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
    sem_gluten  = VALUES(sem_gluten),
    sem_lactose = VALUES(sem_lactose),
    vegetariano = VALUES(vegetariano),
    vegan       = VALUES(vegan)
    ");
    $stmt->bind_param("iiiii", $_SESSION['user_id'], $sem_gluten, $sem_lactose, $vegetariano, $vegan);
    $stmt->execute();

    $sucesso = 'Perfil atualizado com sucesso.';

    // Recarregar dados para mostrar valores atualizados
    $stmt = $conn->prepare("
    SELECT u.id, u.nome, u.email, u.role, u.meta_calorica,
    p.sem_gluten, p.sem_lactose, p.vegetariano, p.vegan
    FROM utilizadores u
    LEFT JOIN perfil_utilizador p ON p.user_id = u.id
    WHERE u.id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
}

//  Gerar iniciais para o avatar
// Protegido contra NULL caso os dados do utilizador falhem
$nome     = $u['nome'] ?? '';
$partes   = explode(' ', trim($nome));
$iniciais = strtoupper(substr($partes[0] ?? '', 0, 1));
if (count($partes) >= 2) $iniciais .= strtoupper(substr($partes[1], 0, 1));

?>
<!DOCTYPE html>
<html lang="pt">
    <head>
        <title>Perfil - Quick Chef</title>
        <link rel="stylesheet" href="css/style-perfil.css">
    </head>
    <body class="body-dashboard">

        <?php include("includes/navbar.php"); ?>

        <div class="page-content">
            <div class="page-header">
                <h1 class="page-title">O meu perfil</h1>
                <a href="dashboard.php" class="btn-secondary">← Voltar à ementa</a>
            </div>

            <?php if ($sucesso): ?>
                <p class="msg-sucesso"><?= htmlspecialchars($sucesso) ?></p>
            <?php endif; ?>

            <form method="POST" action="perfil.php">

                <!-- Avatar com iniciais + informação básica do utilizador -->
                <div class="perfil-topo">
                    <div class="perfil-avatar"><?= $iniciais ?></div>
                    <div class="perfil-info">
                        <div class="perfil-nome"><?= htmlspecialchars($u['nome'] ?? '') ?></div>
                        <div class="perfil-email"><?= htmlspecialchars($u['email'] ?? '') ?></div>
                        <div class="perfil-role">
                            <span class="badge <?= ($u['role'] ?? '') === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                <?= ($u['role'] ?? '') === 'admin' ? 'Administrador' : 'Colaborador' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Secção: meta calórica diária  -->
                <div class="perfil-secao">
                    <h3>Objetivos</h3>
                    <div class="toggle-item">
                        <label for="meta_calorica">Meta calórica do almoço (kcal)</label>
                        <!-- disabled: bloqueado até clicar "Editar perfil" -->
                        <input type="number" id="meta_calorica" name="meta_calorica"
                            value="<?= $u['meta_calorica'] ?? 700 ?>"
                            min="100" max="3000" step="50" disabled>
                    </div>
                </div>

                <!-- Secção: restrições alimentares  -->
                <div class="perfil-secao">
                    <h3>Restrições alimentares</h3>
                    <div class="toggle-item">
                        <label for="sem_gluten">Sem glúten</label>
                        <input type="checkbox" id="sem_gluten" name="sem_gluten"
                        <?= !empty($u['sem_gluten']) ? 'checked' : '' ?> disabled>
                    </div>
                    <div class="toggle-item">
                        <label for="sem_lactose">Sem lactose</label>
                        <input type="checkbox" id="sem_lactose" name="sem_lactose"
                        <?= !empty($u['sem_lactose']) ? 'checked' : '' ?> disabled>
                    </div>
                    <div class="toggle-item">
                        <label for="vegetariano">Vegetariano</label>
                        <input type="checkbox" id="vegetariano" name="vegetariano"
                        <?= !empty($u['vegetariano']) ? 'checked' : '' ?> disabled>
                    </div>
                    <div class="toggle-item">
                        <label for="vegan">Vegan</label>
                        <input type="checkbox" id="vegan" name="vegan"
                        <?= !empty($u['vegan']) ? 'checked' : '' ?> disabled>
                    </div>
                </div>

                <!-- Botões de ação -->
                <div class="perfil-acoes">
                    <!-- Botão inicial: abre modo de edição -->
                    <button type="button" id="btn-editar" onclick="ativarEdicao()">Editar perfil</button>
                    <!-- Guardar: só visível no modo edição -->
                    <input type="submit" id="btn-guardar" value="Guardar alterações">
                    <!-- Cancelar: recarrega a página descartando alterações -->
                    <button type="button" id="btn-cancelar" onclick="cancelarEdicao()">Cancelar</button>
                </div>

            </form>
        </div>

        <script>
            // Ativar modo de edição: desbloquear inputs e mostrar botões guardar/cancelar
            function ativarEdicao() {
                document.querySelectorAll('.perfil-secao input').forEach(i => i.disabled = false);
                document.getElementById('btn-editar').style.display   = 'none';
                document.getElementById('btn-guardar').style.display  = 'inline-block';
                document.getElementById('btn-cancelar').style.display = 'inline-block';
            }

            // Cancelar edição: recarregar a página para repor os valores originais
            function cancelarEdicao() {
                window.location.reload();
            }
        </script>

    </body>
</html>

