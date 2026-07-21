# Conventions API

- Routes métier sous `/api/v1`; réserver `/api/v2` aux ruptures de contrat.
- JSON strict : `Accept: application/json` et Content-Type adapté aux requêtes avec corps.
- Succès dans une enveloppe `data`, éventuellement `meta` et `links`.
- Erreurs `application/problem+json` avec `type`, `title`, `status`, `detail`, `instance` et `request_id`; validation ajoute `errors`.
- Dates UTC au format ISO 8601; identifiants publics ULID.
- Codes HTTP exacts et réponses 204 sans corps utile.
- Auth protégée par bearer JWT; aucune valeur de token dans exemple ou erreur.
- Pagination bornée; filters, sorts, includes et sparse fields en allowlist.
- Chaque accès par identifiant applique une Policy pour prévenir BOLA/IDOR.
- Tout endpoint comporte validation, tests, audit si sensible et synchronisation Scramble.

La référence exécutable est `routes/api.php` avec [OpenAPI](../openapi/openapi.json).
