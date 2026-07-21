<?php
declare(strict_types=1);

session_start();

// Limpiar el archivo de cookie jar temporal
$cookie = $_SESSION['zbx_cookiejar'] ?? '';
if ($cookie && is_file($cookie)) {
    @unlink($cookie);
}

// Limpar todas as variáveis da sessão
$_SESSION = [];

// Apagar o cookie de sessão do navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Redirigir al login
header('Location: login.php');
exit();