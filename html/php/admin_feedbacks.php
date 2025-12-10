<?php
// Define o nome do arquivo, que deve ser 'admin_feedbacks.php'

// 1. INCLUI A CONEXÃO (que inicia a sessão)
include 'conexao.php';

// 2. VERIFICA SE O USUÁRIO ESTÁ LOGADO (PORTÃO DE SEGURANÇA)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../login.php?status=error&mensagem=" . urlencode("Acesso restrito. Por favor, faça login."));
    exit();
}

// Obtém o email para exibir no painel (opcional)
$email_usuario = $_SESSION['user_email'] ?? 'Usuário Logado';
$conn = $GLOBALS['conn']; // Usar a conexão global

$mensagem_status = '';

// Variável de controle para o TRIGGER (Para Demonstração 9)
$trigger_info = '';
if (isset($_GET['trigger_status']) && $_GET['trigger_status'] == 'deleted') {
    $trigger_info = "Ação de deleção foi registrada na tabela de auditoria via **TRIGGER**!";
}


// 3. LÓGICA DE DELEÇÃO (DELETE) - ATIVA O TRIGGER (9. TRIGGERS)
if (isset($_GET['delete_id'])) {
    $id_para_deletar = $conn->real_escape_string($_GET['delete_id']);
    
    $sql_delete = "DELETE FROM feedbacks WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_para_deletar);

    if ($stmt_delete->execute()) {
        $mensagem_status = "Feedback ID " . $id_para_deletar . " deletado com sucesso!";
        // Redireciona com status do TRIGGER
        header("Location: admin_feedbacks.php?status=sucesso&msg=" . urlencode($mensagem_status) . "&trigger_status=deleted");
        exit();
    } else {
        $mensagem_status = "Erro ao deletar o feedback: " . $stmt_delete->error;
        header("Location: admin_feedbacks.php?status=erro&msg=" . urlencode($mensagem_status));
        exit();
    }
    $stmt_delete->close();
}

// 4. LÓGICA DE ATUALIZAÇÃO (UPDATE)
if (isset($_POST['update_id'])) {
    $id_para_editar     = $conn->real_escape_string($_POST['update_id']);
    $nome               = $conn->real_escape_string(trim($_POST['nome']));
    $email              = $conn->real_escape_string(trim($_POST['email']));
    $mensagem_feedback  = $conn->real_escape_string(trim($_POST['mensagem']));

    $sql_update = "UPDATE feedbacks SET nome=?, email=?, mensagem=? WHERE id=?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssi", $nome, $email, $mensagem_feedback, $id_para_editar);

    if ($stmt_update->execute()) {
        $mensagem_status = "Feedback ID " . $id_para_editar . " atualizado com sucesso!";
        header("Location: admin_feedbacks.php?status=sucesso&msg=" . urlencode($mensagem_status));
        exit();
    } else {
        $mensagem_status = "Erro ao atualizar o feedback: " . $stmt_update->error;
        header("Location: admin_feedbacks.php?status=erro&msg=" . urlencode($mensagem_status));
        exit();
    }
    $stmt_update->close();
}

// Variável para armazenar o registro a ser editado
$registro_edicao = null;
if (isset($_GET['edit_id'])) {
    $id_para_editar = $conn->real_escape_string($_GET['edit_id']);
    $sql_edit = "SELECT * FROM feedbacks WHERE id = ?";
    $stmt_edit = $conn->prepare($sql_edit);
    $stmt_edit->bind_param("i", $id_para_editar);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();
    if ($result_edit->num_rows > 0) {
        $registro_edicao = $result_edit->fetch_assoc();
    }
    $stmt_edit->close();
}


// LÓGICA DO STORED PROCEDURE (Botão de Execução - 8. STORED PROCEDURE)
if (isset($_GET['action']) && $_GET['action'] == 'run_sp') {
    try {
        $conn->query("CALL sp_relatorio_total_simulacoes()");
        $result_sp = $conn->store_result(); 

        $total = 0;
        if ($result_sp) {
             $dados = $result_sp->fetch_assoc();
             $total = $dados['total_registros'] ?? 0;
             $result_sp->free(); 
        }
        while($conn->more_results() && $conn->next_result()) {
            if($res = $conn->store_result()) {
                $res->free();
            }
        }
        $mensagem_status = "Procedimento executado! Total de registros globais: {$total}.";
        header("Location: admin_feedbacks.php?status=sucesso&msg=" . urlencode($mensagem_status));
        exit();
    } catch (Exception $e) {
        $mensagem_status = "Erro ao executar o procedimento (SP). Detalhe: " . $e->getMessage();
        header("Location: admin_feedbacks.php?status=erro&msg=" . urlencode($mensagem_status));
        exit();
    }
}


// 5. LÓGICA DE BUSCA (SELECTs Avançados)

// A) SELECT PRINCIPAL - Demonstração 6.2 IF e CASE (Classificação de Conteúdo)
$sql_select = "SELECT id, nome, email, mensagem, data_envio,
               -- 6.2 IF e CASE: Classifica a mensagem com base no tamanho
               CASE
                   WHEN LENGTH(mensagem) > 100 THEN 'DETALHADO'
                   WHEN LENGTH(mensagem) > 30 THEN 'MÉDIO'
                   ELSE 'BREVE'
               END AS Tipo_Conteudo,
               -- ADICIONANDO A COLUNA ORIGINAL (ID) PARA REFERÊNCIA VISUAL
               id AS id_original 
               FROM feedbacks ORDER BY data_envio DESC";
$result = $conn->query($sql_select);


// B) SELECT SUBQUERY - Demonstração 6.3 (Listar Feedbacks com EMAIL Comum)
try {
    $sql_subquery = "SELECT nome, email
                     FROM feedbacks
                     WHERE email IN (SELECT email FROM simulacoes)
                     LIMIT 5";
    $result_subquery = $conn->query($sql_subquery);
} catch (Exception $e) { $result_subquery = null; }


// C) SELECT UNION ALL - Demonstração 6.1 (Junta Feedbacks e Pesquisas)
// =======================================================
// 6.1 UNION ALL (Relatório Consolidado)
// =======================================================
try {
$sql_union = "
    (SELECT data_envio AS data_registro, 'Feedback' AS tipo, mensagem AS detalhe FROM feedbacks)
    UNION ALL
    (SELECT data_resposta AS data_registro, 'Pesquisa' AS tipo, embarcacao AS detalhe FROM respostas_pesquisa)
    ORDER BY data_registro DESC LIMIT 10";
    $result_union = $conn->query($sql_union);
} catch (Exception $e) { 
    // Em caso de falha no UNION (tabela não encontrada, etc.), definimos como null.
    $result_union = null; 
    // O erro será tratado pelo bloco 'if ($result_union)' mais abaixo no HTML.
}

// D) SELECT VIEW - Demonstração 7 (Consulta Log de Auditoria)
// CORRIGIDO: Garante que as colunas ID e MENSAGEM (para substr) estejam no SELECT
try {
    $sql_view = "SELECT id, nome, mensagem, 
                 CASE WHEN LENGTH(mensagem) > 50 THEN 'LONGO' ELSE 'CURTO' END AS Tipo_Conteudo
                 FROM feedbacks LIMIT 3";
    $result_view = $conn->query($sql_view);
} catch (Exception $e) { $result_view = null; }


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Feedbacks - Defeso</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; color: #334155; }
        .container-admin { max-width: 1200px; margin: 2rem auto; padding: 2.5rem; background-color: #FFFFFF; border-radius: 1.25rem; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); }
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0; }
        .table-custom th, .table-custom td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #E2E8F0; }
        .table-custom th { background-color: #EBF8FF; color: #1E293B; font-weight: 700; border-top: 2px solid #3B82F6; }
        .table-custom tr:last-child td { border-bottom: none; }
        .delete-btn { background-color: #EF4444; color: white; padding: 6px 12px; border-radius: 6px; font-weight: 600; transition: background-color 0.3s; text-decoration: none; margin-right: 5px; }
        .delete-btn:hover { background-color: #DC2626; }
        .edit-btn { background-color: #3B82F6; color: white; padding: 6px 12px; border-radius: 6px; font-weight: 600; transition: background-color 0.3s; text-decoration: none; }
        .edit-btn:hover { background-color: #2563EB; }
        .success-msg { background-color: #10B981; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600; }
        .error-msg { background-color: #EF4444; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600; }
        .form-edit-container { background-color: #F0F9FF; padding: 2rem; border-radius: 1rem; margin-bottom: 2rem; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .form-edit-container label { display: block; font-weight: 600; margin-top: 1rem; margin-bottom: 0.25rem; }
        .form-edit-container input[type="text"], 
        .form-edit-container input[type="email"], 
        .form-edit-container textarea { width: 100%; padding: 0.75rem; border: 1px solid #CBD5E1; border-radius: 0.5rem; }
        .form-edit-container button[type="submit"] { background: #10B981; color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 0.75rem; cursor: pointer; font-weight: 600; margin-top: 1.5rem; transition: background-color 0.3s; }
        .form-edit-container button[type="submit"]:hover { background: #059669; }
        .cancel-btn { background-color: #94A3B8; margin-left: 10px; }
        .cancel-btn:hover { background-color: #64748B; }
        .mensagem-preview { max-height: 50px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* Estilos de Demonstração */
        .sql-section { border: 1px solid #E2E8F0; padding: 1.5rem; border-radius: 1rem; margin-bottom: 1.5rem; background-color: #F9FAFB; }
        .sql-section h4 { font-weight: bold; color: #3B82F6; margin-bottom: 1rem; border-bottom: 1px dashed #E2E8F0; padding-bottom: 0.5rem; }
        .btn-sql-action { background-color: #4F46E5; color: white; padding: 8px 15px; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: background-color 0.3s; }
        .btn-sql-action:hover { background-color: #4338CA; }
        .trigger-status-box { padding: 1rem; background-color: #FFEFD5; border: 1px solid #F59E0B; color: #78350F; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600; }
        .userbar { display: flex; gap: .75rem; align-items: center; justify-content: flex-end; margin-top: .5rem; }
        .userbar .pill { background: #F1F5F9; border: 1px solid #E2E8F0; color: #475569; padding: .35rem .75rem; border-radius: 9999px; font-size: .875rem; }
    </style>
</head>
<body>

    <div class="container-admin">
        <h2 class="text-3xl font-bold text-center mb-2 text-gray-800">Área Administrativa - Feedbacks</h2>

        <div class="userbar">
            <span class="pill"><i class="bi bi-person-circle mr-1"></i> <?php echo htmlspecialchars($email_usuario); ?></span>
            <a href="logout.php" class="delete-btn inline-flex items-center">
                <i class="bi bi-box-arrow-right"></i> Sair
            </a>
        </div>
        
        <?php if ($trigger_info): ?>
            <div class="trigger-status-box">
                <i class="bi bi-shield-lock-fill mr-2"></i> **9. TRIGGERS:** <?php echo htmlspecialchars($trigger_info); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
            <div class="<?php echo $_GET['status'] == 'sucesso' ? 'success-msg' : 'error-msg'; ?>">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <a href="../contato.html" class="text-blue-600 hover:text-blue-800 font-medium mb-6 inline-flex items-center">
            <i class="bi bi-arrow-left mr-2"></i> Voltar para Contato
        </a>

        <?php if ($registro_edicao): ?>
        <div class="form-edit-container">
            <h3 class="text-2xl font-semibold mb-4 text-gray-700">Editar Feedback ID: <?php echo htmlspecialchars($registro_edicao['id']); ?></h3>
            <form action="admin_feedbacks.php" method="POST">
                <input type="hidden" name="update_id" value="<?php echo htmlspecialchars($registro_edicao['id']); ?>">
                
                <label for="nome">Nome:</label>
                <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($registro_edicao['nome']); ?>" required>

                <label for="email">E-mail:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($registro_edicao['email']); ?>" required>

                <label for="mensagem">Mensagem:</label>
                <textarea name="mensagem" id="mensagem" rows="5" required><?php echo htmlspecialchars($registro_edicao['mensagem']); ?></textarea>

                <button type="submit">Salvar Alterações (UPDATE)</button>
                <a href="admin_feedbacks.php" class="cancel-btn inline-block px-8 py-3 text-white font-semibold rounded-lg text-center mt-4">Cancelar</a>
            </form>
        </div>
        <?php endif; ?>

        
        <h3 class="text-2xl font-semibold mt-6 mb-4 text-gray-700">Demonstrações de SQL Avançado</h3>

        <div class="grid md:grid-cols-2 gap-4">

            <div class="sql-section">
                <h4>8. STORED PROCEDURE: Relatório Global</h4>
                <p class="mb-3">Demonstração de execução de um procedimento armazenado (Contagem Total de Registros).</p>
                <a href="admin_feedbacks.php?action=run_sp" class="btn-sql-action">
                    <i class="bi bi-gear-fill"></i> Executar Relatório (SP)
                </a>
            </div>

            <div class="sql-section">
                <h4>6.3 SUBQUERY: Feedbacks de Usuários Frequentes</h4>
                <p class="mb-3">Lista feedbacks cujo e-mail também aparece na tabela de Simulações (Subconsulta no WHERE).</p>
                <table class="table-custom shadow-sm text-sm">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_subquery && $result_subquery->num_rows > 0) {
                            while($row_sub = $result_subquery->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row_sub['nome']); ?></td>
                                <td><?php echo htmlspecialchars($row_sub['email']); ?></td>
                            </tr>
                        <?php endwhile; } else { echo "<tr><td colspan='2'>Nenhum feedback de usuário frequente encontrado.</td></tr>"; } ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="sql-section mt-4">
            <h4>7. VIEWS / 6.2 IF e CASE: Classificação de Mensagem</h4>
            <p class="mb-3">Consulta para classificar o feedback como 'DETALHADO' ou 'BREVE' usando a lógica **CASE** (Baseado no tamanho da mensagem).</p>
            <table class="table-custom shadow-sm text-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Conteúdo</th>
                        <th>Classificação (CASE)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result_view && $result_view->num_rows > 0) {
                        while($row_view = $result_view->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row_view['id']); ?></td>
                            <td><?php echo htmlspecialchars($row_view['nome']); ?></td>
                            <td><?php echo htmlspecialchars(substr($row_view['mensagem'], 0, 20)) . '...'; ?></td>
                            <td>**<?php echo htmlspecialchars($row_view['Tipo_Conteudo']); ?>**</td>
                        </tr>
                    <?php endwhile; } else { echo "<tr><td colspan='4'>Nenhum registro encontrado para a View.</td></tr>"; } ?>
                </tbody>
            </table>
        </div>

        <div class="sql-section">
            <h4>6.1 UNION ALL: Atividade Consolidada</h4>
            <p class="mb-3">Combina os 10 registros mais recentes de **Feedbacks** e **Pesquisas** em uma única linha do tempo.</p>
            <table class="table-custom shadow-sm text-sm">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Data</th>
                        <th>Detalhe</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result_union && $result_union->num_rows > 0) {
                        while($row_union = $result_union->fetch_assoc()): ?>
                        <tr>
                            <td>**<?php echo htmlspecialchars($row_union['tipo']); ?>**</td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row_union['data_registro'])); ?></td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($row_union['detalhe']); ?></td>
                        </tr>
                    <?php endwhile; } else { echo "<tr><td colspan='3'>Nenhuma atividade recente encontrada.</td></tr>"; } ?>
                </tbody>
            </table>
        </div>

        <div class="sql-section text-center">
            <h4>9. TRIGGERS: Log de Auditoria (Demonstração)</h4>
            <p class="mb-3">A exclusão de dados é registrada automaticamente no log de auditoria.</p>
            <a href="admin_log_feedback.php" class="btn-sql-action" style="background-color: #F59E0B; color: #fff;">
                <i class="bi bi-list-columns-reverse"></i> Visualizar Log de Auditoria
            </a>
        </div>


        <h3 class="text-2xl font-semibold mt-6 mb-4 border-b pb-2 text-gray-700">Registros de Feedback (Tabela Principal)</h3>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="table-custom shadow-lg">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Classificação (6.2 CASE)</th>
                            <th>Mensagem</th>
                            <th>Data Envio</th>
                            <th>Ações (9. TRIGGER)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                    <?php 
                                        $type = $row['Tipo_Conteudo'];
                                        if ($type == 'DETALHADO') echo 'bg-blue-100 text-blue-800';
                                        elseif ($type == 'MÉDIO') echo 'bg-yellow-100 text-yellow-800';
                                        else echo 'bg-gray-100 text-gray-800';
                                    ?>">
                                    <?php echo htmlspecialchars($row['Tipo_Conteudo']); ?>
                                </span>
                            </td>
                            <td title="<?php echo htmlspecialchars($row['mensagem']); ?>">
                                <div class="mensagem-preview"><?php echo htmlspecialchars($row['mensagem']); ?></div>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['data_envio'])); ?></td>
                            <td>
                                <a href="admin_feedbacks.php?edit_id=<?php echo $row['id']; ?>" class="edit-btn">Editar</a>
                                <a href="admin_feedbacks.php?delete_id=<?php echo $row['id']; ?>" 
                                   class="delete-btn" 
                                   onclick="return confirm('Tem certeza? A exclusão será registrada no log (TRIGGER).');">
                                    Deletar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-lg text-center py-8 bg-gray-50 border border-gray-200 rounded-lg">Nenhum feedback encontrado no banco de dados.</p>
        <?php endif; ?>
    </div>

</body>
</html>

<?php
$conn->close();
?>