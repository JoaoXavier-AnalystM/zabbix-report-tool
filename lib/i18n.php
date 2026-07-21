<?php
// lib/i18n.php
declare(strict_types=1);

// --- Configuração ---
const DEFAULT_LANG = 'pt-br';
const SUPPORTED_LANGS = ['pt-br', 'en'];
// ---------------------

// Array global para armazenar as traduções
global $translations;
$translations = [];

// Função para obter uma tradução
function t(string $key): string {
    global $translations;
    return $translations[$key] ?? $key; // Retorna a chave se a tradução não for encontrada
}

// Lógica para determinar o idioma
function get_language(): string {
    // 1. Prioridade: Parâmetro GET (ex: ?lang=en)
    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS)) {
        return $_GET['lang'];
    }
    // 2. Prioridade: Sessão do usuário
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], SUPPORTED_LANGS)) {
        return $_SESSION['lang'];
    }
    // 3. Detecção do navegador
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (in_array($browser_lang, SUPPORTED_LANGS)) {
            return $browser_lang;
        }
    }
    // 4. Idioma padrão
    return DEFAULT_LANG;
}

// Determinar e salvar o idioma na sessão
$current_lang = get_language();
$_SESSION['lang'] = $current_lang;

// Carregar o arquivo de idioma correspondente
$lang_file = __DIR__ . "/../lang/{$current_lang}.php";

if (file_exists($lang_file)) {
    $translations = require $lang_file;
} else {
    // Fallback para o idioma padrão se o arquivo não existir
    $fallback_file = __DIR__ . "/../lang/" . DEFAULT_LANG . ".php";
    if (file_exists($fallback_file)) {
        $translations = require $fallback_file;
    }
}