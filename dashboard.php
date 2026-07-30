<?php
require_once("includes/auth.php");      // Verificar autenticação
require_once("includes/ligacao.php");   // Ligação à BD
$active_page = 'dashboard';

// Array com os nomes dos dias da semana (1=Segunda ... 7=Domingo)
$dias = [
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado',
    7 => 'Domingo'
];

// Determinar o dia atual (date('N') retorna 1=Segunda ... 7=Domingo)
$hoje    = (int)date('N');
// Usar o dia da URL ou o dia de hoje por defeito
$dia_sel = isset($_GET['dia']) && is_numeric($_GET['dia']) ? (int)$_GET['dia'] : $hoje;
// Garantir que o dia está entre 1 e 7
$dia_sel = min(max($dia_sel, 1), 7);
// Calcular dias anterior e seguinte para as setas de navegação
$dia_ant = $dia_sel > 1 ? $dia_sel - 1 : null;
$dia_seg = $dia_sel < 7 ? $dia_sel + 1 : null;

//  Procurar perfil do utilizador 
// Juntar dados da tabela utilizadores com perfil_utilizador
$stmt = $conn->prepare("
    SELECT u.meta_calorica,
           p.sem_gluten, p.sem_lactose, p.vegetariano, p.vegan
    FROM utilizadores u
    LEFT JOIN perfil_utilizador p ON p.user_id = u.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$perfil = $stmt->get_result()->fetch_assoc();

//  Verificar se é fim de semana ─
// Sábado (6) e Domingo (7) não têm ementa
$fim_de_semana = ($dia_sel >= 6);

//  Construir filtros dinâmicos com base no perfil 
$filtros = "";                                  // Cláusulas SQL adicionais
$params  = [$_SESSION['user_id'], $dia_sel];    // Parâmetros da query
$tipos   = "ii";                                // Tipos dos parâmetros (i=integer)

// Filtrar refeições sem glúten
if (!empty($perfil['sem_gluten'])) {
    $filtros .= " AND r.sem_gluten = 1";
}
// Filtrar refeições sem lactose
if (!empty($perfil['sem_lactose'])) {
    $filtros .= " AND r.sem_lactose = 1";
}
// Filtrar por dieta vegan (mais restritivo que vegetariano)
if (!empty($perfil['vegan'])) {
    $filtros .= " AND r.vegan = 1";
} elseif (!empty($perfil['vegetariano'])) {
    // Vegetariano aceita também pratos vegan
    $filtros .= " AND (r.vegetariano = 1 OR r.vegan = 1)";
}
// Filtrar pela meta calórica (mostrar só pratos com kcal <= meta)
if (!empty($perfil['meta_calorica'])) {
    $filtros .= " AND r.calorias <= ?";
    $params[] = $perfil['meta_calorica'];
    $tipos   .= "i";
}

//  Query principal: refeições da ementa do dia ─
$sql = "
    SELECT r.id, r.nome, r.tipo, r.descricao, r.calorias, r.proteinas,
           r.hidratos, r.lipidos, r.fibra,
           r.sem_gluten, r.sem_lactose, r.vegetariano, r.vegan,
           r.rating_media, r.rating_total,
           a.nota AS minha_nota  -- Avaliação do utilizador atual (NULL se não avaliou)
    FROM ementa_diaria e
    JOIN refeicoes r ON r.id = e.refeicao_id
    LEFT JOIN avaliacoes a ON a.refeicao_id = r.id AND a.user_id = ?
    WHERE e.dia_semana = ?
      AND WEEK(e.data, 1) = WEEK(CURDATE(), 1)   -- Semana atual (modo ISO)
      AND YEAR(e.data) = YEAR(CURDATE())           -- Ano atual
      $filtros
    ORDER BY FIELD(r.tipo, 'sopa', 'prato_principal', 'sobremesa'), r.calorias ASC
";

// Executar query apenas se não for fim de semana
if (!$fim_de_semana) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$params);  // Spread operator para parâmetros dinâmicos
    $stmt->execute();
    $refeicoes = $stmt->get_result();
} else {
    // Objeto anónimo que imita o resultado vazio
    $refeicoes = new class { public $num_rows = 0; public function fetch_assoc() { return null; } };
}

//  Verificar se há filtros ativos para mostrar aviso ─
$tem_filtros = !empty($perfil['sem_gluten'])   ||
               !empty($perfil['sem_lactose'])  ||
               !empty($perfil['vegetariano'])  ||
               !empty($perfil['vegan'])        ||
               !empty($perfil['meta_calorica']);

// Labels legíveis para os tipos de refeição
$tipo_labels = [
    'sopa'            => 'Sopa',
    'prato_principal' => 'Prato Principal',
    'sobremesa'       => 'Sobremesa',
];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <title>Ementa - Quick Chef</title>
    <link rel="stylesheet" href="css/style-dashboard.css">
</head>
<body class="body-dashboard">

    <?php include("includes/navbar.php"); ?>

    <div class="page-content">
        <div class="page-header">
            <h1 class="page-title">Ementa da Semana</h1>
            <!-- Botão que abre o marcar.php no mesmo dia que está a ser visualizado -->
            <a href="marcar.php?dia=<?= $dia_sel ?>" class="btn-marcar"> Marcar refeição</a>
        </div>

        <!-- Aviso de filtros ativos (só aparece se o utilizador tiver restrições) -->
        <?php if ($tem_filtros): ?>
        <div class="filtros-ativos">
            <span>A filtrar por:</span>
            <?php if (!empty($perfil['sem_gluten'])):  ?><span class="badge badge-sem-gluten">Sem Glúten</span><?php endif; ?>
            <?php if (!empty($perfil['sem_lactose'])): ?><span class="badge badge-sem-lactose">Sem Lactose</span><?php endif; ?>
            <?php if (!empty($perfil['vegetariano'])): ?><span class="badge badge-vegetariano">Vegetariano</span><?php endif; ?>
            <?php if (!empty($perfil['vegan'])):       ?><span class="badge badge-vegan">Vegan</span><?php endif; ?>
            <?php if (!empty($perfil['meta_calorica'])): ?>
                <span class="badge badge-calorias">≤ <?= $perfil['meta_calorica'] ?> kcal</span>
            <?php endif; ?>
            <a href="perfil.php" class="filtros-editar">Editar perfil</a>
        </div>
        <?php endif; ?>

        <!-- Navegação entre dias da semana -->
        <div class="dia-nav">
            <?php if ($dia_ant): ?>
                <a href="dashboard.php?dia=<?= $dia_ant ?>" class="dia-btn">&#8592;</a>
            <?php else: ?>
                <span class="dia-btn dia-btn--disabled">&#8592;</span>
            <?php endif; ?>

            <span class="dia-label"><?= $dias[$dia_sel] ?></span>

            <?php if ($dia_seg): ?>
                <a href="dashboard.php?dia=<?= $dia_seg ?>" class="dia-btn">&#8594;</a>
            <?php else: ?>
                <span class="dia-btn dia-btn--disabled">&#8594;</span>
            <?php endif; ?>
        </div>

        <!-- Mensagem para fim de semana -->
        <?php if ($fim_de_semana): ?>
            <div class="aviso-vazio aviso-weekend">
                <p>A cantina está encerrada ao fim de semana.</p>
                <p>A ementa estará disponível na próxima <a href="dashboard.php?dia=1">Segunda-feira</a>.</p>
            </div>

        <!-- Mensagem quando não há ementa disponível -->
        <?php elseif ($refeicoes->num_rows === 0): ?>
            <div class="aviso-vazio">
                <p>Não há ementa disponível para este dia.</p>
            </div>

        <!-- Lista de refeições -->
        <?php else: ?>
            <?php while ($r = $refeicoes->fetch_assoc()): ?>
            <!-- Card clicável que leva ao detalhe da refeição -->
            <a class="refeicao-card" href="refeicao.php?id=<?= $r['id'] ?>&dia=<?= $dia_sel ?>">

                <div class="refeicao-info">
                    <h3><?= htmlspecialchars($r['nome']) ?></h3>
                    <p><?= htmlspecialchars($r['descricao'] ?? '') ?></p>

                    <!-- Badges: tipo + restrições alimentares -->
                    <div>
                        <span class="badge badge-tipo">
                            <?= $tipo_labels[$r['tipo']] ?? $r['tipo'] ?>
                        </span>
                        <?php if ($r['sem_gluten']):  ?><span class="badge badge-sem-gluten">Sem Glúten</span><?php endif; ?>
                        <?php if ($r['sem_lactose']): ?><span class="badge badge-sem-lactose">Sem Lactose</span><?php endif; ?>
                        <?php if ($r['vegetariano']): ?><span class="badge badge-vegetariano">Vegetariano</span><?php endif; ?>
                        <?php if ($r['vegan']):       ?><span class="badge badge-vegan">Vegan</span><?php endif; ?>
                    </div>

                    <!-- Informação nutricional resumida -->
                    <div class="nutrientes">
                        <span class="nut-pill"> <?= $r['calorias'] ?> kcal</span>
                        <span class="nut-pill"> P: <?= $r['proteinas'] ?>g</span>
                        <span class="nut-pill"> HC: <?= $r['hidratos'] ?>g</span>
                        <span class="nut-pill"> L: <?= $r['lipidos'] ?>g</span>
                        <span class="nut-pill"> F: <?= $r['fibra'] ?>g</span>
                    </div>
                </div>

                <!-- Secção de avaliação com carinhas -->
                <div class="avaliar" onclick="event.preventDefault()">
                    <span>Avaliar</span>
                    <div class="carinhas">
                        <?php
                        // Array de avaliações: nota => emoji (ordem decrescente)
                        $carinhas = [3 => '😊', 2 => '😑', 1 => '😡'];
                        foreach ($carinhas as $nota => $emoji):
                            $sel = ($r['minha_nota'] == $nota) ? 'selected' : '';
                            $dis = $r['minha_nota'] ? 'disabled' : '';  // Desativar se já avaliou
                        ?>
                        <button class="carinha-btn <?= $sel ?>" <?= $dis ?>
                                onclick="avaliar(<?= $r['id'] ?>, <?= $nota ?>, this)">
                            <?= $emoji ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <!-- Mostrar média de avaliações se existirem -->
                    <?php if ($r['rating_total'] > 0): ?>
                        <span> <?= number_format($r['rating_media'], 1) ?> (<?= $r['rating_total'] ?>)</span>
                    <?php endif; ?>
                </div>

            </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <script>
    // Função para enviar avaliação via AJAX sem recarregar a página
    function avaliar(refeicao_id, nota, btn) {
        // Desativar todos os botões para evitar dupla avaliação
        const carinhas = btn.closest('.carinhas').querySelectorAll('.carinha-btn');
        carinhas.forEach(b => b.disabled = true);
        btn.classList.add('selected');

        // Enviar avaliação para o servidor
        fetch('/25161/quickchefcodigo/api/avaliar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({refeicao_id, nota})
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                // Se falhou, reativar botões
                carinhas.forEach(b => b.disabled = false);
                btn.classList.remove('selected');
                alert('Erro ao guardar avaliação.');
            }
        });
    }
    </script>

</body>
</html>
