# Autorisation

Spatie Permission utilise le guard `api`. Les permissions réelles sont :

```text
users.viewAny users.view users.create users.update users.delete users.restore users.changeStatus users.assignRoles
roles.viewAny roles.view roles.create roles.update roles.delete roles.assignPermissions
permissions.viewAny permissions.assign
audit.viewAny health.view api.documentation.view horizon.view
```

Le seeder crée idempotemment `super-admin`, `admin` et `user`. Le super-admin reçoit tout, l'admin tout sauf `roles.delete`, et `user` aucune permission.

`Gate::before` autorise le super-admin uniquement pour les abilities granulaires contenant un point. Les autres utilisateurs passent par permissions et Policies. `UserPolicy` conserve notamment les protections de soi-même et du dernier super-admin. Les ressources système ne doivent pas être supprimées ou altérées sans les invariants existants.

Les endpoints sont `/api/v1/users`, `/api/v1/roles` et `GET /api/v1/permissions`, avec routes dédiées au statut, rôles utilisateur et permissions d'un rôle. Ils sont derrière JWT, validation de version et throttles API/administration. Chaque accès par identifiant est autorisé sur l'objet afin d'empêcher BOLA/IDOR.

Pour ajouter une permission : modifier `config/rbac.php`, adapter le seeder idempotent, appliquer Policy/Gate, écrire tests positifs et négatifs, mettre à jour OpenAPI et la documentation, puis tracer les résultats.
