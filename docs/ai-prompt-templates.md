# Templates de prompts pour les agents IA

Ces templates prêts à copier permettent de piloter Cursor, Claude Code, OpenAI Codex ou tout agent compatible avec `AGENTS.md` et les Agent Skills du dépôt. Remplacez chaque placeholder entre crochets avant d'envoyer le prompt.

Avant toute tâche, demandez à l'agent de lire :

- `AGENTS.md` ;
- `CLAUDE.md` avec Claude Code ;
- `.cursor/rules/` avec Cursor ;
- `ai/context-map.yml` ;
- la documentation du module concerné.

Une validation de plan autorise l'implémentation, mais les opérations Git restent soumises aux approbations et scripts sûrs du projet. Aucun agent ne doit modifier directement `main`, `master`, `develop`, `production` ou `release/*`, force-push ou fusionner automatiquement une Pull Request.

## 1. Principes d'utilisation

Un prompt exploitable précise :

- l'objectif métier et le comportement attendu ;
- les utilisateurs concernés et leurs permissions ;
- les données d'entrée et le contrat de réponse ;
- les règles métier, erreurs et effets secondaires ;
- les scénarios de test attendus ;
- les contraintes de sécurité, performance et compatibilité.

Évitez une demande comme « crée cette fonctionnalité » sans contexte. Décrivez des critères d'acceptation observables, demandez les Skills adaptés, un diff minimal, les tests en même temps que le code et la régénération Scramble lorsqu'un contrat API évolue. Exigez une trace assainie et un rapport final fidèle.

Une tâche n'est pas valide parce qu'un agent l'affirme. Le rapport doit distinguer les commandes exécutées, leurs résultats réels, les contrôles non exécutés et les blocages. Ne considérez jamais un test, un audit, une couverture, un push ou une Pull Request comme réussi sans preuve d'exécution.

## Template 1 — Créer une fonctionnalité back-end complète

```text
Lis d'abord AGENTS.md, les Rules applicables, ai/context-map.yml et la
documentation du projet. Avec Claude Code, lis aussi CLAUDE.md.

Utilise les Skills disponibles :
- trace-ai-task
- create-module
- create-api-endpoint
- write-tests
- sync-openapi
- security-review
- quality-gate
- finalize-git-task

Tâche :
[DÉCRIRE LA FONCTIONNALITÉ]

Contexte métier :
- Objectif :
- Utilisateurs concernés :
- Données principales :
- Contraintes métier :
- Résultat attendu :
- Priorité :

Critères d'acceptation :
- [CRITÈRE 1]
- [CRITÈRE 2]
- [CRITÈRE 3]

Architecture attendue, uniquement selon les besoins réels :
- migration PostgreSQL réversible, modèle Eloquent et factory ;
- enum, Value Object et DTO immuable avec suffixe Data ;
- Action applicative et transaction courte si plusieurs écritures sont atomiques ;
- Form Request stricte, Policy, permission Spatie et API Resource ;
- contrôleur final et fin ;
- audit expurgé, événement et listener ;
- job en queue pour tout traitement long ou notification ;
- Redis pour cache, rate limiting ou locks lorsqu'ils sont justifiés.

Contrat API :
- routes sous /api/v1 avec noms api.v1.* ;
- permissions granulaires et Policies contre BOLA/IDOR ;
- pagination bornée et filtres, tris, includes et fields explicitement autorisés ;
- rate limiting adapté ;
- succès dans l'enveloppe JSON standard ;
- erreurs en Problem Details avec request_id ;
- aucun modèle Eloquent exposé directement.

Validation :
- tests Unit, Feature, Integration et Security pertinents avec Pest ;
- PostgreSQL et Redis réels lorsque leur comportement est concerné ;
- Scramble comme unique source OpenAPI, puis export et contrôle de synchronisation ;
- revue de sécurité et quality gate réelle ;
- trace IA avec fichiers, commandes et résultats assainis.

Workflow :
1. Crée la trace avant la première modification.
2. Crée une branche ai/<trace-id>/feat/[slug] depuis la branche de base.
3. Propose un plan et attends sa validation si la tâche est structurante.
4. Implémente un diff minimal, sans package supplémentaire injustifié.
5. Exécute les tests ciblés puis les contrôles globaux.
6. Après approbations explicites, crée un Conventional Commit avec le Trace ID.
7. Pousse uniquement la branche dédiée et crée ou mets à jour une Pull Request.
8. Ne pousse jamais sur une branche protégée et ne fusionne jamais automatiquement.

Rapporte fidèlement les changements, tests, couverture, OpenAPI, contrôles qualité,
commit, push, Pull Request, commandes non exécutées et risques restants.
```

### Exemple — Module Products

```text
Lis AGENTS.md, les Rules, ai/context-map.yml, docs/create-module.md,
docs/create-api-endpoint.md, docs/testing.md et docs/openapi-and-scramble.md.
Utilise trace-ai-task, create-module, create-api-endpoint, write-tests,
sync-openapi, security-review, quality-gate et finalize-git-task.

Crée un module Products permettant aux utilisateurs autorisés de créer, consulter,
lister, modifier et archiver un produit.

Données :
- id ULID ;
- name, description nullable, sku unique, price en unité monétaire mineure ;
- status enum draft|active|archived ;
- created_by et timestamps UTC.

Permissions :
- products.viewAny, products.view, products.create, products.update,
  products.archive.

Critères :
- routes nommées sous /api/v1/products ;
- Policy systématique et protection BOLA/IDOR ;
- CreateProductData et UpdateProductData immuables ;
- Actions transactionnelles, Requests strictes et ProductResource ;
- pagination maximale de 100, filtres status/name, tri name/price/created_at ;
- archivage audité et invalidation du cache de liste ;
- aucun hard delete public ;
- tests Pest Unit, Feature, Integration et Security ;
- OpenAPI Scramble synchronisé.

Crée la trace et la branche dédiée avant toute modification. Exécute la quality
gate, puis finalise commit, push et Pull Request uniquement après approbation.
Ne modifie aucune branche protégée et fournis un rapport basé sur les commandes
réellement exécutées.
```

## Template 2 — Ajouter ou modifier un endpoint API

```text
Lis AGENTS.md, les Rules applicables, ai/context-map.yml,
docs/create-api-endpoint.md, docs/testing.md et docs/openapi-and-scramble.md.
Utilise trace-ai-task, create-api-endpoint, write-tests, sync-openapi,
security-review, quality-gate et finalize-git-task.

Endpoint :
[MÉTHODE HTTP] /api/v1/[URI]

Nom de route :
[api.v1.module.action]

Comportement attendu :
[DÉCRIRE]

Permission :
[permission.name]

Utilisateurs autorisés :
[DÉCRIRE]

Entrées :
- champ :
- type :
- obligatoire :
- nullable :
- valeurs autorisées :
- contraintes :

Sortie attendue :
[DÉCRIRE LE CONTRAT JSON]

Règles métier :
- [RÈGLE 1]
- [RÈGLE 2]

Effets secondaires :
- audit :
- révocation JWT :
- événement :
- notification :
- job en queue :
- invalidation du cache :

Implémente obligatoirement :
1. route versionnée ;
2. nom de route ;
3. permission granulaire ;
4. Policy ;
5. Form Request stricte ;
6. DTO immuable avec suffixe Data ;
7. Action ;
8. transaction si nécessaire ;
9. API Resource ;
10. contrôleur fin ;
11. rate limiting adapté ;
12. audit expurgé si nécessaire ;
13. tests Pest de succès, validation, auth, autorisation, BOLA/IDOR et erreurs ;
14. types et documentation Scramble, puis export OpenAPI ;
15. trace IA complète ;
16. quality gate réellement exécutée ;
17. finalisation Git approuvée sur une branche dédiée.

Préserve le format JSON standard, Problem Details et request_id. Fais un diff
minimal. Ne modifie pas une branche protégée et ne fusionne pas la Pull Request.
```

### Exemple — Changer le statut d'un utilisateur

```text
Ajoute PATCH /api/v1/users/{user}/status, nommé api.v1.users.status.
Lis AGENTS.md et utilise trace-ai-task, create-api-endpoint, write-tests,
sync-openapi, security-review, quality-gate et finalize-git-task.

Exige la permission users.changeStatus et la UserPolicy. Valide status avec
UserStatus : active, inactive ou suspended. Utilise un ChangeUserStatusData,
une Action transactionnelle, une Form Request et UserResource.

Interdis l'auto-désactivation et protège le dernier super-admin. Audite l'ancien
et le nouveau statut sans donnée sensible. Lors du passage à inactive ou
suspended, révoque toutes les sessions, incrémente token_version et invalide les
access tokens existants.

Ajoute les tests Pest de validation, permission, BOLA/IDOR, protections métier,
audit et révocation JWT. Mets Scramble et l'export OpenAPI à jour.
Crée la trace et la branche dédiée, exécute la quality gate, puis commit/push/PR
seulement après approbation. Ne touche jamais directement une branche protégée.
```

## Template 3 — Corriger un bug sans régression

```text
Lis AGENTS.md, les Rules, ai/context-map.yml et la documentation du module.
Utilise trace-ai-task, write-tests, sync-openapi si le contrat est concerné,
security-review, quality-gate et finalize-git-task.

Type :
[BUGFIX / SECURITY / PERFORMANCE]

Problème constaté :
[DÉCRIRE]

Comportement actuel :
[DÉCRIRE]

Comportement attendu :
[DÉCRIRE]

Étapes de reproduction :
1.
2.
3.

Endpoint concerné :
[ENDPOINT]

Module :
[MODULE]

Message d'erreur :
[ERREUR ASSAINIE]

Impact utilisateur :
[IMPACT]

Impact sécurité :
[IMPACT]

Procédure imposée :
1. Reproduis le problème et conserve les preuves non sensibles.
2. Identifie la cause racine, pas uniquement le symptôme.
3. Crée un test de non-régression qui échoue avant la correction.
4. Applique la correction minimale sans refactoring adjacent.
5. Vérifie que le test observe un comportement public et réussit après correction.
6. Exécute les tests ciblés puis la suite globale pertinente.
7. Vérifie Scramble et réexporte OpenAPI si le contrat change.
8. Mets la trace à jour avec les commandes et résultats réels.
9. Après approbation, crée un commit fix(...) avec le Trace ID, pousse la branche
   dédiée et crée ou mets à jour la Pull Request.

Ne supprime ni n'affaiblis aucun test. Ne modifie aucune branche protégée et ne
fusionne jamais automatiquement.
```

## Template 4 — Refactorer sans modifier le comportement

```text
Lis AGENTS.md, les Rules, ai/context-map.yml et les tests du module.
Utilise trace-ai-task, write-tests, quality-gate et finalize-git-task.

Code ou module concerné :
[CHEMINS / MODULE]

Raison du refactoring :
[DÉCRIRE]

Problème technique actuel :
[DÉCRIRE]

Résultat interne attendu :
[DÉCRIRE]

Comportements externes à préserver :
- [COMPORTEMENT 1]
- [COMPORTEMENT 2]

Tests existants :
[LISTER]

Performances à préserver ou améliorer :
[MÉTRIQUES OU CONTRAINTES]

Contraintes :
- aucun changement de contrat API sans demande explicite ;
- aucun changement de comportement externe ;
- diff minimal et aucune abstraction prématurée ;
- aucun package supplémentaire sans justification et approbation ;
- architecture Http → Application → Domain préservée ;
- tests existants maintenus ;
- Scramble et l'export OpenAPI byte-identiques si le contrat ne change pas ;
- tests supplémentaires si la couverture comportementale est insuffisante.

Exécute les tests avant et après, Pint, PHPStan et la quality gate. Trace les
résultats. Après approbation, commit/push/PR sur une branche dédiée uniquement.
Ne modifie pas une branche protégée et ne fusionne pas automatiquement.
```

## Template 5 — Créer ou modifier une structure de données

```text
Lis AGENTS.md, les Rules, ai/context-map.yml et
docs/database-and-migrations.md. Utilise trace-ai-task, create-migration,
review-migration, write-tests, quality-gate et finalize-git-task.

Table concernée :
[TABLE]

Objectif :
[DÉCRIRE]

Colonnes :
- nom :
- type PostgreSQL :
- nullable :
- valeur par défaut :
- contrainte :
- index :

Relations :
- table :
- colonne :
- comportement on delete :
- comportement on update :

Données existantes :
- volume estimé :
- valeurs à migrer :
- stratégie de backfill :
- compatibilité pendant le déploiement :

Crée une nouvelle migration réversible. Ne modifie jamais une migration publiée.
Utilise les types, contraintes CHECK/UNIQUE/NOT NULL, indexes et clés étrangères
PostgreSQL adaptés. Justifie chaque cascade et chaque index.

Vérifie le rollback, les données existantes, les locks et la durée potentielle.
Ajoute des tests d'intégration PostgreSQL pour contraintes, relations, indexes
critiques et rollback pertinent. Fais relire la migration via review-migration.

Crée la trace et la branche dédiée, exécute les validations et finalise Git après
approbation uniquement. N'exécute aucune commande destructive hors testing.
```

## Template 6 — Ajouter ou renforcer les tests

```text
Lis AGENTS.md, les Rules, ai/context-map.yml, tests/AGENTS.md et docs/testing.md.
Utilise obligatoirement le Skill write-tests, puis quality-gate et
finalize-git-task.

Fonctionnalité concernée :
[FONCTIONNALITÉ]

Endpoint concerné :
[ENDPOINT OU AUCUN]

Scénarios déjà couverts :
- [SCÉNARIO]

Scénarios manquants :
- [SCÉNARIO]

Risques :
- [RÉGRESSION / SÉCURITÉ / CONCURRENCE / PERFORMANCE]

Tests demandés :
- Unit :
- Feature :
- Integration PostgreSQL/Redis/JWT :
- Security :
- Architecture :

Les tests doivent être déterministes, indépendants et parallélisables. Teste les
comportements observables, permissions, BOLA/IDOR, erreurs, effets secondaires et
contrat JSON. N'utilise ni réseau externe, ni secret réel, ni délai artificiel.
Ne modifie pas le code métier sauf si un défaut démontré exige une correction.

Crée une trace et une branche dédiée. Mets Scramble à jour si les tests révèlent
un écart de contrat. Exécute les contrôles ciblés et la quality gate.

Rapport obligatoire :
- tests créés ;
- tests modifiés ;
- tests exécutés ;
- résultats réels ;
- couverture avant ;
- couverture après ;
- scénarios restant non couverts.

Après approbation seulement, commit/push/PR. Aucun push protégé ni merge automatique.
```

## Template 7 — Effectuer une revue de sécurité

```text
Lis AGENTS.md, les Rules de sécurité/JWT, ai/context-map.yml et docs/security.md.
Utilise trace-ai-task, security-review, write-tests, quality-gate et
finalize-git-task.

Périmètre :
[BRANCHE / DIFF / MODULE / ENDPOINTS]

Analyse au minimum :
- authentification JWT RS256 ;
- rotation des refresh tokens, détection de rejeu et révocation ;
- blacklist Redis et token_version ;
- BOLA/IDOR et mass assignment ;
- validation, permissions et Policies ;
- rate limiting et CORS ;
- logs, erreurs et données sensibles ;
- secrets et configuration ;
- dépendances et Composer Audit ;
- exposition de Scalar, OpenAPI et Horizon en production.

Pour chaque constat, indique :
- risque et scénario d'impact ;
- sévérité [CRITIQUE / ÉLEVÉE / MOYENNE / FAIBLE] ;
- preuve assainie et fichiers concernés ;
- correctif minimal recommandé ;
- test de non-régression à ajouter.

Implémente uniquement les corrections explicitement autorisées. Exécute les
commandes réellement disponibles et distingue résultats, contrôles non exécutés
et limites de la revue. Ne stocke aucun secret ou token dans la trace.

Utilise une branche dédiée. Après approbation, commit/push/PR sans force push ni
fusion automatique.
```

## Template 8 — Valider et finaliser une tâche IA

```text
Lis AGENTS.md, les Rules, docs/definition-of-done.md et la trace active.
Utilise quality-gate, sync-openapi, security-review, finalize-git-task et
prepare-pull-request.

1. Relis le diff complet depuis la branche de base.
2. Vérifie et exclus les fichiers inattendus.
3. Exécute les tests ciblés.
4. Exécute les tests globaux pertinents.
5. Exécute Pint.
6. Exécute PHPStan.
7. Exécute Composer Audit.
8. Exporte Scramble.
9. Vérifie OpenAPI et sa synchronisation.
10. Vérifie les secrets sans afficher leur contenu.
11. Complète la trace avec les résultats réels.
12. Après approbation, crée un Conventional Commit.
13. Inclus `Trace: <trace-id>` dans le commit.
14. Pousse uniquement la branche dédiée.
15. Crée ou mets à jour la Pull Request.
16. Ne fusionne jamais automatiquement et ne force-push jamais.

Rapport final :

Trace
- Identifiant :
- Branche :
- Statut :

Résultat
- Fonctionnalité :
- Endpoints :
- Permissions :
- Migrations :

Fichiers
- Créés :
- Modifiés :
- Supprimés :

Tests
- Ajoutés :
- Exécutés :
- Résultat :
- Couverture :

Documentation
- Scramble :
- OpenAPI :
- Documentation humaine :

Qualité
- Pint :
- PHPStan :
- Composer Audit :
- Secret scan :

Git
- Commit :
- Push :
- Pull Request :

Limites
- Commandes non exécutées :
- Risques restants :
```

## Prompts rapides

### Créer un CRUD

```text
Lis AGENTS.md et utilise trace-ai-task, create-module, create-api-endpoint,
write-tests, sync-openapi et quality-gate. Crée le CRUD [RESSOURCE] sous /api/v1
avec permissions, Policies, Requests, DTO Data, Actions, Resources, pagination,
filtres autorisés et protection BOLA. Ajoute les tests Pest, mets Scramble à jour,
exécute la quality gate et finalise Git sur une branche dédiée après approbation.
```

### Ajouter un filtre

```text
Lis AGENTS.md et utilise les Skills adaptés. Ajoute le filtre allowlisté
[FILTRE] à [ENDPOINT], sans permettre de colonne arbitraire. Ajoute tests Pest
valides/invalides, mets Scramble à jour, crée la trace, exécute la quality gate
et finalise commit/push/PR sur une branche dédiée après approbation.
```

### Ajouter une permission

```text
Lis AGENTS.md et utilise trace-ai-task, write-tests, security-review et
quality-gate. Ajoute [PERMISSION] à config/rbac.php, au seeding idempotent et à
la Policy concernée, sans rôle codé en dur. Ajoute tests Pest accordé/refusé,
vérifie Scramble, trace la tâche et finalise Git sur une branche dédiée.
```

### Ajouter un endpoint de statut

```text
Lis AGENTS.md et utilise create-api-endpoint avec les Skills de trace, tests,
OpenAPI et qualité. Ajoute [MÉTHODE] /api/v1/[RESSOURCE]/{id}/status avec enum,
permission, Policy, Request, DTO Data, Action, audit et effets JWT éventuels.
Teste validation/BOLA, mets Scramble à jour, exécute la quality gate et finalise
Git sur une branche dédiée après approbation.
```

### Créer une notification en queue

```text
Lis AGENTS.md et utilise trace-ai-task, create-api-endpoint, write-tests et
quality-gate. Ajoute la notification queueable [NOTIFICATION], déclenchée après
commit, sans donnée sensible dans le payload ou les logs. Ajoute tests Pest avec
fake et intégration utile, vérifie Scramble, trace, quality gate et Git dédié.
```

### Corriger une erreur JWT

```text
Lis AGENTS.md et la Rule JWT. Utilise implement-jwt-auth, write-tests,
security-review, sync-openapi et quality-gate. Reproduis [ERREUR], ajoute un test
de non-régression, corrige sans affaiblir rotation/rejeu/token_version, mets
Scramble à jour si nécessaire, crée la trace et finalise Git sur branche dédiée.
```

### Optimiser une requête N+1

```text
Lis AGENTS.md et utilise trace-ai-task, write-tests et quality-gate. Mesure puis
corrige le N+1 dans [ENDPOINT] avec eager loading et sélection minimale, sans
changer le contrat JSON. Ajoute un test de nombre de requêtes, vérifie Scramble,
exécute la quality gate et finalise Git sur une branche dédiée.
```

### Ajouter un index PostgreSQL

```text
Lis AGENTS.md et utilise create-migration, review-migration, write-tests et
quality-gate. Ajoute un index PostgreSQL justifié sur [TABLE.COLONNES] via une
nouvelle migration réversible, évalue données/locks, ajoute tests d'intégration,
vérifie Scramble si pertinent, crée la trace et finalise Git sur branche dédiée.
```

### Compléter des tests

```text
Lis AGENTS.md et utilise write-tests, trace-ai-task et quality-gate. Complète les
tests de [FONCTIONNALITÉ] pour [SCÉNARIOS], avec PostgreSQL/Redis réels si requis,
sans affaiblir les assertions. Vérifie Scramble, rapporte couverture avant/après,
exécute la quality gate et finalise Git sur une branche dédiée.
```

### Mettre à jour Scramble

```text
Lis AGENTS.md et utilise sync-openapi, write-tests, trace-ai-task et
quality-gate. Synchronise Scramble pour [ENDPOINT], vérifie Request, Resource,
types, sécurité bearer, paramètres et réponses, exporte OpenAPI et teste la
parité routes/document. Trace les résultats et finalise Git sur branche dédiée.
```
