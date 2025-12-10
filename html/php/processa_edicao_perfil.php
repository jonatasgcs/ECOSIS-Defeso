<?php
// ARQUIVO: php/processa_edicao_perfil.php

include 'conexao.php'; 

// PORTÃO DE SEGURANÇA
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../login.php?status=error&mensagem=" . urlencode("Acesso inválido."));
    exit();
}

$user_id = $_POST['user_id'] ?? 0;
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha_nova = $_POST['senha_nova'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';

$caminho_retorno = "../perfil.php"; // Página de destino após o processamento

// 1. Validação Simples
if (empty($nome) || empty($email) || $user_id == 0) {
    header("Location: {$caminho_retorno}?status=error&mensagem=" . urlencode("Nome e Email são obrigatórios."));
    exit();
}

// 2. Verifica a consistência da nova senha
if ($senha_nova != $confirmar_senha) {
    header("Location: {$caminho_retorno}?status=error&mensagem=" . urlencode("As senhas não coincidem."));
    exit();
}

// 3. Verifica se o novo e-mail já existe (se o e-mail foi alterado)
$email_atual = $_SESSION['user_email'];

if ($email !== $email_atual) {
    $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        header("Location: {$caminho_retorno}?status=error&mensagem=" . urlencode("Este novo email já está sendo usado por outra conta."));
        exit();
    }
    $stmt->close();
}


// 4. Constrói a Query de Atualização
$updates = [];
$params = [];
$types = "";

// A) Atualizar Nome e Email (Sempre)
$updates[] = "nome = ?";
$params[] = $nome;
$types .= "s";

$updates[] = "email = ?";
$params[] = $email;
$types .= "s";


// B) Atualizar Senha (Se for fornecida)
if (!empty($senha_nova)) {
    $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
    $updates[] = "senha_hash = ?";
    $params[] = $senha_hash;
    $types .= "s";
}

// 5. Executa a Atualização
$sql = "UPDATE usuarios SET " . implode(', ', $updates) . " WHERE id_usuario = ?";

// Adiciona o user_id e o tipo 'i' ao final do array de parâmetros e tipos
$params[] = $user_id;
$types .= "i";

$stmt = $conn->prepare($sql);

// O mysqli_stmt_bind_param exige que os parâmetros sejam passados por referência.
// Usamos call_user_func_array para construir a chamada de forma dinâmica.
call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));


if ($stmt->execute()) {
    // Atualiza a sessão com o novo email (se alterado)
    $_SESSION['user_email'] = $email;
    
    header("Location: {$caminho_retorno}?status=success&mensagem=" . urlencode("Perfil atualizado com sucesso!"));
    exit();
} else {
    header("Location: {$caminho_retorno}?status=error&mensagem=" . urlencode("Erro ao atualizar no banco de dados."));
    exit();
}
$stmt->close();
$conn->close();
?>