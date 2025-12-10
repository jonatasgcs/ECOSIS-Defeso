<?php
// Arquivo: php/logout.php

// Garante que a sessão está ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Destrói a sessão e encerra a variável 'logado'
session_destroy();

// Redireciona para a página inicial (index.html está um nível acima da pasta html/php)
header("Location: ../../index.html");
exit();
?>