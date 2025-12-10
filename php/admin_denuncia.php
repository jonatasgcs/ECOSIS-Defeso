<?php
session_start();

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', '12345');

if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $login_error = "Usuário ou senha inválidos!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Conexão com o banco de dados (apenas se logado, ou para listar/buscar)
$conn = null;
if ($admin_logged_in) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "site_defeso";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Conexão falhou: " . $conn->connect_error);
    }

    $lista_denuncias = [];
    $feedback = null;
    $msg_sucesso = null;
    $msg_erro = null;
    $erro_busca = null;

    // Lógica para listar, deletar, atualizar e buscar
    if (isset($_POST['listar']) || !isset($_POST['login']) && !isset($_POST['deletar']) && !isset($_POST['atualizar']) && !isset($_POST['buscar_id'])) {
        // Lista todas as denúncias ao carregar a página ou quando 'listar' é clicado
        $sql = "SELECT * FROM denuncias";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $lista_denuncias[] = $row;
            }
        }
    }

    if (isset($_POST['deletar'])) {
        $idParaExcluir = (int)$_POST['id_excluir'];
        $sql = "DELETE FROM denuncias WHERE id = $idParaExcluir";
        if ($conn->query($sql) === TRUE) {
            $msg_sucesso = "Denúncia com ID $idParaExcluir excluída com sucesso.";
        } else {
            $msg_erro = "Erro ao excluir: " . $conn->error;
        }
        // Após deletar, recarrega a lista
        $sql = "SELECT * FROM denuncias";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $lista_denuncias[] = $row;
            }
        }
    }

    if (isset($_POST['atualizar'])) {
        $idParaAtualizar = (int)$_POST['id_atualizar'];
        $novoLocal = $conn->real_escape_string($_POST['novo_local']);
        $novaDescricao = $conn->real_escape_string($_POST['nova_descricao']);

        $sql = "UPDATE denuncias SET local='$novoLocal', descricao='$novaDescricao' WHERE id=$idParaAtualizar";
        if ($conn->query($sql) === TRUE) {
            $msg_sucesso = "Denúncia com ID $idParaAtualizar atualizada com sucesso.";
        } else {
            $msg_erro = "Erro ao atualizar: " . $conn->error;
        }
        // Após atualizar, recarrega a lista
        $sql = "SELECT * FROM denuncias";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $lista_denuncias[] = $row;
            }
        }
    }

    if (isset($_POST['buscar_id'])) {
        $idBusca = intval($_POST['buscar_id']);
        $resultado = $conn->query("SELECT * FROM denuncias WHERE id = $idBusca");
        if ($resultado->num_rows > 0) {
            $feedback = $resultado->fetch_assoc();
        } else {
            $erro_busca = "Nenhuma denúncia encontrada com ID $idBusca.";
        }
        // Também recarrega a lista completa ao buscar
        $sql = "SELECT * FROM denuncias";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $lista_denuncias[] = $row;
            }
        }
    }
}

if ($conn) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Denúncias</title>
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

        /* Estilo base para os blocos de conteúdo principais */
        .section-block {
            max-width: 900px; /* Largura padrão para conteúdo */
            margin: 2.5rem auto;
            padding: 2.5rem;
            background-color: #FFFFFF;
            border-radius: 1.25rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        .section-block:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }

        .section-block h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: #1E293B;
            font-weight: 800;
            text-align: center;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.05);
        }

        .section-block h3 {
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
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-grow: 1; /* Faz o container de login ocupar o espaço restante */
        }
        .login-form {
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
        .error-message { /* Renomeado de .error para evitar conflito com Tailwind */
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
        a.logout-btn { /* Renomeado de .sair para .logout-btn */
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

        form.search-form { /* Renomeado de .buscar para .search-form */
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
        .success-message { /* Renomeado de .mensagem */
            color: #22C55E;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1rem;
        }
        .error-message-small { /* Renomeado de .erro */
            color: #EF4444;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1rem;
        }

        /* Estilo para formulário de edição */
        .edit-form-container { /* Renomeado de .form-editar */
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
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            border-radius: 0.75rem;
            overflow: hidden; /* Para aplicar border-radius nas células */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        .data-table:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.12);
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

        /* Ajustes responsivos */
        @media (max-width: 768px) {
            .section-block {
                margin: 1.5rem auto;
                padding: 1.5rem;
            }
            .section-block h2 {
                font-size: 2rem;
            }
            .section-block h3 {
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
    <?php if (!$admin_logged_in): ?>
        <div class="login-container">
            <div class="login-form">
                <h3>Login do Administrador</h3>
                <?php if (isset($login_error)) echo "<p class='error-message'>$login_error</p>"; ?>
                <form method="post">
                    <label for="username">Usuário:</label>
                    <input type="text" name="username" id="username" required>
                    <label for="password">Senha:</label>
                    <input type="password" name="password" id="password" required>
                    <button type="submit" name="login">Entrar</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="section-block">
            <div class="admin-header">
                <h2>Administração de Denúncias</h2>
                <a href="?logout=1" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Sair</a>
            </div>

            <form method="POST" class="search-form">
                <label for="buscar_id">Buscar Denúncia pelo ID:</label>
                <div class="flex items-center">
                    <input type="number" name="buscar_id" id="buscar_id" required class="flex-grow">
                    <button type="submit" class="ml-2"><i class="bi bi-search"></i> Buscar</button>
                </div>
                <?php if (isset($erro_busca)) echo "<p class='error-message-small'>$erro_busca</p>"; ?>
            </form>

            <?php if (isset($msg_sucesso)) echo "<p class='success-message'>$msg_sucesso</p>"; ?>
            <?php if (isset($msg_erro)) echo "<p class='error-message-small'>$msg_erro</p>"; ?>

            <?php if ($feedback) { ?>
                <div class="edit-form-container">
                    <h3>Editar Denúncia (ID <?= $feedback['id'] ?>)</h3>
                    <form method="POST">
                        <input type="hidden" name="id_atualizar" value="<?= $feedback['id'] ?>">
                        <label for="novo_local">Novo Local:</label>
                        <input type="text" name="novo_local" id="novo_local" value="<?= htmlspecialchars($feedback['local'] ?? '') ?>" required>
                        <label for="nova_descricao">Nova Descrição:</label>
                        <textarea name="nova_descricao" id="nova_descricao" required><?= htmlspecialchars($feedback['descricao'] ?? '') ?></textarea>
                        <button name="atualizar" type="submit">Atualizar</button>
                        <a href="?deletar=<?= $feedback["id'] ?>" onclick="return confirm('Deseja realmente excluir esta denúncia?')" class="delete-link"><i class="bi bi-trash"></i> Excluir</a>
                    </form>
                </div>
            <?php } ?>

            <h3>Todas as Denúncias</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Local</th>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($lista_denuncias)): ?>
                    <?php foreach ($lista_denuncias as $den): ?>
                        <tr>
                            <td><?= $den['id'] ?></td>
                            <td><?= htmlspecialchars($den['nome']) ?></td>
                            <td><?= htmlspecialchars($den['local']) ?></td>
                            <td><?= $den['data'] ?></td>
                            <td><?= htmlspecialchars($den['descricao']) ?></td>
                            <td class="flex flex-col sm:flex-row gap-2">
                                <form method="post" class="inline-block">
                                    <input type="hidden" name="buscar_id" value="<?= $den['id'] ?>">
                                    <button type="submit" class="table-action-btn edit"><i class="bi bi-pencil"></i> Editar</button>
                                </form>
                                <form method="post" class="inline-block">
                                    <input type="hidden" name="id_excluir" value="<?= $den['id'] ?>">
                                    <button type="submit" onclick="return confirm('Deseja realmente excluir esta denúncia?')" class="table-action-btn delete"><i class="bi bi-trash"></i> Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-4">Nenhuma denúncia registrada.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <div class="reload-button-container">
                <form method="post">
                    <button type="submit" name="listar"><i class="bi bi-arrow-clockwise"></i> Recarregar Lista de Denúncias</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
