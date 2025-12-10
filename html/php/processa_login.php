<?php
// Arquivo: php/processa_login.php

include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // 1. Busca o usuário
    $stmt = $conn->prepare("SELECT nome, senha_hash FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // 2. Verifica a senha
        if (password_verify($senha, $usuario['senha_hash'])) {
            
            // SUCESSO! Cria a variável de sessão 'logado' (SIMPLES E FUNCIONAL)
            $_SESSION['logado'] = true; 
            $_SESSION['user_email'] = $email; 
            
            // Redireciona para a página administrativa/inicial (Exemplo: admin_simulacoes)
           header("Location: ../../index.html");
            exit();
        }
    }
    
    // Falha no login
    header("Location: ../login.html?status=error&mensagem=" . urlencode("Email/Senha inválidos."));
    $stmt->close();
    $conn->close();
}
?>