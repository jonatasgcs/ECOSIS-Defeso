<?php
// ARQUIVO: php/admin_log_feedback.php

include 'conexao.php'; 

// PORTÃO DE SEGURANÇA
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../login.php?status=error&mensagem=" . urlencode("Acesso restrito. Faça login para ver o log."));
    exit();
}

$conn = $GLOBALS['conn'];
$email_usuario = $_SESSION['user_email'] ?? 'Usuário Logado';

// Consulta o Log de Auditoria, FILTRANDO APENAS A TABELA FEEDBACKS
$sql_log = "SELECT * FROM log_auditoria 
            WHERE tabela_origem = 'feedbacks' 
            ORDER BY data_acao DESC";
$result_log = $conn->query($sql_log);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria de Feedbacks - TRIGGERS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; color: #334155; }
        .container-admin { max-width: 1000px; margin: 2rem auto; padding: 2.5rem; background-color: #FFFFFF; border-radius: 1.25rem; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); }
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0; }
        .table-custom th, .table-custom td { padding: 10px 15px; text-align: left; border-bottom: 1px solid #E2E8F0; }
        .table-custom th { background-color: #EBF8FF; color: #1E293B; font-weight: 700; border-top: 2px solid #3B82F6; }
        .success-msg { background-color: #10B981; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container-admin">
        <h2 class="text-3xl font-bold text-center mb-6 text-gray-800">
            <i class="bi bi-gear-fill mr-2"></i> Auditoria de Feedbacks (TRIGGERS)
        </h2>
        
        <div class="mb-4">
            <p class="text-lg text-gray-700">Esta tabela mostra todas as ações de exclusão registradas automaticamente pelo **TRIGGER** na tabela de Feedbacks.</p>
        </div>

        <?php if ($result_log && $result_log->num_rows > 0): ?>
            <div class="overflow-x-auto mt-6">
                <table class="table-custom shadow-lg">
                    <thead>
                        <tr>
                            <th>ID Log</th>
                            <th>ID Deletado</th>
                            <th>Detalhe</th>
                            <th>Data da Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result_log->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['log_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['id_registro_deletado']); ?></td>
                            <td><?php echo htmlspecialchars($row['detalhes']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['data_acao'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-lg text-center py-8 bg-gray-50 border border-gray-200 rounded-lg">Nenhum evento de auditoria registrado ainda para esta tabela.</p>
        <?php endif; ?>
        
        <a href="admin_feedbacks.php" class="text-blue-600 hover:text-blue-800 font-medium mt-6 inline-flex items-center">
            <i class="bi bi-arrow-left mr-2"></i> Voltar para Admin Feedbacks
        </a>
    </div>
</body>
</html>
<?php $conn->close(); ?>