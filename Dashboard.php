<?php
// Démarre la session utilisateur pour gérer la connexion
session_start();

// ============================================================
// CONFIGURATION — À personnaliser avant utilisation
// ============================================================
$ADMIN_USER = 'admin';         // Nom d'utilisateur
$ADMIN_PASS = 'password';      // Mot de passe

$EMAIL_DESTINATAIRE = 'you@example.com';  // Email de notification
$EMAIL_EXPEDITEUR   = 'noreply@yourdomain.com';

// ============================================================
// AUTHENTIFICATION A2F
// ============================================================

/**
 * Génère un code A2F à 6 chiffres et l'envoie par email.
 */
function generate2FACode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function send2FACodeEmail($code) {
    global $EMAIL_DESTINATAIRE, $EMAIL_EXPEDITEUR;
    $timestamp = date('d/m/Y H:i:s');
    $subject   = "Code A2F — Dashboard LXC";
    $message   = "<html><body>
        <div style='font-family:Arial,sans-serif;padding:20px;border:1px solid #16a34a;border-radius:8px;'>
            <h2>Code d'authentification requis</h2>
            <p>Utilisez le code ci-dessous pour finaliser votre connexion :</p>
            <h1 style='color:#16a34a;text-align:center;font-size:36px;border:2px dashed #ccc;padding:15px;letter-spacing:8px;'>{$code}</h1>
            <small>Tentative le {$timestamp} — si ce n'est pas vous, ignorez cet email.</small>
        </div>
    </body></html>";
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Dashboard LXC <{$EMAIL_EXPEDITEUR}>\r\n";
    return mail($EMAIL_DESTINATAIRE, $subject, $message, $headers);
}

/**
 * Envoie une notification de connexion réussie.
 */
function sendLoginNotification($username) {
    global $EMAIL_DESTINATAIRE, $EMAIL_EXPEDITEUR;
    $ip        = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    $ua        = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
    $timestamp = date('d/m/Y H:i:s');
    $subject   = "Connexion Dashboard LXC — {$timestamp}";
    $message   = "<html><body>
        <p>Connexion de <strong>{$username}</strong> le {$timestamp}.</p>
        <p>IP : {$ip}<br>User-Agent : {$ua}</p>
    </body></html>";
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Dashboard LXC <{$EMAIL_EXPEDITEUR}>\r\n";
    return mail($EMAIL_DESTINATAIRE, $subject, $message, $headers);
}

// Étape 1 : vérification identifiants → envoi code A2F
if (isset($_POST['login'])) {
    if ($_POST['username'] === $ADMIN_USER && $_POST['password'] === $ADMIN_PASS) {
        $code = generate2FACode();
        $_SESSION['2fa_required'] = true;
        $_SESSION['2fa_code']     = $code;
        send2FACodeEmail($code);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        session_destroy();
        header('Location: /');
        exit;
    }
}

// Étape 2 : vérification code A2F
if (isset($_POST['verify_2fa']) && !empty($_SESSION['2fa_required'])) {
    if (isset($_SESSION['2fa_code']) && $_POST['2fa_code'] === $_SESSION['2fa_code']) {
        $_SESSION['logged_in']  = true;
        $_SESSION['email_sent'] = false;
        unset($_SESSION['2fa_required'], $_SESSION['2fa_code']);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        session_destroy();
        header('Location: /');
        exit;
    }
}

// Notification de connexion (une seule fois par session)
if (!empty($_SESSION['logged_in']) && empty($_SESSION['email_sent'])) {
    sendLoginNotification($ADMIN_USER);
    $_SESSION['email_sent'] = true;
}

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /');
    exit;
}

// ============================================================
// PAGES DE CONNEXION (si non authentifié)
// ============================================================
if (empty($_SESSION['logged_in'])) {

    // Page A2F
    if (!empty($_SESSION['2fa_required'])) {
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>2FA — Dashboard LXC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #0d1117; color: #c9d1d9; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 48px 40px; width: 100%; max-width: 420px; box-shadow: 0 16px 48px rgba(0,0,0,.5); }
        .logo { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; }
        .logo-icon { width: 40px; height: 40px; background: linear-gradient(135deg,#16a34a,#0d9488); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .logo-name { font-size: 1.1em; font-weight: 600; color: #e6edf3; }
        .logo-host { font-size: .75em; color: #484f58; font-family: 'JetBrains Mono', monospace; }
        h2 { font-size: 1.3em; font-weight: 600; color: #e6edf3; margin-bottom: 8px; }
        p { font-size: .88em; color: #8b949e; margin-bottom: 24px; line-height: 1.5; }
        label { display: block; font-size: .78em; font-weight: 600; color: #8b949e; letter-spacing: .08em; text-transform: uppercase; margin-bottom: 6px; }
        input { width: 100%; padding: 11px 14px; background: #0d1117; border: 1px solid #30363d; border-radius: 8px; color: #e6edf3; font-size: 1.1em; font-family: 'JetBrains Mono', monospace; letter-spacing: .25em; text-align: center; outline: none; transition: border-color .2s; margin-bottom: 14px; }
        input:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.15); }
        button { width: 100%; padding: 12px; background: #16a34a; color: #fff; border: none; border-radius: 8px; font-size: .95em; font-weight: 600; cursor: pointer; transition: background .2s; }
        button:hover { background: #15803d; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <div class="logo-icon">🔐</div>
            <div>
                <div class="logo-name">Dashboard LXC</div>
                <div class="logo-host">yourdomain.com</div>
            </div>
        </div>
        <h2>Vérification A2F</h2>
        <p>Un code à 6 chiffres a été envoyé à votre adresse email.</p>
        <form method="POST">
            <label>Code d'authentification</label>
            <input type="text" name="2fa_code" placeholder="000000" required maxlength="6" pattern="\d{6}" autofocus>
            <button type="submit" name="verify_2fa">Vérifier →</button>
        </form>
    </div>
</body>
</html>
<?php
        exit;
    }

    // Page Login
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Login — Dashboard LXC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #0d1117; color: #c9d1d9; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 48px 40px; width: 100%; max-width: 420px; box-shadow: 0 16px 48px rgba(0,0,0,.5); }
        .logo { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; }
        .logo-icon { width: 40px; height: 40px; background: linear-gradient(135deg,#16a34a,#0d9488); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .logo-name { font-size: 1.1em; font-weight: 600; color: #e6edf3; }
        .logo-host { font-size: .75em; color: #484f58; font-family: 'JetBrains Mono', monospace; }
        h2 { font-size: 1.3em; font-weight: 600; color: #e6edf3; margin-bottom: 28px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: .78em; font-weight: 600; color: #8b949e; letter-spacing: .08em; text-transform: uppercase; margin-bottom: 6px; }
        input { width: 100%; padding: 11px 14px; background: #0d1117; border: 1px solid #30363d; border-radius: 8px; color: #e6edf3; font-size: .95em; outline: none; transition: border-color .2s; }
        input:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.15); }
        button { width: 100%; padding: 12px; background: #16a34a; color: #fff; border: none; border-radius: 8px; font-size: .95em; font-weight: 600; cursor: pointer; margin-top: 8px; transition: background .2s; }
        button:hover { background: #15803d; }
        .hint { margin-top: 20px; text-align: center; font-size: .78em; color: #484f58; font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <div class="logo-icon">🐧</div>
            <div>
                <div class="logo-name">Dashboard LXC</div>
                <div class="logo-host">yourdomain.com</div>
            </div>
        </div>
        <h2>Connexion</h2>
        <form method="POST">
            <div class="form-group">
                <label>Identifiant</label>
                <input type="text" name="username" placeholder="admin" required autofocus>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" name="login">Se connecter →</button>
        </form>
        <div class="hint">Authentification à deux facteurs requise</div>
    </div>
</body>
</html>
<?php
    exit;
}

// ============================================================
// FONCTIONS MÉTIER
// ============================================================

function getContainerStatus($container) {
    exec("sudo lxc-info -n $container -s 2>/dev/null | head -n 2 | tail -n 1 | awk '{print $2}'", $output);
    return isset($output[0]) ? trim($output[0]) : 'UNKNOWN';
}

function getContainerIP($container) {
    exec("sudo lxc-info -n $container -iH 2>/dev/null", $output);
    return isset($output[0]) ? trim($output[0]) : 'N/A';
}

function getSystemIPserver() {
    exec("ip a|grep brd|grep inet|tr \" \" \"-\"|cut -d \"-\" -f 6|head -n 1", $output);
    return ['IPS' => $output[0] ?? 'N/A'];
}

function getSystemInfoUptime() {
    exec("uptime -p", $output);
    return ['uptime' => $output[0] ?? 'N/A'];
}

function getSystemInfoLoad() {
    exec("uptime | awk -F'load average:' '{print $2}'", $output);
    return ['load' => trim($output[0] ?? 'N/A')];
}

function getSystemInfoMemory() {
    exec("free -h | awk '{print $3}' | head -n 2 | tail -n 1", $output);
    return ['memory' => $output[0] ?? 'N/A'];
}

function getSystemInfoDisk() {
    exec("df -h | grep \"/dev/sda1\" | awk '{print $4}' | head -n 1", $output);
    return ['disk' => $output[0] ?? 'N/A'];
}

/**
 * Retourne les IPs bannies depuis votre fichier des ip banies.
 * Adaptez le chemin selon votre configuration.
 */
function getGenInfoBanIP() {
    $output = [];
    exec("sudo cat /path/to/your/IPSet/IPD.csv", $output);
    return ['BanIP' => array_filter(array_map('trim', $output))];
}

/**
 * Liste les fichiers de backup.
 * Adaptez le chemin selon votre configuration.
 */
function getGenInfoBackups() {
    $output = [];
    exec("sudo ls /backups", $output);
    return ['Backup' => array_filter(array_map('trim', $output))];
}

/**
 * Liste les dossiers de logs.
 * Adaptez le chemin selon votre configuration.
 */
function getGenInfoLogs() {
    $output = [];
    exec("sudo ls /logs", $output);
    return ['Logs' => array_filter(array_map('trim', $output))];
}

function getGenInfoBackupsStatus() {
    exec("systemctl status backup.service | grep Active | awk '{print $2}'", $output);
    return ['BackupStatus' => isset($output[0]) ? trim($output[0]) : 'N/A'];
}

function deleteBackupFile($filename) {
    $escaped = escapeshellarg("/backups/" . basename($filename));
    exec("sudo rm -f $escaped 2>&1", $output, $ret);
    return $ret === 0;
}

function startContainer($container)   { exec("sudo lxc-start -n $container"); }
function stopContainer($container)    { exec("sudo lxc-stop -n $container"); }
function restartContainer($container) { exec("sudo lxc-stop -n $container && sudo lxc-start $container"); }
function startAllContainers()   { exec("sudo systemctl start lxcStart.service"); }
function stopAllContainers()    { exec("sudo systemctl start lxcStop.service"); }
function restartAllContainers() { exec("sudo systemctl start lxcStop.service && sudo systemctl start lxcStart.service"); }
function startBackup()  { exec("sudo systemctl start backup.service"); }
function startReboot()  { exec("sudo reboot"); }

/**
 * Retourne les 10 dernières lignes du log d'erreur d'un conteneur.
 * Adaptez le chemin selon votre configuration.
 */
function getContainerLogs($container) {
    $log = escapeshellarg("/var/log/sites/" . $container . "-error.log");
    exec("/usr/bin/sudo /usr/bin/tail -n 10 $log 2>&1", $output, $ret);
    if ($ret !== 0 || empty($output)) return ["Aucun log disponible pour $container."];
    return $output;
}

// ============================================================
// CONTENEURS — À personnaliser
// ============================================================
$containers = [
    'conteneur1' => ['name' => 'Mon Service 1', 'port' => 443, 'port2' => 80, 'ip' => '10.0.3.10'],
    'conteneur2' => ['name' => 'Mon Service 2', 'port' => 443, 'port2' => 80, 'ip' => '10.0.3.20'],
];

// ============================================================
// CHARGEMENT DES DONNÉES
// ============================================================
$system_IP    = getSystemIPserver();
$system_info1 = getSystemInfoUptime();
$system_info2 = getSystemInfoLoad();
$system_info3 = getSystemInfoMemory();
$system_info4 = getSystemInfoDisk();
$gen_info1    = getGenInfoBanIP();
$banned_ips   = $gen_info1['BanIP'];
$gen_info2    = getGenInfoBackups();
$Backups      = $gen_info2['Backup'];
$gen_info4    = getGenInfoLogs();
$Logs         = $gen_info4['Logs'];

// ============================================================
// ACTIONS POST
// ============================================================
if (isset($_POST['start']))         { startContainer($_POST['container_id']);   header("Location: ".$_SERVER['PHP_SELF']."?section=containers"); exit; }
if (isset($_POST['stop']))          { stopContainer($_POST['container_id']);    header("Location: ".$_SERVER['PHP_SELF']."?section=containers"); exit; }
if (isset($_POST['restart']))       { restartContainer($_POST['container_id']); header("Location: ".$_SERVER['PHP_SELF']."?section=containers"); exit; }
if (isset($_POST['start_all']))     { startAllContainers();   header("Location: ".$_SERVER['PHP_SELF']."?section=containers"); exit; }
if (isset($_POST['stop_all']))      { stopAllContainers();    header("Location: ".$_SERVER['PHP_SELF']."?section=containers"); exit; }
if (isset($_POST['restart_all']))   { restartAllContainers(); header("Location: ".$_SERVER['PHP_SELF']."?section=containers"); exit; }
if (isset($_POST['backup']))        { startBackup(); header("Location: ".$_SERVER['PHP_SELF']."?section=backups"); exit; }
if (isset($_POST['delete_backup'])) { deleteBackupFile($_POST['backup_filename']); header("Location: ".$_SERVER['PHP_SELF']."?section=backups"); exit; }
if (isset($_POST['reboot']))        { startReboot(); header("Location: ".$_SERVER['PHP_SELF']); exit; }

$section = $_GET['section'] ?? 'overview';

// Cat d'un fichier de log sélectionné
$log_cat_output = null;
$log_cat_name   = null;
if ($section === 'logfiles' && !empty($_GET['logfile'])) {
    $safe = basename($_GET['logfile']);
    exec("sudo cat " . escapeshellarg("/logs/" . $safe) . " 2>&1", $cat_out);
    $log_cat_name   = $safe;
    $log_cat_output = implode("\n", $cat_out);
}

$runningCount = 0;
$stoppedCount = 0;
foreach ($containers as $cid => $d) {
    if (getContainerStatus($cid) === 'RUNNING') $runningCount++; else $stoppedCount++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard LXC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 240px;
            --topbar-h:  56px;
            --bg-base:     #0d1117;
            --bg-surface:  #161b22;
            --bg-elevated: #1c2128;
            --bg-hover:    #21262d;
            --border:      #30363d;
            --border-sub:  #21262d;
            --text-1:  #e6edf3;
            --text-2:  #8b949e;
            --text-3:  #484f58;
            --green:   #16a34a;
            --green-l: #22c55e;
            --red:     #dc2626;
            --red-l:   #ef4444;
            --amber:   #d97706;
            --amber-l: #f59e0b;
            --cyan:    #0891b2;
            --cyan-l:  #06b6d4;
            --purple-l:#a78bfa;
        }

        html, body { height: 100%; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-base); color: var(--text-1); display: flex; flex-direction: column; }

        /* ── TOPBAR ── */
        .topbar {
            position: fixed; top: 0; left: 0; right: 0; height: var(--topbar-h);
            background: var(--bg-surface); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; padding: 0 20px; gap: 16px; z-index: 100;
        }
        .topbar-brand { display: flex; align-items: center; gap: 10px; width: calc(var(--sidebar-w) - 20px); flex-shrink: 0; }
        .brand-icon { width: 32px; height: 32px; background: linear-gradient(135deg,var(--green),var(--cyan)); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
        .brand-name { font-weight: 700; font-size: .95em; color: var(--text-1); }
        .brand-host { font-size: .72em; color: var(--text-3); font-family: 'JetBrains Mono', monospace; }
        .topbar-sep { flex: 1; }
        .topbar-status { display: flex; align-items: center; gap: 6px; font-size: .8em; color: var(--text-2); }
        .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--green); box-shadow: 0 0 6px var(--green); }
        .dot.red { background: var(--red); box-shadow: 0 0 6px var(--red); }
        .topbar-ip { font-family: 'JetBrains Mono', monospace; font-size: .8em; color: var(--text-2); background: var(--bg-elevated); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border); }
        .topbar-actions { display: flex; gap: 8px; align-items: center; }
        .btn-top { padding: 6px 14px; border-radius: 6px; font-size: .82em; font-weight: 600; border: 1px solid var(--border); cursor: pointer; background: var(--bg-elevated); color: var(--text-1); text-decoration: none; transition: all .15s; }
        .btn-top:hover { background: var(--bg-hover); }
        .btn-top.warning { background: rgba(217,119,6,.1); border-color: rgba(217,119,6,.3); color: var(--amber-l); }
        .btn-top.purple  { background: rgba(124,58,237,.1); border-color: rgba(124,58,237,.3); color: var(--purple-l); }
        .btn-top.danger  { background: rgba(220,38,38,.1);  border-color: rgba(220,38,38,.3);  color: var(--red-l); }

        /* ── LAYOUT ── */
        .layout { display: flex; margin-top: var(--topbar-h); min-height: calc(100vh - var(--topbar-h)); }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w); background: var(--bg-surface); border-right: 1px solid var(--border);
            position: fixed; top: var(--topbar-h); bottom: 0; left: 0; overflow-y: auto; padding: 16px 0; z-index: 90;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }
        .nav-lbl { font-size: .68em; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--text-3); padding: 8px 16px 4px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 16px; font-size: .87em; font-weight: 500; color: var(--text-2); text-decoration: none; border-left: 3px solid transparent; margin: 1px 0; transition: all .15s; }
        .nav-item:hover { background: var(--bg-hover); color: var(--text-1); }
        .nav-item.active { background: rgba(22,163,74,.08); color: var(--green-l); border-left-color: var(--green); }
        .nav-icon { width: 18px; text-align: center; flex-shrink: 0; }
        .nav-badge { margin-left: auto; font-size: .72em; font-family: 'JetBrains Mono', monospace; background: var(--bg-elevated); padding: 2px 7px; border-radius: 20px; color: var(--text-3); border: 1px solid var(--border); }
        .nav-badge.green { background: rgba(22,163,74,.15); color: var(--green-l); border-color: rgba(22,163,74,.3); }
        .nav-badge.red   { background: rgba(220,38,38,.15); color: var(--red-l);   border-color: rgba(220,38,38,.3); }
        .nav-divider { height: 1px; background: var(--border-sub); margin: 8px 16px; }

        /* ── MAIN ── */
        .main { margin-left: var(--sidebar-w); flex: 1; padding: 28px; min-width: 0; }

        /* ── PAGE HEADER ── */
        .page-header { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border-sub); }
        .page-title { font-size: 1.3em; font-weight: 700; }
        .page-sub { font-size: .82em; color: var(--text-3); margin-top: 2px; font-family: 'JetBrains Mono', monospace; }

        /* ── SECTIONS ── */
        .section { display: none; }
        .section.active { display: block; }

        /* ── STAT CARDS ── */
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--bg-surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; }
        .stat-card.green::before  { background: linear-gradient(90deg,var(--green),var(--green-l)); }
        .stat-card.cyan::before   { background: linear-gradient(90deg,var(--cyan),var(--cyan-l)); }
        .stat-card.amber::before  { background: linear-gradient(90deg,var(--amber),var(--amber-l)); }
        .stat-card.red::before    { background: linear-gradient(90deg,var(--red),var(--red-l)); }
        .stat-card.purple::before { background: linear-gradient(90deg,#7c3aed,var(--purple-l)); }
        .stat-lbl { font-size: .75em; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--text-3); margin-bottom: 10px; }
        .stat-val { font-size: 1.5em; font-weight: 700; font-family: 'JetBrains Mono', monospace; line-height: 1.2; }
        .stat-icon { position: absolute; top: 16px; right: 16px; font-size: 1.4em; opacity: .4; }

        /* ── PANEL ── */
        .panel { background: var(--bg-surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; margin-bottom: 20px; }
        .panel-hdr { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid var(--border); background: var(--bg-elevated); }
        .panel-title { font-size: .9em; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .panel-actions { display: flex; gap: 8px; }
        .panel-body { padding: 20px; }

        /* ── TABLE ── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .875em; }
        thead tr { background: var(--bg-elevated); }
        th { text-align: left; padding: 10px 14px; font-size: .75em; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--text-3); border-bottom: 1px solid var(--border); }
        td { padding: 12px 14px; border-bottom: 1px solid var(--border-sub); color: var(--text-2); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,.02); }
        .mono { font-family: 'JetBrains Mono', monospace; font-size: .9em; }

        /* ── BADGES ── */
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 20px; font-size: .72em; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .badge-dot { width: 6px; height: 6px; border-radius: 50%; }
        .badge.running  { background: rgba(22,163,74,.12);  color: var(--green-l); border: 1px solid rgba(22,163,74,.3); }
        .badge.running .badge-dot  { background: var(--green-l); box-shadow: 0 0 4px var(--green); }
        .badge.stopped  { background: rgba(220,38,38,.12);  color: var(--red-l);   border: 1px solid rgba(220,38,38,.3); }
        .badge.stopped .badge-dot  { background: var(--red-l); }
        .badge.active   { background: rgba(22,163,74,.12);  color: var(--green-l); border: 1px solid rgba(22,163,74,.3); }
        .badge.inactive { background: rgba(220,38,38,.12);  color: var(--red-l);   border: 1px solid rgba(220,38,38,.3); }

        /* ── BUTTONS ── */
        .btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 6px; font-size: .8em; font-weight: 600; border: 1px solid var(--border); cursor: pointer; background: var(--bg-elevated); color: var(--text-1); white-space: nowrap; text-decoration: none; transition: all .15s; }
        .btn:hover { background: var(--bg-hover); }
        .btn.green  { background: rgba(22,163,74,.1);  border-color: rgba(22,163,74,.3);  color: var(--green-l); }
        .btn.green:hover  { background: rgba(22,163,74,.2); }
        .btn.red    { background: rgba(220,38,38,.1);  border-color: rgba(220,38,38,.3);  color: var(--red-l); }
        .btn.red:hover    { background: rgba(220,38,38,.2); }
        .btn.amber  { background: rgba(217,119,6,.1);  border-color: rgba(217,119,6,.3);  color: var(--amber-l); }
        .btn.amber:hover  { background: rgba(217,119,6,.2); }
        .btn-group { display: flex; gap: 6px; }

        /* ── LOG CONSOLE ── */
        .log-console {
            background: var(--bg-base); border: 1px solid var(--border); border-radius: 6px; padding: 14px;
            font-family: 'JetBrains Mono', monospace; font-size: .78em; color: #7ee787;
            overflow: auto; white-space: pre; max-height: 200px; line-height: 1.6;
        }
        .log-console::-webkit-scrollbar { width: 4px; height: 4px; }
        .log-console::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        /* ── LOGS GRID ── */
        .logs-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 16px; }

        /* ── IP LIST ── */
        .ip-list { max-height: 280px; overflow-y: auto; }
        .ip-list::-webkit-scrollbar { width: 4px; }
        .ip-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }
        .ip-row { display: flex; align-items: center; padding: 7px 0; border-bottom: 1px solid var(--border-sub); font-family: 'JetBrains Mono', monospace; font-size: .82em; color: var(--text-2); }
        .ip-row:last-child { border-bottom: none; }
        .ip-row::before { content: '⊘'; margin-right: 8px; color: var(--red-l); }

        /* ── EMPTY STATE ── */
        .empty-state { text-align: center; padding: 32px; color: var(--text-3); font-size: .85em; }
        .empty-icon { font-size: 2em; margin-bottom: 8px; opacity: .5; }

        /* ── MISC ── */
        .controls-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        select { width: 100%; padding: 9px 12px; background: var(--bg-base); border: 1px solid var(--border); border-radius: 6px; color: var(--text-1); font-size: .875em; cursor: pointer; outline: none; }
        select:focus { border-color: var(--green); }
        .main-footer { padding: 16px 28px; font-size: .78em; color: var(--text-3); font-family: 'JetBrains Mono', monospace; border-top: 1px solid var(--border-sub); display: flex; justify-content: space-between; margin-left: var(--sidebar-w); }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main { margin-left: 0; padding: 16px; }
            .topbar-ip, .topbar-status { display: none; }
            .logs-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-brand">
        <div class="brand-icon">🐧</div>
        <div>
            <div class="brand-name">Dashboard LXC</div>
            <div class="brand-host">yourdomain.com</div>
        </div>
    </div>
    <div class="topbar-sep"></div>
    <div class="topbar-status">
        <div class="dot <?= $stoppedCount > 0 ? 'red' : '' ?>"></div>
        <?= $runningCount ?>/<?= count($containers) ?> conteneurs actifs
    </div>
    <div class="topbar-ip"><?= htmlspecialchars($system_IP['IPS']) ?></div>
    <div class="topbar-actions">
        <form method="POST" style="margin:0"><button type="submit" name="backup" class="btn-top warning">💾 Backup</button></form>
        <form method="POST" style="margin:0"><button type="submit" name="reboot" class="btn-top purple">⚡ Reboot</button></form>
        <a href="?logout" class="btn-top danger">🚪 Déconnexion</a>
    </div>
</div>

<!-- LAYOUT -->
<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="nav-lbl">Général</div>
        <a class="nav-item <?= $section==='overview'?'active':'' ?>" href="?section=overview">
            <span class="nav-icon">📊</span> Vue d'ensemble
        </a>

        <div class="nav-divider"></div>

        <div class="nav-lbl">Infrastructure</div>
        <a class="nav-item <?= $section==='containers'?'active':'' ?>" href="?section=containers">
            <span class="nav-icon">📦</span> Conteneurs LXC
            <?php if ($stoppedCount > 0): ?>
                <span class="nav-badge red"><?= $stoppedCount ?> ↓</span>
            <?php else: ?>
                <span class="nav-badge green"><?= $runningCount ?> ↑</span>
            <?php endif; ?>
        </a>
        <a class="nav-item <?= $section==='logs'?'active':'' ?>" href="?section=logs">
            <span class="nav-icon">📄</span> Logs d'erreur
        </a>

        <div class="nav-divider"></div>

        <div class="nav-lbl">Système</div>
        <a class="nav-item <?= $section==='backups'?'active':'' ?>" href="?section=backups">
            <span class="nav-icon">💾</span> Sauvegardes
            <span class="nav-badge"><?= count($Backups) ?></span>
        </a>
        <a class="nav-item <?= $section==='security'?'active':'' ?>" href="?section=security">
            <span class="nav-icon">🔒</span> Sécurité
            <span class="nav-badge red"><?= count($banned_ips) ?></span>
        </a>
        <a class="nav-item <?= $section==='logfiles'?'active':'' ?>" href="?section=logfiles">
            <span class="nav-icon">🗂️</span> Fichiers logs
            <span class="nav-badge"><?= count($Logs) ?></span>
        </a>
    </aside>

    <!-- MAIN -->
    <main class="main">

        <!-- ═══ OVERVIEW ═══ -->
        <div class="section <?= $section==='overview'?'active':'' ?>">
            <div class="page-header">
                <div class="page-title">Vue d'ensemble</div>
                <div class="page-sub">Supervision temps réel — actualisation toutes les 30s</div>
            </div>
            <div class="stat-grid">
                <div class="stat-card green">  <div class="stat-icon">⏱️</div><div class="stat-lbl">Uptime</div><div class="stat-val" style="font-size:1em"><?= htmlspecialchars($system_info1['uptime']) ?></div></div>
                <div class="stat-card cyan">   <div class="stat-icon">📈</div><div class="stat-lbl">Load Average</div><div class="stat-val" style="font-size:1em"><?= htmlspecialchars($system_info2['load']) ?></div></div>
                <div class="stat-card amber">  <div class="stat-icon">🧠</div><div class="stat-lbl">RAM utilisée</div><div class="stat-val"><?= htmlspecialchars($system_info3['memory']) ?></div></div>
                <div class="stat-card purple"> <div class="stat-icon">💿</div><div class="stat-lbl">Disque libre</div><div class="stat-val"><?= htmlspecialchars($system_info4['disk']) ?></div></div>
                <div class="stat-card green">  <div class="stat-icon">📦</div><div class="stat-lbl">Actifs</div><div class="stat-val"><?= $runningCount ?><span style="font-size:.55em;color:var(--text-3)"> / <?= count($containers) ?></span></div></div>
                <div class="stat-card <?= $stoppedCount>0?'red':'green' ?>"><div class="stat-icon">🔒</div><div class="stat-lbl">IPs bannies</div><div class="stat-val"><?= count($banned_ips) ?></div></div>
            </div>
            <div class="panel">
                <div class="panel-hdr">
                    <div class="panel-title">📦 État des conteneurs</div>
                    <div class="panel-actions"><a href="?section=containers" class="btn">Gérer →</a></div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Nom</th><th>ID</th><th>IP interne</th><th>Port DNAT</th><th>Statut</th></tr></thead>
                        <tbody>
                        <?php foreach ($containers as $cid => $d):
                            $s = getContainerStatus($cid); $ip = getContainerIP($cid); $r = ($s==='RUNNING'); ?>
                        <tr>
                            <td style="color:var(--text-1);font-weight:500"><?= htmlspecialchars($d['name']) ?></td>
                            <td class="mono"><?= htmlspecialchars($cid) ?></td>
                            <td class="mono"><?= htmlspecialchars($ip) ?></td>
                            <td class="mono"><?= $d['port'] ?></td>
                            <td><span class="badge <?= $r?'running':'stopped' ?>"><span class="badge-dot"></span><?= $r?'Running':'Stopped' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ═══ CONTAINERS ═══ -->
        <div class="section <?= $section==='containers'?'active':'' ?>">
            <div class="page-header">
                <div class="page-title">Conteneurs LXC</div>
                <div class="page-sub">Gestion et contrôle des conteneurs</div>
            </div>
            <div class="panel" style="margin-bottom:20px">
                <div class="panel-hdr"><div class="panel-title">⚡ Contrôle global</div></div>
                <div class="panel-body">
                    <div class="controls-row">
                        <form method="POST"><button type="submit" name="start_all"   class="btn green">▶ Démarrer tous</button></form>
                        <form method="POST"><button type="submit" name="stop_all"    class="btn red">■ Arrêter tous</button></form>
                        <form method="POST"><button type="submit" name="restart_all" class="btn amber">↺ Redémarrer tous</button></form>
                    </div>
                </div>
            </div>
            <div class="panel">
                <div class="panel-hdr"><div class="panel-title">📦 Liste des conteneurs</div></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Nom</th><th>ID</th><th>IP interne</th><th>Port ext.</th><th>Port int.</th><th>Statut</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($containers as $cid => $d):
                            $s = getContainerStatus($cid); $ip = getContainerIP($cid); $r = ($s==='RUNNING'); ?>
                        <tr>
                            <td style="color:var(--text-1);font-weight:500"><?= htmlspecialchars($d['name']) ?></td>
                            <td class="mono"><?= htmlspecialchars($cid) ?></td>
                            <td class="mono"><?= htmlspecialchars($ip) ?></td>
                            <td class="mono"><?= $d['port'] ?></td>
                            <td class="mono"><?= $d['port2'] ?></td>
                            <td><span class="badge <?= $r?'running':'stopped' ?>"><span class="badge-dot"></span><?= $r?'Running':'Stopped' ?></span></td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($r): ?>
                                        <form method="POST"><input type="hidden" name="container_id" value="<?= $cid ?>"><button type="submit" name="stop" class="btn red">■ Stop</button></form>
                                    <?php else: ?>
                                        <form method="POST"><input type="hidden" name="container_id" value="<?= $cid ?>"><button type="submit" name="start" class="btn green">▶ Start</button></form>
                                    <?php endif; ?>
                                    <form method="POST"><input type="hidden" name="container_id" value="<?= $cid ?>"><button type="submit" name="restart" class="btn amber">↺</button></form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ═══ LOGS ═══ -->
        <div class="section <?= $section==='logs'?'active':'' ?>">
            <div class="page-header">
                <div class="page-title">Logs d'erreur</div>
                <div class="page-sub">10 dernières lignes par conteneur</div>
            </div>
            <div class="logs-grid">
            <?php foreach ($containers as $cid => $d):
                $logs = getContainerLogs($cid); ?>
            <div class="panel">
                <div class="panel-hdr">
                    <div class="panel-title"><span style="color:var(--cyan-l)">📄</span> <?= htmlspecialchars($d['name']) ?> <span style="color:var(--text-3);font-weight:400;font-family:'JetBrains Mono',monospace;font-size:.85em">(<?= htmlspecialchars($cid) ?>)</span></div>
                </div>
                <div class="panel-body" style="padding:14px">
                    <div class="log-console"><?= htmlspecialchars(implode("\n", $logs)) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>

        <!-- ═══ BACKUPS ═══ -->
        <div class="section <?= $section==='backups'?'active':'' ?>">
            <div class="page-header">
                <div class="page-title">Sauvegardes</div>
                <div class="page-sub">Gestion des backups locaux</div>
            </div>
            <?php $bkStatus = getGenInfoBackupsStatus(); $bkActive = ($bkStatus['BackupStatus']==='active'); ?>
            <div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
                <div class="stat-card <?= $bkActive?'green':'red' ?>">
                    <div class="stat-icon">⚙️</div><div class="stat-lbl">Service backup</div>
                    <div class="stat-val" style="font-size:1em"><span class="badge <?= $bkActive?'active':'inactive' ?>"><span class="badge-dot"></span><?= $bkActive?'Active':'Inactive' ?></span></div>
                </div>
                <div class="stat-card cyan"><div class="stat-icon">📁</div><div class="stat-lbl">Fichiers</div><div class="stat-val"><?= count($Backups) ?></div></div>
            </div>
            <div class="panel">
                <div class="panel-hdr">
                    <div class="panel-title">💾 Backups locaux</div>
                    <div class="panel-actions"><form method="POST"><button type="submit" name="backup" class="btn amber">+ Lancer un backup</button></form></div>
                </div>
                <div class="panel-body" style="padding:0">
                    <?php if (!empty($Backups)): ?>
                    <div class="table-wrap"><table>
                        <thead><tr><th>Fichier</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($Backups as $bk): ?>
                        <tr>
                            <td class="mono" style="color:var(--text-1)"><?= htmlspecialchars($bk) ?></td>
                            <td><form method="POST"><input type="hidden" name="backup_filename" value="<?= htmlspecialchars($bk) ?>"><button type="submit" name="delete_backup" class="btn red">✕ Supprimer</button></form></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                    <?php else: ?>
                    <div class="empty-state"><div class="empty-icon">📭</div>Aucun backup local trouvé</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ═══ SECURITY ═══ -->
        <div class="section <?= $section==='security'?'active':'' ?>">
            <div class="page-header">
                <div class="page-title">Sécurité</div>
                <div class="page-sub">IPs bannies — IPSet proxy</div>
            </div>
            <div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
                <div class="stat-card red"><div class="stat-icon">⊘</div><div class="stat-lbl">IPs bloquées</div><div class="stat-val"><?= count($banned_ips) ?></div></div>
            </div>
            <!-- Recherche IP -->
            <div class="panel" style="margin-bottom:20px">
                <div class="panel-hdr"><div class="panel-title">🔍 Vérifier une IP</div></div>
                <div class="panel-body">
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                        <input type="text" id="ip-input" placeholder="Ex: 192.168.1.1"
                            style="flex:1;min-width:200px;padding:9px 14px;background:var(--bg-base);border:1px solid var(--border);border-radius:6px;color:var(--text-1);font-family:'JetBrains Mono',monospace;font-size:.9em;outline:none;"
                            oninput="checkIP(this.value)">
                        <div id="ip-result"></div>
                    </div>
                    <div id="ip-hint" style="margin-top:10px;font-size:.78em;color:var(--text-3)">Tapez pour filtrer la liste ci-dessous…</div>
                </div>
            </div>
            <div class="panel">
                <div class="panel-hdr">
                    <div class="panel-title">🔒 Liste des IPs bannies</div>
                    <div id="ip-count" style="font-size:.78em;color:var(--text-3);font-family:'JetBrains Mono',monospace"><?= count($banned_ips) ?> entrées</div>
                </div>
                <div class="panel-body">
                    <?php if (!empty($banned_ips)): ?>
                    <div class="ip-list" id="ip-list">
                        <?php foreach ($banned_ips as $bip): ?>
                        <div class="ip-row ip-entry" data-ip="<?= htmlspecialchars($bip) ?>"><?= htmlspecialchars($bip) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><div class="empty-icon">✅</div>Aucune IP bannie</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ═══ LOG FILES ═══ -->
        <div class="section <?= $section==='logfiles'?'active':'' ?>">
            <div class="page-header">
                <div class="page-title">Fichiers de logs</div>
                <div class="page-sub">Contenu des fichiers dans /logs</div>
            </div>
            <div class="panel">
                <div class="panel-hdr"><div class="panel-title">🗂️ Sélectionner un fichier (<?= count($Logs) ?> disponibles)</div></div>
                <div class="panel-body">
                    <?php if (!empty($Logs)): ?>
                    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                        <input type="hidden" name="section" value="logfiles">
                        <select name="logfile" style="flex:1;min-width:220px" onchange="this.form.submit()">
                            <option value="">— Sélectionnez un fichier —</option>
                            <?php foreach ($Logs as $log): ?>
                            <option value="<?= htmlspecialchars($log) ?>" <?= (isset($_GET['logfile']) && $_GET['logfile']===$log)?'selected':'' ?>><?= htmlspecialchars($log) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($log_cat_name): ?><a href="?section=logfiles" class="btn red" style="text-decoration:none">✕ Fermer</a><?php endif; ?>
                    </form>
                    <?php else: ?>
                    <div class="empty-state"><div class="empty-icon">📂</div>Aucun fichier de log trouvé</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($log_cat_name !== null): ?>
            <div class="panel">
                <div class="panel-hdr">
                    <div class="panel-title"><span style="color:var(--cyan-l)">📄</span> <span class="mono" style="font-size:.9em">/logs/<?= htmlspecialchars($log_cat_name) ?></span></div>
                    <div style="font-size:.75em;color:var(--text-3);font-family:'JetBrains Mono',monospace"><?= substr_count($log_cat_output, "\n") + 1 ?> lignes</div>
                </div>
                <div class="panel-body" style="padding:14px">
                    <?php if (!empty(trim($log_cat_output))): ?>
                    <div class="log-console" style="max-height:520px"><?= htmlspecialchars($log_cat_output) ?></div>
                    <?php else: ?>
                    <div class="empty-state"><div class="empty-icon">📭</div>Fichier vide ou non lisible</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<div class="main-footer">
    <span>Dashboard LXC</span>
    <span>Actualisation auto toutes les 30s · PHP/LXC</span>
</div>

<script>
function checkIP(val) {
    val = val.trim();
    const result  = document.getElementById('ip-result');
    const hint    = document.getElementById('ip-hint');
    const entries = document.querySelectorAll('.ip-entry');
    const count   = document.getElementById('ip-count');
    let visible = 0;
    entries.forEach(r => {
        const match = !val || r.getAttribute('data-ip').includes(val);
        r.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    if (count) count.textContent = visible + ' entrée' + (visible > 1 ? 's' : '');
    if (!val) { result.innerHTML = ''; hint.textContent = 'Tapez pour filtrer la liste ci-dessous…'; return; }
    hint.textContent = visible + ' résultat' + (visible !== 1 ? 's' : '') + ' pour "' + val + '"';
    let found = false;
    entries.forEach(r => { if (r.getAttribute('data-ip') === val) found = true; });
    result.innerHTML = found
        ? '<span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;background:rgba(220,38,38,.12);border:1px solid rgba(220,38,38,.3);border-radius:6px;color:#ef4444;font-weight:700;font-family:\'JetBrains Mono\',monospace;">⊘ Bannie</span>'
        : '<span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.3);border-radius:6px;color:#22c55e;font-weight:700;font-family:\'JetBrains Mono\',monospace;">✓ Non bannie</span>';
}
</script>

</body>
</html>
