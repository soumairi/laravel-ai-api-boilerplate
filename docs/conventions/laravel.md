# Conventions Laravel

- Controllers fins; Form Requests strictes; Actions pour les cas d'usage.
- Resources explicites; ne jamais exposer directement un modèle Eloquent.
- Policies objet et permissions granulaires, sans rôle codé dans un contrôleur.
- Transactions dans Application, courtes et limitées à une unité métier.
- Eager loading et allowlists Query Builder pour prévenir N+1 et entrées libres.
- Notifications et traitements longs en queue; Jobs idempotents.
- Events nommés au passé, Actions par verbe métier, suffixes `Controller`, `Request`, `Resource`, `Policy`, `Action`, `Data`.
- Aucun accès `request()` dans Domain et aucune dépendance Domain → Http.
- Configuration cacheable et effets externes encapsulés.

Respecter le guide local `app/AGENTS.md` et [l'architecture](../architecture.md).
