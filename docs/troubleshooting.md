# Dépannage

Commencer par :

```bash
make ps
docker compose logs app nginx postgres redis horizon scheduler
```

- PostgreSQL indisponible : vérifier santé, `DB_*`, existence de la base et migrations; readiness renvoie 503.
- Redis indisponible : vérifier santé, `REDIS_*` et extension `redis`; cache, locks, queues et readiness sont affectés.
- Erreur JWT : vérifier `JWT_ALGO=RS256`, chemins `file://storage/keys/...`, droits et paire correspondante sans afficher les clés.
- Migration échouée : confirmer la base ciblée, lire l'erreur, corriger par une nouvelle migration; ne jamais faire de reset destructif en production.
- Horizon inactif : vérifier Redis, `docker compose logs horizon` et `php artisan horizon:status`.
- Scheduler : vérifier le service `scheduler` et `php artisan schedule:list`.
- Scramble : lancer `make docs`, puis vérifier configuration et types; ne pas éditer le JSON à la main.
- Tests : reconstruire l'image `test`, vérifier PostgreSQL/Redis et les clés temporaires; ne pas basculer sur SQLite.
- Permissions : confirmer guard `api`, rôle/permission seedé, Policy, puis exécuter `php artisan permission:cache-reset`.
- Docker : vérifier ports 8080, 54320, 63790, 8025 et 1025, volumes et santé; utiliser `make restart` sans supprimer les volumes.

Ne jamais copier `.env`, token ou clé dans un ticket ou une trace.
