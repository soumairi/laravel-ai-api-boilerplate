# Déploiement

## Préparation

- construire le stage `production` avec `make build-production`;
- injecter secrets et configuration au runtime, jamais dans l'image;
- utiliser `APP_ENV=production`, `APP_DEBUG=false`, HTTPS et proxies explicites;
- fournir une paire RS256 protégée et planifier sa rotation;
- limiter CORS et les ports exposés;
- exécuter tests, couverture, analyse, audit, OpenAPI et validations avant promotion.

## Données et processus

Sauvegarder PostgreSQL et tester la restauration avant migration. Appliquer `php artisan migrate --force` dans une étape contrôlée, sans `migrate:fresh`. Redis doit être persistant/supervisé. Superviser PHP-FPM, Nginx, Horizon et scheduler; préserver un arrêt gracieux des workers.

Après injection de l'environnement, préparer les caches Laravel compatibles avec la plateforme. L'image active OPcache et s'exécute avec l'utilisateur `www-data` non-root.

## Exposition

Publier seulement Nginx derrière le reverse proxy. Garder PostgreSQL, Redis, Horizon et Scalar sur réseau privé ou derrière contrôles forts. L'accès production à `/docs/api` et `/docs/api.json` exige `api.documentation.view`; Horizon exige `horizon.view`. Une restriction réseau reste recommandée. Les health checks doivent exposer le minimum.

Ne pas utiliser `boilerplate:init --force` comme migration ordinaire : l'option remplace les clés JWT et peut invalider les tokens.
