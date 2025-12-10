<?php
// Define o nome do arquivo, que deve ser 'admin_pesquisa.php'

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
    
    // NOTA: Para simplificar, o DELETE é executado sem filtro de usuário
    $sql_delete = "DELETE FROM respostas_pesquisa WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_para_deletar);

    if ($stmt_delete->execute()) {
        $mensagem_status = "Registro ID " . $id_para_deletar . " deletado com sucesso!";
        // Redireciona com status do TRIGGER
        header("Location: admin_pesquisa.php?status=sucesso&msg=" . urlencode($mensagem_status) . "&trigger_status=deleted");
        exit();
    } else {
        $mensagem_status = "Erro ao deletar o registro: " . $stmt_delete->error;
        header("Location: admin_pesquisa.php?status=erro&msg=" . urlencode($mensagem_status));
        exit();
    }
    $stmt_delete->close();
}

// 4. LÓGICA DE ATUALIZAÇÃO (UPDATE)
if (isset($_POST['update_id'])) {
    $id_para_editar     = $conn->real_escape_string($_POST['update_id']);
    $embarcacao         = $conn->real_escape_string(trim($_POST['embarcacao']));
    $frequencia         = $conn->real_escape_string(trim($_POST['frequencia']));
    $pretende_beneficio = $conn->real_escape_string(trim($_POST['pretende_beneficio']));
    $material           = $conn->real_escape_string(trim($_POST['material']));
    $especies           = $conn->real_escape_string(trim($_POST['especies']));
    $satisfacao         = $conn->real_escape_string(trim($_POST['satisfacao']));

    // Simplificado: UPDATE sem restrição por id_usuario
    $sql_update = "UPDATE respostas_pesquisa 
                   SET embarcacao=?, frequencia=?, pretende_beneficio=?, material=?, especies=?, satisfacao=? 
                   WHERE id=?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssssssi", $embarcacao, $frequencia, $pretende_beneficio, $material, $especies, $satisfacao, $id_para_editar);

    if ($stmt_update->execute()) {
        $mensagem_status = "Registro ID " . $id_para_editar . " atualizado com sucesso!";
        header("Location: admin_pesquisa.php?status=sucesso&msg=" . urlencode($mensagem_status));
        exit();
    } else {
        $mensagem_status = "Erro ao atualizar o registro: " . $stmt_update->error;
        header("Location: admin_pesquisa.php?status=erro&msg=" . urlencode($mensagem_status));
        exit();
    }
    $stmt_update->close();
}

// Variável para armazenar o registro a ser editado
$registro_edicao = null;
if (isset($_GET['edit_id'])) {
    $id_para_editar = $conn->real_escape_string($_GET['edit_id']);
    $sql_edit = "SELECT * FROM respostas_pesquisa WHERE id = ?";
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
        // NOTA: Reutilizamos o SP de simulações para demonstrar a chamada, mas idealmente seria um SP específico.
        $sql = "CALL sp_relatorio_total_simulacoes()"; 
        $result_sp = $conn->query($sql);
        $dados = $result_sp->fetch_assoc();
        $total = $dados['total_registros'] ?? 0;
        $mensagem_status = "Procedimento (SP) executado! Total de registros globais: {$total}.";
        header("Location: admin_pesquisa.php?status=sucesso&msg=" . urlencode($mensagem_status));
        exit();
    } catch (Exception $e) {
        $mensagem_status = "Erro ao executar o procedimento (SP).";
        header("Location: admin_pesquisa.php?status=erro&msg=" . urlencode($mensagem_status));
        exit();
    }
}


// 5. LÓGICA DE BUSCA (SELECTs Avançados)

// A) SELECT PRINCIPAL - Demonstração VIEW/CASE (Usaremos a lógica CASE direto no SELECT para simplicidade na Pesquisa)
$sql_select = "SELECT id, embarcacao, frequencia, material, especies, satisfacao, data_resposta,
               -- 6.2 IF e CASE: Classifica se o pescador pretende ou não o benefício 
               CASE
                   WHEN pretende_beneficio = 'Sim' THEN 'INTERESSADO'
                   ELSE 'SEM INTERESSE'
               END AS Status_Intencao
               FROM respostas_pesquisa ORDER BY data_resposta DESC";
$result = $conn->query($sql_select);


// B) SELECT SUBQUERY - Demonstração 6.3 (Encontrando pesquisas acima da frequência 'Semanalmente')
// A subquery determina o valor de 'Semanalmente' para filtrar
$sql_subquery = "SELECT embarcacao, frequencia 
                 FROM respostas_pesquisa 
                 WHERE frequencia IN ('Diariamente', 'Semanalmente')
                 AND frequencia > (SELECT MIN(frequencia) FROM respostas_pesquisa WHERE frequencia = 'Raramente')
                 ORDER BY frequencia DESC";
$result_subquery = $conn->query($sql_subquery);


// C) SELECT UNION ALL - Demonstração 6.1 (Junta Pesquisas e Feedbacks)
$sql_union = "
    (SELECT 'Pesquisa' AS tipo, data_resposta AS data_registro, embarcacao AS detalhe FROM respostas_pesquisa)
    UNION ALL
    (SELECT 'Feedback' AS tipo, data_envio AS data_registro, mensagem AS detalhe FROM feedbacks)
    ORDER BY data_registro DESC LIMIT 10";
$result_union = $conn->query($sql_union);

// D) SELECT VIEW - Consulta simples (Demonstração 7)
// NOTA: Para funcionar, você precisaria criar uma VIEW no DB (Ex: vw_pesquisas_ativas)
$sql_view = "SELECT embarcacao, frequencia, satisfacao FROM respostas_pesquisa LIMIT 3";
$result_view = $conn->query($sql_view);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Pesquisas - Defeso</title>
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
        .form-edit-container input[type="text"], .form-edit-container select { width: 100%; padding: 0.75rem; border: 1px solid #CBD5E1; border-radius: 0.5rem; }
        .form-edit-container button[type="submit"] { background: #10B981; color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 0.75rem; cursor: pointer; font-weight: 600; margin-top: 1.5rem; transition: background-color 0.3s; }
        .form-edit-container button[type="submit"]:hover { background: #059669; }
        .cancel-btn { background-color: #94A3B8; margin-left: 10px; }
        .cancel-btn:hover { background-color: #64748B; }
        .radio-group-edit { display: flex; gap: 1.5rem; margin-top: 0.5rem; }
        .radio-group-edit label { display: inline-flex; align-items: center; font-weight: 500; }

        /* Estilos de Demonstração */
        .sql-section { border: 1px solid #E2E8F0; padding: 1.5rem; border-radius: 1rem; margin-bottom: 1.5rem; background-color: #F9FAFB; }
        .sql-section h4 { font-weight: bold; color: #3B82F6; margin-bottom: 1rem; border-bottom: 1px dashed #E2E8F0; padding-bottom: 0.5rem; }
        .btn-sql-action { background-color: #4F46E5; color: white; padding: 8px 15px; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: background-color 0.3s; }
        .btn-sql-action:hover { background-color: #4338CA; }
        .trigger-status-box { padding: 1rem; background-color: #FFEFD5; border: 1px solid #F59E0B; color: #78350F; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600; }
        .userbar { display: flex; gap: .75rem; align-items: center; justify-content: flex-end; margin-top: .5rem; } /* Ajustado justify-content */
        .userbar .pill { background: #F1F5F9; border: 1px solid #E2E8F0; color: #475569; padding: .35rem .75rem; border-radius: 9999px; font-size: .875rem; }
    </style>
</head>
<body>

    <div class="container-admin">
        <h2 class="text-3xl font-bold text-center mb-2 text-gray-800">Área Administrativa - Respostas da Pesquisa</h2>

        <div class="userbar">
            <span class="pill"><i class="bi bi-person-circle mr-1"></i> <?php echo htmlspecialchars($email_usuario); ?></span>
            <a href="logout.php" class="delete-btn inline-flex items-center">
                <i class="bi bi-box-arrow-right mr-1"></i> Sair
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

        <a href="../educacao.html" class="text-blue-600 hover:text-blue-800 font-medium mb-6 inline-flex items-center">
            <i class="bi bi-arrow-left mr-2"></i> Voltar para Educação
        </a>

        <?php if ($registro_edicao): ?>
        <div class="form-edit-container">
            <h3 class="text-2xl font-semibold mb-4 text-gray-700">Editar Registro ID: <?php echo htmlspecialchars($registro_edicao['id']); ?></h3>
            <form action="admin_pesquisa.php" method="POST">
                <input type="hidden" name="update_id" value="<?php echo htmlspecialchars($registro_edicao['id']); ?>">
                
                <label for="embarcacao">Tipo de Embarcação:</label>
                <input type="text" name="embarcacao" id="embarcacao" value="<?php echo htmlspecialchars($registro_edicao['embarcacao']); ?>" required>

                <label for="frequencia">Frequência da Pesca:</label>
                <select name="frequencia" id="frequencia" required>
                    <?php 
                        $opcoes_frequencia = ['Diariamente', 'Semanalmente', 'Mensalmente', 'Raramente'];
                        foreach ($opcoes_frequencia as $op) {
                            $selected = ($registro_edicao['frequencia'] == $op) ? 'selected' : '';
                            echo "<option value='{$op}' {$selected}>{$op}</option>";
                        }
                    ?>
                </select>

                <label>Pretende Benefício:</label>
                <div class="radio-group-edit">
                    <label><input type="radio" name="pretende_beneficio" value="Sim" <?php echo ($registro_edicao['pretende_beneficio'] == 'Sim') ? 'checked' : ''; ?> required> Sim</label>
                    <label><input type="radio" name="pretende_beneficio" value="Não" <?php echo ($registro_edicao['pretende_beneficio'] == 'Não') ? 'checked' : ''; ?>> Não</label>
                </div>

                <label for="material">Material de Pesca:</label>
                <input type="text" name="material" id="material" value="<?php echo htmlspecialchars($registro_edicao['material']); ?>" required>

                <label for="especies">Espécies Capturadas:</label>
                <input type="text" name="especies" id="especies" value="<?php echo htmlspecialchars($registro_edicao['especies']); ?>" required>

                <label>Satisfação:</label>
                <div class="radio-group-edit">
                    <label><input type="radio" name="satisfacao" value="Sim" <?php echo ($registro_edicao['satisfacao'] == 'Sim') ? 'checked' : ''; ?> required> Sim</label>
                    <label><input type="radio" name="satisfacao" value="Não" <?php echo ($registro_edicao['satisfacao'] == 'Não') ? 'checked' : ''; ?>> Não</label>
                </div>

                <button type="submit">Salvar Alterações (UPDATE)</button>
                <a href="admin_pesquisa.php" class="cancel-btn inline-block px-8 py-3 text-white font-semibold rounded-lg text-center mt-4">Cancelar</a>
            </form>
        </div>
        <?php endif; ?>

        <h3 class="text-2xl font-semibold mt-6 mb-4 text-gray-700">Demonstrações de SQL Avançado</h3>

        <div class="grid md:grid-cols-2 gap-4">

            <div class="sql-section">
                <h4>8. STORED PROCEDURE: Relatório Global</h4>
                <p class="mb-3">Demonstração de execução de um procedimento armazenado (Contagem Total de Registros).</p>
                <a href="admin_pesquisa.php?action=run_sp" class="btn-sql-action">
                    <i class="bi bi-gear-fill"></i> Executar Relatório (SP)
                </a>
            </div>

            <div class="sql-section">
                <h4>6.3 SUBQUERY: Pesquisas Acima da Frequência Média</h4>
                <p class="mb-3">Lista pesquisas onde a frequência é considerada alta (Subconsulta no WHERE).</p>
                <table class="table-custom shadow-sm text-sm">
                    <thead>
                        <tr>
                            <th>Embarcação</th>
                            <th>Frequência</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_subquery && $result_subquery->num_rows > 0) {
                            while($row_sub = $result_subquery->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row_sub['embarcacao']); ?></td>
                                <td><?php echo htmlspecialchars($row_sub['frequencia']); ?></td>
                            </tr>
                        <?php endwhile; } else { echo "<tr><td colspan='2'>Nenhum registro de alta frequência encontrado.</td></tr>"; } ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="sql-section mt-4">
            <h4>7. VIEWS / 6.2 IF e CASE: Classificação de Intenção</h4>
            <p class="mb-3">Consulta para classificar a intenção de benefício do pescador usando lógica **CASE** (Demonstração 7).</p>
            <table class="table-custom shadow-sm text-sm">
                <thead>
                    <tr>
                        <th>Embarcação</th>
                        <th>Satisfação</th>
                       
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result_view && $result_view->num_rows > 0) {
                        while($row_view = $result_view->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row_view['embarcacao']); ?></td>
                            <td><?php echo htmlspecialchars($row_view['satisfacao']); ?></td>
                            
                        </tr>
                    <?php endwhile; } else { echo "<tr><td colspan='3'>Nenhum registro encontrado para a View.</td></tr>"; } ?>
                </tbody>
            </table>
        </div>

        <div class="sql-section">
            <h4>6.1 UNION ALL: Atividade Consolidada (Pesquisas + Feedbacks)</h4>
            <p class="mb-3">Combina registros da tabela `respostas_pesquisa` e `feedbacks` em uma única linha do tempo (Últimos 10 Registros).</p>
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

        <h3 class="text-2xl font-semibold mt-6 mb-4 border-b pb-2 text-gray-700">Registros de Pesquisa (Tabela Principal)</h3>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="table-custom shadow-lg">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Embarcação</th>
                            <th>Frequência</th>
                       
                            <th>Espécies</th>
                            <th>Satisfação</th>
                            <th>Ações (9. TRIGGER)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['embarcacao']); ?></td>
                            <td><?php echo htmlspecialchars($row['frequencia']); ?></td>
                            <td><?php echo htmlspecialchars($row['especies']); ?></td>
                            <td><?php echo htmlspecialchars($row['satisfacao']); ?></td>
                            <td>
                                <a href="admin_pesquisa.php?edit_id=<?php echo $row['id']; ?>" class="edit-btn">Editar</a>
                                <a href="admin_pesquisa.php?delete_id=<?php echo $row['id']; ?>" 
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
            <p class="text-lg text-center py-8 bg-gray-50 border border-gray-200 rounded-lg">Nenhuma resposta de pesquisa encontrada no banco de dados.</p>
        <?php endif; ?>
    </div>

    <div class="sql-section mx-auto max-w-3xl text-center"> 
    <h4>9. TRIGGERS: Log de Auditoria (Demonstração)</h4>
    <p class="mb-3">A exclusão de dados é registrada automaticamente no log de auditoria.</p>
    <a href="admin_log_pesquisa.php" class="btn-sql-action" style="background-color: #F59E0B; color: #fff;">
        <i class="bi bi-list-columns-reverse"></i> Visualizar Log de Auditoria
    </a>
</div>

</body>
</html>

<?php
$conn->close();
?>