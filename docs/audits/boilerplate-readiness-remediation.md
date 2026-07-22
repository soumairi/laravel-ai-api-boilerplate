# Remédiation de l'audit de préparation

Cette livraison traite les constats confirmés par l'audit de préparation du boilerplate. Le rapport source a été produit comme Canvas; aucun chemin Markdown versionné n'était fourni dans la demande.

## Risques prioritaires corrigés

1. Escalade RBAC et comptes privilégiés
   - l'admin n'obtient plus les permissions de gestion des rôles/permissions;
   - les rôles `admin` et `super-admin` ne peuvent être assignés que par un super-admin;
   - un admin ne peut modifier credentials, statut ou rôles d'un compte protégé;
   - les rôles système sont immuables via l'API;
   - les changements sensibles verrouillent les utilisateurs concernés et sérialisent l'invariant du dernier super-admin actif.

2. Cycle de vie compte et tokens
   - toutes les surfaces JWT vérifient signature, blacklist, `sub`, statut, `token_version`, issuer et audience;
   - Scalar et Horizon utilisent le même validateur que l'API;
   - changement/reset administratif de credentials, suspension, suppression et logout-all révoquent les sessions atomiquement;
   - une vérification d'e-mail n'active qu'un compte `pending`;
   - les sessions expirées/révoquées sont indexées et prunées avec une rétention configurable.

3. Images et exploitation
   - les clés et formats de secrets sont exclus du contexte Docker;
   - la CI inspecte l'image de production pour empêcher l'inclusion de `.env` et des clés JWT;
   - `compose.production.yaml` fournit des runtimes immuables, non-root et en lecture seule;
   - PostgreSQL, Redis et Mailpit de développement sont liés à `127.0.0.1`;
   - Nginx ne journalise plus les query strings;
   - le healthcheck Horizon vérifie les processus supervisor et worker.

## Cohérence et stabilité

- Form Requests dédiés et champs inconnus rejetés pour auth et administration.
- Pagination validée entre 1 et 100.
- OpenAPI synchronisé, Problem Details documenté pour toutes les erreurs et 204 sans body.
- La CI compare l'export Scramble au fichier versionné.
- Migrations entièrement réversibles et rollback testé sur PostgreSQL.
- Notifications auth avec retries, backoff, timeout et dispatch après commit.
- Dépendances inutilisées `spatie/laravel-health` et Telescope retirées; Tinker est dev-only.
- Actions GitHub épinglées par SHA; Gitleaks migré vers Node 24.
- Approbations Git liées au contenu/HEAD et invalidées par toute modification.

## Preuves de non-régression

Les scénarios sont couverts par :

- `tests/Feature/AdminPrivilegeBoundaryTest.php`;
- `tests/Feature/AuthApiTest.php`;
- `tests/Integration/OpenApiTest.php`;
- `tests/Integration/RefreshSessionPruningTest.php`;
- `tests/Integration/GitApprovalTest.php`.

Les résultats exacts des commandes ciblées, migrations, quality gate et couverture sont consignés dans la trace active.
