# Autorisation

Spatie Permission utilise le guard `api`. Les permissions réelles sont :

```text
users.viewAny users.view users.create users.update users.delete users.changeStatus users.assignRoles
roles.viewAny roles.view roles.create roles.update roles.delete roles.assignPermissions
permissions.viewAny api.documentation.view horizon.view
```

Le seeder crée idempotemment `super-admin`, `admin` et `user`. Le super-admin reçoit tout. L'admin reçoit uniquement les permissions utilisateur et opérationnelles explicitement listées par `rbac.admin_permissions`; il ne peut ni créer/modifier des rôles, ni assigner des permissions. `user` ne reçoit aucune permission.

`Gate::before` autorise le super-admin uniquement pour les abilities granulaires contenant un point. Les autres utilisateurs passent par permissions et Policies. Un admin ne peut pas gérer un compte portant `admin` ou `super-admin`, ni assigner ces rôles. Les rôles système `super-admin`, `admin` et `user` sont immuables via l'API. Les changements de rôle, statut et suppression prennent un advisory lock transactionnel PostgreSQL stable avant de relire et verrouiller les comptes concernés, afin de sérialiser l'invariant d'au moins un super-admin actif.

Les endpoints sont `/api/v1/users`, `/api/v1/roles` et `GET /api/v1/permissions`, avec routes dédiées au statut, rôles utilisateur et permissions d'un rôle. Ils sont derrière JWT, validation de version et throttles API/administration. Chaque accès par identifiant est autorisé sur l'objet afin d'empêcher BOLA/IDOR.

Pour ajouter une permission : modifier `config/rbac.php`, adapter le seeder idempotent, appliquer Policy/Gate, écrire tests positifs et négatifs, mettre à jour OpenAPI et la documentation, puis tracer les résultats.
