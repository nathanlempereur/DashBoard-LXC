# Dashboard LXC

## 📋 Présentation
Ce dépôt contient un dashboard web interactif pour gérer vos conteneurs LXC via une interface moderne et intuitive.

---

## Installation du Dashboard

### 1. Installation d'Apache et PHP
Sur votre serveur principal, installez les dépendances nécessaires :

```bash
apt update
apt install apache2 php php-cli php-common libapache2-mod-php
```

### 2. Configuration du Dashboard

1. Créez le dossier pour le dashboard :
```bash
   mkdir -p /var/www/lxc
```

2. Déplacez le fichier `index.php` dans `/var/www/lxc`

3. Modifiez la configuration Apache dans `/etc/apache2/sites-available/000-default.conf` :
```apache
   DocumentRoot /var/www/lxc
```

4. Définissez les permissions :
```bash
   chown -R www-data:www-data /var/www/lxc
```

5. Redémarrez Apache :
```bash
   systemctl restart apache2
```

### 3. Configuration du fichier index.php

Ouvrez le fichier `index.php` et modifiez les paramètres suivants :

**Identifiants de connexion (lignes 7-8) :**
```php
$ADMIN_USER = 'votre_username';
$ADMIN_PASS = 'votre_password';
```

**Liste des conteneurs (ligne 130) :**
```php
$containers = [
    'conteneur1' => ['name' => 'Serveur 1', 'port' => 30000, 'ip' => '10.0.3.10'],
    'conteneur2' => ['name' => 'Serveur 2', 'port' => 30001, 'ip' => '10.0.3.5']
];
```

### Aperçu sur des conteneurs Minetest
<img width="1886" height="957" alt="Dashboard - Vue principale" src="https://github.com/user-attachments/assets/7e84cab3-c646-4dee-ba52-3af48074730e" />
<img width="1889" height="958" alt="Dashboard - Vue détaillée" src="https://github.com/user-attachments/assets/caf28b72-6633-4b0e-ae01-59fb64a8fe6a" />

---

## Configuration des permissions sudo

L'utilisateur `www-data` doit pouvoir exécuter les commandes LXC sans mot de passe :

```bash
visudo
```

Ajoutez les lignes suivantes :

```bash
www-data ALL=(ALL) NOPASSWD: /usr/bin/lxc-start
www-data ALL=(ALL) NOPASSWD: /usr/bin/lxc-stop
www-data ALL=(ALL) NOPASSWD: /usr/bin/lxc-info
www-data ALL=(ALL) NOPASSWD: /bin/systemctl start lxcStart.service
www-data ALL=(ALL) NOPASSWD: /bin/systemctl start lxcStop.service
```

---

## Installation des Scripts (optionnel)

Pour utiliser les fonctions de démarrage/arrêt global :

1. Déplacez les scripts `.sh` dans `/usr/local/bin/`
2. Déplacez les fichiers `.service` dans `/etc/systemd/system/`
3. Rechargez systemd :
```bash
   systemctl daemon-reload
```
4. Rendez les scripts exécutables :
```bash
   chmod +x /usr/local/bin/*.sh
```

---

## Informations importantes

Ce dashboard est pleinement compatible avec les distributions disposant de :
- LXC (Linux Containers)
- Apache2
- PHP

Le dashboard s'actualise automatiquement toutes les **30 secondes**.

---

## Contribution

Les contributions sont les bienvenues ! Vous pouvez :
- **Modifier** et **améliorer** le code
- **Proposer des mises à jour**
- Ouvrir une **issue** pour signaler un problème
- Soumettre une **pull request** pour vos améliorations

### Contact
- Email : **contact@nlempereur.ovh**
- Site web : https://nlempereur.ovh/contact.php

---

## Licence

Ce projet est sous **licence libre**.  
Vous êtes libre de l'utiliser, le modifier et le redistribuer selon vos besoins.

---

**Merci d'utiliser ce projet !**  
🔗 https://nlempereur.ovh
