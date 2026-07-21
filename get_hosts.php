<?php
declare(strict_types=1);

// === INICIO DE SESI�N SEGURO ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticaci�n
if (empty($_SESSION['zbx_auth_ok'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Sesi�n inv�lida - Por favor inicie sesi�n nuevamente']);
    exit;
}

// Verificar que las credenciales existen
if (empty($_SESSION['zbx_api_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Credenciales no disponibles en sesi�n']);
    exit;
}

require_once __DIR__ . '/lib/i18n.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/ZabbixApiFactory.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $api = ZabbixApiFactory::createWithAuth(
        ZABBIX_API_URL,
        $_SESSION['zbx_api_token'],
        [
            'timeout' => 10,
            'verify_ssl' => defined('VERIFY_SSL') ? VERIFY_SSL : false
        ]
    );
    
    $hosts = $api->call('host.get', [
        'output' => ['hostid', 'name']
    ]);
    
    if (!is_array($hosts)) {
        echo json_encode(['error' => 'La API no devolvi� un array', 'data' => $hosts]);
        exit;
    }
    
    echo json_encode($hosts);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error en get_hosts.php',
        'message' => $e->getMessage()
    ]);
}