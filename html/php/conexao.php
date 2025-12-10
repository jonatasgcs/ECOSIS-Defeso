<?php
// Arquivo: php/conexao.php

// Define as credenciais do banco de dados (MUDE ESSES VALORES)
$servidor = "localhost";
$usuario = "root"; 
$senha = ""; 
$banco = "site_defeso";

// Inicia a sessão (CRUCIAL para gerenciar o estado de login)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cria a conexão
$conn = new mysqli($servidor, $usuario, $senha, $banco);

// Verifica a conexão
if ($conn->connect_error) {
    // Em caso de erro, mostre a mensagem de forma segura
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>