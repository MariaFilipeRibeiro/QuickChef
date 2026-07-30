<?php
session_start();
require_once("../includes/ligacao.php");

// Determinar qual ação foi submetida (login ou registar)
$acao = $_POST['acao'] ?? '';

//  LOGIN 
if ($acao === 'login') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // Validar campos obrigatórios
    if (empty($email) || empty($senha)) {
        header("Location: login.php?erro=campos");
        exit();
    }

    // Usar prepared statement para evitar SQL Injection
    $stmt = $conn->prepare("SELECT id, nome, password_hash, role FROM utilizadores WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $dados = $resultado->fetch_assoc();

        // Verificar password com bcrypt (password_verify compara com o hash guardado)
        if (password_verify($senha, $dados['password_hash'])) {
            // Guardar dados do utilizador na sessão
            $_SESSION['user_id']   = $dados['id'];
            $_SESSION['user_nome'] = $dados['nome'];
            $_SESSION['user_role'] = $dados['role'];

            // Redirecionar para o dashboard após login bem-sucedido
            header("Location: ../dashboard.php");
            exit();
        }
    }

    // Credenciais incorretas
    header("Location: login.php?erro=credenciais");
    exit();
}

//  REGISTAR 
if ($acao === 'registar') {
    $nome      = trim($_POST['nome']      ?? '');
    $email     = trim($_POST['email']     ?? '');
    $senha     = $_POST['senha']          ?? '';
    $senha_conf = $_POST['senha_conf']    ?? '';

    // Validar campos obrigatórios
    if (empty($nome) || empty($email) || empty($senha)) {
        header("Location: login.php?erro=campos&tab=registar");
        exit();
    }

    // Validar confirmação de password e comprimento mínimo (6 caracteres)
    if ($senha !== $senha_conf || strlen($senha) < 6) {
        header("Location: login.php?erro=senha&tab=registar");
        exit();
    }

    // Verificar se o email já está registado
    $stmt = $conn->prepare("SELECT id FROM utilizadores WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: login.php?erro=email&tab=registar");
        exit();
    }

    // Criar hash segura da password com bcrypt
    $hash = password_hash($senha, PASSWORD_DEFAULT);

    // Inserir novo utilizador com role 'user' por defeito
    $stmt = $conn->prepare("INSERT INTO utilizadores (nome, email, password_hash, role) VALUES (?, ?, ?, 'user')");
    $stmt->bind_param("sss", $nome, $email, $hash);
    $stmt->execute();

    // Criar perfil alimentar vazio para o novo utilizador
    $novo_id = $conn->insert_id;
    $conn->query("INSERT INTO perfil_utilizador (user_id) VALUES ($novo_id)");

    // Iniciar sessão imediatamente após registo
    $_SESSION['user_id']   = $novo_id;
    $_SESSION['user_nome'] = $nome;
    $_SESSION['user_role'] = 'user';

    // Redirecionar para o dashboard
    header("Location: ../dashboard.php");
    exit();
}

// Se nenhuma ação válida, voltar ao login
header("Location: login.php");
exit();
?>
