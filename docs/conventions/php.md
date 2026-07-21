# Conventions PHP

- PHP 8.4, `declare(strict_types=1);`, types de paramètres et retours explicites.
- Classes `final` et propriétés `readonly` lorsque l'extension n'est pas requise.
- DTO immuables, enums et Value Objects pour les concepts stables.
- PSR-12 et conventions Laravel, contrôlées par Pint.
- Noms explicites, méthodes courtes, aucune abstraction prématurée.
- Pas de `dd`, `dump`, `ray`, `var_dump`, code mort ou `TODO` sans contexte.
- Exceptions métier dans Domain; adaptation HTTP centralisée.
- `env()` uniquement dans les fichiers de configuration ou le bootstrap explicitement prévu.
- PHPStan niveau 5 via Larastan; ne pas masquer une erreur par une annotation fausse.

Exécuter `make lint` et `make analyse` avant livraison.
