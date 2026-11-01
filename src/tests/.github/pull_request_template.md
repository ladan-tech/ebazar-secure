## Description

<!-- Qu'apporte cette modification ? -->

## Checklist sécurité

- [ ] Toute nouvelle entrée utilisateur est validée côté serveur
- [ ] Toute sortie est échappée avec `e()`
- [ ] Toute requête SQL est préparée
- [ ] Toute action modifiant l'état est en POST et vérifie le jeton CSRF
- [ ] Le rôle **et** le propriétaire sont contrôlés
- [ ] Aucun script ni style en ligne (CSP)
- [ ] La suite `tests/security/run.sh` passe ; un test a été ajouté si nécessaire
- [ ] Le modèle de menaces est mis à jour si la surface d'attaque change
