<?php
// ARQUIVO: html/editar_perfil.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../php/conexao.php'; 

// PORTÃO DE SEGURANÇA
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: login.php?status=error&mensagem=" . urlencode("Acesso negado. Faça login para editar seu perfil."));
    exit();
}

$email_usuario = $_SESSION['user_email'];
$user_id = $_SESSION['user_id'] ?? 0;

// 1. SELECT para carregar dados atuais do usuário
$stmt = $conn->prepare("SELECT nome, email FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$dados_atuais = $result->fetch_assoc();
$stmt->close();

$nome_atual = $dados_atuais['nome'] ?? '';
$email_atual = $dados_atuais['email'] ?? '';
$mensagem_status = $_GET['mensagem'] ?? '';
$status_type = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Editar Perfil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />

    <style>
        /* (Inclua o CSS base do seu projeto: .glass-form, .btn-submit, .status-message, etc.) */
        body { font-family: 'Inter', sans-serif; line-height: 1.6; background-color: #1a1a2e; background-image: linear-gradient(135deg, #1e3a8a 0%, #1a1a2e 100%); color: #E2E8F0; min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        .glass-form { background-color: rgba(255, 255, 255, 0.1); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.4); border-radius: 1.5rem; padding: 3rem; width: 90%; max-width: 600px; }
        .glass-form h2 { color: #FFFFFF; font-weight: 700; text-align: center; margin-bottom: 2rem; font-size: 2rem; }
        .glass-form label { color: #CBD5E1; font-weight: 600; margin-top: 0.5rem; display: block; }
        .glass-form input { background-color: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.3); color: #E2E8F0; padding: 0.75rem; border-radius: 0.5rem; width: 100%; margin-top: 0.25rem; }
        .btn-submit { background: linear-gradient(45deg, #10B981, #059669); color: white; padding: 0.5rem -0.0rem; border: none; border-radius: 0.75rem; cursor: pointer; font-weight: 600; font-size: 1.1rem; transition: all 0.3s ease-in-out; box-shadow: 0 4px px rgba(0, 0, 0, 0.4); margin-top: 1.2rem; width: 100%; }
        .btn-submit:hover { background: linear-gradient(5deg, #059669, #047857); transform: translateY(-2px); }
        .status-message { padding: 1rem; border-radius: 0.5rem; font-weight: 600; text-align: center; margin-bottom: 1.5rem; }
        .status-success { background-color: #10B981; color: white; }
        .status-error { background-color: #EF4444; color: white; }
        .btn-cancel { background-color: #94A3B8; color: white; margin-top: 1rem; }
        .btn-cancel:hover { background-color: #64748B; }
        
    </style>
</head>
<body>

    <?php if ($mensagem_status): ?>
        <div class="status-message status-<?php echo htmlspecialchars($status_type); ?>" style="position: absolute; top: 20px;">
            <?php echo htmlspecialchars(urldecode($mensagem_status)); ?>
        </div>
    <?php endif; ?>

    <div class="glass-form">
        <h2><i class="bi bi-pencil-square"></i> Editar Seu Perfil</h2>
        <form action="processa_edicao_perfil.php" method="POST">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
            
            <label for="nome">Nome Completo:</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome_atual); ?>" required>

            <label for="email">Email (Atual: <?php echo htmlspecialchars($email_atual); ?>):</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email_atual); ?>" required>
            
            <label for="senha_nova">Nova Senha (Deixe em branco para manter a atual):</label>
            <input type="password" id="senha_nova" name="senha_nova">

            <label for="confirmar_senha">Confirmar Nova Senha:</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha">
            
            <button type="submit" class="btn-submit">Salvar Alterações</button>

            <a href="processa_edicao_perfil.php" class="btn-submit btn-cancel">
                <i class="bi bi-arrow-left"></i> Voltar sem Salvar
            </a>
        </form>
    </div>

</body>
</html>
<?php $conn->close(); ?>