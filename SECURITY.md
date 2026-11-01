# Politique de sécurité

## Versions prises en charge

| Version | Prise en charge |
|---|:---:|
| 2.x | ✅ |
| 1.x | ❌ |

## Signaler une vulnérabilité

Merci de contribuer à la sécurité d'e-bazar. **N'ouvrez pas d'issue publique** pour une vulnérabilité.

1. Utilisez l'onglet **Security → Report a vulnerability** de ce dépôt (signalement privé GitHub).
2. Décrivez : la fonctionnalité concernée, les étapes de reproduction, l'impact estimé et, si possible,
   une preuve de concept minimale.
3. Délais visés : accusé de réception sous **72 h**, évaluation sous **7 jours**, correctif selon la
   gravité (CVSS v3.1).

## Périmètre

**Inclus** : le code de ce dépôt (`src/`), sa configuration par défaut et la suite de tests.

**Exclus** : le déni de service volumétrique, l'ingénierie sociale, les vulnérabilités de PHP, Apache ou
MySQL eux-mêmes, et les rapports issus uniquement de scanners automatiques sans démonstration d'impact.

## Divulgation

Divulgation coordonnée : les détails sont publiés une fois le correctif disponible, avec mention de
l'auteur du signalement s'il le souhaite.

## Bonnes pratiques appliquées au projet

- Modèle de menaces maintenu à jour : [docs/THREAT_MODEL.md](docs/THREAT_MODEL.md)
- Tests d'intrusion automatisés à chaque modification : [docs/SECURITY_TESTS.md](docs/SECURITY_TESTS.md)
- Recherche de secrets (Gitleaks) et analyse statique (Semgrep) en intégration continue
- Aucune dépendance tierce, donc aucune vulnérabilité héritée de la chaîne d'approvisionnement
