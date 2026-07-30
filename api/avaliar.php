<?php
//  API: Guardar avaliação de uma refeição 
// Endpoint chamado via AJAX pelo dashboard e refeicao.php
require_once("../includes/auth.php");      // Verificar autenticação
require_once("../includes/ligacao.php");   // Ligação à BD

// Resposta sempre em JSON
header('Content-Type: application/json');

// Ler dados enviados no corpo da requisição (JSON)
$dados       = json_decode(file_get_contents('php://input'), true);
$refeicao_id = isset($dados['refeicao_id']) ? (int)$dados['refeicao_id'] : 0;
$nota        = isset($dados['nota'])        ? (int)$dados['nota']        : 0;

// Validar dados recebidos
// nota válida: 1=Mau, 2=Ok, 3=Bom
if ($refeicao_id <= 0 || $nota < 1 || $nota > 3) {
    echo json_encode(['ok' => false, 'erro' => 'Dados inválidos.']);
    exit();
}

$uid = $_SESSION['user_id'];

//  Verificar se o utilizador marcou esta refeição 
// Só é permitido avaliar refeições que o utilizador tenha marcado
$stmt = $conn->prepare("
    SELECT m.id FROM marcacoes m
    WHERE m.user_id = ?
      AND (m.sopa_id = ? OR m.prato_id = ? OR m.sobremesa_id = ?)
");
$stmt->bind_param("iiii", $uid, $refeicao_id, $refeicao_id, $refeicao_id);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    // Utilizador não marcou esta refeição, não pode avaliar
    echo json_encode(['ok' => false, 'erro' => 'Só podes avaliar refeições que marcaste.']);
    exit();
}

//  Guardar avaliação 
// ON DUPLICATE KEY UPDATE permite atualizar uma avaliação existente
$stmt = $conn->prepare("
    INSERT INTO avaliacoes (refeicao_id, user_id, nota)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE nota = VALUES(nota)
");
$stmt->bind_param("iii", $refeicao_id, $uid, $nota);

if ($stmt->execute()) {
    // Os triggers na BD atualizam automaticamente rating_media e rating_total
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'erro' => 'Erro ao guardar.']);
}
?>
