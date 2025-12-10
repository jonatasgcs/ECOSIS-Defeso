<?php
// Configurações do banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "site_defeso";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    // Redireciona com mensagem de erro se a conexão falhar
    header("Location: direitos.html?status=erro&mensagem=" . urlencode("Falha na conexão com o banco de dados."));
    exit();
}

// Capturar os dados do formulário
// Adicionado isset para evitar notices caso algum campo não seja enviado
$nome = isset($_POST['nome']) ? $conn->real_escape_string($_POST['nome']) : '';
$email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
$q1 = isset($_POST['q1']) ? $_POST['q1'] : '';
$q2 = isset($_POST['q2']) ? $_POST['q2'] : '';
$q3 = isset($_POST['q3']) ? $_POST['q3'] : '';
$q4 = isset($_POST['q4']) ? $_POST['q4'] : '';
$q5 = isset($_POST['q5']) ? $_POST['q5'] : '';
$q6 = isset($_POST['q6']) ? $_POST['q6'] : '';

// Verificar se todas as respostas são "Sim"
$todas_sim = ($q1 === "Sim" && $q2 === "Sim" && $q3 === "Sim" && $q4 === "Sim" && $q5 === "Sim" && $q6 === "Sim");
$resultado = $todas_sim ? "Sim" : "Não"; // Alterado para "Sim" ou "Não" para consistência com o banco

// Mensagem para o popup
$mensagem_popup = "Obrigado, " . htmlspecialchars($nome) . "!";
if ($todas_sim) {
    $mensagem_popup .= " ✅ Você tem direito ao auxílio.";
} else {
    $mensagem_popup .= " ❌ Você não tem direito ao auxílio.";
}

// Inserir no banco apenas nome, email e resultado
$sql = "INSERT INTO simulacoes (nome, email, resultado) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

// Verifica se a preparação da query foi bem-sucedida
if ($stmt === false) {
    header("Location: direitos.html?status=erro&mensagem=" . urlencode("Erro na preparação da query: " . $conn->error));
    exit();
}

$stmt->bind_param("sss", $nome, $email, $resultado);

// Redireciona para a página direitos.html com a mensagem do popup
if ($stmt->execute()) {
    header("Location: direitos.html?status=sucesso&mensagem=" . urlencode($mensagem_popup));
    exit();
} else {
    header("Location: direitos.html?status=erro&mensagem=" . urlencode("Erro ao registrar simulação: " . $stmt->error));
    exit();
}

$stmt->close();
$conn->close();
?>
