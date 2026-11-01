# Catalogue des contrôles de sécurité

Correspondance entre chaque contrôle, le risque **OWASP Top 10 (2021)**, l'exigence **OWASP ASVS 4.0**
la plus proche, son emplacement dans le code et le test qui le vérifie.

## 1. Authentification

| Contrôle | OWASP | ASVS | Implémentation | Test |
|---|---|---|---|---|
| Hachage Argon2id (repli bcrypt), re-hachage automatique si l'algorithme évolue | A02, A07 | V2.4.1, V2.4.4 | `Utilisateur::hacher()`, `Utilisateur::authentifier()` | §3 |
| Politique de mot de passe : ≥ 12 caractères, ≤ 128, minuscule, majuscule, chiffre, spécial | A07 | V2.1.1, V2.1.2 | `Utilisateur::verifierPolitique()` | §3 |
| Refus d'un mot de passe contenant l'identifiant ou un mot courant | A07 | V2.1.7 | `Utilisateur::verifierPolitique()` | §3 |
| Mêmes règles affichées côté client (jauge de robustesse), **sans s'y fier** | — | V2.1.8 | `public/js/app.js` | — |
| Verrouillage après 5 échecs par (e-mail, IP) pendant 15 min ; 20 échecs par IP | A07 | V2.2.1 | `Journal::etatTentatives()`, `AuthController::connexion()` | §3 |
| Mot de passe non vérifié pendant le verrouillage | A07 | V2.2.1 | `AuthController::connexion()` | §3 |
| Alerte du membre : « N tentatives échouées depuis votre dernière visite » | A09 | V2.2.3 | `Journal::echecsDepuisDernierSucces()` | — |
| Message d'échec unique, temps de réponse uniforme (hachage factice) | A07 | V2.2.1, V3.2.x | `Utilisateur::authentifier()` | §3 |
| Ancien mot de passe exigé pour en changer | A07 | V2.1.5 | `CompteController::motDePasse()` | — |
| Pot de miel + 5 inscriptions max par IP et par heure | A04, A07 | V11.1.4 | `AuthController::inscription()` | §3 |
| Comparaisons de secrets à temps constant (`hash_equals`) | A02 | V2.9.x | `Session::verifierCsrf()`, contrôleurs | — |

## 2. Gestion des sessions

| Contrôle | OWASP | ASVS | Implémentation | Test |
|---|---|---|---|---|
| Cookie `HttpOnly`, `SameSite=Lax`, `Secure` sous HTTPS, chemin restreint | A07 | V3.4.1–V3.4.5 | `Session::demarrer()` | §1 |
| Mode strict : identifiant inconnu refusé (anti-fixation) | A07 | V3.2.1 | `Session::demarrer()` | §4 |
| Nouvel identifiant et session vierge à la connexion | A07 | V3.2.1 | `Session::connecter()` | §4 |
| Régénération de l'identifiant toutes les 10 minutes | A07 | V3.2.x | `Session::verifier()` | — |
| Expiration : 30 min d'inactivité, 8 h au maximum | A07 | V3.3.1, V3.3.2 | `Session::verifier()` | — |
| Empreinte navigateur : cookie rejoué ailleurs → session détruite et journalisée | A07 | V3.5.x | `Session::verifier()` | §4 |
| **Révocation** : version de session en base, incrémentée au changement de mot de passe, sur demande ou à la suppression du compte | A07 | V3.3.3, V3.3.4 | `Utilisateur::revoquerSessions()`, `Session::verifier()` | §4 |
| Déconnexion en POST avec destruction côté serveur et suppression du cookie | A07 | V3.3.1 | `Session::deconnecter()` | §4 |
| Pages authentifiées en `Cache-Control: no-store` | A04 | V8.2.1 | `Controller::vue()` | §4 |

## 3. Contrôle d'accès

| Contrôle | OWASP | ASVS | Implémentation | Test |
|---|---|---|---|---|
| Routeur à **liste blanche** (contrôleur → actions autorisées) | A01 | V4.1.1 | `App::ROUTES` | §2 |
| Contrôle du rôle au plus près de l'action (`exigerMembre`, `exigerAdmin`) | A01 | V4.1.3 | `Controller` | §7 |
| `AdminController` protégé dans son constructeur (impossible d'oublier une action) | A01 | V4.1.5 | `AdminController::__construct()` | §7 |
| Vérification du **propriétaire** (anti-IDOR) : suppression, réception, visibilité d'un bien vendu | A01 | V4.2.1 | `AnnonceController`, `AchatController` | §7 |
| Moindre privilège : l'administrateur ne peut ni déposer ni acheter | A01 | V4.1.3 | `Controller::exigerMembre()` | — |
| CSRF : jeton 256 bits par session, renouvelé à la connexion, POST obligatoire | A01 | V4.2.2 | `Session::jetonCsrf()`, `Controller::exigerPost()` | §5 |
| Redirection après connexion limitée aux chemins internes (pas de redirection ouverte) | A01 | V5.1.5 | `Session::extraireRedirection()` | — |
| Installateur verrouillé après usage | A05 | V14.1.x | `InstallController::index()` | §0 |

## 4. Validation des entrées et encodage des sorties

| Contrôle | OWASP | ASVS | Implémentation | Test |
|---|---|---|---|---|
| Requêtes préparées **natives** (`ATTR_EMULATE_PREPARES = false`), typage des paramètres | A03 | V5.3.4 | `Database::creer()`, `Model::requete()` | §6 |
| Identifiants validés en entiers positifs (`FILTER_VALIDATE_INT`) | A03 | V5.1.3 | `Controller::idParam()` | §6 |
| Jokers `%` et `_` échappés dans la recherche `LIKE` | A03 | V5.3.x | `Annonce::motifLike()` | §6 |
| Règles métier revérifiées côté serveur (titre, description, prix, livraison) | A04 | V5.1.3 | `Annonce::valider()` | — |
| Encodage HTML contextuel de toute sortie (`htmlspecialchars`, UTF-8) | A03 | V5.3.1 | `e()` dans `helpers.php`, toutes les vues | §6 |
| Contraintes de base : clés étrangères, `CHECK` (prix ≥ 0, au moins un mode de livraison) | A04 | V5.1.x | `sql/schema.sql` | — |

## 5. Fichiers téléversés

| Contrôle | OWASP | ASVS | Implémentation | Test |
|---|---|---|---|---|
| Type déterminé par le **contenu** (`finfo` + `getimagesize`), jamais par l'extension ni le MIME déclaré | A04 | V12.2.1 | `Photo::validerEnvoi()` | §9 |
| Taille ≤ 200 Kio, dimensions ≤ 6000 px (vérifiées avant décodage), 5 fichiers max | A04 | V12.1.1 | `Photo::validerEnvoi()` | §9 |
| **Ré-encodage GD** : supprime EXIF / GPS et tout contenu caché (polyglottes) | A08 | V12.2.1 | `Photo::enregistrerFichier()` | §9 |
| Nom de fichier aléatoire de 128 bits, extension `.jpg` imposée | A04 | V12.3.1 | `Photo::enregistrerFichier()` | §9 |
| Exécution de scripts désactivée dans `uploads/`, seuls les noms attendus sont servis | A05 | V12.4.1 | `uploads/.htaccess`, `router.php` | §9 |
| Fichiers effacés du disque **après** validation de la transaction | A04 | — | `Utilisateur::supprimer()`, `Annonce::supprimer()` | — |

## 6. Configuration et en-têtes HTTP

| En-tête / réglage | Valeur | Rôle |
|---|---|---|
| `Content-Security-Policy` | `default-src 'self'; script-src 'self'; style-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'` | Bloque les scripts injectés (XSS), le clickjacking et la redirection de formulaires |
| `X-Frame-Options` | `DENY` | Anti-clickjacking (anciens navigateurs) |
| `X-Content-Type-Options` | `nosniff` | Empêche l'interprétation d'une image comme un script |
| `Referrer-Policy` | `same-origin` | Aucune URL interne transmise aux sites tiers |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=(), payment=()` | Désactive les API sensibles |
| `Cross-Origin-Opener-Policy` | `same-origin` | Isole le contexte de navigation |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` (sous HTTPS) | Force HTTPS |
| `X-Powered-By` | supprimé | Ne divulgue pas la version de PHP |
| `display_errors` | `0` | Aucune trace technique affichée |

Pour respecter la CSP sans `unsafe-inline`, l'interface ne contient **aucun** script ni attribut `style`
en ligne : toutes les interactions sont dans `public/js/app.js` et les icônes sont des SVG en ligne.

## 7. Journalisation

Table `journal` : `utilisateur_id`, `email`, `ip`, `evenement`, `details`, `date_evt`.

| Événement | Déclencheur |
|---|---|
| `connexion_reussie` / `connexion_echouee` / `connexion_bloquee` | Formulaire de connexion |
| `session_suspecte` | Empreinte navigateur différente (cookie rejoué) |
| `inscription`, `deconnexion`, `mdp_modifie`, `sessions_revoquees` | Actions du membre |
| `admin_suppr_annonce`, `admin_suppr_membre`, `admin_categorie` | Actions de l'administrateur |
| `installation` | Initialisation de la base |

- Aucune donnée secrète n'est journalisée (ni mot de passe, ni jeton, ni identifiant de session).
- L'IP provient uniquement de `REMOTE_ADDR` (`X-Forwarded-For` est falsifiable par le client).
- Une panne du journal n'interrompt pas l'application (`Journal::ajouter()` est tolérant aux erreurs).
