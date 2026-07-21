# Observabilité

L'API ajoute un `request_id`, le renvoie dans `X-Request-ID` et dans les Problem Details. Les logs JSON sont adaptés à une collecte centralisée. L'audit Activitylog couvre les événements sensibles.

`GET /health/live` retourne uniquement `{"data":{"status":"ok"}}`. `GET /health/ready` teste PostgreSQL et Redis; en cas d'échec, il renvoie 503 sans nom d'hôte, credential ni détail d'exception.

Les logs et outils d'observabilité ne doivent jamais recevoir mots de passe, JWT, refresh tokens, cookies, clés, `.env`, chaîne de connexion, header Authorization ou donnée personnelle inutile. Corréler par `request_id`, route, statut et identifiant utilisateur minimal.

Surveiller au minimum disponibilité, latence, taux 5xx/429, saturation PostgreSQL/Redis, files Horizon, failed jobs, scheduler, espace disque et expirations de certificats/clefs. Toute intégration Sentry ou équivalente reste optionnelle et doit appliquer la même expurgation.
