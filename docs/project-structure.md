# Structure du projet

```text
app/                    code Application, Domain, Http et Infrastructure
bootstrap/app.php       routes, middleware et rendu centralisé des erreurs
config/                 configuration issue de l'environnement
database/               migrations, factory et seeder idempotent
routes/api.php           API métier v1
routes/health.php        liveness et readiness
tests/                   Unit, Feature, Integration, Architecture, Security
docs/openapi/            export OpenAPI versionné
docker/                  Nginx, PHP et initialisation PostgreSQL
scripts/ai/              gouvernance, trace et livraison Git sûre
.agents/skills/          source canonique des Skills
.cursor/rules/           règles ciblées Cursor
ai/                      politique, carte de contexte et trace
```

`compose.yaml` définit `app`, `nginx`, `postgres`, `redis`, `horizon`, `scheduler`, `mailpit` et le profil `test`. Le `Dockerfile` fournit les stages `development`, `test`, `vendor-production` et `production`.

Avant un changement, lire `AGENTS.md`, puis le guide local : `app/AGENTS.md`, `database/AGENTS.md` ou `tests/AGENTS.md`. Ne modifier ni `vendor/`, ni les caches, ni les copies de Skills synchronisées.
