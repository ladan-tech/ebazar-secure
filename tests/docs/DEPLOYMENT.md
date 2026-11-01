# Déploiement et durcissement

## 1. Développement local

```bash
sudo apt install php-cli php-mysql php-mbstring php-gd mariadb-server
sudo mysql -e "CREATE DATABASE ebazar CHARACTER SET utf8mb4;
CREATE USER 'ebazar'@'localhost' IDENTIFIED BY 'change-moi';
GRANT ALL ON ebazar.* TO 'ebazar'@'localhost';"
cd src && php -S localhost:8000 router.php
```

`router.php` reproduit pour le serveur intégré les protections des fichiers `.htaccess`
(dossiers internes, fichiers cachés, `uploads/`).

## 2. Production (Debian + Apache)

```bash
# 1. Déposer l'application (le contenu de src/) dans la racine web
sudo cp -r src /var/www/html/ebazar

# 2. Droits : le serveur web n'écrit QUE dans config/ (une fois) et uploads/
sudo chown -R root:www-data /var/www/html/ebazar
sudo find /var/www/html/ebazar -type d -exec chmod 750 {} \;
sudo find /var/www/html/ebazar -type f -exec chmod 640 {} \;
sudo chown www-data /var/www/html/ebazar/config /var/www/html/ebazar/uploads
sudo chmod 770 /var/www/html/ebazar/config /var/www/html/ebazar/uploads

# 3. Autoriser les .htaccess (désactivés par défaut sous Debian)
sudo tee /etc/apache2/conf-available/ebazar.conf <<'EOF'
<Directory /var/www/html/ebazar>
    AllowOverride All
</Directory>
EOF
sudo a2enconf ebazar && sudo a2enmod headers && sudo systemctl reload apache2
```

Ouvrir `http://<serveur>/ebazar/` : l'assistant d'installation demande les paramètres de la base
(déjà existante) et crée le compte administrateur. Il se verrouille ensuite automatiquement.

**Installation manuelle** (si l'assistant ne peut pas écrire dans `config/`) :

```bash
mysql -u <user> -p <base> < src/sql/schema.sql
mysql -u <user> -p <base> < src/sql/donnees_initiales.sql   # admin@ebazar.local / Vert&Bleu-2026!
cp src/config/config.php.dist src/config/config.php          # puis renseigner les accès
```

⚠️ Changez immédiatement le mot de passe administrateur par défaut de l'installation manuelle.

## 3. Checklist de durcissement

### Serveur web
- [ ] **HTTPS obligatoire** (Let's Encrypt / certbot) avec redirection 80 → 443 : le cookie `Secure` et HSTS s'activent automatiquement.
- [ ] `ServerTokens Prod` et `ServerSignature Off` dans `security.conf` (version d'Apache masquée).
- [ ] Vérifier que `http://<serveur>/ebazar/sql/schema.sql` renvoie **403** (sinon `AllowOverride` n'est pas actif).
- [ ] Idéalement, ne publier que `src/` comme `DocumentRoot` d'un hôte virtuel dédié.

### PHP (`php.ini`)
- [ ] `display_errors = Off`, `log_errors = On`
- [ ] `expose_php = Off`
- [ ] `session.cookie_secure = 1` (sous HTTPS), `session.use_strict_mode = 1`
- [ ] `upload_max_filesize = 1M`, `post_max_size = 2M`
- [ ] `disable_functions = exec,passthru,shell_exec,system,proc_open,popen` (l'application n'en a pas besoin)
- [ ] `open_basedir = /var/www/html/ebazar:/tmp`

### Base de données
- [ ] Compte applicatif **dédié** limité à sa base : `GRANT SELECT, INSERT, UPDATE, DELETE ON ebazar.*` après l'installation (les droits `CREATE` / `DROP` ne servent qu'à l'installateur).
- [ ] MySQL écoute uniquement sur `127.0.0.1` (`bind-address`).
- [ ] Sauvegardes chiffrées et régulières (`mysqldump`).

### Exploitation
- [ ] Surveiller le **journal de sécurité** (onglet admin) : pics d'échecs de connexion, sessions suspectes.
- [ ] Purger périodiquement le journal (ex. : événements de plus de 12 mois) conformément au RGPD.
- [ ] Mettre à jour PHP, Apache et MariaDB (`unattended-upgrades`).
