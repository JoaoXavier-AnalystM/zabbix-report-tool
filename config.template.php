<?php
/**
 * config.template.php — Template para CI/CD
 * O deploy via GitHub Actions substitui {{VARIAVEIS}} pelos secrets.
 */

// URL do frontend do Zabbix (sem barra no final)
define('ZABBIX_URL', '{{ZABBIX_URL}}');
define('ZABBIX_TZ', '{{ZABBIX_TZ}}');

// URL completa da API do Zabbix
define('ZABBIX_API_URL', rtrim(ZABBIX_URL, '/').'/api_jsonrpc.php');

// Cada usuário autentica com suas próprias credenciais Zabbix
define('CUSTOM_LOGO_PATH', 'assets/Zabbix_logo.png');
define('APPLY_LOGO_BLEND_MODE', true);

// ===== CONFIGURAÇÃO AVANÇADA =====

define('PDF_ENGINE', 'dompdf');
define('VERIFY_SSL', true);

// Prefixo/sufixo para LDAP/AD (deixe vazio se não usa)
define('ZBX_USER_PREFIX', '');
define('ZBX_USER_SUFFIX', '');

// Diretórios
$baseDir = __DIR__;

if (!defined('TMP_DIR')) {
    $tmpDir = "{$baseDir}/tmp";
    if (!is_writable($tmpDir) && !@mkdir($tmpDir, 0777, true)) {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zbx_pdf';
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0777, true);
    }
    define('TMP_DIR', $tmpDir);
}

if (!defined('LOG_DIR')) {
    $logDir = "{$baseDir}/logs";
    if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
    define('LOG_DIR', $logDir);
}

// Erros
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', LOG_DIR . '/error.log');

date_default_timezone_set('America/Santiago');

// Autoloader
if (file_exists(__DIR__ . '/autoload.php')) {
    require_once __DIR__ . '/autoload.php';
} else {
    require_once __DIR__ . '/vendor/autoload.php';
}
