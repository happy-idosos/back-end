<?php
header('Content-Type: application/json; charset=utf-8');

$uri = $_SERVER['REQUEST_URI'] ?? '';
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$rewriteWorking = !str_contains($uri, 'check_rewrite.php');

echo json_encode([
    'status' => 200,
    'message' => $rewriteWorking
        ? '✅ Rewrite ativo — URLs sem index.php funcionarão.'
        : '❌ Rewrite inativo — Apache não está redirecionando para index.php.',
    'uri' => $uri,
    'script' => $script,
    'mod_rewrite_hint' => $rewriteWorking
        ? 'mod_rewrite está interceptando corretamente as requisições.'
        : 'Verifique se AllowOverride está como All e o mod_rewrite está habilitado no Apache.'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
