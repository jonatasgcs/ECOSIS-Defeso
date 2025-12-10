<?php
// Configurações do banco de dados
$host = "localhost";
$dbname = "site_defeso";
$username = "root";
$password = "";

// Conectar ao banco de dados
$conn = new mysqli($host, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    // Redireciona com mensagem de erro se a conexão falhar
    header("Location: educacao.html?status=erro&mensagem=" . urlencode("Falha na conexão com o banco de dados."));
    exit();
}

// Obter os dados do formulário
// Adicionado isset para evitar notices caso algum campo não seja enviado e real_escape_string para segurança
$embarcacao = isset($_POST['embarcacao']) ? $conn->real_escape_string($_POST['embarcacao']) : '';
$frequencia = isset($_POST['frequencia']) ? $conn->real_escape_string($_POST['frequencia']) : '';
$pretende_beneficio = isset($_POST['pretende_beneficio']) ? $conn->real_escape_string($_POST['pretende_beneficio']) : '';
$material = isset($_POST['material']) ? $conn->real_escape_string($_POST['material']) : '';
$especies = isset($_POST['especies']) ? $conn->real_escape_string($_POST['especies']) : '';
$satisfacao = isset($_POST['satisfacao']) ? $conn->real_escape_string($_POST['satisfacao']) : '';

// Inserir no banco
$sql = "INSERT INTO respostas_pesquisa (embarcacao, frequencia, pretende_beneficio, material, especies, satisfacao)
VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

// Verifica se a preparação da query foi bem-sucedida
if ($stmt === false) {
    header("Location: educacao.html?status=erro&mensagem=" . urlencode("Erro na preparação da query: " . $conn->error));
    exit();
}

$stmt->bind_param("ssssss", $embarcacao, $frequencia, $pretende_beneficio, $material, $especies, $satisfacao);

if ($stmt->execute()) {
    // Redireciona com mensagem de sucesso
    header("Location: educacao.html?status=sucesso&mensagem=" . urlencode("Suas respostas foram enviadas com sucesso!"));
} else {
    // Redireciona com mensagem de erro
    header("Location: educacao.html?status=erro&mensagem=" . urlencode("Erro ao enviar respostas: " . $stmt->error));
}

$stmt->close();
$conn->close();
?>
