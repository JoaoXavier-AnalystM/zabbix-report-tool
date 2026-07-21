<?php
require_once __DIR__ . '/ZabbixApi.php';
require_once __DIR__ . '/adapters/ZabbixApi60Adapter.php';
require_once __DIR__ . '/adapters/ZabbixApi64Adapter.php';
require_once __DIR__ . '/adapters/ZabbixApi70Adapter.php';

class ZabbixApiFactory
{
    private static $versionCache = [];
    
    public static function create(string $url, string $user, string $pass, $options = null): ZabbixApiAdapter
    {
        $version = self::detectVersion($url);
        error_log("ZabbixApiFactory: Versão detectada: $version");

        if (version_compare($version, '7.0', '>=')) {
            return new ZabbixApi70Adapter($url, $user, $pass, $options);
        } elseif (version_compare($version, '6.4', '>=')) {
            return new ZabbixApi64Adapter($url, $user, $pass, $options);
        } else {
            return new ZabbixApi60Adapter($url, $user, $pass, $options);
        }
    }

    public static function createWithAuth(string $url, string $authToken, $options = null): ZabbixApiAdapter
    {
        $version = self::detectVersion($url);

        // Cria adapter sem fazer login (skipLogin=true)
        // Depois injeta o token diretamente
        if (version_compare($version, '7.0', '>=')) {
            $adapter = new ZabbixApi70Adapter($url, '', '', $options, true);
            // Zabbix 7.x usa Authorization: Bearer header
            $adapterRef = new ReflectionClass($adapter);
            $tokenProp = $adapterRef->getProperty('token');
            $tokenProp->setAccessible(true);
            $tokenProp->setValue($adapter, $authToken);
        } elseif (version_compare($version, '6.4', '>=')) {
            $adapter = new ZabbixApi64Adapter($url, '', '', $options, true);
        } else {
            $adapter = new ZabbixApi60Adapter($url, '', '', $options, true);
        }

        // Zabbix 6.0/6.4: injeta token no campo 'auth' do JSON-RPC
        if (!($adapter instanceof ZabbixApi70Adapter)) {
            $adapterRef = new ReflectionClass($adapter);
            $apiProp = $adapterRef->getProperty('api');
            $apiProp->setAccessible(true);
            $api = $apiProp->getValue($adapter);

            $apiRef = new ReflectionClass($api);
            $authProp = $apiRef->getProperty('auth');
            $authProp->setAccessible(true);
            $authProp->setValue($api, $authToken);
        }

        return $adapter;
    }
    
    private static function detectVersion(string $url): string
    {
        $cacheKey = md5($url);
        if (isset(self::$versionCache[$cacheKey])) {
            return self::$versionCache[$cacheKey];
        }
        
        if (isset($_SESSION['zabbix_version'])) {
            self::$versionCache[$cacheKey] = $_SESSION['zabbix_version'];
            return $_SESSION['zabbix_version'];
        }
        
        // ============================================================
        // MÉTODO 1: Tentar apiinfo.version SEM NENHUMA AUTENTICAÇÃO
        // ============================================================
        $version = self::tryApiInfoVersion($url);
        if ($version !== null) {
            $_SESSION['zabbix_version'] = $version;
            self::$versionCache[$cacheKey] = $version;
            return $version;
        }
        
        // ============================================================
        // MÉTODO 2: Testar por comportamento (sem usar apiinfo.version)
        // ============================================================
        $version = self::detectByBehavior($url);
        if ($version !== null) {
            $_SESSION['zabbix_version'] = $version;
            self::$versionCache[$cacheKey] = $version;
            return $version;
        }
        
        // ============================================================
        // FALLBACK FINAL: Assumir 6.0 (o mais compatível)
        // ============================================================
        return '6.0.0';
    }
    
    private static function tryApiInfoVersion(string $url): ?string
    {
        try {
            $ch = curl_init(rtrim($url, '/') . '/api_jsonrpc.php');
            $payload = json_encode([
                'jsonrpc' => '2.0',
                'method' => 'apiinfo.version',
                'params' => [],
                'id' => 1
            ]);
            
            // Configurar cURL SEM ABSOLUTAMENTE NADA de autenticação
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json-rpc'],
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_USERAGENT => 'ZabbixApiFactory/1.0',
                // FORÇAR a não enviar autenticação
                CURLOPT_HTTPAUTH => CURLAUTH_ANY,
                CURLOPT_USERPWD => '',
                CURLOPT_UNRESTRICTED_AUTH => false,
            ]);
            
            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $resp) {
                $data = json_decode($resp, true);
                if (isset($data['result'])) {
                    return $data['result'];
                }
            }
        } catch (Throwable $e) {
            error_log("ZabbixApiFactory: Erro no apiinfo.version: " . $e->getMessage());
        }
        
        return null;
    }
    
    private static function detectByBehavior(string $url): ?string
    {
        // Testar primeiro com login no formato 7.0 (username)
        try {
            $testPayload = json_encode([
                'jsonrpc' => '2.0',
                'method' => 'user.login',
                'params' => [
                    'username' => 'test',
                    'password' => 'test'
                ],
                'id' => 1
            ]);
            
            $ch = curl_init(rtrim($url, '/') . '/api_jsonrpc.php');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $testPayload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json-rpc'],
                CURLOPT_TIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            
            curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            
            // Se o erro for por credenciais (não por sintaxe), então é 7.0+
            if (strpos($error, 'error') === false) {
                // A sintaxe é válida, mesmo que as credenciais estejam incorretas
                return '7.0.0';
            }
        } catch (Throwable $e) {
            // Ignorar
        }
        
        // Se chegamos aqui, provavelmente é 6.0
        return '6.0.0';
    }
}