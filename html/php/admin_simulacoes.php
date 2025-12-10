<?php
// Define o nome do arquivo, que deve ser 'admin_simulacoes.php'

// 1. INCLUI A CONEXÃO (que inicia a sessão)
include 'conexao.php';

// 2. VERIFICA SE O USUÁRIO ESTÁ LOGADO (PORTÃO DE SEGURANÇA)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    // CORREÇÃO: Usar login.php
    header("Location: ../login.php?status=error&mensagem=" . urlencode("Acesso restrito. Por favor, faça login."));
    exit();
}

// Obtém o email para exibir no painel (opcional)
$email_usuario = $_SESSION['user_email'] ?? 'Usuário Logado';

$mensagem_status = '';

// Variável de controle para o TRIGGER
$trigger_info = '';
if (isset($_GET['trigger_status']) && $_GET['trigger_status'] == 'deleted') {
    $trigger_info = "Ação de deleção foi registrada na tabela de auditoria via **TRIGGER**!";
}


// 3. LÓGICA DE DELEÇÃO (DELETE) - ATIVA O TRIGGER (9. TRIGGERS)
if (isset($_GET['delete_id'])) {
    $id_para_deletar = $conn->real_escape_string($_GET['delete_id']);

    $sql_delete = "DELETE FROM simulacoes WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_para_deletar);

    if ($stmt_delete->execute()) {
        $mensagem_status = "Registro ID " . $id_para_deletar . " deletado com sucesso!";
        // Redireciona com status do TRIGGER
        header("Location: admin_simulacoes.php?status=sucesso&msg=" . urlencode($mensagem_status) . "&trigger_status=deleted");
        exit();
    } else {
        $mensagem_status = "Erro ao deletar o registro: " . $stmt_delete->error;
        header("Location: admin_simulacoes.php?status=erro&msg=" . urlencode($mensagem_status));
        exit();
    }
    $stmt_delete->close();
}

// 4. LÓGICA DE BUSCA (SELECTs Avançados)

// =======================================================
// 8. STORED PROCEDURE (Ação de Rotina)
// =======================================================
if (isset($_GET['action']) && $_GET['action'] == 'run_sp') {
    try {
        // 1. CHAMA O STORED PROCEDURE
        // Usa query simples, sem prepare, para CALL
        $sql = "CALL sp_relatorio_total_simulacoes()";
        $result_sp = $conn->query($sql);

        // 2. TRATAMENTO DO RESULTADO: Limpa o resultado anterior antes de continuar
        // Se a chamada do SP retornar um resultado, faz o fetch
        if ($result_sp) {
            $dados = $result_sp->fetch_assoc();
            
            // 3. ATRIBUIÇÃO DOS VALORES PELOS ALIASES CORRETOS DO SP
            $total = $dados['total_registros'] ?? 0;
            $ultima_data = $dados['ultima_data_registro'] ?? 'N/A';
            
            // 4. Múltiplos resultados do SP podem ser o problema. Fechamos o resultado.
            $result_sp->close();
            
            // 5. Necessário para evitar "Commands out of sync"
            // Se houver mais de um conjunto de resultados (o que CALLs fazem), precisamos limpar.
            while($conn->more_results() && $conn->next_result()) {
                // Esvazia os resultados adicionais
                if($res = $conn->store_result()) {
                    $res->free();
                }
            }

            $mensagem_status = "Procedimento executado! Total Simulações: {$total}. Última Data: " . date('d/m/Y H:i', strtotime($ultima_data));
        } else {
            // Se a query de CALL falhou (mas não lançou exceção)
            throw new Exception("Falha na execução do CALL: " . $conn->error);
        }
        
        header("Location: admin_simulacoes.php?status=sucesso&msg=" . urlencode($mensagem_status));
        exit();
        
    } catch (Exception $e) {
        // Captura erros de exceção (incluindo o que o CALL pode gerar)
        $mensagem_status = "Erro ao executar o procedimento (SP). Verifique o DB. Detalhe: " . $e->getMessage();
        header("Location: admin_simulacoes.php?status=erro&msg=" . urlencode($mensagem_status));
        exit();
    }
}


// =======================================================
// 6.3 SUBQUERY (Análise Comparativa)
// =======================================================
$sql_subquery = "SELECT nome, COUNT(id) AS contagem
                 FROM simulacoes
                 GROUP BY nome
                 HAVING contagem > (SELECT AVG(contagem_interna) FROM (SELECT COUNT(id) AS contagem_interna FROM simulacoes GROUP BY email) AS subquery_avg)
                 ORDER BY contagem DESC";
$result_subquery = $conn->query($sql_subquery);


// =======================================================
// 6.1 UNION ALL (Relatório Consolidado)
// =======================================================
$sql_union = "
    (SELECT email, data_simulacao AS data_registro, 'Simulação' AS tipo, resultado AS detalhe FROM simulacoes)
    UNION ALL
    (SELECT email, data_envio AS data_registro, 'Feedback' AS tipo, mensagem AS detalhe FROM feedbacks)
    ORDER BY data_registro DESC LIMIT 10";
$result_union = $conn->query($sql_union);


// =======================================================
// 7. VIEWS / 6.2 IF e CASE (Consulta Classificada)
// =======================================================
// NOTA: A VIEW (vw_simulacoes_classificadas) já usa a lógica CASE embutida
$sql_select = "SELECT id, nome, email, resultado, Status_Classificado, data_simulacao 
               FROM vw_simulacoes_classificadas 
               ORDER BY data_simulacao DESC";
$result = $conn->query($sql_select);


// SELECT VIEW Simples (Demonstração 7)
$sql_view = "SELECT id, nome, Status_Classificado, resultado FROM vw_simulacoes_classificadas LIMIT 3";
$result_view = $conn->query($sql_view);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Simulações - Defeso</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; color: #334155; }
        .container-admin { max-width: 1200px; margin: 2rem auto; padding: 2.0rem; background-color: #FFFFFF; border-radius: 1.rem; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); }
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0; }
        .table-custom th, .table-custom td { padding: 12px 15px; text-align: left; border-bottom: 0.3spx solid #E2E8F0; }
        .table-custom th { background-color: #EBF8FF; color: #1E293B; font-weight: 700; border-top: 2px solid #3B82F6; }
        .table-custom tr:last-child td { border-bottom: none; }
        .delete-btn { background-color: #EF4444; color: white; padding: 6px 12px; border-radius: 6px; font-weight: 600; transition: background-color 0.3s; text-decoration: none; }
        .delete-btn:hover { background-color: #DC2626; }
        .success-msg { background-color: #10B981; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600; }
        .error-msg { background-color: #EF4444; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600; }
        .sql-section { border: 1px solid #E2E8F0; padding: 1.5rem; border-radius: 1rem; margin-bottom: 1.5rem; background-color: #F9FAFB; }
        .sql-section h4 { font-weight: bold; color: #3B82F6; margin-bottom: 1rem; border-bottom: 1px dashed #E2E8F0; padding-bottom: 0.5rem; }
        .btn-sql-action { background-color: #4F46E5; color: white; padding: 8px 15px; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: background-color 0.3s; }
        .btn-sql-action:hover { background-color: #4338CA; }
        .trigger-status-box { padding: 1rem; background-color: #FFEFD5; border: 1px solid #F59E0B; color: #78350F; border-radius: 8px; font-weight: 600; margin-top: 1.5rem; }
        .userbar { display: flex; gap: .75rem; align-items: center; justify-content: center; margin-top: .5rem; }
        .userbar .pill { background: #F1F5F9; border: 1px solid #E2E8F0; color: #475569; padding: .35rem .75rem; border-radius: 9999px; font-size: .875rem; }
    </style>
</head>
<body>

    <div class="container-admin">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h2 class="text-3xl font-bold text-gray-800">Área Administrativa - Simulações</h2>
            <div class="flex items-center userbar">
                <span class="pill"><i class="bi bi-person-circle mr-1"></i> <?php echo htmlspecialchars($email_usuario); ?></span>
                <a href="logout.php" class="delete-btn">
                    <i class="bi bi-box-arrow-right"></i> Sair
                </a>
            </div>
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

        <a href="../direitos.html" class="text-blue-600 hover:text-blue-800 font-medium mb-6 inline-flex items-center">
            <i class="bi bi-arrow-left mr-2"></i> Voltar para Simulação
        </a>

        
        <h3 class="text-2xl font-semibold mt-6 mb-4 text-gray-700">Demonstrações de SQL Avançado</h3>

        <div class="grid md:grid-cols-2 gap-4">

            <div class="sql-section">
                <h4>8. STORED PROCEDURE: Relatório Global</h4>
                <p class="mb-3">Demonstração de execução de um procedimento armazenado (Contagem Total de Registros).</p>
                <a href="admin_simulacoes.php?action=run_sp" class="btn-sql-action">
                    <i class="bi bi-gear-fill"></i> 8.1 Executar sp_relatorio_total_simulacoes
                </a>
            </div>

            <div class="sql-section">
                <h4>6.3 SUBQUERY: Usuários Acima da Média de Registros</h4>
                <p class="mb-3">Lista usuários com mais registros de simulação do que a média geral (Subconsulta e HAVING).</p>
                <table class="table-custom shadow-sm text-sm">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Total Registros</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_subquery && $result_subquery->num_rows > 0) {
                            while($row_sub = $result_subquery->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row_sub['nome']); ?></td>
                                <td><?php echo htmlspecialchars($row_sub['contagem']); ?></td>
                            </tr>
                        <?php endwhile; } else { echo "<tr><td colspan='2'>Nenhum usuário acima da média.</td></tr>"; } ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="sql-section mt-4">
            <h4>7. VIEWS / 6.2 IF e CASE: Classificação de Status</h4>
            <p class="mb-3">Consulta simples na **VIEW** (`vw_simulacoes_classificadas`) para visualizar o status pré-classificado via lógica **CASE**.</p>
            <table class="table-custom shadow-sm text-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Resultado Original</th>
                        <th>Status Classificado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result_view && $result_view->num_rows > 0) {
                        while($row_view = $result_view->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row_view['id']); ?></td>
                            <td><?php echo htmlspecialchars($row_view['nome']); ?></td>
                            <td><?php echo htmlspecialchars($row_view['resultado']); ?></td>
                            <td>**<?php echo htmlspecialchars($row_view['Status_Classificado']); ?>**</td>
                        </tr>
                    <?php endwhile; } else { echo "<tr><td colspan='4'>Nenhum registro encontrado na View.</td></tr>"; } ?>
                </tbody>
            </table>
        </div>

        <div class="sql-section">
            <h4>6.1 UNION ALL: Atividade Consolidada (Simulações + Feedbacks)</h4>
            <p class="mb-3">Combina registros da tabela `simulacoes` e `feedbacks` em uma única linha do tempo (Últimos 10 Registros).</p>
            <table class="table-custom shadow-sm text-sm">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Email</th>
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
                            <td><?php echo htmlspecialchars($row_union['email']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row_union['data_registro'])); ?></td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($row_union['detalhe']); ?></td>
                        </tr>
                    <?php endwhile; } else { echo "<tr><td colspan='4'>Nenhuma atividade recente encontrada.</td></tr>"; } ?>
                </tbody>
            </table>
        </div>
        
        <!-- REGISTROS DE SIMULAÇÕES (TABELA PRINCIPAL) -->
        <h3 class="text-2xl font-semibold mt-6 mb-4 border-b pb-2 text-gray-700">
            Registros de Simulações (Tabela Principal)
        </h3>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="table-custom shadow-lg">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Status (6.2 CASE)</th>
                            <th>Data Simulação</th>
                            <th>Ação (9. TRIGGER)</th>
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
                                    <?php echo $row['resultado'] == 'Provável Benefício' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo htmlspecialchars($row['Status_Classificado']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['data_simulacao'])); ?></td>
                            <td>
                                <a href="admin_simulacoes.php?delete_id=<?php echo $row['id']; ?>" 
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
            <p class="text-lg text-center py-8 bg-gray-50 border border-gray-200 rounded-lg">
                Nenhuma simulação encontrada no banco de dados.
            </p>
        <?php endif; ?>

        <!-- 9. TRIGGERS: LOG DE AUDITORIA (AGORA DENTRO DO CONTAINER) -->
        <div class="sql-section mt-6 text-center">
            <h4>9. TRIGGERS: Log de Auditoria (Demonstração)</h4>
            <p class="mb-3">A exclusão de dados é registrada automaticamente no log de auditoria.</p>
            <a href="admin_log.php" class="btn-sql-action" style="background-color: #F59E0B; color: #fff;">
                <i class="bi bi-list-columns-reverse"></i> Visualizar Log de Auditoria
            </a>
        </div>

    </div> <!-- fecha .container-admin -->

</body>
</html>

<?php
$conn->close();
?>
