# Créer un endpoint API

- [ ] Route sous `/api/v1`, nommée et méthode HTTP correcte
- [ ] Permission existante ou ajoutée à `config/rbac.php`
- [ ] Policy objet appliquée
- [ ] Form Request stricte créée
- [ ] DTO et Action créés lorsque le cas d'usage le justifie
- [ ] Transaction courte ajoutée si plusieurs écritures forment une unité
- [ ] API Resource ou enveloppe standard utilisée
- [ ] Codes HTTP, JSON et Problem Details cohérents
- [ ] Audit expurgé ajouté si l'action est sensible
- [ ] Rate limiter adapté appliqué
- [ ] Filtres, tris, includes, fields et pagination autorisés explicitement
- [ ] Types et documentation Scramble vérifiés
- [ ] Tests happy path, validation, authentification et autorisation ajoutés
- [ ] Tests BOLA/IDOR, limites et absence de champs sensibles ajoutés
- [ ] Export OpenAPI et documentation humaine mis à jour
- [ ] Trace append-only mise à jour
- [ ] `make lint`, `make analyse`, tests ciblés et validations réussis
- [ ] Commit/push/PR réalisés seulement via scripts sûrs et approbations

Contrôler le contrat avec `php artisan route:list --except-vendor` et `make docs`. Ne documenter aucune route supposée : `routes/api.php` et [OpenAPI](openapi/openapi.json) sont les références.
