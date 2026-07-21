# Sécurité

## Invariants

- validation stricte, champs inconnus rejetés et limite de requête de 1 MiB par défaut;
- Policies et permissions sur chaque ressource, protections BOLA/IDOR et dernier super-admin;
- CORS en liste blanche, proxies de confiance explicites, headers défensifs;
- access JWT RS256 court, refresh opaque hash-only, rotation/rejeu et révocation globale;
- PostgreSQL/Redis non exposés publiquement en production;
- erreurs sûres, logs JSON expurgés et audit sans secrets;
- dépendances auditées et scan de secrets en CI.

Ne jamais versionner `.env`, clés, credentials, tokens, cookies ou données personnelles. Les fichiers JWT privés sont mode 0600 et restent sous `storage/keys/`, ignoré par Git. Aucun token n'entre dans une trace.

Tous les throttles sont configurables par `.env` : login 5/minute par e-mail normalisé+IP, inscription 3/minute par IP, mot de passe oublié 3/15 minutes, refresh 10/minute, API publique 60/minute par IP, API authentifiée 120/minute par utilisateur et administration 60/minute par utilisateur. Les variables `API_RATE_LIMIT_*` et leurs valeurs par défaut sont dans `.env.example`.

En production : HTTPS, `APP_DEBUG=false`, CORS précis, secrets gérés par la plateforme, rotation des clés, compte administrateur nominatif, documentation/Horizon protégés, sauvegardes testées, alertes et scans continus.

Une vulnérabilité se signale par le canal privé défini par l'organisation. Aucun contact public de sécurité n'est déclaré dans le dépôt.
