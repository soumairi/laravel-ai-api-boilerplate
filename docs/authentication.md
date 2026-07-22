# Authentification

L'access token est un JWT RS256 émis par `App\Application\Auth\TokenService`. Les DTO immuables `TokenClaims` et `TokenPair`, la value object `TokenIdentifier`, et les Actions d'émission/rotation gardent les contrôleurs minces. Le JWT inclut l'utilisateur (`sub`), la version (`ver`) et le statut; sa durée par défaut est 15 minutes. `AccessTokenValidator` vérifie signature, expiration/blacklist, `sub`, statut, `token_version`, issuer et audience. La même validation protège l'API, Scalar en production et Horizon.

Le refresh token n'est pas un JWT. `RefreshTokenService` génère 64 octets aléatoires encodés en base64url et ne persiste que leur HMAC-SHA-256 avec `APP_KEY`. Une session possède un ULID, une famille, une expiration, un état de révocation et des métadonnées client.

À chaque refresh :

1. le hash identifie la session;
2. un lock Redis de famille (TTL 10 s, attente 3 s) sérialise la rotation;
3. PostgreSQL verrouille la ligne dans une transaction;
4. l'ancien token devient `rotated`;
5. un nouveau token de la même famille est créé avec la même expiration.

La durée `REFRESH_TOKEN_TTL=43200` est exprimée en minutes (30 jours). La rotation ne prolonge pas cette échéance et refuse un compte non actif avant d'émettre un remplacement. Réutiliser un ancien token marqué `rotated` révoque toute la famille, incrémente `token_version` et renvoie 401. Un lock indisponible renvoie 409. Un token inconnu, expiré ou révoqué pour une autre cause renvoie 401. `model:prune` supprime quotidiennement les sessions expirées/révoquées au-delà de `REFRESH_SESSION_RETENTION_DAYS`.

Routes publiques : configuration d'inscription, inscription, login, refresh, mot de passe oublié/réinitialisé et vérification d'e-mail. Routes protégées : `me`, logout, logout-all et `PATCH /api/v1/auth/password`.

Un reset/changement de mot de passe, une modification administrative des credentials ou logout-all révoque les sessions et invalide globalement les JWT. La vérification d'e-mail n'active qu'un compte `pending`; elle ne réactive jamais un compte suspendu. Ne jamais stocker l'access token, le refresh token ou une clé privée dans Git, logs ou traces.
