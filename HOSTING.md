# Live hosting

## Laravel Cloud (*.laravel.cloud)

If your live URL looks like `something.laravel.cloud`, **read [LARAVEL_CLOUD.md](LARAVEL_CLOUD.md) first.**

SQLite does not persist there. You must attach **Laravel MySQL** or **Serverless Postgres** in the Laravel Cloud dashboard. The “Create admin account” screen on SQLite is temporary and will keep coming back after redeploys.

## Other servers (Forge, VPS, etc.)

1. **Get the latest code onto the server** — pull latest `main` from https://github.com/cgunn5/08-MLB-Draft

2. **Open your live URL** → **Login**

3. SQLite on a normal server uses `storage/app/persistent/database.sqlite` (see [FORGE.md](FORGE.md) if you use Forge).

## If setup keeps coming back (non–Laravel Cloud)

- Click **Restore my data & go to login** if you see it  
- Do **not** create a new account unless there is no backup  
- Redeploy latest `main`

## For someone technical (one sentence)

> Pull latest `main`, run `composer install --no-dev`, `npm ci && npm run build`, `php artisan migrate --force`. On Forge, leave `DB_DATABASE` unset. On Laravel Cloud, attach a hosted database and use deploy command `php artisan app:laravel-cloud-bootstrap`.
