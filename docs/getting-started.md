# Démarrage

## Docker

```bash
cp .env.example .env
make up
make init
make ps
```

L'API écoute sur `http://localhost:8080`, Scalar sur `/docs/api` et Mailpit sur `http://localhost:8025`. `make init` crée `APP_KEY` et les clés RS256 absentes, puis lance migrations et seeders. Renseigner `ADMIN_EMAIL` et `ADMIN_PASSWORD` avant le seeding crée ou met à jour le super-administrateur; aucun mot de passe par défaut n'existe.

## Sans Docker

Installer PHP 8.4 avec les extensions du `Dockerfile`, Composer 2, PostgreSQL et Redis. Adapter les hôtes de `.env`, puis :

```bash
composer install
cp .env.example .env
php artisan boilerplate:init
php artisan serve --host=127.0.0.1 --port=8080
php artisan horizon
php artisan schedule:work --verbose --no-interaction
```

Les trois dernières commandes représentent des processus distincts. En production, `boilerplate:init` exige `--force`, qui remplace également les clés JWT : ne l'utiliser qu'avec une procédure de rotation approuvée.

## Vérifier

```bash
make test
make docs
make docs-check
make readme-check
```

Lire ensuite [l'architecture](architecture.md), [le workflow](development-workflow.md) et [la sécurité](security.md).
