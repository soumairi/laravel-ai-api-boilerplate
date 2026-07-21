# Workflow des agents IA

## Sources de vérité

Lire dans cet ordre : plan approuvé, `AGENTS.md`, guide local, trace active, puis un profil de `ai/context-map.yml`. `.agents/skills/` est canonique; `.claude/skills/`, `.cursor/skills/` et `.codex/skills/` sont générés.

Skills obligatoires : `bootstrap-project`, `create-module`, `create-api-endpoint`, `create-migration`, `implement-jwt-auth`, `write-tests`, `sync-openapi`, `security-review`, `review-migration`, `quality-gate`, `trace-ai-task`, `finalize-git-task` et `prepare-pull-request`. Les Skills transversaux `governance-workflow`, `laravel-api`, `testing-quality` et `safe-git` complètent ces procédures.

```bash
scripts/ai/context.sh application
scripts/ai/skill-sync.sh
scripts/ai/validate.sh
```

## Cycle

1. Identifier la trace et la branche `ai/<trace-id>/<type>/<slug>`.
2. Reformuler objectif, critères, risques, migrations, permissions et tests.
3. Charger uniquement le contexte utile.
4. Réaliser le changement minimal, sans écraser le travail concurrent.
5. Exécuter contrôles ciblés puis validation.
6. Append commandes et résultats vrais avec `scripts/ai/trace.sh append`.
7. Enregistrer l'approbation requise.
8. Utiliser uniquement `safe-commit.sh`, `safe-push.sh` et `prepare-pr.sh`.

Le format canonique d'un nouvel ID est `AI-YYYYMMDD-HHMMSS-XXXXXXXX`. Les traces sont indexées dans `ai/traces/index.jsonl`, stockées sous `ai/traces/YYYY/MM/`, et la trace historique actuelle est redirigée par `ai/trace-map.yml`. La branche canonique recommandée est `ai/AI-20260721-111333-E453EF8E/feat/bootstrap-laravel-api`.

## Interdits

Pas de `.env`, secret, token, clé ou donnée personnelle dans le contexte/traces. Pas de commande destructive non contrôlée, de branche protégée, de force push, de contournement de test/Policy ni de succès inventé. L'agent ne commit, push ou prépare une PR qu'après approbation explicite.
