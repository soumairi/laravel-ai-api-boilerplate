# Conventions de test

- Pest, scénarios observables et noms décrivant le comportement.
- PostgreSQL et Redis réels pour leurs propriétés d'intégration.
- Tests indépendants, déterministes, parallélisables et sans réseau externe.
- Factory states et helpers réutilisables; aucune donnée secrète.
- Couvrir happy path, validation, authN, authZ, erreurs, limites et sécurité.
- Vérifier enveloppes, Problem Details, `request_id`, types et absence de champs sensibles.
- Utiliser fakes pour effets externes, pas pour masquer une intégration critique.
- Aucun `sleep`, retry pour cacher un flaky test, ordre implicite ou token réel.
- Une correction de bug ajoute un test de non-régression.
- Ne jamais affaiblir ou supprimer une assertion pour faire passer CI.

Voir [testing.md](../testing.md) et `tests/AGENTS.md`.
