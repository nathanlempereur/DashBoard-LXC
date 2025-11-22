<?php
// Démarre la session utilisateur pour gérer la connexion
session_start();

// Nom d'utilisateur et mot de passe de l'administrateur
// ** À personnaliser avant l'utilisation **
$ADMIN_USER = '#';
$ADMIN_PASS = '#';

if (isset($_POST['login'])) {
	// Vérification des informations de connexion
    if ($_POST['username'] === $ADMIN_USER && $_POST['password'] === $ADMIN_PASS) {
        $_SESSION['logged_in'] = true;
    }
}

if (isset($_GET['logout'])) {
	// Déconnexion et destruction de la session
    session_destroy();
    header('Location: /');
    exit;
}

// Si l'utilisateur n'est pas connecté, afficher la page de connexion
if (!isset($_SESSION['logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Login - Dashboard LXC</title>
        <style>
            body { font-family: Arial; background: #1a1a1a; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .login-box { background: #2a2a2a; padding: 40px; border-radius: 10px; min-width: 300px; }
            h2 { text-align: center; color: #4CAF50; margin-bottom: 30px; }
            input { width: 100%; padding: 12px; margin: 10px 0; border: none; border-radius: 5px; box-sizing: border-box; }
            button { width: 100%; padding: 12px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🐧 Dashboard LXC</h2>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Connexion</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}


/**
 * Fonction pour obtenir l'état d'un conteneur LXC
 * @param string $container Le nom du conteneur
 * @return string L'état du conteneur (par exemple: RUNNING, STOPPED)
 */
function getContainerStatus($container) {
    exec("sudo lxc-info -n $container -s 2>/dev/null | head -n 2 | tail -n 1 | awk '{print $2}'", $output);
    return isset($output[0]) ? trim($output[0]) : 'UNKNOWN';
}

/**
 * Fonction pour obtenir l'adresse IP d'un conteneur LXC
 * @param string $container Le nom du conteneur
 * @return string L'adresse IP du conteneur
 */
function getContainerIP($container) {
    exec("sudo lxc-info -n $container -iH 2>/dev/null", $output);
    return isset($output[0]) ? trim($output[0]) : 'N/A';
}

/**
 * Fonction pour obtenir l'uptime du système
 * @return array Informations sur l'uptime du système
 */
function getSystemInfoUptime() {
    exec("uptime -p", $output);
    $info1['uptime'] = $output[0] ?? 'N/A';
    return $info1;
}

/**
 * Fonction pour obtenir la charge moyenne du système
 * @return array Informations sur la charge moyenne du système
 */
function getSystemInfoLoad() {
    exec("uptime | awk -F'load average:' '{print $2}'", $output);
    $info2['load'] = trim($output[0] ?? 'N/A');
    return $info2;
}

/**
 * Fonction pour obtenir la mémoire utilisée sur le système
 * @return array Informations sur l'utilisation de la mémoire
 */
function getSystemInfoMemory() {
    exec("free -h | awk '{print $3}' | head -n 2 | tail -n 1", $output);
    $info3['memory'] = $output[0] ?? 'N/A';
    return $info3;
}


/**
 * Fonction pour démarrer un conteneur LXC
 * @param string $container Le nom du conteneur à démarrer
 */
function startContainer($container) {
    exec("sudo lxc-start -n $container");
}

/**
 * Fonction pour arrêter un conteneur LXC
 * @param string $container Le nom du conteneur à arrêter
 */
function stopContainer($container) {
    exec("sudo lxc-stop -n $container");
}

// Fonction pour démarrer tous les conteneurs via un service
function startAllContainers() {
    exec("sudo systemctl start lxcStart.service");
}
// Fonction pour arrêter tous les conteneurs
function stopAllContainers() {
    exec("sudo systemctl start lxcStop.service");
}

// Liste des conteneurs à gérer (peut être personnalisé selon les besoins)
// name -> Nom du conteneur
// port -> Port externe (DNAT)
// ip -> Ip du conteneur
$containers = [
    'conteneur1' => ['name' => 'conteneur1', 'port' => 30000, 'ip' => '10.0.3.10'],
    'conteneur2' => ['name' => 'conteneur2', 'port' => 30001, 'ip' => '10.0.3.5']
];


// On charge les informations système
$system_info1 = getSystemInfoUptime();
$system_info2 = getSystemInfoLoad();
$system_info3 = getSystemInfoMemory();

// Vérification de l'action (démarrer ou arrêter)
if (isset($_POST['start'])) {
    $container_to_start = $_POST['container_id'];
    startContainer($container_to_start);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['stop'])) {
    $container_to_stop = $_POST['container_id'];
    stopContainer($container_to_stop);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// Vérification de lactions globales pour tous les conteneurs (démarrer ou arrêter)
if (isset($_POST['start_all'])) {
    startAllContainers();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['stop_all'])) {
    stopAllContainers();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard LXC</title>
    <meta http-equiv="refresh" content="30">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #1a1a1a; color: #fff; padding: 20px; }
        header { background: #2a2a2a; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        h1 { color: #4CAF50; }
        .logout { background: #f44336; padding: 10px 20px; border-radius: 5px; color: white; text-decoration: none; }
        .system-info { background: #2a2a2a; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .system-info h2 { color: #4CAF50; margin-bottom: 15px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .info-card { background: #333; padding: 15px; border-radius: 8px; }
        .info-label { color: #888; font-size: 0.9em; }
        .info-value { font-size: 1.2em; font-weight: bold; color: #4CAF50; margin-top: 5px; }
        
        /* Section de contrôle global */
        .global-controls { background: #2a2a2a; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .global-controls h2 { color: #4CAF50; margin-bottom: 15px; }
        .global-controls form { display: inline-block; margin: 0 10px; }
        .global-btn { padding: 12px 30px; border-radius: 5px; font-size: 1em; font-weight: bold; border: none; cursor: pointer; transition: all 0.3s; }
        .global-btn.start { background: #4CAF50; color: white; }
        .global-btn.start:hover { background: #45a049; }
        .global-btn.stop { background: #f44336; color: white; }
        .global-btn.stop:hover { background: #da190b; }
        
        .containers-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .container-card { background: #2a2a2a; padding: 20px; border-radius: 10px; border-left: 4px solid #4CAF50; }
        .container-card.stopped { border-left-color: #f44336; }
        .container-header { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .container-title { font-size: 1.3em; font-weight: bold; }
        .status { padding: 5px 15px; border-radius: 20px; font-size: 0.9em; font-weight: bold; }
        .status.running { background: #4CAF50; }
        .status.stopped { background: #f44336; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #333; }
        .detail-label { color: #888; }
        .detail-value { font-weight: bold; color: #4CAF50; }
        .action { padding: 5px 15px; border-radius: 20px; font-size: 0.9em; font-weight: bold; border: none; cursor: pointer; }
    </style>
</head>
<body>
	<!-- Header de la page -->
    <header>
        <h1>🐧 Dashboard LXC</h1>
        <a href="?logout" class="logout">Déconnexion</a>
    </header>

	<!-- Informations système -->
    <div class="system-info">
        <h2>📊 Serveur Central</h2>
        <div class="info-grid">
            <div class="info-card">
                <div class="info-label">Uptime</div>
                <div class="info-value"><?= htmlspecialchars($system_info1['uptime']) ?></div>
            </div>
            <div class="info-card">
                <div class="info-label">Load Average</div>
                <div class="info-value"><?= htmlspecialchars($system_info2['load']) ?></div>
            </div>
            <div class="info-card">
                <div class="info-label">Mémoire use</div>
                <div class="info-value"><?= htmlspecialchars($system_info3['memory']) ?></div>
            </div>
        </div>
    </div>

    <!-- Section de contrôle global -->
    <div class="global-controls">
        <h2>Contrôle Global des Conteneurs :</h2>
        <form method="POST" style="display: inline;">
            <button type="submit" name="start_all" class="global-btn start">Démarrer TOUS les conteneurs</button>
        </form>
        <form method="POST" style="display: inline;">
            <button type="submit" name="stop_all" class="global-btn stop">Arrêter TOUS les conteneurs</button>
        </form>
    </div>

	<!-- Liste des conteneurs -->
    <h2 style="margin-bottom: 20px; color: #4CAF50;"> Conteneurs Minetest</h2>
    
    <!-- Division de Tous les conteneurs renseigner-->
    <div class="containers-grid">
        <?php foreach ($containers as $container_id => $data):
            $status = getContainerStatus($container_id);
            $ip = getContainerIP($container_id);
            $is_running = ($status === 'RUNNING');
        ?>
        
        <!-- Affichage du status en fonction de  getContainerStatus($container) -->
        <div class="container-card <?= $is_running ? '' : 'stopped' ?>">
            <div class="container-header">
                <div class="container-title"><?= $data['name'] ?></div>
                <div class="status <?= $is_running ? 'running' : 'stopped' ?>">
                    <?= $is_running ? '✓ RUNNING' : '✗ STOPPED' ?>
                </div>
            </div>
            <!-- Nom du conteneur -->
            <div class="detail-row">
                <span class="detail-label">Conteneur:</span>
                <span class="detail-value"><?= $container_id ?></span>
            </div>
            <!-- l'ip renseigner -->
            <div class="detail-row">
                <span class="detail-label">IP interne:</span>
                <span class="detail-value"><?= $ip ?></span>
            </div>
            <!-- Port Dnat renseigner -->
            <div class="detail-row">
                <span class="detail-label">Port externe:</span>
                <span class="detail-value"><?= $data['port'] ?></span>
            </div>
            <!-- Port interne (supprimer si pas besoin) -->
            <div class="detail-row">
                <span class="detail-label">Port interne:</span>
                <span class="detail-value">30000</span>
            </div>

            <!-- Boutons de contrôle pour démarrer et arrêter -->
            <form method="POST" style="margin-top: 10px;">
                <input type="hidden" name="container_id" value="<?= $container_id ?>">
                <?php if ($is_running): ?>
                    <button class="action" type="submit" name="stop" style="background-color: #f44336; color: white;">Arrêter</button>
                <?php else: ?>
                    <button class="action" type="submit" name="start" style="background-color: #4CAF50; color: white;">Démarrer</button>
                <?php endif; ?>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="text-align: center; margin-top: 30px; color: #888;">
        <p>Actualisation automatique toutes les 30 secondes</p>
    </div>
</body>
</html>
