<?php

// --- Configurações do banco de dados ---

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "site_defeso";

// --- Criar e Verificar Conexão com o Banco de Dados ---

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

//========================================================

// --- Receber so Dados do Formulário ---
// Coleta os dados enviados via método POST do formulário.
// prevenindo ataques de SQL Injection ao escapar caracteres especiais.
$nome = isset($_POST['nome']) ? $conn->real_escape_string($_POST['nome']) : '';
$local = isset($_POST['local']) ? $conn->real_escape_string($_POST['local']) : '';
$data = isset($_POST['data']) ? $conn->real_escape_string($_POST['data']) : '';
$descricao = isset($_POST['descricao']) ? $conn->real_escape_string($_POST['descricao']) : '';

// --- Validação Simples dos Campos Obrigatórios ---
// Realiza uma validação básica para garantir que os campos essenciais
if (empty($local) || empty($data) || empty($descricao)) {
    die("Campos obrigatórios não foram preenchidos.");
}

//=====================================================================

// --- Tratamento e Upload de Arquivo (Opcional) ---
// Inicializa a variável para o nome do arquivo.
// Verifica se um arquivo foi enviado
$arquivoNome = null;
if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == 0) {
    // Define o diretório de destino para os uploads e o cria se não existir.
    $pastaUpload = "uploads/";
    if (!is_dir($pastaUpload)) {
        mkdir($pastaUpload, 0755, true);
    }

    // Extrai informações do arquivo e gera um nome único para ele.
    $arquivoTmp = $_FILES['arquivo']['tmp_name'];
    $arquivoNomeOriginal = basename($_FILES['arquivo']['name']);
    $ext = strtolower(pathinfo($arquivoNomeOriginal, PATHINFO_EXTENSION));
    $arquivoNome = uniqid() . "." . $ext;
    $caminhoDestino = $pastaUpload . $arquivoNome;

    // Valida o tipo de arquivo, permitindo apenas extensões específicas.
    $tiposPermitidos = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi'];
    if (!in_array($ext, $tiposPermitidos)) {
        die("Tipo de arquivo não permitido.");
    }

    // Move o arquivo temporário para o diretório de uploads permanente.
    // Se a movimentação falhar, o script é interrompido.
    if (!move_uploaded_file($arquivoTmp, $caminhoDestino)) {
        die("Erro ao salvar o arquivo enviado.");
    }

//===============================================================================
}

// --- Inserir Dados no Banco de Dados---
// Prepara a instrução SQL para inserir os dados da denúncia na tabela 'denuncias'.
$sql = "INSERT INTO denuncias (nome, local, data, descricao, arquivo) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $nome, $local, $data, $descricao, $arquivoNome);


// --- Executar a Inserção e Redirecionar feedback---
// Tenta executar a instrução SQL preparada.
// Se a execução for bem-sucedida, o usuário é redirecionado para 'denuncia.html'
if ($stmt->execute()) {
    header("Location: html/denuncia.html?envio=sucesso");
    exit();
} else {
    header("Location: html/denuncia.html?envio=erro");
    echo "Erro ao salvar denúncia: " . $stmt->error;
    exit();
}

// --- Fechar Conexões ---
// Fecha o statement preparado e a conexão com o banco de dados para liberar recursos.
$stmt->close();
$conn->close();
?>
