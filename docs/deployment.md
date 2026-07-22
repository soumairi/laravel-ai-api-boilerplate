# Déploiement

## Préparation

- construire le stage `production` avec `make build-production`;
- valider puis utiliser `compose.production.yaml`, sans bind mount du code;
- injecter secrets et configuration au runtime, jamais dans l'image;
- utiliser `APP_ENV=production`, `APP_DEBUG=false`, HTTPS et proxies explicites;
- fournir une paire RS256 protégée et planifier sa rotation;
- limiter CORS et les ports exposés;
- exécuter tests, couverture, analyse, audit, OpenAPI et validations avant promotion.

## Données et processus

Sauvegarder PostgreSQL et tester la restauration avant migration. Appliquer `php artisan migrate --force` dans une étape contrôlée, sans `migrate:fresh`. Redis doit être persistant/supervisé. Superviser PHP-FPM, Nginx, Horizon et scheduler; préserver un arrêt gracieux des workers.

Après injection de l'environnement, préparer les caches Laravel compatibles avec la plateforme. Les images PHP et Nginx embarquent le code/public généré, sont non-root et en lecture seule; seuls `storage`, `bootstrap/cache` et les répertoires temporaires sont inscriptibles. Les clés JWT sont montées sous `/run/secrets` et `storage/keys`, `.env`, `*.pem`, `*.key`, `*.p12` et `*.pfx` sont exclus du contexte Docker.

## Exposition

Publier seulement Nginx derrière le reverse proxy. Le Compose de développement lie PostgreSQL, Redis et Mailpit exclusivement à `127.0.0.1`; le modèle de production n'expose ni PostgreSQL ni Redis. L'accès production à `/docs/api` et `/docs/api.json` exige un JWT pleinement valide et `api.documentation.view`; Horizon exige le même cycle JWT et `horizon.view`. Une restriction réseau reste recommandée. Les logs Nginx omettent la query string pour éviter les tokens accidentels.

Ne pas utiliser `boilerplate:init --force` comme migration ordinaire : l'option remplace les clés JWT et peut invalider les tokens.
