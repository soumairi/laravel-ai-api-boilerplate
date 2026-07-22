# Laravel AI-Ready API Boilerplate

Laravel AI-Ready API Boilerplate est une base API-only professionnelle pour développer des API Laravel sécurisées, documentées et testées. Elle associe JWT RS256, PostgreSQL, Redis, Spatie Permission, Scramble/OpenAPI, Pest et Docker à une gouvernance utilisable par des développeurs et par Claude Code, Cursor ou Codex.

Le dépôt fournit des Rules, des Skills canoniques, une trace assainie et des scripts Git défensifs. Il ne contient ni interface web métier, ni dépendance JavaScript, ni secret.

## Fonctionnalités principales

- API JSON versionnée sous `/api/v1`, validation stricte et Resources explicites.
- Access tokens JWT RS256 courts et refresh tokens opaques avec rotation.
- Révocation JWT, détection du rejeu, familles de sessions et invalidation globale.
- Utilisateurs ULID, rôles, permissions, Policies et protections administratives.
- Erreurs `application/problem+json` proches de RFC 9457 avec `request_id`.
- PostgreSQL 17, Redis 7.4, Horizon, scheduler, Mailpit, audit et health checks.
- Filtres, tris, includes, champs et pagination explicitement autorisés.
- OpenAPI 3.1 généré par Scramble et interface Scalar.
- Pest, tests Unit, Feature, Integration, Architecture et Security.
- Couverture minimale de 85 %, Pint, Larastan/PHPStan et GitHub Actions.
- Docker multi-stage, processus non-root et PHP-FPM/OPcache.
- Rules, Agent Skills, contexte ciblé, traces assainies et scripts Git sûrs.

## Stack technique

Les versions sont celles verrouillées dans `composer.lock` ou déclarées dans `compose.yaml`.

| Composant | Version / technologie |
|---|---|
| Langage | PHP 8.4 |
| Framework | Laravel 13.20.0 |
| Base | PostgreSQL 17 (`postgres:17-bookworm`) |
| Cache, queues, locks | Redis 7.4 (`redis:7.4-alpine`) |
| Authentification | jwt-auth 2.9.2, RS256 |
| Autorisation | Spatie Permission 8.3.0 |
| Requêtes | Spatie Query Builder 7.3.0 |
| Audit / health | Activitylog 5.0.0 / endpoints live-ready internes |
| Queues | Horizon 5.48.1 |
| OpenAPI | Scramble 0.13.35 / Scalar |
| Tests | Pest 4.7.5, Pest Laravel 4.1.0 |
| Analyse | Larastan 3.10.0, PHPStan 2.2.5 |
| Style | Pint 1.29.3 |
| Outils locaux | Tinker 3.0.2, Boost 2.4.13 |
| Courrier local | Mailpit 1.27 |

## Architecture

Le sens des dépendances est `Http → Application → Domain`; `Infrastructure` adapte les bibliothèques et services externes.

```text
app/
├── Application/      # cas d'usage, DTO, transactions
├── Domain/           # règles, enums, exceptions
├── Http/             # contrôleurs, middleware, Requests, Resources
├── Infrastructure/   # JWT, HTTP, logs, OpenAPI
├── Models/           # persistance Eloquent
├── Policies/         # autorisation objet
└── Support/          # utilitaires transverses
```

Les contrôleurs restent fins. Les Form Requests valident le contrat HTTP, les Actions portent les cas d'usage, les DTO transportent les données, les Policies protègent les objets et les Resources fixent les sorties. JWT, Redis et les services externes restent encapsulés. Voir [Architecture](docs/architecture.md) et [Structure](docs/project-structure.md).

## Prérequis

La voie recommandée requiert Git, Docker Engine/Desktop et le plugin Docker Compose. L'installation native requiert PHP 8.4, Composer 2, PostgreSQL, Redis, OpenSSL et les extensions du Dockerfile, notamment `pdo_pgsql`, `redis`, `mbstring`, `intl`, `bcmath`, `pcntl`, `posix`, `sockets`, `zip` et `opcache`. PCOV est nécessaire pour mesurer la couverture comme dans l'image de test.

## Installation rapide avec Docker

```bash
cd <project-directory>
cp .env.example .env
make up
make init
```

`make up` construit et démarre Nginx, PHP-FPM, PostgreSQL, Redis, Horizon, le scheduler et Mailpit. `make init` conserve les clés existantes, génère `APP_KEY` et une paire RSA si elles manquent, puis exécute migrations et seeders. `make composer-install` réinstalle les dépendances dans le conteneur. Pour la production, `compose.production.yaml` utilise des images immuables, non-root et en lecture seule, sans bind mount du dépôt.

```bash
make ps
make test
make docs
make docs-check
```

| Service | URL par défaut |
|---|---|
| API | `http://localhost:8080/api/v1` |
| Scalar | `http://localhost:8080/docs/api` |
| OpenAPI dynamique | `http://localhost:8080/docs/api.json` |
| Mailpit | `http://localhost:8025` |
| Horizon | `http://localhost:8080/horizon` |
| Liveness | `http://localhost:8080/health/live` |
| Readiness | `http://localhost:8080/health/ready` |

PostgreSQL et Redis sont publiés sur `localhost:54320` et `localhost:63790`. En production, la documentation et Horizon exigent un utilisateur autorisé; ne les exposez pas directement à Internet.

## Installation sans Docker

Configurez PostgreSQL et Redis puis adaptez `.env` (`DB_HOST`, `REDIS_HOST`, identifiants et URL).

```bash
composer install
cp .env.example .env
php artisan boilerplate:init
php artisan serve --host=127.0.0.1 --port=8080
```

Dans deux processus supervisés séparés :

```bash
php artisan horizon
php artisan schedule:work --verbose --no-interaction
```

`boilerplate:init` refuse la production sans `--force`; cette option remplace aussi les clés JWT. `--skip-migrate` et `--skip-seed` dissocient les opérations. Voir [Démarrage](docs/getting-started.md).

## Configuration

`.env.example` est l'unique exemple versionné.

- application : `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_PORT`;
- PostgreSQL : `DB_*`, `POSTGRES_PORT`, `TEST_DB_DATABASE`;
- Redis : `REDIS_*`, `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER`;
- JWT : `JWT_ALGO`, chemins des clés, `JWT_TTL`, `REFRESH_TOKEN_TTL`, `REFRESH_SESSION_RETENTION_DAYS`;
- authentification : `REGISTRATION_ENABLED`, `REQUIRE_EMAIL_VERIFICATION`;
- sécurité : `CORS_ALLOWED_ORIGINS`, `TRUSTED_PROXIES`, `API_MAX_REQUEST_BYTES` et les variables `API_RATE_LIMIT_*` de `.env.example`;
- administrateur initial : `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`;
- Horizon et mail : `HORIZON_*`, `MAIL_*`, `MAILPIT_*`.

Il n'existe pas de variables `.env` pour piloter la trace ou Git : ces règles sont dans `ai/policy.yml` et `scripts/ai/`. Voir [Authentification](docs/authentication.md).

## Authentification JWT

1. `POST /api/v1/auth/login` vérifie le compte.
2. La réponse contient un access token RS256 (`JWT_TTL=15`, soit 900 secondes) et un refresh token opaque.
3. L'access token s'envoie avec `Authorization: Bearer <access-token>`.
4. `POST /api/v1/auth/refresh` consomme le refresh et renvoie une nouvelle paire.
5. `POST /api/v1/auth/logout` invalide l'access courant et révoque le refresh fourni.
6. `POST /api/v1/auth/logout-all` révoque toutes les sessions et incrémente `token_version`.

```bash
curl --request POST \
  --url http://localhost:8080/api/v1/auth/login \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{"email":"user@example.test","password":"your-password"}'

curl --request GET \
  --url http://localhost:8080/api/v1/auth/me \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer <access-token>'
```

Le refresh token est une valeur aléatoire de 64 octets encodée en base64url. Seul son HMAC-SHA-256 est stocké. Sa durée par défaut est `43200` minutes, soit 30 jours. Une rotation révoque l'ancien mais conserve l'expiration initiale de la famille : elle ne prolonge pas la session. Un lock Redis de famille et une transaction PostgreSQL sérialisent l'opération. Réutiliser un token marqué `rotated` révoque toute la famille, incrémente `token_version` et invalide les access tokens existants. Une contention supérieure à trois secondes produit 409; un refresh inconnu, expiré ou révoqué autrement produit 401.

Ne versionnez, ne loggez et ne placez jamais de token ou de clé dans une trace IA.

## Rôles et permissions

`PermissionSeeder`, `RoleSeeder` et `AdminUserSeeder` sont idempotents et orchestrés par `DatabaseSeeder`. Ils créent `super-admin`, `admin` et `user`; l'administrateur initial est ignoré sans `ADMIN_EMAIL` et explicitement refusé si son email est configuré sans `ADMIN_PASSWORD`.

Les permissions granulaires correspondent aux endpoints réellement exposés pour utilisateurs, rôles, permissions, documentation API et Horizon. `api.documentation.view` et `horizon.view` restent distinctes; l'admin ne possède aucune permission de mutation RBAC.

Les contrôles combinent Spatie Permission, Policies et `Gate::before`. Une permission ajoutée doit être déclarée dans `config/rbac.php`, appliquée, testée, documentée et tracée. Les routes d'administration sont `/api/v1/users`, `/api/v1/roles` et `/api/v1/permissions`. Voir [Autorisation](docs/authorization.md).

## Documentation de l'API

Scramble est l'unique source OpenAPI. Scalar est à `/docs/api`, le JSON dynamique à `/docs/api.json` et l'export versionné reste [docs/openapi/openapi.json](docs/openapi/openapi.json).

```bash
make docs
make docs-check
```

En production, les deux routes documentaires exigent `api.documentation.view`; Horizon exige séparément `horizon.view`. Toute évolution d'endpoint doit garder routes, Form Requests, Resources, types, tests et export synchronisés. Voir [Scramble et OpenAPI](docs/openapi-and-scramble.md).

## Tests

```bash
make test
make test-unit
make test-feature
make test-integration
make test-security
make test-architecture
make test-parallel
make test-coverage
```

Les catégories présentes sont Unit, Feature, Integration, Architecture et Security. Les intégrations utilisent PostgreSQL et Redis; SQLite n'est pas la référence. `make test-coverage` active PCOV et impose 85 %. Voir [Tests](docs/testing.md).

## Qualité du code

```bash
make lint
make analyse
make security-check
make docs-check
make readme-check
make quality
make validate
```

`make quality` enchaîne validation, Pint, PHPStan, Pest, audit/scan, OpenAPI et contrôles documentaires. `make validate` vérifie Compose, Composer et les contrôles documentaires/gouvernance. La couverture à 85 % reste une cible distincte imposée par la CI.

## Créer un nouveau module métier

Le flux attendu est : besoin et critères, permissions, migration PostgreSQL, modèle/factory, Domain, DTO/Action, Form Request, Policy, Resource, contrôleur fin, routes, audit/rate limit, tests, Scramble, documentation, validation et trace. Voir [Créer un module](docs/create-module.md).

```text
Lis AGENTS.md et utilise les skills laravel-api, testing-quality et
governance-workflow pour créer un module Products. Ajoute permissions,
Policies, tests Pest, contrat Scramble et trace assainie, puis valide.
```

## Créer un nouvel endpoint

La checklist complète se trouve dans [Créer un endpoint](docs/create-api-endpoint.md). Un prompt doit préciser méthode, chemin, permission, règles métier, audit, tests et impact OpenAPI.

```text
Ajoute PATCH /api/v1/users/{user}/status.
Préserve la Policy, la validation enum, la révocation des sessions,
l'audit, les tests de sécurité et la synchronisation OpenAPI.
```

Comparez toujours la demande à `routes/api.php` et OpenAPI.

## Utilisation avec les agents IA

Tous les agents commencent par `AGENTS.md`, le guide local le plus proche, le plan approuvé et la trace active. `ai/context-map.yml` définit les profils ciblés :

```bash
scripts/ai/context.sh application
scripts/ai/context.sh tests
scripts/ai/context.sh governance
```

- Claude Code lit `CLAUDE.md`; ses Skills synchronisés sont sous `.claude/skills/`.
- Cursor applique `.cursor/rules/`; ses Skills synchronisés sont sous `.cursor/skills/`.
- Codex utilise `.codex/README.md` et `.codex/skills/`.

Les copies synchronisées ne sont jamais modifiées directement. Voir [Workflow IA](docs/ai-agent-workflow.md).

## Templates de prompts pour les agents IA

Des templates français prêts à copier sont disponibles pour cadrer une fonctionnalité complète, un endpoint, une correction de bug, un refactoring, une migration PostgreSQL, des tests, une revue de sécurité ou la finalisation Git. Ils imposent le contexte métier, les Skills adaptés, la trace, les tests Pest, Scramble/OpenAPI, la quality gate et le travail sur une branche dédiée.

[Consulter les templates de prompts IA](docs/ai-prompt-templates.md)

## Agent Skills

`.agents/skills/` est la source canonique.

| Skill réel | Utilisation |
|---|---|
| `bootstrap-project` | initialiser le projet avec Docker |
| `create-module` / `create-api-endpoint` | créer une capacité ou un endpoint |
| `create-migration` / `review-migration` | créer ou revoir une migration PostgreSQL |
| `implement-jwt-auth` | modifier JWT et refresh tokens |
| `write-tests` | ajouter des tests Pest significatifs |
| `sync-openapi` | exporter et vérifier Scramble |
| `security-review` | revoir les frontières de sécurité |
| `quality-gate` | exécuter les validations de livraison |
| `trace-ai-task` | créer et maintenir une trace |
| `finalize-git-task` / `prepare-pull-request` | préparer la livraison Git sûre |
| `governance-workflow` / `laravel-api` / `testing-quality` / `safe-git` | procédures transversales |

```bash
make ai-sync-skills
```

## Rules des agents IA

`AGENTS.md`, `CLAUDE.md`, les guides locaux et les dix Rules ciblées `00-project-core.mdc` à `90-git-workflow.mdc` définissent architecture, API, JWT, sécurité, base, tests, OpenAPI, traçabilité et livraison.

## Traçabilité des tâches IA

Les traces publiques sont indexées dans `ai/traces/index.jsonl` et rangées sous `ai/traces/YYYY/MM/`. Le format canonique est `AI-YYYYMMDD-HHMMSS-XXXXXXXX`; un petit redirect conserve la compatibilité avec l'identifiant historique de bootstrap sans dupliquer son historique.

```bash
scripts/ai/trace.sh append "<résultat assaini>"
scripts/ai/trace-check.sh
scripts/ai/validate.sh
```

Une trace contient les commandes et résultats réels, jamais `.env`, credential, token, donnée personnelle ou clé. Les écritures Git exigent une approbation enregistrée par `scripts/ai/approval.sh`.

## Workflow Git

```text
Demande → trace → plan approuvé → branche ai/<trace-id>/<type>/<slug>
→ changement minimal → tests → OpenAPI → validation → approbation
→ safe-commit → safe-push → préparation de PR → revue humaine
```

Utilisez `scripts/ai/safe-commit.sh`, `scripts/ai/safe-push.sh`, `scripts/ai/prepare-pr.sh` et `scripts/ai/ai-finish.sh`. Ils refusent branches protégées, validations manquantes, secrets et opérations non approuvées. Aucun force push ni merge automatique.

## Commandes principales

| Commande | Rôle |
|---|---|
| `make help` | afficher les cibles |
| `make install` / `make fresh` | installer / réinitialiser en environnement de test |
| `make build` | construire app et Nginx |
| `make up` / `make down` | démarrer / arrêter |
| `make init` | initialiser clés, migrations, seeders |
| `make migrate` / `make seed` | appliquer schéma / références |
| `make test-*` / `make test-parallel` | exécuter les suites Pest |
| `make test-coverage` | imposer 85 % |
| `make lint` / `make analyse` | Pint / PHPStan |
| `make docs` | exporter OpenAPI |
| `make docs-check` / `make readme-check` | valider les documents |
| `make security-check` | audit et scan sensible |
| `make rules-check` / `make skills-check` | vérifier Rules / Skills |
| `make quality` / `make validate` | qualité / cohérence globale |
| `make ai-context` / `make ai-sync-skills` | contexte ciblé / synchronisation |
| `make ai-task-start PURPOSE="..."` / `make ai-task-*` / `make ai-finish` | cycle de trace et validation |
| `make git-safe-commit` / `make git-safe-push` / `make pr-create` | livraison approuvée |

`make db-fresh` refuse toute exécution sans `APP_ENV=testing` dans le processus Make.

## Mise en production

- `APP_ENV=production`, `APP_DEBUG=false`, secrets hors Git.
- HTTPS, proxies de confiance et CORS explicitement limités.
- Clés RS256 protégées, permissions minimales, rotation testée.
- PostgreSQL sauvegardé; Redis persistant et supervisé.
- Image `production`, OPcache actif, processus non-root.
- Migrations contrôlées; Horizon et scheduler supervisés.
- Caches Laravel préparés après injection de la configuration.
- Logs JSON expurgés et alertes sur readiness/queues.
- Documentation et Horizon derrière authentification ou réseau privé.
- `make validate`, audit, tests, couverture et scan réussis.

Voir [Déploiement](docs/deployment.md) et [Sécurité](docs/security.md).

## Dépannage

Commencez par `make ps` puis les logs du service. PostgreSQL/Redis affectent readiness; des chemins JWT incorrects empêchent la signature; Horizon dépend de Redis; les changements RBAC peuvent nécessiter de vider le cache de permissions; Scramble requiert une application configurée. Voir [Dépannage](docs/troubleshooting.md).

## Sécurité

Ne versionnez jamais `.env`, clé privée, JWT, refresh token ou cookie. Ne loggez pas `Authorization`. N'exposez ni Scalar ni Horizon publiquement et n'utilisez pas de compte administrateur partagé. Signalez une vulnérabilité aux mainteneurs par un canal privé convenu par l'organisation; aucun canal public n'est défini ici.

## Documentation

- [Index](docs/README.md)
- [Démarrage](docs/getting-started.md)
- [Architecture](docs/architecture.md) et [structure](docs/project-structure.md)
- [Authentification](docs/authentication.md) et [autorisation](docs/authorization.md)
- [Base et migrations](docs/database-and-migrations.md)
- [Tests](docs/testing.md) et [Scramble](docs/openapi-and-scramble.md)
- [Queues](docs/queues-and-jobs.md), [cache](docs/caching.md), [observabilité](docs/observability.md)
- [Sécurité](docs/security.md) et [workflow IA](docs/ai-agent-workflow.md)
- [Templates de prompts pour les agents IA](docs/ai-prompt-templates.md)
- [Créer un projet](docs/create-project-from-boilerplate.md), [module](docs/create-module.md), [endpoint](docs/create-api-endpoint.md)
- [Déploiement](docs/deployment.md), [dépannage](docs/troubleshooting.md), [Definition of Done](docs/definition-of-done.md)
- [Remédiation de l'audit de préparation](docs/audits/boilerplate-readiness-remediation.md)
- [Conventions](docs/conventions/php.md) et [décisions](docs/decisions/README.md)

## Contribution

Toute contribution doit respecter les guides applicables, préserver l'architecture et la sécurité, ajouter les tests nécessaires, maintenir Scramble et la documentation, compléter la trace, puis réussir les validations. Les écritures Git passent exclusivement par les scripts sûrs après approbation. Aucune licence propre au projet n'est déclarée dans ce dépôt.
