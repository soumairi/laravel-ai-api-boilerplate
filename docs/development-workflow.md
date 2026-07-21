# Workflow de développement

1. Lire le plan approuvé, `AGENTS.md`, la trace active et le guide local.
2. Charger un seul profil avec `scripts/ai/context.sh application|database|tests|governance`.
3. Reformuler les critères, permissions, contrat API, migration, risques et tests.
4. Réaliser le plus petit changement cohérent sans écraser un travail concurrent.
5. Exécuter les tests ciblés, Pint, PHPStan et les validations concernées.
6. Régénérer OpenAPI pour tout changement de contrat.
7. Mettre à jour la documentation et append la commande/résultat réel à la trace.
8. Exécuter `scripts/ai/validate.sh`.
9. Après approbations explicites seulement, utiliser les scripts Git sûrs.

Commandes usuelles :

```bash
make lint
make analyse
make test
make docs
make docs-check
make quality
make validate
```

Ne jamais affaiblir un test, contourner une Policy, afficher `.env`, exécuter une opération destructive hors test, force-push ou déclarer une validation non exécutée. Voir [Definition of Done](definition-of-done.md) et [workflow IA](ai-agent-workflow.md).
