# Créer un module

Suivre cet ordre et tracer les décisions :

1. Définir le besoin métier.
2. Définir les critères d'acceptation.
3. Définir les permissions.
4. Concevoir une migration PostgreSQL réversible.
5. Créer modèle et factory.
6. Créer enums et Value Objects utiles.
7. Créer les DTO immuables.
8. Créer les Actions applicatives.
9. Créer les Form Requests strictes.
10. Créer la Policy.
11. Créer l'API Resource.
12. Créer un contrôleur fin.
13. Déclarer les routes versionnées.
14. Vérifier rate limiting et pagination.
15. Ajouter l'audit sans donnée sensible.
16. Maintenir le contrat Scramble.
17. Créer les tests Unit/Feature/Integration/Security nécessaires.
18. Exécuter la quality gate.
19. Append fichiers, commandes et résultats à la trace.
20. Finaliser Git exclusivement après approbation via les scripts sûrs.

Le module respecte `Http → Application → Domain`, n'ajoute pas de repository générique sans frontière justifiée et ne dépend pas de SQLite comme référence. Voir [endpoint](create-api-endpoint.md), [architecture](architecture.md) et [tests](testing.md).
