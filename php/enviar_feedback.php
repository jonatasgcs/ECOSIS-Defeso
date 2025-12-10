<?php
header('Content-Type: application/json'); // Define o cabeçalho para indicar que a resposta é JSON

// Dados de conexão com o banco de dados
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "site_defeso";

// Cria uma nova conexão com o banco
$conn = new mysqli($servidor, $usuario, $senha, $banco);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    echo json_encode(['status' => 'erro', 'message' => 'Erro de conexão com o banco de dados: ' . $conn->connect_error]);
    exit(); // Encerra o script se houver erro
}

// Recebe os dados enviados pelo formulário (via método POST)
// Adicionado isset e real_escape_string para segurança e evitar notices
$nome = isset($_POST['nome']) ? $conn->real_escape_string($_POST['nome']) : '';
$email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
$mensagem = isset($_POST['mensagem']) ? $conn->real_escape_string($_POST['mensagem']) : '';

// Comando SQL com placeholders (?) para segurança (evita SQL Injection)
$sql = "INSERT INTO feedbacks (nome, email, mensagem) VALUES (?, ?, ?)";

// Prepara o comando SQL
$stmt = $conn->prepare($sql);

// Verifica se a preparação da query foi bem-sucedida
if ($stmt === false) {
    echo json_encode(['status' => 'erro', 'message' => 'Erro na preparação da query: ' . $conn->error]);
    exit();
}

// Associa os valores aos placeholders: s = string
$stmt->bind_param("sss", $nome, $email, $mensagem);

// Executa o comando
if ($stmt->execute()) {
    echo json_encode(['status' => 'sucesso', 'message' => 'Obrigado pelo seu feedback!']); // Sucesso
} else {
    echo json_encode(['status' => 'erro', 'message' => 'Erro ao enviar feedback: ' . $stmt->error]); // Exibe erro, se houver
}

// Fecha a conexão com o banco
$stmt->close();
$conn->close();
?>
