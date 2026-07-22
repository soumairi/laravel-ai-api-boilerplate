# Conventions Git

- Une tâche IA utilise `ai/<trace-id>/<type>/<slug>`.
- Branches protégées : `main`, `master`, `develop`, `production`, `release/*`.
- Pas de force push, reset destructif, nettoyage agressif ou merge automatique.
- Commits Conventional Commits, petits et cohérents, avec l'identifiant de trace.
- Relire statut, diff et staged diff; exclure secrets, caches, dépendances et clés.
- Enregistrer les approbations `commit`, `push`, `pr`. Une approbation commit est liée à la branche, au parent et au diff staged hors trace; push est lié à la branche, au SHA HEAD et au remote; PR ajoute la branche de base. Toute modification de ce contexte invalide l'approbation.
- Utiliser uniquement `scripts/ai/safe-commit.sh`, `safe-push.sh` et `prepare-pr.sh`.
- Une validation échouée bloque la livraison; ne pas contourner hooks ou contrôles. `safe-commit.sh` refuse aussi tout fichier non suivi ou changement non staged afin que la quality gate porte exactement sur le contenu approuvé.
- Le remote autorisé est `origin`; hôtes autorisés : GitHub et GitLab.
- La Pull Request décrit objectif, contrat, migration, tests, validations, risques et trace.

Voir [workflow IA](../ai-agent-workflow.md).
