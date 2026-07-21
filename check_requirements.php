<?php
/**
 * Verificação de requisitos do sistema
 *
 * Este script verifica se o servidor atende aos requisitos mínimos
 * para executar a aplicação de geração de relatórios PDF do Zabbix.
 */

// Versão mínima do PHP necessária
define('MIN_PHP_VERSION', '7.2.0');

// Extensões PHP necessárias
$requiredExtensions = [
    'curl', 'gd', 'json', 'mbstring', 'xml', 'zip', 'zlib', 'fileinfo'
];

// Verificar versão do PHP
$phpVersion = phpversion();
$phpVersionOk = version_compare($phpVersion, MIN_PHP_VERSION, '>=');

// Verificar extensões
$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

// Verificar permissões de escrita
$writableDirs = [
    __DIR__ . '/tmp' => 'Diretório temporário',
    __DIR__ . '/logs' => 'Diretório de logs'
];

$permissionIssues = [];
foreach ($writableDirs as $dir => $description) {
    if (!is_writable($dir) && !@mkdir($dir, 0777, true)) {
        $permissionIssues[] = "$description ($dir) não tem permissão de escrita";
    }
}

// Mostrar resultados
header('Content-Type: text/plain; charset=utf-8');
echo "=== Verificação de Requisitos do Sistema ===\n\n";

echo "Versão do PHP: $phpVersion " . ($phpVersionOk ? "✓" : "✗ (É necessário " . MIN_PHP_VERSION . " ou superior)") . "\n";

if (!empty($missingExtensions)) {
    echo "\n✗ Extensões PHP faltando: " . implode(', ', $missingExtensions) . "\n";
} else {
    echo "✓ Todas as extensões PHP necessárias estão instaladas\n";
}

if (!empty($permissionIssues)) {
    echo "\n✗ Problemas de permissão:\n  " . implode("\n  ", $permissionIssues) . "\n";
} else {
    echo "✓ Permissões de escrita corretas\n";
}

// Verificar dependências do Composer
$vendorAutoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    echo "\n✗ As dependências do Composer não estão instaladas.\n";
    echo "  Execute 'composer install --no-dev --optimize-autoloader'\n";
} else {
    echo "✓ Dependências do Composer instaladas\n";
}

// Resumo
$hasErrors = !$phpVersionOk || !empty($missingExtensions) || !empty($permissionIssues) || !file_exists($vendorAutoload);

if ($hasErrors) {
    echo "\n❌ Foram encontrados problemas que devem ser resolvidos antes de continuar.\n";
    exit(1);
} else {
    echo "\n✅ Todos os requisitos são atendidos!\n";
    echo "Você pode executar a aplicação sem problemas.\n";
    exit(0);
}
