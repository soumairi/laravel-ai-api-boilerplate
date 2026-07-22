# Queues et jobs

`QUEUE_CONNECTION=redis` est la valeur locale Compose. Le service `horizon` exécute `php artisan horizon` avec un arrêt gracieux de 30 secondes et un healthcheck sur les processus supervisor/worker. Les notifications d'e-mail sont queueables après commit avec 3 essais, un timeout de 30 secondes et un backoff de 10/60/300 secondes; tout traitement long futur doit devenir un Job idempotent.

Le scheduler exécute `schedule:work`. Il lance `horizon:snapshot` toutes les cinq minutes et `model:prune` chaque jour, sans chevauchement.

```bash
make up
make ps
make logs
```

En production, superviser Horizon et le scheduler, définir capacité, équilibrage, retries, backoff, timeout et alertes selon la charge réelle. Déployer le code puis redémarrer les workers gracieusement. Surveiller files en attente et failed jobs; ne pas journaliser payloads sensibles.

Horizon est à `/horizon` et son accès exige `horizon.view`. Il doit rester derrière HTTPS, authentification et idéalement un réseau d'administration.
