# Conventions base de données

- PostgreSQL est la référence; ne pas supposer une équivalence SQLite.
- ULID pour les identifiants publics et pivots compatibles.
- Contraintes, foreign keys, unicité, indexes et nullabilité explicites.
- Noms de table/colonnes Laravel cohérents; timestamps en UTC.
- Nouvelle migration pour toute évolution déjà publiée.
- `up()` atomique lorsque possible et `down()` sûr; documenter les irréversibilités.
- Évaluer locks, durée, backfill, taille de table et déploiement progressif.
- Transactions courtes et niveau de verrouillage justifié.
- Seeders idempotents, sans credential par défaut ni sortie sensible.
- Destruction autorisée uniquement sur l'environnement `testing` isolé.

Lire `database/AGENTS.md` et [database-and-migrations.md](../database-and-migrations.md).
