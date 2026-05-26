# Live hosting (no SSH required from you)

Your app runs on a **web server** (the logs show `/var/www/html`). You do **not** need Laravel Forge or any server knowledge for day-to-day use.

## What you do

1. **Get the latest code onto the server** — ask whoever set up the site (IT, a contractor, or your host’s “Redeploy from GitHub” button) to pull the latest `main` branch from https://github.com/cgunn5/08-MLB-Draft

2. **Open your live URL in the browser**

3. **First time only:** complete **one-time setup** (name, email, password) **once**

4. **Every time after that:** use **Login** only

5. **Upload CSVs** under HS DATA / NCAA DATA, enter notes under Notes/Grades

The app now **automatically** stores the database under `storage/app/persistent/` so redeploys should not wipe your data, even if the server still has an old `database/database.sqlite` setting.

## If setup keeps coming back

- Click **Restore my data & go to login** if you see it  
- Do **not** create a new account (that starts empty)  
- Ask your host to redeploy latest `main` again

## If someone technical helps you (one sentence for them)

> Pull latest `main`, run `composer install --no-dev`, `npm ci && npm run build`, `php artisan migrate --force`, `php artisan optimize:clear`. Remove `DB_DATABASE` from `.env` if it points at `database/database.sqlite`. Ensure `storage/` is writable.

## Optional: Laravel Forge

Only if your server actually uses [Laravel Forge](https://forge.laravel.com), see [FORGE.md](FORGE.md). Most people never touch Forge directly.
