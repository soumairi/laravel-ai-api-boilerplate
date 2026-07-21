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

## Utiliser un template de prompt

Le guide [Templates de prompts pour les agents IA](ai-prompt-templates.md) fournit des demandes prêtes à copier pour fonctionnalités, endpoints, bugs, refactoring, migrations, tests, sécurité et finalisation.

1. Choisir le template correspondant au résultat attendu, pas à l'outil utilisé : les mêmes prompts fonctionnent avec Cursor, Claude Code et Codex.
2. Remplacer tous les placeholders entre crochets par un contexte précis : objectif, utilisateurs, permission, contrat, règles métier, effets secondaires, risques et critères observables.
3. Lancer la tâche en demandant d'abord la lecture des instructions et l'utilisation des Skills cités. L'agent crée alors la trace et la branche dédiée avant la première modification.
4. Pour une tâche structurante, relire le plan proposé, vérifier son périmètre, ses migrations, permissions, tests et impacts OpenAPI, puis l'approuver ou demander sa correction.
5. Contrôler la trace avec `make ai-trace-check` et vérifier qu'elle contient uniquement des informations assainies, les fichiers touchés, les commandes réellement exécutées et leurs vrais résultats.
6. Pour finaliser, utiliser le Template 8, relire le diff et exiger les contrôles pertinents. Commit, push et préparation de Pull Request restent soumis aux approbations et scripts Git sûrs; aucune fusion n'est automatique.

Un placeholder non renseigné doit conduire l'agent à demander une clarification lorsqu'il modifie matériellement le contrat ou la sécurité. Il ne doit jamais être interprété comme une autorisation implicite.

## Interdits

Pas de `.env`, secret, token, clé ou donnée personnelle dans le contexte/traces. Pas de commande destructive non contrôlée, de branche protégée, de force push, de contournement de test/Policy ni de succès inventé. L'agent ne commit, push ou prépare une PR qu'après approbation explicite.
