<?php
// Inicia a sessão PHP. Essencial para gerenciar o estado de login do administrador.
session_start();

// Credenciais de login do administrador. Em um ambiente de produção, estas deveriam ser armazenadas de forma mais segura (ex: variáveis de ambiente, arquivo de configuração fora do diretório web, senhas hashadas).
$adminUser = "admin";
$adminPass = "12345";

// Variáveis para a mensagem do popup (sucesso/erro).
$status_type = null;
$status_message = null;

// === CONEXÃO COM O BANCO DE DADOS ===
// Define os parâmetros de conexão.
$servername = "localhost"; // Endereço do servidor do banco de dados.
$username_db = "root";     // Nome de usuário do banco de dados.
$password_db = "";         // Senha do banco de dados.
$dbname = "site_defeso";   // Nome do banco de dados.

// Cria uma nova conexão MySQLi.
$conn = new mysqli($servername, $username_db, $password_db, $dbname);
// Verifica se a conexão falhou. Se sim, interrompe o script e exibe o erro.
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// === LÓGICA DE LOGOUT ===
// Verifica se o parâmetro 'sair' está presente na URL (requisição GET).
if (isset($_GET['sair'])) {
    // Destrói todas as variáveis de sessão, efetivamente deslogando o usuário.
    session_destroy();
    // Redireciona o navegador para a mesma página, limpando os parâmetros da URL.
    header("php/admin_pesquisa.php");
    exit; // Garante que o script pare de executar após o redirecionamento.
}

// === LÓGICA DE LOGIN ===
// Verifica se o administrador NÃO está logado (checa a variável de sessão 'admin_logado').
if (!isset($_SESSION['admin_logado'])) {
    // Se o método da requisição for POST (significa que o formulário de login foi submetido).
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Obtém o usuário e a senha do formulário, usando o operador null coalesce (??) para evitar avisos se a variável não estiver definida.
        $usuario = $_POST['usuario'] ?? '';
        $senha = $_POST['senha'] ?? '';

        // Compara as credenciais fornecidas com as credenciais padrão.
        if ($usuario === $adminUser && $senha === $adminPass) {
            // Se as credenciais estiverem corretas, define a variável de sessão 'admin_logado' como true.
            $_SESSION['admin_logado'] = true;
            // Redireciona para a página de administração após o login bem-sucedido.
            header("php/admin_pesquisa.php");
            exit; // Interrompe o script.
        } else {
            // Se as credenciais estiverem incorretas, define uma mensagem de erro.
            $erro = "Usuário ou senha incorretos.";
        }
    }

    // Se o administrador não estiver logado, exibe o formulário de login e encerra o script PHP.
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Admin</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
        <style>
            /* Estilos para a tela de login */
            body {
                font-family: 'Inter', sans-serif;
                background-color: #F8FAFC; /* Fundo claro */
                color: #334155;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
            }
            .login-form-container { /* Contêiner principal do formulário de login */
                max-width: 450px;
                padding: 2.5rem;
                background-color: #FFFFFF;
                border-radius: 1.25rem;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
                transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            }
            .login-form-container:hover {
                transform: translateY(-5px);
                box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
            }
            .login-form-container h2 {
                font-size: 2.5rem;
                text-align: center;
                color: #1E293B;
                margin-bottom: 1.5rem;
                font-weight: 800;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.05);
            }
            .login-form-container label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 600;
                color: #334155;
            }
            .login-form-container input[type="text"],
            .login-form-container input[type="password"] {
                width: 100%;
                padding: 0.75rem;
                margin-bottom: 1.5rem;
                border-radius: 0.5rem;
                border: 1px solid #CBD5E1;
                transition: all 0.2s ease-in-out;
                color: #334155;
            }
            .login-form-container input[type="text"]:focus,
            .login-form-container input[type="password"]:focus {
                border-color: #3B82F6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
                outline: none;
            }
            .login-form-container button[type="submit"] {
                background: linear-gradient(45deg, #3B82F6, #2563EB);
                color: white;
                border: none;
                padding: 0.8rem 1.5rem;
                width: 100%;
                cursor: pointer;
                border-radius: 0.75rem;
                font-size: 1.1rem;
                font-weight: 600;
                transition: all 0.3s ease-in-out;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            }
            .login-form-container button[type="submit"]:hover {
                background: linear-gradient(45deg, #2563EB, #1D4ED8);
                transform: translateY(-2px);
                box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            }
            .error-message {
                color: #EF4444;
                font-weight: bold;
                text-align: center;
                margin-bottom: 1rem;
            }
            @media (max-width: 768px) {
                .login-form-container {
                    margin: 1.5rem;
                    padding: 1.5rem;
                }
                .login-form-container h2 {
                    font-size: 2rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-form-container">
            <h2>Login do Administrador</h2>
            <?php if (isset($erro)) echo "<p class='error-message'>$erro</p>"; ?>
            <form method="POST">
                <label for="usuario">Usuário:</label>
                <input type="text" name="usuario" id="usuario" required autofocus>
                <label for="senha">Senha:</label>
                <input type="password" name="senha" id="senha" required>
                <button type="submit">Entrar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit; // Interrompe a execução do script após exibir o formulário de login.
}

// === LÓGICA DE EXCLUSÃO (DELETE) ===
// Verifica se o parâmetro 'excluir' está presente na URL (requisição GET).
if (isset($_GET['excluir'])) {
    // Converte o ID para um inteiro para segurança.
    $id = intval($_GET['excluir']);
    // Query SQL para deletar uma resposta da pesquisa pelo ID.
    $sql_delete = "DELETE FROM respostas_pesquisa WHERE id = $id";
    if ($conn->query($sql_delete)) {
        $status_type = 'sucesso';
        $status_message = "Resposta excluída com sucesso!";
    } else {
        $status_type = 'erro';
        $status_message = "Erro ao excluir resposta: " . $conn->error;
    }
    // Redireciona para a mesma página para limpar o GET e evitar re-exclusão ao recarregar.
    header("Location: admin_pesquisa.php?status={$status_type}&mensagem=" . urlencode($status_message));
    exit;
}

// === LÓGICA DE ATUALIZAÇÃO (UPDATE) ===
// Verifica se o formulário de edição foi submetido (botão 'editar' no POST).
if (isset($_POST['editar'])) {
    // Converte o ID para um inteiro.
    $id = intval($_POST['id']);
    // Escapa as strings para prevenir SQL Injection, garantindo que caracteres especiais sejam tratados corretamente.
    $embarcacao = $conn->real_escape_string($_POST['embarcacao']);
    $frequencia = $conn->real_escape_string($_POST['frequencia']);
    $pretende_beneficio = $conn->real_escape_string($_POST['pretende_beneficio']);
    $material = $conn->real_escape_string($_POST['material']);
    $especies = $conn->real_escape_string($_POST['especies']);
    $satisfacao = $conn->real_escape_string($_POST['satisfacao']);

    // Query SQL para atualizar os campos da resposta da pesquisa.
    $sql_update = "UPDATE respostas_pesquisa SET
        embarcacao='$embarcacao',
        frequencia='$frequencia',
        pretende_beneficio='$pretende_beneficio',
        material='$material',
        especies='$especies',
        satisfacao='$satisfacao'
        WHERE id=$id";

    if ($conn->query($sql_update)) {
        $status_type = 'sucesso';
        $status_message = "Resposta atualizada com sucesso!";
    } else {
        $status_type = 'erro';
        $status_message = "Erro ao atualizar resposta: " . $conn->error;
    }
    // Redireciona para a mesma página para limpar o POST e evitar re-envio ao recarregar.
    header("Location: admin_pesquisa.php?status={$status_type}&mensagem=" . urlencode($status_message));
    exit;
}

// === LÓGICA DE BUSCA (SELECT ONE) ===
// Inicializa a variável $resposta_para_editar como nula. Ela armazenará os dados de uma resposta específica se encontrada.
$resposta_para_editar = null;
// Verifica se o formulário de busca por ID para edição foi submetido.
if (isset($_POST['buscar_id_para_editar'])) {
    // Converte o ID de busca para um inteiro.
    $idBusca = intval($_POST['buscar_id_para_editar']);
    // Query SQL para selecionar uma resposta específica pelo 'id'.
    $sql_select_one = "SELECT * FROM respostas_pesquisa WHERE id = $idBusca";
    $resultado_query = $conn->query($sql_select_one);
    // Verifica se a query retornou algum resultado.
    if ($resultado_query->num_rows > 0) {
        // Se uma resposta for encontrada, armazena seus dados na variável $resposta_para_editar.
        $resposta_para_editar = $resultado_query->fetch_assoc();
    } else {
        // Se nenhuma resposta for encontrada, define uma mensagem de erro.
        $erro = "Nenhuma resposta encontrada com ID $idBusca para edição.";
    }
}

// === LÓGICA PARA LISTAR RESPOSTAS (SELECT ALL) ===
// Inicializa o array para armazenar a lista de respostas.
$lista_respostas = [];
// Esta condição verifica se o botão 'listar_respostas' foi clicado OU se a página foi carregada sem uma ação específica de POST (editar, excluir, buscar_id_para_editar).
// Isso garante que a lista de respostas seja exibida por padrão ao carregar a página.
if (isset($_POST['listar_respostas']) || (!isset($_POST['editar']) && !isset($_GET['excluir']) && !isset($_POST['buscar_id_para_editar']))) {
    // Query SQL para selecionar todas as respostas da pesquisa, ordenadas pelo ID em ordem decrescente (as mais recentes primeiro).
    $sqlTodos = "SELECT * FROM respostas_pesquisa ORDER BY id DESC";
    $resultadoTodos = $conn->query($sqlTodos);
    // Verifica se a query retornou resultados.
    if ($resultadoTodos->num_rows > 0) {
        // Itera sobre cada linha do resultado e adiciona-a ao array $lista_respostas.
        while($row = $resultadoTodos->fetch_assoc()) {
            $lista_respostas[] = $row;
        }
    }
}

// Fecha a conexão com o banco de dados. É importante fechar a conexão quando ela não é mais necessária.
$conn->close();

// Mensagem de status da URL (ainda útil se houver redirecionamentos de outras páginas ou para limpar GET/POST).
$status_from_url = $_GET['status'] ?? null;
$message_from_url = $_GET['mensagem'] ?? null;

// Prioriza a mensagem da URL se existir, senão usa as do PHP.
if ($status_from_url && $message_from_url) {
    $status_type = $status_from_url;
    $status_message = $message_from_url;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Pesquisa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <style>
        /* Define a fonte Inter para todo o corpo do documento e cores base */
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            background-color: #F8FAFC; /* Um cinza muito claro para o fundo */
            color: #334155; /* Cor de texto padrão, um cinza escuro */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Estilo base para os blocos de conteúdo principais na área administrativa */
        .admin-section-block {
            max-width: 1100px; /* Largura um pouco maior para tabelas */
            margin: 2.5rem auto;
            padding: 2.5rem;
            background-color: #FFFFFF;
            border-radius: 1.25rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        .admin-section-block:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }

        .admin-section-block h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: #1E293B;
            font-weight: 800;
            text-align: center;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.05);
        }

        .admin-section-block h3 {
            font-size: 2rem;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            color: #1E293B;
            font-weight: 700;
            border-bottom: 2px solid #E2E8F0;
            padding-bottom: 0.5rem;
            text-align: center; /* Centraliza subtítulos */
        }

        /* Estilos para a tela de login */
        .login-form-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-grow: 1; /* Faz o container de login ocupar o espaço restante */
        }
        .login-form { /* Estilo do formulário de login */
            max-width: 450px; /* Largura menor para o formulário de login */
            padding: 2.5rem;
            background-color: #FFFFFF;
            border-radius: 1.25rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .login-form:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }
        .login-form h3 {
            font-size: 2.5rem;
            text-align: center;
            color: #1E293B;
            margin-bottom: 1.5rem;
            font-weight: 800;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.05);
            border-bottom: none; /* Remove a borda inferior do h3 de login */
            padding-bottom: 0;
        }
        .login-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #334155;
        }
        .login-form input[type="text"], .login-form input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid #CBD5E1;
            transition: all 0.2s ease-in-out;
            color: #334155;
        }
        .login-form input[type="text"]:focus, .login-form input[type="password"]:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
            outline: none;
        }
        .login-form button[type="submit"] {
            background: linear-gradient(45deg, #3B82F6, #2563EB);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            width: 100%;
            cursor: pointer;
            border-radius: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .login-form button[type="submit"]:hover {
            background: linear-gradient(45deg, #2563EB, #1D4ED8);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        .error-message {
            color: #EF4444;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1rem;
        }

        /* Estilos para a área administrativa */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .admin-header h2 {
            margin: 0; /* Remove margem padrão do h2 */
            text-align: left; /* Alinha o título à esquerda */
            border-bottom: none; /* Remove a borda inferior */
            padding-bottom: 0;
            font-size: 2.5rem; /* Tamanho do título da página admin */
            font-weight: 800;
            color: #1E293B;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.05);
        }
        a.logout-btn { /* Estilo para o botão Sair */
            background-color: #EF4444;
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            border-radius: 0.75rem;
            transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: inline-flex; /* Para centralizar o ícone */
            align-items: center;
            gap: 0.5rem;
        }
        a.logout-btn:hover {
            background-color: #DC2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* Estilo para o formulário de busca */
        form.search-form {
            margin-bottom: 1.5rem;
            background-color: #F0F9FF; /* Fundo suave */
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        form.search-form:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        form.search-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #334155;
        }
        form.search-form input[type=number] {
            width: calc(100% - 120px); /* Ajusta largura para caber o botão */
            padding: 0.6rem;
            margin-right: 0.5rem;
            border: 1px solid #CBD5E1;
            border-radius: 0.5rem;
            transition: all 0.2s ease-in-out;
        }
        form.search-form input[type=number]:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
            outline: none;
        }
        form.search-form button[type="submit"] {
            background-color: #3B82F6;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 0.75rem;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        form.search-form button[type="submit"]:hover {
            background-color: #2563EB;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .success-message {
            color: #22C55E;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1rem;
        }
        .error-message-small {
            color: #EF4444;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1rem;
        }

        /* Estilo para formulário de edição */
        .edit-form-container {
            background-color: #EBF8FF; /* Fundo mais claro para edição */
            padding: 2rem;
            margin-top: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .edit-form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        .edit-form-container h3 {
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: #1E293B;
            font-weight: 700;
            border-bottom: 2px solid #E2E8F0;
            padding-bottom: 0.5rem;
        }
        .edit-form-container label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #334155;
        }
        .edit-form-container input[type=text],
        .edit-form-container input[type=email],
        .edit-form-container textarea {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid #CBD5E1;
            border-radius: 0.5rem;
            transition: all 0.2s ease-in-out;
            color: #334155;
        }
        .edit-form-container input[type=text]:focus,
        .edit-form-container input[type=email]:focus,
        .edit-form-container textarea:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
            outline: none;
        }
        .edit-form-container button[type="submit"] {
            background: linear-gradient(45deg, #3B82F6, #2563EB);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 0.75rem;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .edit-form-container button[type="submit"]:hover {
            background: linear-gradient(45deg, #2563EB, #1D4ED8);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        .edit-form-container .delete-link {
            color: #EF4444;
            float: right;
            margin-top: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease-in-out;
        }
        .edit-form-container .delete-link:hover {
            color: #DC2626;
            text-decoration: underline;
        }

        /* Estilo para tabelas de dados */
        .data-table-wrapper { /* Novo wrapper para a tabela */
            overflow-x: auto; /* Permite rolagem horizontal em telas pequenas */
            margin-top: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .data-table-wrapper:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.12);
        }

        .data-table {
            width: 100%;
            min-width: 800px; /* Define uma largura mínima para a tabela para forçar a rolagem */
            border-collapse: collapse;
            background-color: white;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #E2E8F0;
            padding: 0.8rem 1.2rem;
            text-align: left;
        }

        .data-table th {
            background-color: #0F172A; /* Cabeçalho escuro */
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #F8FAFC; /* Linhas pares mais claras */
        }

        .data-table tbody tr:hover {
            background-color: #EBF8FF; /* Fundo suave no hover da linha */
        }

        /* Estilo para botões de ação na tabela (Editar, Excluir) */
        .table-action-btn {
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .table-action-btn.edit {
            background-color: #3B82F6;
            color: white;
        }
        .table-action-btn.edit:hover {
            background-color: #2563EB;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table-action-btn.delete {
            background-color: #EF4444;
            color: white;
            margin-left: 0.5rem;
        }
        .table-action-btn.delete:hover {
            background-color: #DC2626;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Botão de recarregar lista */
        .reload-button-container {
            margin-top: 2rem;
            text-align: center;
        }
        .reload-button-container button {
            background: linear-gradient(45deg, #10B981, #059669); /* Verde vibrante */
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .reload-button-container button:hover {
            background: linear-gradient(45deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }

        /* Estilo para a notificação popup (global) */
        .popup-notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #22C55E; /* Verde vibrante para sucesso */
            color: white; /* Texto branco para contrastar */
            padding: 1.2rem 2.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            opacity: 0;
            transition: top 0.3s ease-out, opacity 0.3s ease-in-out;
            top: -50px;
            font-weight: 600;
        }
        .popup-notification.show {
            top: 20px;
            opacity: 1;
        }
        .popup-notification.error {
            background-color: #EF4444; /* Vermelho vibrante para erro */
        }

        /* Ajustes responsivos */
        @media (max-width: 768px) {
            .admin-section-block {
                margin: 1.5rem auto;
                padding: 1.5rem;
            }
            .admin-section-block h2 {
                font-size: 2rem;
            }
            .admin-section-block h3 {
                font-size: 1.5rem;
                text-align: center; /* Centraliza subtítulos em mobile */
            }
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 1.5rem;
            }
            .admin-header h2 {
                text-align: center;
                width: 100%;
                margin-bottom: 1rem;
            }
            a.logout-btn {
                width: 100%;
                justify-content: center;
            }
            form.search-form input[type=number] {
                width: calc(100% - 100px); /* Ajusta largura para mobile */
            }
            /* A tabela agora rola horizontalmente, então as células não precisam diminuir tanto */
            .data-table th, .data-table td {
                padding: 0.6rem 0.8rem;
                font-size: 0.8rem;
            }
            .table-action-btn {
                padding: 0.3rem 0.6rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div id="popup-notification-container" class="popup-notification"></div>

    <?php if (!isset($_SESSION['admin_logado'])): // Se o administrador NÃO estiver logado, exibe o formulário de login ?>
        <div class="login-form-container">
            <div class="login-form">
                <h2>Login do Administrador</h2>
                <?php if (isset($erro)) echo "<p class='error-message'>$erro</p>"; // Exibe mensagem de erro de login ?>
                <form method="POST">
                    <label for="usuario">Usuário:</label>
                    <input type="text" name="usuario" id="usuario" required autofocus>
                    <label for="senha">Senha:</label>
                    <input type="password" name="senha" id="senha" required>
                    <button type="submit">Entrar</button>
                </form>
            </div>
        </div>
    <?php else: // Se o administrador ESTIVER logado, exibe o painel de administração ?>
        <div class="admin-section-block">
            <div class="admin-header">
                <h2>Administração das Respostas da Pesquisa</h2>
                <a href="?sair=1" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Sair</a>
            </div>

            <form method="POST" class="search-form">
                <label for="buscar_id_para_editar">Buscar Resposta pelo ID:</label>
                <div class="flex items-center">
                    <input type="number" name="buscar_id_para_editar" id="buscar_id_para_editar" class="flex-grow" placeholder="Digite o ID da resposta">
                    <button type="submit" class="ml-2"><i class="bi bi-search"></i> Buscar</button>
                </div>
                <?php if (isset($erro)) echo "<p class='error-message-small'>$erro</p>"; // Mensagem de erro de busca ?>
            </form>

            <?php if (isset($status_type) && isset($status_message)): // Exibe o popup de status ?>
                <div id="status-popup" class="popup-notification show <?= $status_type ?>">
                    <?= urldecode($status_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($resposta_para_editar): // Exibe o formulário de edição se uma resposta foi buscada ?>
                <div class="edit-form-container">
                    <h3>Editar Resposta (ID <?= $resposta_para_editar['id'] ?>)</h3>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= $resposta_para_editar['id'] ?>">
                        <label for="embarcacao">Embarcação:</label>
                        <input type="text" name="embarcacao" id="embarcacao" value="<?= htmlspecialchars($resposta_para_editar['embarcacao']) ?>" required>
                        <label for="frequencia">Frequência:</label>
                        <input type="text" name="frequencia" id="frequencia" value="<?= htmlspecialchars($resposta_para_editar['frequencia']) ?>" required>
                        <label for="pretende_beneficio">Pretende Benefício:</label>
                        <input type="text" name="pretende_beneficio" id="pretende_beneficio" value="<?= htmlspecialchars($resposta_para_editar['pretende_beneficio']) ?>" required>
                        <label for="material">Material:</label>
                        <input type="text" name="material" id="material" value="<?= htmlspecialchars($resposta_para_editar['material']) ?>" required>
                        <label for="especies">Espécies:</label>
                        <input type="text" name="especies" id="especies" value="<?= htmlspecialchars($resposta_para_editar['especies']) ?>" required>
                        <label for="satisfacao">Satisfação:</label>
                        <input type="text" name="satisfacao" id="satisfacao" value="<?= htmlspecialchars($resposta_para_editar['satisfacao']) ?>" required>
                        <button name="editar" type="submit">Atualizar</button>
                        <a href="?excluir=<?= $resposta_para_editar["id'] ?>" onclick="return confirm('Deseja realmente excluir esta resposta?')" class="delete-link"><i class="bi bi-trash"></i> Excluir</a>
                    </form>
                </div>
            <?php endif; ?>

            <h3>Todas as Respostas</h3>
            <div class="data-table-wrapper"> <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Embarcação</th>
                            <th>Frequência</th>
                            <th>Pretende Benefício</th>
                            <th>Material</th>
                            <th>Espécies</th>
                            <th>Satisfação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($lista_respostas)): ?>
                        <?php foreach ($lista_respostas as $row): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['embarcacao']) ?></td>
                                <td><?= htmlspecialchars($row['frequencia']) ?></td>
                                <td><?= htmlspecialchars($row['pretende_beneficio']) ?></td>
                                <td><?= htmlspecialchars($row['material']) ?></td>
                                <td><?= htmlspecialchars($row['especies']) ?></td>
                                <td><?= htmlspecialchars($row['satisfacao']) ?></td>
                                <td class="flex flex-col sm:flex-row gap-2">
                                    <form method="post" class="inline-block">
                                        <input type="hidden" name="buscar_id_para_editar" value="<?= $row['id'] ?>">
                                        <button type="submit" class="table-action-btn edit"><i class="bi bi-pencil"></i> Editar</button>
                                    </form>
                                    <form method="get" class="inline-block"> <input type="hidden" name="excluir" value="<?= $row['id'] ?>">
                                        <button type="submit" onclick="return confirm('Deseja realmente excluir esta resposta?')" class="table-action-btn delete"><i class="bi bi-trash"></i> Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-4">Nenhuma resposta listada. Clique no botão "Listar Todas as Respostas" para exibir.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div> <div class="reload-button-container">
                <form method="post">
                    <button type="submit" name="listar_respostas"><i class="bi bi-arrow-clockwise"></i> Listar Todas as Respostas</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // As mensagens agora vêm de parâmetros na URL após o redirecionamento
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const mensagem = urlParams.get('mensagem');
            const notificationContainer = document.getElementById('popup-notification-container');

            if (status && mensagem) {
                notificationContainer.textContent = decodeURIComponent(mensagem);
                notificationContainer.className = `popup-notification show ${status}`; // Adiciona 'show' ou 'error'
                setTimeout(() => {
                    notificationContainer.classList.remove('show');
                    // Limpa os parâmetros da URL para que o popup não apareça novamente ao recarregar
                    history.replaceState({}, document.title, window.location.pathname);
                }, 3000); // Tempo em milissegundos que a mensagem ficará visível (3 segundos)
            }
        });
    </script>
</body>
</html>
