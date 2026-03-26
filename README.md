# Dashboard LXC
![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![Status](https://img.shields.io/badge/status-Libre-orange)
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4)
![LXC](https://img.shields.io/badge/LXC-compatible-success)

## Présentation

Ce dépôt contient un dashboard web interactif pour gérer vos conteneurs LXC via une interface moderne inspirée des panels d'administration (Proxmox, Webmin).

**Fonctionnalités principales :**
- Authentification sécurisée avec **code A2F par email**
- Interface **panel avec barre latérale** et navigation par sections
- Vue d'ensemble avec métriques système en temps réel (uptime, load, RAM, disque)
- Gestion individuelle et globale des conteneurs LXC (start / stop / restart)
- Consultation des **logs d'erreur** par conteneur (10 dernières lignes)
- Gestion des **sauvegardes** locales avec suppression
- Section **sécurité** : liste des IPs bannies avec recherche et filtrage en direct
- Explorateur de **fichiers de logs** avec affichage du contenu (`cat`)
- Notification par email à chaque connexion réussie
- **Actualisation automatique** toutes les 30 secondes

---

## Installation

### 1. Dépendances

Sur votre serveur principal, installez Apache et PHP :

```bash
apt update
apt install apache2 php php-cli php-common libapache2-mod-php
```

### 2. Déploiement

Créez le dossier du dashboard et déposez-y le fichier :

```bash
mkdir -p /var/www/lxc
cp index.php /var/www/lxc/
chown -R www-data:www-data /var/www/lxc
```

Modifiez la configuration Apache dans `/etc/apache2/sites-available/000-default.conf` :

```apache
DocumentRoot /var/www/lxc
```

Rechargez Apache :

```bash
systemctl restart apache2
```

### 3. Configuration du fichier `index.php`

Ouvrez `index.php` et adaptez les paramètres en haut du fichier :

**Identifiants de connexion :**
```php
$ADMIN_USER = 'votre_username';
$ADMIN_PASS = 'votre_password';
```

**Configuration email (pour l'A2F et les notifications) :**
```php
$EMAIL_DESTINATAIRE = 'you@example.com';
$EMAIL_EXPEDITEUR   = 'noreply@yourdomain.com';
```

**Liste des conteneurs :**
```php
$containers = [
    'conteneur1' => ['name' => 'Mon Service 1', 'port' => 443, 'port2' => 80, 'ip' => '10.0.3.10'],
    'conteneur2' => ['name' => 'Mon Service 2', 'port' => 443, 'port2' => 80, 'ip' => '10.0.3.20'],
];
```

**Chemins à adapter selon votre infrastructure :**
```php
// Dans getGenInfoBanIP() — fichier IPSet des IPs bannies
exec("sudo cat /path/to/your/IPSet/IPD.csv", $output);

// Dans getGenInfoBackups() — dossier des backups
exec("sudo ls /backups", $output);

// Dans getGenInfoLogs() — dossier des fichiers de logs
exec("sudo ls /logs", $output);

// Dans getContainerLogs() — logs d'erreur par conteneur
$log = escapeshellarg("/var/log/sites/" . $container . "-error.log");
```

---

## Permissions sudo

L'utilisateur `www-data` doit pouvoir exécuter les commandes LXC sans mot de passe.

```bash
visudo
```

Ajoutez les lignes suivantes selon les fonctionnalités utilisées :

```bash
# Gestion des conteneurs
www-data ALL=(ALL) NOPASSWD: /usr/bin/lxc-start
www-data ALL=(ALL) NOPASSWD: /usr/bin/lxc-stop
www-data ALL=(ALL) NOPASSWD: /usr/bin/lxc-info

# Contrôle global via services systemd
www-data ALL=(ALL) NOPASSWD: /bin/systemctl start lxcStart.service
www-data ALL=(ALL) NOPASSWD: /bin/systemctl start lxcStop.service

# Backup
www-data ALL=(ALL) NOPASSWD: /bin/systemctl start backup.service
www-data ALL=(ALL) NOPASSWD: /bin/systemctl status backup.service
www-data ALL=(ALL) NOPASSWD: /bin/rm -f /backups/*

# Logs et fichiers (adapter les chemins)
www-data ALL=(ALL) NOPASSWD: /usr/bin/tail -n 10 /var/log/sites/*
www-data ALL=(ALL) NOPASSWD: /bin/cat /logs/*
www-data ALL=(ALL) NOPASSWD: /bin/cat /path/to/your/IPSet/IPD.csv
www-data ALL=(ALL) NOPASSWD: /bin/ls /backups
www-data ALL=(ALL) NOPASSWD: /bin/ls /logs

# Reboot (optionnel)
www-data ALL=(ALL) NOPASSWD: /sbin/reboot
```

---

## Scripts systemd (optionnel)

Pour utiliser les fonctions de démarrage/arrêt global des conteneurs :

1. Déplacez les scripts `.sh` dans `/usr/local/bin/`
2. Déplacez les fichiers `.service` dans `/etc/systemd/system/`
3. Rechargez systemd et rendez les scripts exécutables :

```bash
systemctl daemon-reload
chmod +x /usr/local/bin/*.sh
```

---

## Compatibilité

| Composant | Requis |
|-----------|--------|
| OS | Debian / Ubuntu (LXC compatible) |
| PHP | 7.4+ (8.x recommandé) |
| Serveur web | Apache2 (mod_php) |
| LXC | Toute version récente |
| Email A2F | Fonction `mail()` PHP opérationnelle |

---

## Contribution

Les contributions sont les bienvenues !

- Ouvrir une **issue** pour signaler un bug ou proposer une amélioration
- Soumettre une **pull request** pour vos modifications
- **Forker** le projet et l'adapter à votre infrastructure

### Contact

- Email : **contact@nlempereur.ovh**
- Site web : [https://nlempereur.ovh/contact.php](https://nlempereur.ovh/contact.php)

---

## Licence

Ce projet est sous **licence libre**.  
Vous êtes libre de l'utiliser, le modifier et le redistribuer selon vos besoins.

---

**Merci d'utiliser ce projet !** 🚀  
🔗 [https://nlempereur.ovh](https://nlempereur.ovh)
