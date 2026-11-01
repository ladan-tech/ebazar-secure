# Tests de sécurité

La suite [`tests/security/run.sh`](../tests/security/run.sh) est un ensemble de **tests d'intrusion
automatisés en boîte noire** : elle n'a aucun accès au code ni à la base et attaque l'application
uniquement via HTTP, comme un attaquant réel.

## Exécution

```bash
# Base dédiée : le script RÉINSTALLE l'application (tables recréées)
sudo mysql -e "CREATE DATABASE ebazar_test CHARACTER SET utf8mb4;
GRANT ALL ON ebazar_test.* TO 'ebazar'@'localhost';"

# Serveur de test (4 processus pour tester réellement la concurrence)
cd src && PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:8000 router.php &

# Lancement
BASE_URL=http://127.0.0.1:8000 DB_NAME=ebazar_test DB_PASS=secret ./tests/security/run.sh
```

| Variable | Défaut | Rôle |
|---|---|---|
| `BASE_URL` | `http://localhost:8000` | URL de l'application |
| `APP_DIR` | `./src` | Dossier de l'application (pour la réinstallation) |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | `127.0.0.1`, `3306`, `ebazar_test`, `ebazar`, `secret` | Base de test |

Code de sortie : `0` si tous les tests réussissent, `1` sinon (utilisable en CI).

## Scénarios (63)

| Famille | Scénarios d'attaque |
|---|---|
| **0. Installation** | Installation automatique ; installateur verrouillé après usage |
| **1. En-têtes** (A05) | CSP stricte sans `unsafe-inline` ; anti-clickjacking ; `nosniff` ; `Referrer-Policy` / `Permissions-Policy` ; absence de `X-Powered-By` ; drapeaux du cookie |
| **2. Exposition de fichiers** (A01/A05) | Lecture de `config.php`, du SQL, du code, des `.htaccess` ; traversée `?c=../../etc/passwd` ; appel de `__construct` via l'URL |
| **3. Authentification** (A07) | Mot de passe faible ; mot de passe contenant l'identifiant ; pot de miel anti-robots ; énumération de comptes ; **force brute** (verrouillage même avec le bon mot de passe) ; verrouillage lié à l'IP |
| **4. Sessions** (A07) | **Fixation** de session ; **rejeu d'un cookie volé** depuis un autre navigateur ; révocation des autres appareils ; conservation de la session courante ; déconnexion serveur ; `no-store` |
| **5. CSRF** (A01) | Jeton absent ; jeton falsifié ; action d'état en GET |
| **6. Injections** (A03) | SQLi `' OR '1'='1` sur la connexion ; SQLi `UNION SELECT` dans la recherche ; SQLi dans un `id` numérique ; jokers `LIKE` ; **XSS stockée** ; XSS réfléchie |
| **7. Contrôle d'accès** (A01) | Anonyme → espace membre ; membre → admin ; **IDOR** (suppression, réception) ; action admin par un membre ; auto-achat ; falsification du mode de livraison ; bien vendu caché aux tiers ; e-mail masqué |
| **8. Logique métier** | **Situation de compétition** : 10 achats simultanés du même bien → 1 seul réussit |
| **9. Téléversement** (A04/A08) | **Webshell** déguisé en `.jpg` avec MIME falsifié ; fichier trop gros ; nom aléatoire ; **JPEG polyglotte** (code PHP caché) ; **EXIF / GPS** supprimés ; accès à `uploads/*.php` |
| **10. Journalisation** (A09) | Échecs, blocages et sessions suspectes journalisés ; aucune trace technique dans les erreurs |

## Fiabilité des tests : test de mutation

Un test de sécurité qui réussit toujours ne prouve rien. Pour valider la suite, des vulnérabilités ont été
**réintroduites volontairement** dans le code, puis la suite a été relancée :

| Mutation introduite | Tests en échec (attendu) |
|---|---|
| `e()` renvoie le texte sans échappement (XSS) | XSS stockée ; XSS réfléchie |
| `Journal::etatTentatives()` ne bloque jamais (force brute) | Verrouillage après 5 échecs ; journalisation des blocages |

Résultat : `59 réussi(s), 4 échec(s)` avec les mutations, puis `63 réussi(s), 0 échec(s)` une fois le code
restauré. Les tests détectent donc bien les régressions qu'ils sont censés détecter.

## Intégration continue

Le workflow [`.github/workflows/security.yml`](../.github/workflows/security.yml) exécute à chaque `push`
et chaque *pull request* :

1. **Lint** : `php -l` sur tous les fichiers PHP ;
2. **Tests d'intrusion** : la suite complète contre une base MariaDB éphémère ;
3. **Recherche de secrets** : Gitleaks sur tout l'historique Git ;
4. **Analyse statique (SAST)** : Semgrep avec les règles PHP et OWASP Top 10 (informatif).

## Vérifications manuelles complémentaires

À réaliser avec un proxy d'interception (Burp Suite, OWASP ZAP) :

- [ ] Scan passif ZAP sur l'ensemble du parcours (aucune alerte haute ou moyenne attendue)
- [ ] Rejeu d'une requête d'achat avec un `id` d'annonce vendue
- [ ] Modification du champ caché `MAX_FILE_SIZE` (le contrôle serveur doit s'appliquer)
- [ ] Vérification de la CSP avec l'outil CSP Evaluator de Google
- [ ] Vérification des en-têtes sur securityheaders.com (une fois l'application déployée en HTTPS)
