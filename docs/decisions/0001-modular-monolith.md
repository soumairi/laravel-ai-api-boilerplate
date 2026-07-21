# 0001 — Monolithe modulaire Laravel-native

- Statut : Acceptée
- Date : 2026-07-21

## Contexte

Le boilerplate doit rester rapide à comprendre pour humains et agents IA, tout en isolant règles métier, orchestration, HTTP et dépendances externes. Des microservices ou une Clean Architecture exhaustive ajouteraient des frontières opérationnelles et du code cérémoniel sans domaine justifiant ce coût.

## Décision

Adopter un monolithe modulaire avec direction `Http → Application → Domain`. `Infrastructure` adapte JWT, Redis et services externes derrière les frontières utiles. Eloquent reste dans `Models`; les Policies protègent les objets; les Resources définissent les sorties. Aucun repository générique n'est imposé.

## Conséquences

- déploiement et transactions restent simples;
- les modules partagent Laravel, PostgreSQL et Redis;
- les tests d'architecture empêchent les dépendances inverses;
- un module peut être extrait plus tard si ses frontières et besoins opérationnels le justifient;
- l'équipe doit éviter les dépendances transverses informelles et garder les contrôleurs fins.
