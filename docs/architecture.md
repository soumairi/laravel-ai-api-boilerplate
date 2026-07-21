# Architecture

Le projet est un monolithe modulaire Laravel-native. La direction autorisée est `Http → Application → Domain`; l'infrastructure implémente les frontières techniques.

- `Domain` contient enums, règles et exceptions sans dépendance HTTP.
- `Application` orchestre les cas d'usage, DTO, transactions et contrats.
- `Http` adapte requêtes/réponses : middleware, Form Requests, contrôleurs et Resources.
- `Infrastructure` encapsule JWT, réponses HTTP, observabilité et génération OpenAPI.
- `Models` représente la persistance Eloquent; il ne remplace pas les services métier.
- `Policies` protège chaque accès objet; `Gate::before` accorde les permissions granulaires au super-admin.
- `Support` contient uniquement les utilitaires transverses justifiés.

Les contrôleurs ne portent ni règle métier importante ni requête complexe. Les entrées inconnues sont rejetées, les sorties passent par Resources/enveloppes, les actions sensibles sont transactionnelles et auditées. Les dépendances externes sont injectées lorsqu'elles constituent une frontière réelle; aucun repository générique n'est imposé autour d'Eloquent.

Les identifiants publics sont des ULID. PostgreSQL est la référence sémantique, Redis porte cache, queues, rate limiting et locks. Les erreurs API utilisent `application/problem+json`; aucune information d'infrastructure ne doit fuir.

Voir [structure](project-structure.md), [conventions Laravel](conventions/laravel.md) et la [décision 0001](decisions/0001-modular-monolith.md).
