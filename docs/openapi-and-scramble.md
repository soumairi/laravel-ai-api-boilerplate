# OpenAPI et Scramble

Scramble 0.13.35 est l'unique générateur. Il analyse routes, types, Form Requests, Resources et enums; l'extension interne documente les allowlists Query Builder. La sécurité globale `bearerAuth` est `http/bearer/JWT`; les opérations publiques la neutralisent.

```bash
make docs
make docs-check
```

`make docs` exporte vers `docs/openapi/openapi.json`. Ne modifier jamais cet export manuellement. Scalar est servi sur `http://localhost:8080/docs/api` et le JSON dynamique sur `/docs/api.json`.

En production, `ProductionDocsAccess` exige un JWT valide (signature, expiration, `sub`, statut, version, issuer et audience) et `api.documentation.view`. Horizon applique le même cycle de validation avec `horizon.view`.

Pour chaque endpoint, maintenir résumé, paramètres, payload, validation, auth, réponses, pagination, filtres, tris, includes et exemples non sensibles. Les opérations d'authentification et d'administration utilisent des Form Requests dédiés afin que chaque schéma corresponde à son endpoint. Les erreurs documentées utilisent `application/problem+json`; les réponses 204 n'ont aucun body. Le test d'intégration vérifie JSON, sécurité bearer, typage, allowlists, Problem Details et égalité exacte avec `docs/openapi/openapi.json`. La CI régénère aussi l'export et échoue sur tout diff.

Après un changement de contrat : exécuter les tests OpenAPI, exporter, décoder le JSON, inspecter le diff et vérifier qu'aucun secret ou champ sensible n'apparaît.
