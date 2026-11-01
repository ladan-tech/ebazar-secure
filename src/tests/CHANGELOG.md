# Changelog

Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) ; versionnage [SemVer](https://semver.org/lang/fr/).

## [2.0.0] — 2026-10-30 — Durcissement de sécurité

### Sécurité
- Hachage des mots de passe en **Argon2id** (repli bcrypt) avec re-hachage automatique.
- Politique de mot de passe alignée sur les recommandations de l'ANSSI (12+ caractères, 4 classes, pas l'identifiant).
- **Anti force brute** : verrouillage par couple (e-mail, IP) et par IP, alerte au membre.
- Sessions durcies : mode strict, régénération périodique, empreinte navigateur, expiration, **révocation à distance**.
- **Content-Security-Policy** stricte sans `unsafe-inline` ; en-têtes `Permissions-Policy`, `COOP`, HSTS sous HTTPS.
- Ré-encodage des images téléversées : suppression des métadonnées EXIF (GPS) et neutralisation des fichiers polyglottes.
- Journal d'audit de sécurité ; pot de miel et limitation des inscriptions.
- `router.php` : protections équivalentes à `.htaccess` pour le serveur de développement.

### Ajouté
- Tableaux de bord du membre (ventes, achats, sécurité du compte) et de l'administrateur (indicateurs, journal).
- Recherche d'annonces.
- Suite de **63 tests d'intrusion automatisés** et intégration continue (lint, tests, Gitleaks, Semgrep).
- Modèle de menaces STRIDE et catalogue des contrôles (OWASP Top 10 / ASVS).

### Modifié
- Refonte complète de l'interface.

## [1.0.0] — 2026-10-30

### Ajouté
- Version initiale répondant au cahier des charges du module LW1 : architecture MVC, inscription et connexion,
  dépôt d'annonces avec photos, achat, confirmation de réception, administration, installation web.
