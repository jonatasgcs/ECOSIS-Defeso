<?php
// Define o nome do arquivo, que deve ser 'processar_pesquisa.php'

// 1. CONFIGURAÇÕES DO BANCO DE DADOS
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "site_defeso";

// 2. CONEXÃO COM O BANCO DE DADOS
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    $mensagem = urlencode("Erro de conexão: " . $conn->connect_error);
    header("Location: ../educacao.html?status=error&mensagem=" . $mensagem);
    exit();
}

// 3. COLETA E VALIDAÇÃO DOS DADOS DO FORMULÁRIO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Filtra e escapa as variáveis para segurança
    $embarcacao = $conn->real_escape_string(trim($_POST['embarcacao']));
    $frequencia = $conn->real_escape_string(trim($_POST['frequencia']));
    $pretende_beneficio = $conn->real_escape_string(trim($_POST['pretende_beneficio']));
    $material = $conn->real_escape_string(trim($_POST['material']));
    $especies = $conn->real_escape_string(trim($_POST['especies']));
    $satisfacao = $conn->real_escape_string(trim($_POST['satisfacao']));

    // 4. INSERÇÃO DOS DADOS NA TABELA 'respostas_pesquisa'
    $sql = "INSERT INTO respostas_pesquisa (embarcacao, frequencia, pretende_beneficio, material, especies, satisfacao) VALUES (?, ?, ?, ?, ?, ?)";
    
    // Prepara a query
    $stmt = $conn->prepare($sql);
    
    // Bind dos parâmetros
    $stmt->bind_param("ssssss", $embarcacao, $frequencia, $pretende_beneficio, $material, $especies, $satisfacao);

    // Executa a query
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        
        $mensagem = urlencode("Obrigado! Sua pesquisa foi registrada com sucesso.");
        // Redireciona de volta para educacao.html
        header("Location: ../educacao.html?status=sucesso&mensagem=" . $mensagem);
        exit();
    } else {
        $mensagem = urlencode("Erro ao salvar a pesquisa: " . $stmt->error);
        header("Location: ../educacao.html?status=error&mensagem=" . $mensagem);
        exit();
    }
} else {
    // Se o acesso não foi via POST, redireciona para a Home
    header("Location: ../../index.html");
    exit();
}
?>