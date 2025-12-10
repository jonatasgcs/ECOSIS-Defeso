<?php 
// ARQUIVO: html/perfil.php (CENTRAL DE PERFIL AVANÇADA)

// 1. Inicia a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. PORTÃO DE SEGURANÇA: VERIFICA SE O USUÁRIO ESTÁ LOGADO
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../login.html?status=error&mensagem=" . urlencode("Você deve fazer login ou se cadastrar para acessar o seu perfil."));
    exit();
}

// 3. Inclui a conexão com o banco de dados
include '../php/conexao.php';

// Variáveis essenciais
$email_usuario = $_SESSION['user_email'] ?? 'Usuário';
$user_id       = $_SESSION['user_id'] ?? 0; 

// Busca ID do usuário e data de cadastro
if ($user_id == 0) {
    $stmt_id = $conn->prepare("SELECT id_usuario, data_cadastro FROM usuarios WHERE email = ?");
    $stmt_id->bind_param("s", $email_usuario);
    $stmt_id->execute();
    $dados_user = $stmt_id->get_result()->fetch_assoc();
    $user_id = $dados_user['id_usuario'] ?? 0;
    $data_cadastro_raw = $dados_user['data_cadastro'] ?? 'N/A';
    $stmt_id->close();
} else {
    $sql_data_cadastro = "SELECT data_cadastro FROM usuarios WHERE id_usuario = ?";
    $stmt5 = $conn->prepare($sql_data_cadastro);
    $stmt5->bind_param("i", $user_id);
    $stmt5->execute();
    $data_cadastro_raw = $stmt5->get_result()->fetch_assoc()['data_cadastro'] ?? 'N/A';
    $stmt5->close();
}
$data_cadastro = ($data_cadastro_raw != 'N/A') ? date('d/m/Y', strtotime($data_cadastro_raw)) : 'N/A';


// =========================================================================
//  MÉTRICAS GLOBAIS (SELECT COUNT)
// =========================================================================

$total_simulacoes = $conn->query("SELECT COUNT(id) AS total FROM simulacoes")
                         ->fetch_assoc()['total'] ?? 0;

$total_feedbacks = $conn->query("SELECT COUNT(id) AS total FROM feedbacks")
                        ->fetch_assoc()['total'] ?? 0;

$total_pesquisas = $conn->query("SELECT COUNT(id) AS total FROM respostas_pesquisa")
                        ->fetch_assoc()['total'] ?? 0;

$resultados_positivos = $conn->query("SELECT COUNT(id) AS total FROM simulacoes WHERE resultado = 'Provável Benefício'")
                             ->fetch_assoc()['total'] ?? 0;


// =========================================================================
// 1. SELECT CASE & VIEW (Status da última Simulação + Resumo Geral de Risco)
// =========================================================================
$status_simulacao     = 'N/A';
$resumo_risco         = [];
$total_riscos         = 0;
$status_predominante  = 'Sem dados';

// Última simulação (usa VIEW com CASE)
try {
    $sql_status_sim = "
        SELECT Status_Classificado, resultado, data_simulacao
        FROM vw_simulacoes_classificadas 
        ORDER BY data_simulacao DESC 
        LIMIT 1
    ";
    $res_status = $conn->query($sql_status_sim);
    if ($res_status) {
        $row_status      = $res_status->fetch_assoc();
        $status_simulacao = $row_status['Status_Classificado'] ?? 'N/A';
        $resultado_bruto  = $row_status['resultado'] ?? null;
        $data_ultima_sim  = $row_status['data_simulacao'] ?? null;
        $res_status->free();
    }
} catch (Exception $e) { 
    $status_simulacao = 'VIEW/CASE N/A'; 
    $resultado_bruto  = null;
    $data_ultima_sim  = null;
}

// Resumo geral por status (também usando a VIEW)
try {
    $sql_resumo_risco = "
        SELECT Status_Classificado, COUNT(*) AS total
        FROM vw_simulacoes_classificadas
        GROUP BY Status_Classificado
    ";
    $res_resumo = $conn->query($sql_resumo_risco);
    if ($res_resumo) {
        while ($row = $res_resumo->fetch_assoc()) {
            $status_label = $row['Status_Classificado'] ?? 'N/A';
            $qtd          = (int)($row['total'] ?? 0);
            $total_riscos += $qtd;
            $resumo_risco[] = [
                'status' => $status_label,
                'total'  => $qtd
            ];
        }
        $res_resumo->free();
    }

    if ($total_riscos > 0 && count($resumo_risco) > 0) {
        // encontra status predominante
        $max = 0;
        foreach ($resumo_risco as $item) {
            if ($item['total'] > $max) {
                $max = $item['total'];
                $status_predominante = $item['status'];
            }
        }
    } elseif ($total_riscos == 0) {
        $status_predominante = 'Sem simulações cadastradas';
    }

} catch (Exception $e) {
    $resumo_risco        = [];
    $total_riscos        = 0;
    $status_predominante = 'Erro ao carregar resumo de risco';
}


// =========================================================================
// 2. SUBQUERY (Participação Global na Pesquisa)
// =========================================================================
$total_respostas_glob    = 0;
$total_usuarios_glob     = 0;
$media_respostas_usuario = 0.0;
$nivel_participacao      = 'Sem dados';

try {
    $sql_sub_global = "
        SELECT
            COUNT(*) AS total_respostas,
            COUNT(DISTINCT id_usuario) AS total_usuarios,
            (
                SELECT AVG(qtd) 
                FROM (
                    SELECT COUNT(*) AS qtd
                    FROM respostas_pesquisa
                    GROUP BY id_usuario
                ) AS t
            ) AS media_por_usuario
        FROM respostas_pesquisa
    ";

    $res_sub_global = $conn->query($sql_sub_global);
    if ($res_sub_global) {
        $row_g = $res_sub_global->fetch_assoc();
        $total_respostas_glob    = (int)($row_g['total_respostas'] ?? 0);
        $total_usuarios_glob     = (int)($row_g['total_usuarios'] ?? 0);
        $media_respostas_usuario = $row_g['media_por_usuario'] !== null ? (float)$row_g['media_por_usuario'] : 0.0;
        $res_sub_global->free();
    }

    if ($total_respostas_glob == 0 || $total_usuarios_glob == 0) {
        $nivel_participacao = 'Sem dados suficientes';
    } else {
        if ($media_respostas_usuario < 2) {
            $nivel_participacao = 'Baixa participação';
        } elseif ($media_respostas_usuario < 5) {
            $nivel_participacao = 'Participação moderada';
        } else {
            $nivel_participacao = 'Alta participação';
        }
    }

} catch (Exception $e) {
    $nivel_participacao      = 'Erro na Subquery';
    $total_respostas_glob    = 0;
    $total_usuarios_glob     = 0;
    $media_respostas_usuario = 0.0;
}


// =========================================================================
// 3. UNION ALL (Linha do Tempo GERAL - GLOBAL)
// =========================================================================
$sql_union = "
    (SELECT data_simulacao AS data, 'Simulação' AS tipo, resultado AS detalhe FROM simulacoes)
    UNION ALL
    (SELECT data_resposta AS data, 'Pesquisa' AS tipo, embarcacao AS detalhe FROM respostas_pesquisa)
    UNION ALL
    (SELECT data_envio AS data, 'Feedback' AS tipo, mensagem AS detalhe FROM feedbacks)
    ORDER BY data DESC 
    LIMIT 5
";
$result_union = $conn->query($sql_union);


// =========================================================================
// 4. SELECT DETALHADO (Última Pesquisa Global)
// =========================================================================
$sql_last_pesquisa = "
    SELECT embarcacao, data_resposta 
    FROM respostas_pesquisa 
    ORDER BY data_resposta DESC 
    LIMIT 1
";
$res_last_pesq = $conn->query($sql_last_pesquisa);
$ultimo_pesquisa = $res_last_pesq ? $res_last_pesq->fetch_assoc() : null;
$data_pesquisa = $ultimo_pesquisa ? date('d/m/Y', strtotime($ultimo_pesquisa['data_resposta'])) : 'N/A';
$embarcacao = $ultimo_pesquisa ? htmlspecialchars($ultimo_pesquisa['embarcacao']) : 'Nenhum registro';


// =========================================================================
// 5. LOG DE AUDITORIA (TRIGGERS) - últimos 5 registros
// =========================================================================
$sql_log_auditoria = "
    SELECT detalhes, data_acao, id_usuario_acao 
    FROM log_auditoria 
    ORDER BY data_acao DESC 
    LIMIT 5
";
$result_log = $conn->query($sql_log_auditoria);


// =========================================================================
// 6. STORED PROCEDURE (SP) - EXECUTAR RELATÓRIO VIA BOTÃO
// =========================================================================

$sp_total_registros   = null;
$sp_ultima_data       = null;
$sp_dias_desde_ultima = null;
$sp_msg               = '';

if (isset($_GET['action']) && $_GET['action'] === 'run_sp') {
    $sql_sp = "CALL sp_relatorio_total_simulacoes()";
    if ($result_sp = $conn->query($sql_sp)) {
        $dados_sp = $result_sp->fetch_assoc();
        $sp_total_registros = (int)($dados_sp['total_registros'] ?? 0);
        $ultima_raw = $dados_sp['ultima_data_registro'] ?? null;

        if ($ultima_raw) {
            $sp_ultima_data = date('d/m/Y H:i', strtotime($ultima_raw));

            try {
                $dt_ultima = new DateTime($ultima_raw);
                $dt_hoje   = new DateTime();
                $diff      = $dt_hoje->diff($dt_ultima);
                $sp_dias_desde_ultima = $diff->days;
            } catch (Exception $e) {
                $sp_dias_desde_ultima = null;
            }

        } else {
            $sp_ultima_data       = 'N/A';
            $sp_dias_desde_ultima = null;
        }

        $result_sp->free();

        if ($conn->more_results()) {
            $conn->next_result();
        }

        $sp_msg = "Stored Procedure executada com sucesso!";
    } else {
        $sp_msg = "Erro ao executar a Stored Procedure: " . $conn->error;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Central de Perfil - Pesca +</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <style>
        body {
            font-family: 'Inter', sans-serif; 
            line-height: 1.6; 
            background-color: #1a1a2e; 
            background-image: linear-gradient(135deg, #1e3a8a 0%, #1a1a2e 100%); 
            color: #E2E8F0;
        }
        .glass {
            background-color: rgba(255, 255, 255, 0.1); 
            backdrop-filter: blur(12px); 
            border: 1px solid rgba(255, 255, 255, 0.2); 
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.4);
            border-radius: 1.5rem; 
            padding: 2.5rem; 
            transition: all 0.4s;
        }
        .data-card {
            background-color: rgba(0, 0, 0, 0.3); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            padding: 1.5rem; 
            border-radius: 1rem;
        }
        .registro-acesso-btn {
            padding: 0.75rem 1rem; 
            border-radius: 0.5rem; 
            font-weight: 600; 
            text-decoration: none; 
            transition: all 0.3s ease; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 0.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .btn-login { border: 1px solid #93c5fd; color: #93c5fd; background-color: transparent; }
        .btn-logout { background-color: #ef4444; color: white; }
        .btn-cadastro { background-color: #3b82f6; color: white; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .text-title-slogan { font-size: 1.1rem; color: #93c5fd; }
        .info-table th, .info-table td { 
            padding: 8px; 
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); 
            font-size: 0.85rem; 
        }
        .badge-status {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    
    <div class="fixed top-0 left-0 w-full bg-gray-900 z-50 p-4 text-center">
        <p class="text-title-slogan">Pesca + (Central de Perfil)</p>
    </div>

    <main class="flex-grow max-w-7xl mx-auto p-8 mt-16">
        <h1 class="text-4xl font-bold text-center mb-4 text-white">
            <i class="bi bi-person-check-fill mr-2"></i> Central de Perfil
        </h1>
        <p class="text-center text-blue-300 mb-8 text-xl">
            Bem-vindo(a), <strong><?php echo htmlspecialchars($email_usuario); ?></strong>!
        </p>

        <div class="grid md:grid-cols-3 gap-6">
            
            <div class="flex flex-col gap-4">
                <h3 class="text-xl font-semibold mb-2 text-blue-300">Ações Rápidas</h3>
                
                <a href="../../index.html" class="registro-acesso-btn btn-login w-full text-center hover:bg-blue-900/20">
                    <i class="bi bi-house-door"></i> Voltar ao Site Normal
                </a>
                
                <a href="editar_perfil.php" class="registro-acesso-btn btn-cadastro w-full text-center hover:bg-indigo-700">
                    <i class="bi bi-pencil-square"></i> Editar Cadastro
                </a>
                
                <a href="../php/logout.php" class="registro-acesso-btn btn-logout w-full text-center hover:bg-red-700">
                    <i class="bi bi-box-arrow-right"></i> Sair da Conta
                </a>
                
                <div class="mt-4 p-4 glass text-center">
                    <p class="text-sm font-semibold text-gray-300">
                        Conta criada em: <span class="text-blue-200"><?php echo $data_cadastro; ?></span>
                    </p>
                </div>
            </div>

            <div class="md:col-span-2 glass">
                <h3 class="text-2xl font-bold mb-4 border-b pb-2 text-white">Seus Registros e Análises</h3>
                
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="data-card">
                        <p class="text-2xl font-bold text-blue-300"><?php echo (int)$total_simulacoes; ?></p>
                        <p class="text-xs text-gray-400">Total Simulações</p>
                    </div>
                    <div class="data-card">
                        <p class="text-2xl font-bold text-yellow-300"><?php echo (int)$total_pesquisas; ?></p>
                        <p class="text-xs text-gray-400">Total Pesquisas</p>
                    </div>
                    <div class="data-card">
                        <p class="text-2xl font-bold text-green-300"><?php echo (int)$resultados_positivos; ?></p>
                        <p class="text-xs text-gray-400">Simulações C/ Benefício</p>
                    </div>
                </div>

                <!-- 1. Status de Risco -->
                <h4 class="text-lg font-semibold mt-6 mb-3 text-blue-300 border-b pb-1">
                    1. Status de Risco (CASE / VIEW)
                </h4>
                <div class="data-card">
                    <p class="text-sm text-gray-300 mb-1">
                        Última simulação registrada:
                    </p>
                    <p class="text-lg font-bold text-white mb-1">
                        <?php 
                            // cor do badge de acordo com o status
                            $classe_badge = 'bg-gray-200 text-gray-800';
                            if (isset($status_simulacao)) {
                                if (strpos($status_simulacao, 'APROVADO') !== false) {
                                    $classe_badge = 'bg-green-100 text-green-800';
                                } elseif (strpos($status_simulacao, 'REVISÃO') !== false) {
                                    $classe_badge = 'bg-red-100 text-red-800';
                                } elseif (strpos($status_simulacao, 'AGUARDANDO') !== false) {
                                    $classe_badge = 'bg-yellow-100 text-yellow-800';
                                }
                            }
                        ?>
                        <span class="badge-status <?php echo $classe_badge; ?>">
                            <?php echo htmlspecialchars($status_simulacao); ?>
                        </span>
                    </p>
                    <?php if (!empty($resultado_bruto) || !empty($data_ultima_sim)): ?>
                        <p class="text-xs text-gray-300 mt-1">
                            Resultado original: 
                            <span class="font-semibold">
                                <?php echo htmlspecialchars($resultado_bruto ?? 'N/A'); ?>
                            </span>
                            <?php if (!empty($data_ultima_sim)): ?>
                                &nbsp;|&nbsp; Data:
                                <span class="font-semibold">
                                    <?php echo date('d/m/Y H:i', strtotime($data_ultima_sim)); ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>

                    <hr class="my-3 border-gray-600/40">

                    <p class="text-sm text-gray-300 mb-2">
                        Distribuição geral de risco (todas as simulações):
                    </p>

                    <?php if ($total_riscos > 0 && count($resumo_risco) > 0): ?>
                        <ul class="text-xs text-gray-200 space-y-1">
                            <?php foreach ($resumo_risco as $item): 
                                $perc = ($total_riscos > 0) ? ($item['total'] / $total_riscos) * 100 : 0;
                            ?>
                                <li>
                                    • <?php echo htmlspecialchars($item['status']); ?>: 
                                    <span class="font-semibold"><?php echo (int)$item['total']; ?></span>
                                    (<?php echo number_format($perc, 1, ',', '.'); ?>%)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="text-xs text-blue-200 mt-2">
                            Status predominante: <strong><?php echo htmlspecialchars($status_predominante); ?></strong>
                        </p>
                    <?php else: ?>
                        <p class="text-xs text-gray-400">
                            Ainda não há simulações suficientes para montar o resumo de risco.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- 2. Participação Global (SUBQUERY) -->
                <h4 class="text-lg font-semibold mt-6 mb-3 text-blue-300 border-b pb-1">
                    2. Participação Global na Pesquisa (SUBQUERY)
                </h4>
                <div class="data-card">
                    <?php if ($total_respostas_glob > 0 && $total_usuarios_glob > 0): ?>
                        <p class="text-sm text-gray-300 mb-1">
                            Total de respostas registradas: 
                            <span class="font-semibold"><?php echo $total_respostas_glob; ?></span>
                        </p>
                        <p class="text-sm text-gray-300 mb-1">
                            Total de usuários que responderam: 
                            <span class="font-semibold"><?php echo $total_usuarios_glob; ?></span>
                        </p>
                        <p class="text-sm text-gray-300 mb-2">
                            Média de respostas por usuário:
                            <span class="font-semibold">
                                <?php echo number_format((float)$media_respostas_usuario, 2, ',', '.'); ?>
                            </span>
                        </p>
                        <p class="text-sm font-bold mt-1
                            <?php 
                                if ($nivel_participacao === 'Baixa participação') {
                                    echo 'text-red-400';
                                } elseif ($nivel_participacao === 'Participação moderada') {
                                    echo 'text-yellow-300';
                                } elseif ($nivel_participacao === 'Alta participação') {
                                    echo 'text-green-400';
                                } else {
                                    echo 'text-gray-300';
                                }
                            ?>">
                            Nível de participação geral: <?php echo htmlspecialchars($nivel_participacao); ?>
                        </p>
                    <?php else: ?>
                        <p class="text-sm text-gray-300">
                            Ainda não há dados suficientes na pesquisa para calcular a participação global.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- 3. Linha do tempo -->
                <h4 class="text-lg font-semibold mt-6 mb-3 text-blue-300 border-b pb-1">
                    3. Linha do Tempo de Atividade (UNION ALL)
                </h4>
                <table class="w-full text-sm text-left info-table">
                    <thead class="text-xs text-gray-100 uppercase bg-gray-700/50">
                        <tr>
                            <th class="py-2 px-3">Tipo</th>
                            <th class="py-2 px-3">Detalhe</th>
                            <th class="py-2 px-3">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_union && $result_union->num_rows > 0) {
                            while($row = $result_union->fetch_assoc()): ?>
                            <tr class="bg-gray-800/20 border-b border-gray-700">
                                <td class="py-2 px-3 font-medium text-white">
                                    <?php echo htmlspecialchars($row['tipo']); ?>
                                </td>
                                <td class="py-2 px-3">
                                    <?php 
                                        $det = $row['detalhe'] ?? '';
                                        echo htmlspecialchars(mb_strimwidth($det, 0, 30, '...'));
                                    ?>
                                </td>
                                <td class="py-2 px-3">
                                    <?php echo date('d/m/Y', strtotime($row['data'])); ?>
                                </td>
                            </tr>
                        <?php endwhile; 
                        } else { 
                            echo '<tr><td colspan="3" class="py-3 px-3 text-center">Nenhuma atividade recente.</td></tr>'; 
                        } ?>
                    </tbody>
                </table>

                <!-- 4. Última pesquisa -->
                <h4 class="text-lg font-semibold mt-6 mb-3 text-blue-300 border-b pb-1">
                    4. Última Pesquisa Registrada (SELECT Detalhado)
                </h4>
                <div class="data-card">
                    <p class="text-lg font-bold text-white mb-2">
                        Embarcação: <span class="text-yellow-300"><?php echo $embarcacao; ?></span>
                    </p>
                    <p class="text-sm text-gray-400">
                        Data: <?php echo $data_pesquisa; ?>
                    </p>
                </div>

                <!-- 5. Log auditoria -->
                <h4 class="text-lg font-semibold mt-6 mb-3 text-blue-300 border-b pb-1">
                    5. Log de Auditoria (TRIGGER)
                </h4>
                <table class="w-full text-sm text-left info-table">
                    <thead class="text-xs text-gray-100 uppercase bg-gray-700/50">
                        <tr>
                            <th class="py-2 px-3">Usuário (ID)</th>
                            <th class="py-2 px-3">Detalhe da Ação</th>
                            <th class="py-2 px-3">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result_log && $result_log->num_rows > 0) {
                            while($row = $result_log->fetch_assoc()): ?>
                            <tr class="bg-gray-800/20 border-b border-gray-700">
                                <td class="py-2 px-3 font-medium text-blue-300">
                                    <?php echo (int)$row['id_usuario_acao']; ?>
                                </td>
                                <td class="py-2 px-3 font-medium text-red-400">
                                    <?php 
                                        $det2 = $row['detalhes'] ?? '';
                                        echo htmlspecialchars(mb_strimwidth($det2, 0, 45, '...'));
                                    ?>
                                </td>
                                <td class="py-2 px-3">
                                    <?php echo date('d/m/Y', strtotime($row['data_acao'])); ?>
                                </td>
                            </tr>
                        <?php endwhile; 
                        } else { 
                            echo '<tr><td colspan="3" class="py-3 px-3 text-center text-gray-500">Nenhuma ação de auditoria registrada.</td></tr>'; 
                        } ?>
                    </tbody>
                </table>

                <!-- 6. SP + dados da conta -->
                <h4 class="text-lg font-semibold mt-6 mb-3 text-blue-300 border-b pb-1">
                    6. Ações (SP) e Informações da Conta (SELECT JOIN)
                </h4>

                <a href="perfil.php?action=run_sp" class="registro-acesso-btn btn-login mt-2 hover:bg-blue-900/20">
                    <i class="bi bi-file-earmark-bar-graph"></i> Executar Relatório Resumido (SP)
                </a>

                <?php if (!empty($sp_msg)): ?>
                    <div class="data-card mt-4">
                        <p class="text-sm text-gray-300 mb-1">
                            <?php echo htmlspecialchars($sp_msg); ?>
                        </p>

                        <?php if ($sp_total_registros !== null): ?>
                            <p class="text-sm text-gray-300">
                                Total de simulações (SP): 
                                <span class="font-semibold"><?php echo $sp_total_registros; ?></span><br>
                                Última simulação registrada (SP):
                                <span class="font-semibold"><?php echo $sp_ultima_data; ?></span>
                            </p>

                            <?php if ($sp_dias_desde_ultima !== null): ?>
                                <p class="text-sm text-gray-300 mt-1">
                                    Dias desde a última simulação:
                                    <span class="font-semibold"><?php echo $sp_dias_desde_ultima; ?></span>
                                </p>
                                <p class="text-xs mt-1 
                                    <?php echo ($sp_dias_desde_ultima > 30) ? 'text-red-400' : 'text-green-400'; ?>">
                                    <?php 
                                        if ($sp_dias_desde_ultima > 30) {
                                            echo "Você está há mais de 30 dias sem simular. Considere atualizar sua análise.";
                                        } else {
                                            echo "Suas simulações estão relativamente recentes.";
                                        }
                                    ?>
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="data-card mt-4">
                    <p class="text-lg font-bold text-white mb-2">
                        Seu Email: <span class="text-green-300"><?php echo htmlspecialchars($email_usuario); ?></span>
                    </p>
                    <p class="text-sm text-gray-400">
                        Data de Cadastro: <?php echo $data_cadastro; ?>
                    </p>
                </div>
                
            </div>
        </div>
    </main>
</body>
</html>
