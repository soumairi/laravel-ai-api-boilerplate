# Conventions Git

- Une tâche IA utilise `ai/<trace-id>/<type>/<slug>`.
- Branches protégées : `main`, `master`, `develop`, `production`, `release/*`.
- Pas de force push, reset destructif, nettoyage agressif ou merge automatique.
- Commits Conventional Commits, petits et cohérents, avec l'identifiant de trace.
- Relire statut, diff et staged diff; exclure secrets, caches, dépendances et clés.
- Enregistrer les approbations `commit`, `push`, `pr`.
- Utiliser uniquement `scripts/ai/safe-commit.sh`, `safe-push.sh` et `prepare-pr.sh`.
- Une validation échouée bloque la livraison; ne pas contourner hooks ou contrôles.
- Le remote autorisé est `origin`; hôtes autorisés : GitHub et GitLab.
- La Pull Request décrit objectif, contrat, migration, tests, validations, risques et trace.

Voir [workflow IA](../ai-agent-workflow.md).
