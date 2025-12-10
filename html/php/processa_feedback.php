<?php
// Define o nome do arquivo, que deve ser 'processa_feedback.php'

// 1. CONFIGURAÇÕES DO BANCO DE DADOS
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "site_defeso";

// 2. CONEXÃO COM O BANCO DE DADOS
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    $mensagem = urlencode("Erro de conexão com o banco de dados: " . $conn->connect_error);
    header("Location: ../contato.html?status=error&mensagem=" . $mensagem);
    exit();
}

// 3. COLETA E VALIDAÇÃO DOS DADOS DO FORMULÁRIO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Filtra e escapa as variáveis para segurança
    $nome = $conn->real_escape_string(trim($_POST['nome']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $mensagem_feedback = $conn->real_escape_string(trim($_POST['mensagem'])); // Evitei conflito de nome

    // 4. INSERÇÃO DOS DADOS NA TABELA 'feedbacks'
    $sql = "INSERT INTO feedbacks (nome, email, mensagem) VALUES (?, ?, ?)";
    
    // Prepara a query
    $stmt = $conn->prepare($sql);
    
    // Bind dos parâmetros
    $stmt->bind_param("sss", $nome, $email, $mensagem_feedback);

    // Executa a query
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        
        $mensagem = urlencode("Seu feedback foi enviado com sucesso! Agradecemos.");
        // Redireciona de volta para contato.html
        header("Location: ../contato.html?status=sucesso&mensagem=" . $mensagem);
        exit();
    } else {
        $mensagem = urlencode("Erro ao salvar o feedback: " . $stmt->error);
        header("Location: ../contato.html?status=error&mensagem=" . $mensagem);
        exit();
    }
} else {
    // Se o acesso não foi via POST, redireciona para a Home
    header("Location: ../../index.html");
    exit();
}
?>