<?php
declare(strict_types=1);

header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline';");

session_start();
require_once __DIR__ . '/lib/i18n.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/ZabbixApiFactory.php';  // <-- IMPORTANTE

if (!defined('ZBX_USER_PREFIX')) define('ZBX_USER_PREFIX','');
if (!defined('ZBX_USER_SUFFIX')) define('ZBX_USER_SUFFIX','');

// Função para autenticar via API e retornar o token
function api_authenticate(string $user, string $pass, ?string &$token = null, ?string &$err = null): bool {
    try {
        $api = ZabbixApiFactory::create(
            ZABBIX_API_URL,
            $user,
            $pass,
            ['timeout' => 10, 'verify_ssl' => defined('VERIFY_SSL') ? VERIFY_SSL : false]
        );
        $token = $api->getAuthToken();
        return $token !== null && $token !== '';
    } catch (Throwable $e) {
        $err = $e->getMessage();
        error_log("Login API failed for user [$user]: " . $e->getMessage());
        return false;
    }
}

// Função para obter o tipo de usuário a partir do token
function get_user_type_from_token(string $token, string $username): int {
    try {
        $api = ZabbixApiFactory::createWithAuth(
            ZABBIX_API_URL,
            $token,
            ['timeout' => 10, 'verify_ssl' => defined('VERIFY_SSL') ? VERIFY_SSL : false]
        );
        return $api->getUserType($username);
    } catch (Throwable $e) {
        error_log("get_user_type failed: " . $e->getMessage());
    }
    return 1;
}

function web_login(string $user, string $pass, string $cookieJar, ?string &$err=null): bool {
    $base = rtrim(ZABBIX_URL,'/');
    @file_put_contents($cookieJar,'');
    @chmod($cookieJar,0600);
    $loginUser = ZBX_USER_PREFIX.$user.ZBX_USER_SUFFIX;

    $opt=[
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_MAXREDIRS=>5,
        CURLOPT_COOKIEFILE=>$cookieJar,
        CURLOPT_COOKIEJAR=>$cookieJar,
        CURLOPT_USERAGENT=>'Mozilla/5.0',
        CURLOPT_CONNECTTIMEOUT=>10,
        CURLOPT_TIMEOUT=>30
    ];

    if (stripos($base,'https://')===0 && defined('VERIFY_SSL') && !VERIFY_SSL){
        $opt[CURLOPT_SSL_VERIFYPEER]=0;
        $opt[CURLOPT_SSL_VERIFYHOST]=0;
    }

    $post=['name'=>$loginUser,'password'=>$pass,'autologin'=>1,'enter'=>'Sign in'];
    $postUrl = $base.'/index.php';

    $ch=curl_init($postUrl);
    curl_setopt_array($ch,$opt+[
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>http_build_query($post),
        CURLOPT_REFERER=>$postUrl,
        CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded']
    ]);

    $resp=curl_exec($ch);
    curl_close($ch);

    $ch=curl_init($base.'/zabbix.php?action=dashboard.view');
    curl_setopt_array($ch,$opt);
    $dash=curl_exec($ch);
    $eff=curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
    $hc3=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($dash===false || $hc3===401 || stripos((string)$eff,'index.php')!==false){
        $err='Sem acesso ao dashboard (possível redirecionamento para login)';
        return false;
    }

    return true;
}

$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $u=trim($_POST['user']??'');
    $p=trim($_POST['pass']??'');

    if ($u==='' || $p===''){
        $msg=t('login_error_invalid_form');
    } else {
        $apiToken = null;
        if (!api_authenticate($u, $p, $apiToken, $apiErr)){
            error_log("Login failed for user [$u]: $apiErr");
            $msg = t('login_error_invalid_credentials');
        } else {
            $cookieJar = TMP_DIR.DIRECTORY_SEPARATOR.'cj_'.bin2hex(random_bytes(6)).'.txt';

            if (web_login($u, $p, $cookieJar, $webErr)){
                $user_type = get_user_type_from_token($apiToken, $u);

                // Regenerar ID de sessão (previne session fixation)
                session_regenerate_id(true);

                $_SESSION['zbx_user']      = $u;
                $_SESSION['zbx_api_token'] = $apiToken;
                $_SESSION['zbx_cookiejar'] = $cookieJar;
                $_SESSION['zbx_auth_ok']   = true;
                $_SESSION['zbx_user_type'] = $user_type;

                header('Location: latest_data.php');
                exit;
            } else {
                error_log("Web login failed for user [$u]: $webErr");
                @unlink($cookieJar);
                $msg = t('login_error_frontend_rejected');
            }
        }
    }
}
?>
<!doctype html>
<html lang="<?= htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="author" content="Axel Del Canto">
<title><?= t('login_title') ?></title>
<link rel="stylesheet" href="assets/css/login.css">
<?php if (defined('APPLY_LOGO_BLEND_MODE') && APPLY_LOGO_BLEND_MODE): ?>
<style>body.dark-theme .custom-logo { mix-blend-mode: multiply; }</style>
<?php endif; ?>
</head>
<body class="dark-theme">

<div class="bg-deco"></div>

<!-- TOP BAR -->
<div class="top-bar">
  <div class="lang-switcher">
    <a href="?lang=pt-br"<?= ($current_lang==='pt-br') ? ' class="active"' : '' ?>>PT</a>
    <a href="?lang=en"<?= ($current_lang==='en') ? ' class="active"' : '' ?>>EN</a>
  </div>
  <button id="theme-toggle" class="theme-btn">&#9788; <?= t('theme_light') ?></button>
</div>

<div class="wrap">
  <div class="login-card">

    <div class="logo-row">
      <img src="assets/unicred.svg" alt="Unicred" class="custom-logo" onerror="this.style.display='none'">
      <div class="logo-divider"></div>
      <span class="zabbix-badge">ZABBIX</span>
    </div>

    <div class="login-heading"><?= t('login_heading') ?></div>
    <div class="login-sub"><?= t('login_subheading') ?></div>

    <?php if ($msg): ?>
    <div class="login-error"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" autocomplete="off">
      <div class="field">
        <label><?= t('login_user_label') ?></label>
        <input type="text" name="user" required placeholder="username" autofocus>
      </div>
      <div class="field">
        <label><?= t('login_pass_label') ?></label>
        <input type="password" name="pass" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn-login"><?= t('login_button') ?></button>
    </form>

    <div class="login-server">
      Zabbix: <span><?= htmlspecialchars(ZABBIX_URL, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="credit" style="border-top:1px solid var(--border);padding-top:14px;margin-top:14px">
      <div style="font-size:12px;color:var(--text3);margin-bottom:10px"><?= t('common_author_credit') ?></div>
    </div>
  </div>
</div>

<script>
(function(){
  const btn  = document.getElementById('theme-toggle');
  const body = document.body;
  function setTheme(t) {
    body.classList.toggle('dark-theme',  t==='dark');
    body.classList.toggle('light-theme', t!=='dark');
    btn.textContent = t==='dark' ? '\u2600 <?= t('theme_light') ?>' : '\ud83c\udf19 <?= t('theme_dark') ?>';
    localStorage.setItem('zbx-theme', t);
  }
  btn.addEventListener('click', () => setTheme(body.classList.contains('dark-theme') ? 'light' : 'dark'));
  const saved = localStorage.getItem('zbx-theme');
  setTheme(saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
})();
</script>
</body>
</html>
