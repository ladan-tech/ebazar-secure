# Architecture

Application PHP 8 sans framework, organisée selon le patron **Modèle-Vue-Contrôleur** avec un
**contrôleur frontal** unique.

## Couches

| Couche | Dossier | Responsabilité | Ce qu'elle ne fait jamais |
|---|---|---|---|
| Contrôleur frontal | `src/index.php` | Point d'entrée unique, en-têtes de sécurité, chargement automatique | Logique métier |
| Noyau | `src/app/core/` | Routage, session, accès aux données, rendu | — |
| Contrôleurs | `src/app/controllers/` | Droits d'accès, CSRF, orchestration, redirections | Requêtes SQL, HTML |
| Modèles | `src/app/models/` | Règles métier, validation, requêtes préparées | Lire `$_GET` / `$_POST`, produire du HTML |
| Vues | `src/app/views/` | Affichage, échappement des données | Accès à la base |

## Cycle d'une requête

```mermaid
sequenceDiagram
    autonumber
    participant N as Navigateur
    participant F as index.php
    participant S as Session
    participant R as Routeur (App)
    participant C as Contrôleur
    participant M as Modèle
    participant DB as MySQL
    participant V as Vue

    N->>F: GET/POST index.php?c=…&a=…
    F->>F: En-têtes CSP, X-Frame-Options, nosniff…
    F->>S: demarrer() — cookie HttpOnly / SameSite, mode strict
    F->>R: executer()
    R->>S: verifier() — inactivité, durée, empreinte, version
    S->>DB: SELECT version_session
    R->>R: c/a présents dans la liste blanche ? sinon 404
    R->>C: action()
    C->>C: exigerMembre() / exigerAdmin() / exigerCsrf()
    C->>M: appel métier (données validées)
    M->>DB: requête préparée
    DB-->>M: résultat
    alt Requête POST réussie
        C-->>N: 303 See Other (Post/Redirect/Get)
    else Affichage
        C->>V: vue(données)
        V-->>N: HTML échappé + Cache-Control: no-store si connecté
    end
```

## Cycle de vie d'une session

```mermaid
stateDiagram-v2
    [*] --> Anonyme
    Anonyme --> Authentifiée: connexion réussie\n(nouvel ID, session vierge, nouveau jeton CSRF)
    Anonyme --> Verrouillée: 5 échecs (e-mail, IP)
    Verrouillée --> Anonyme: après 15 min
    Authentifiée --> Authentifiée: activité (ID régénéré toutes les 10 min)
    Authentifiée --> Anonyme: déconnexion
    Authentifiée --> Anonyme: 30 min d'inactivité ou 8 h écoulées
    Authentifiée --> Anonyme: empreinte navigateur différente (journalisé)
    Authentifiée --> Anonyme: version révoquée\n(mot de passe changé, autre appareil, compte supprimé)
```

## Cycle de vie d'une annonce

```mermaid
stateDiagram-v2
    [*] --> en_vente: dépôt (membre)
    en_vente --> vendue: achat atomique\nUPDATE … WHERE statut = 'en_vente'
    en_vente --> [*]: suppression (vendeur ou admin)
    vendue --> [*]: réception confirmée par l'acheteur\n(retirée de toutes les listes)
    vendue --> [*]: suppression (admin)
```

## Modèle de données

```mermaid
erDiagram
    UTILISATEUR ||--o{ ANNONCE : "vend (CASCADE)"
    UTILISATEUR |o--o{ ANNONCE : "achète (SET NULL)"
    CATEGORIE ||--o{ ANNONCE : "classe (RESTRICT)"
    ANNONCE ||--o{ PHOTO : "illustre (CASCADE)"
    UTILISATEUR |o--o{ JOURNAL : "concerne (SET NULL)"

    UTILISATEUR {
        int id PK
        varchar email UK
        varchar mot_de_passe "Argon2id"
        enum role "membre | admin"
        int version_session "révocation"
        datetime derniere_connexion
    }
    ANNONCE {
        int id PK
        int vendeur_id FK
        int acheteur_id FK
        int categorie_id FK
        varchar titre "5 à 30"
        varchar description "5 à 200"
        decimal prix "CHECK >= 0"
        enum statut "en_vente | vendue"
        enum mode_livraison
    }
    PHOTO {
        int id PK
        int annonce_id FK
        varchar fichier "aléatoire 128 bits"
        tinyint ordre "0 = vignette"
    }
    CATEGORIE {
        int id PK
        varchar nom UK
    }
    JOURNAL {
        bigint id PK
        int utilisateur_id FK
        varchar email
        varchar ip
        varchar evenement
        datetime date_evt
    }
```

## API client ↔ serveur

URL : `index.php?c=<contrôleur>&a=<action>[&id=N]`. Toute action qui modifie l'état est en **POST**
et exige le champ `csrf`.

| Méthode | c / a | Paramètres | Accès | Effet |
|---|---|---|---|---|
| GET | `home/index` | — | tous | Catégories et compteurs, 4 dernières annonces |
| GET | `categorie/voir` | `id`, `page` | tous | Liste paginée (10 par page) |
| GET | `annonce/rechercher` | `q` (≤ 60), `page` | tous | Recherche dans le titre et la description |
| GET | `annonce/voir` | `id` | tous* | Fiche détaillée, galerie |
| GET/POST | `auth/inscription` | `email`, `mot_de_passe`, `confirmation`, `cgu` | anonyme | Création du compte |
| GET/POST | `auth/connexion` | `email`, `mot_de_passe` | anonyme | Connexion limitée, reprise de l'opération |
| POST | `auth/deconnexion` | — | connecté | Fin de session |
| GET/POST | `annonce/deposer` | `categorie_id`, `titre`, `description`, `prix`, `livraison_postale`, `livraison_main_propre`, `photos[]` | membre | Dépôt |
| POST | `annonce/supprimer` | `id` | vendeur, bien non vendu | Suppression |
| GET/POST | `achat/acheter` | `id`, `mode_livraison` | membre ≠ vendeur | Achat atomique |
| POST | `achat/confirmerReception` | `id` | acheteur | Clôture de la vente |
| GET | `compte/index` | `onglet` | membre | Tableau de bord |
| POST | `compte/motDePasse` | `actuel`, `nouveau`, `confirmation` | membre | Changement + révocation des autres sessions |
| POST | `compte/revoquerSessions` | — | membre | Déconnexion des autres appareils |
| GET | `admin/index` | `onglet`, `filtre` | admin | Tableau de bord, journal de sécurité |
| POST | `admin/ajouterCategorie` · `admin/renommerCategorie` | `nom` (+ `id`) | admin | Catégories |
| POST | `admin/supprimerAnnonce` · `admin/supprimerUtilisateur` | `id` | admin | Modération |
| GET/POST | `install/index` | paramètres BD + compte admin | avant installation | Initialisation |

\* Un bien vendu n'est visible que par son vendeur, son acheteur et l'administrateur.

Codes de réponse : `303` après un POST réussi, `403` accès refusé, `404` introuvable,
`405` mauvaise méthode, `500` erreur interne (message générique).
