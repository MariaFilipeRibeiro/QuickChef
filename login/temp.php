<?php
// temp.php — Cria o utilizador admin de teste
require_once("../includes/ligacao.php");

$nome = 'Admin Cantina';
$email = 'admin@quickchef.pt';
$role = 'admin';
$senha = password_hash('123456', PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO utilizadores (nome, email, password_hash, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nome, $email, $senha, $role);

if ($stmt->execute()) {
    echo "Admin criado com sucesso! Email: admin@quickchef.pt | Password: 123456";
} else {
    echo "Erro: " . $conn->error;
}
?>
