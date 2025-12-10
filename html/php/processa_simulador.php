<?php
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
    header("Location: ../direitos.html?status=error&mensagem=" . $mensagem);
    exit();
}

// 3. COLETA E VALIDAÇÃO DOS DADOS DO FORMULÁRIO (Simulador)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $conn->real_escape_string(trim($_POST['nome']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    
    // Coletar respostas e definir o resultado da simulação
    $q1 = isset($_POST['q1']) && $_POST['q1'] === 'Sim' ? 'Sim' : 'Não';
    $q2 = isset($_POST['q2']) && $_POST['q2'] === 'Sim' ? 'Sim' : 'Não';
    $q3 = isset($_POST['q3']) && $_POST['q3'] === 'Sim' ? 'Sim' : 'Não';
    $q4 = isset($_POST['q4']) && $_POST['q4'] === 'Sim' ? 'Sim' : 'Não';
    $q5 = isset($_POST['q5']) && $_POST['q5'] === 'Sim' ? 'Sim' : 'Não';
    $q6 = isset($_POST['q6']) && $_POST['q6'] === 'Sim' ? 'Sim' : 'Não';

    // Lógica da Simulação
    if ($q1 == 'Sim' && $q2 == 'Sim' && $q3 == 'Sim' && $q4 == 'Sim' && $q5 == 'Sim' && $q6 == 'Sim') {
        $resultado = "Provável Benefício";
    } else {
        $resultado = "Risco de Negação";
    }

    // 4. INSERÇÃO DOS DADOS NA TABELA 'simulacoes'
    $sql = "INSERT INTO simulacoes (nome, email, resultado) VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nome, $email, $resultado);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        
        $mensagem = urlencode("Simulação concluída com sucesso! Resultado: " . $resultado);
        header("Location: ../direitos.html?status=sucesso&mensagem=" . $mensagem);
        exit();
    } else {
        $mensagem = urlencode("Erro ao salvar a simulação: " . $stmt->error);
        header("Location: ../direitos.html?status=error&mensagem=" . $mensagem);
        exit();
    }
} else {
    // Redireciona se o acesso não foi via POST
    header("Location: ../../index.html");
    exit();
}
?>