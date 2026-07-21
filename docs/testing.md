# Tests

Pest 4 couvre cinq catégories présentes :

- `Unit` : enums et règles isolées;
- `Feature` : authentification et autorisation administrative;
- `Integration` : PostgreSQL, Redis, JWT, refresh, queues et OpenAPI;
- `Architecture` : direction des dépendances et conventions;
- `Security` : négociation JSON, limites, erreurs et données sensibles.

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

Les commandes utilisent le stage Docker `test`, PostgreSQL et Redis. PCOV y est activé; `make test-coverage` impose 85 %. SQLite n'est pas un substitut au moteur de production.

Un changement doit tester happy path, validation, authentification, autorisation, erreurs métier, BOLA/IDOR, limites, concurrence pertinente, effets secondaires, contrat JSON et OpenAPI. Les tests sont déterministes, parallélisables, sans réseau externe, secret réel, délai artificiel ou dépendance d'ordre.

Utiliser factories et fakes Laravel quand ils représentent correctement la frontière; conserver les tests d'intégration pour les propriétés PostgreSQL/Redis/JWT. Ne jamais supprimer ou affaiblir une assertion pour obtenir un pipeline vert.
