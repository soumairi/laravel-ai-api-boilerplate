# Base de données et migrations

PostgreSQL est le moteur de référence. Compose utilise PostgreSQL 17 et crée aussi `laravel_testing`. Les identifiants publics sont des ULID; contraintes, clés étrangères, unicité et index doivent être explicites.

```bash
make migrate
make seed
APP_ENV=testing make db-fresh
```

`make db-fresh` refuse l'exécution si `APP_ENV` n'est pas exactement `testing`. Ne jamais exécuter `migrate:fresh`, `db:wipe` ou une suppression de données contre une base partagée ou de production.

Une migration publiée n'est pas réécrite : créer une nouvelle migration, définir `up()` et un `down()` sûr, évaluer locks, volume, backfill, nullabilité et rollback. Tester sur PostgreSQL, jamais uniquement sur SQLite.

`DatabaseSeeder` est idempotent. Il crée les permissions de `config/rbac.php`, les rôles `super-admin` et `admin`, puis un super-admin seulement si `ADMIN_EMAIL` est défini; `ADMIN_PASSWORD` devient alors obligatoire. Ne jamais utiliser ou logger un mot de passe par défaut.
