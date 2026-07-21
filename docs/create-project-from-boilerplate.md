# Créer un projet depuis le boilerplate

1. Cloner le dépôt réel ou l'utiliser comme template, puis renommer dépôt, image et `APP_NAME` si nécessaire.
2. Vérifier Git et créer une branche dédiée conforme à `ai/<trace-id>/<type>/<slug>`.
3. Copier `.env.example` vers `.env`; ne jamais versionner ce fichier.
4. Renseigner PostgreSQL, Redis, CORS et les valeurs `ADMIN_*` sans mot de passe par défaut.
5. Lancer `make up`, puis `make init`. Cette commande génère `APP_KEY`, prépare les clés RSA, migre et seed.
6. Vérifier `make ps`, `/health/live` et `/health/ready`.
7. Confirmer la présence du super-admin uniquement si `ADMIN_EMAIL` et `ADMIN_PASSWORD` ont été fournis.
8. Horizon et le scheduler sont déjà lancés par Compose; sans Docker, lancer `php artisan horizon` et `php artisan schedule:work`.
9. Exporter OpenAPI avec `make docs` et ouvrir `http://localhost:8080/docs/api`.
10. Exécuter `make test`, `make test-coverage`, `make quality` et `make validate`.
11. Adapter le nom des rôles/permissions à la politique du nouveau produit, avec migrations/seeders idempotents et tests.
12. Créer le premier module selon [create-module.md](create-module.md).

Avant production, suivre [deployment.md](deployment.md). `php artisan boilerplate:init --force` remplace les clés JWT; ne jamais l'utiliser comme commande de déploiement routinière.
