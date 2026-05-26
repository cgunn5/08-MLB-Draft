# Laravel Forge setup (MLB Draft)

Follow this **once** on Forge so redeploys never wipe your database, CSV uploads, notes, or grades.

## 1. Environment variables (Forge → Site → Environment)

Set or confirm these lines. **Remove any `DB_DATABASE=` line that points at `database/database.sqlite`.**

```env
DB_CONNECTION=sqlite
CACHE_STORE=file
SESSION_DRIVER=file
APP_DEBUG=false
```

Leave **`DB_DATABASE` unset** so the app uses `storage/app/persistent/database.sqlite` (shared between deploys).

## 2. Deploy script (Forge → Site → Deploy Script)

Replace the deploy script with the contents of [`scripts/forge-deploy.sh`](scripts/forge-deploy.sh) in this repo, or paste:

```bash
cd $FORGE_SITE_PATH

git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci
npm run build

php artisan migrate --force
php artisan optimize:clear
php artisan app:forge-persistence-doctor --ansi
```

**Do not** run `migrate:fresh`, `db:seed`, or point the database at `database/database.sqlite`.

## 3. Shared storage (default on Forge)

Forge Laravel sites symlink `storage/` at the site root (outside `releases/`). All live data must stay under that folder:

| Data | Path |
|------|------|
| SQLite database | `storage/app/persistent/database.sqlite` |
| CSV uploads | `storage/app/private/data-source-uploads/` |
| DB backups | `storage/app/database-backups/` |

After deploy, run **Deploy Now** once and check the deploy log for `Forge persistence: ready for redeploys without data loss.`

## 4. First-time setup (browser only)

1. Open your site URL → **one-time setup** → create your **admin** account **once**.
2. Every later visit → **login** only. Setup should not appear again.
3. Upload CSVs under **HS DATA** / **NCAA DATA**, enter notes under **Notes/Grades** on live.

If setup reappears after a deploy:

1. Click **Restore my data & go to login** if shown.
2. Otherwise verify step 1 (no `DB_DATABASE=database/...` in Forge env) and redeploy.

## 5. Invited viewers

Use **Users** (admin nav) to invite accounts. They can open **Home**, **Board** (read-only), **NCAA**, and **HS** only. Only the admin can upload data and edit notes/grades.

## 6. Optional: scheduler for nightly backups

Forge → Site → Scheduler → add:

```
php artisan schedule:run
```

Frequency: every minute (Forge runs the scheduler).

---

**Summary:** Never store the database in `database/`. Keep Forge env clean, use the deploy script above, create your admin account once, then always log in.
