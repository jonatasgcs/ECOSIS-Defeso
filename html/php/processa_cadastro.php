<?php
// Arquivo: php/processa_cadastro.php

include 'conexao.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // 1. Criptografa a senha para segurança (MESMO SIMPLES, É MELHOR)
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // 2. Verifica se o e-mail já existe
    $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        header("Location: ../cadastro.html?status=error&mensagem=" . urlencode("Email já cadastrado."));
        exit();
    }
    
    $stmt->close();
    
    // 3. Insere o novo usuário
    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nome, $email, $senha_hash);

    if ($stmt->execute()) {
        header("Location: ../login.html?status=success&mensagem=" . urlencode("Cadastro OK! Faça login."));
    } else {
        header("Location: ../cadastro.html?status=error&mensagem=" . urlencode("Falha no DB."));
    }

    $stmt->close();
    $conn->close();
}
?>