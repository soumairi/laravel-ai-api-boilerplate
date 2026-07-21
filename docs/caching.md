# Cache et Redis

Redis sert de cache, backend de queues, sessions locales, rate limiting, blacklist JWT et locks de rotation. Compose utilise `redis:7.4-alpine`, AOF activé et politique `noeviction`; le port hôte par défaut est `63790`.

Les clés doivent être préfixées, bornées par une TTL justifiée et invalidées explicitement lors d'une écriture. Ne jamais mettre en cache mot de passe, token brut, header Authorization ou donnée personnelle non nécessaire.

La rotation refresh utilise `refresh-family:<ulid>` comme lock distribué. Modifier le store de cache sans conserver des locks atomiques compatibles affaiblirait la protection contre les courses.

Après une évolution RBAC, le cache Spatie peut être vidé avec :

```bash
docker compose exec app php artisan permission:cache-reset
```

Diagnostiquer Redis avec `make ps` et les logs, pas en affichant l'environnement ou le contenu sensible des clés.
