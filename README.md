# MLB Draft App

A personal project for tracking, analyzing, and experimenting with Major League Baseball draft data and workflows.

## Goals

- **Centralize Draft Information** — Keep data, players, and boards all in one place hosted live with smooth filtering functionality. The app should be able to be updated only by me while having ability to share boards, player profiles, etc. with other stakeholders.
- **Support decision-making** — Make it easier to compare players, boards, and scenarios over time.

## Core Components

- A "Working Board" where players have cards featuring information I choose, with the ability to move them up/down and create different groupings, sort, and filter.
- A "NCAA Dashboard" page that pulls in player data, information, and notes into one clean page for NCAA/JUCO players.
- A "HS Dashboard" page that aggregates player data, information, and notes into one clean page for HS players.
- A "Note Input" page where I can input notes on specific sections regarding a player's skillset.
- Data tabs where I can upload various data sources. These will come in different forms/structures and all should be able to be pulled into a single player's profile so the setup must allow for this.

## Themes

- All font throughout the app should be Carbon Regular font and in all caps.
- The app should allow for conditional formatting for high/low values with red being representative of high/good and blue being representative of low/poor. Utility classes: `.cf-value-high` and `.cf-value-low`.

### Nice-to-haves / later

- [ ] **Export / share** — PDF, CSV, or shareable snapshot of a board/player profile.

## Tech Stack

- Laravel (PHP) web app, password-protected. Public self-registration is **disabled**. The first admin account is created via the database seeder; that account can invite additional **non-admin** users from **Users** in the nav. Granting admin to another account is done manually in the database (or via a future artisan command).

## Getting Started

Requirements: PHP 8.4+, Composer, Node.js 20+ (for Vite).

```bash
cd /path/to/08-MLB-Draft
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
```

In another terminal, run the app:

```bash
php artisan serve
```

- **URL:** http://127.0.0.1:8000  
- **Default admin (after seed):** `admin@example.com` / `password`  

Change the admin email and password immediately in production. Use HTTPS, strong `APP_KEY`, and set `APP_DEBUG=false`. For production hardening, also configure your web server, rate limiting, and backups.

### Production deploy (after `git pull`)

Login can return **500** while the login form still loads if deploy steps are skipped:

1. **Stale route cache** — The home page after login renders the nav, which references routes such as `ncaa-data-sources.index`. If `php artisan route:cache` ran *before* the new code was on disk, Laravel throws `Route […] not defined` and you get a 500. Clear caches after every deploy, then rebuild them from the current code.
2. **Missing Vite build** — `public/build/` is not in git. Run `npm ci && npm run build` on the server (or in CI) so `@vite` can find `public/build/manifest.json`.

Recommended one-liner from the app root:

```bash
composer run deploy
```

That runs `optimize:clear`, migrations, `npm ci`, `npm run build`, and re-caches config/routes/views. If you deploy manually, at minimum run:

```bash
php artisan optimize:clear
php artisan migrate --force
npm ci && npm run build
```

Only run `php artisan route:cache` / `config:cache` **after** the new code is present and caches were cleared.

After deploy, stale `bootstrap/cache/routes-*.php` files are dropped automatically when `routes/*.php` is newer than the cache (see `App\Support\StaleRouteCacheGuard` in `bootstrap/app.php`), so the first request after `git pull` should recover without manual `route:clear`—but `composer run deploy` is still recommended.

**If login fails after setup:** use the same host as `APP_URL` in `.env` (e.g. open `http://127.0.0.1:8000`, not `http://localhost:8000`, when `APP_URL` is `http://127.0.0.1:8000`).

**Backups (notes, grades, and all app DB state):** Player **notes and grades** live in the database (`players` columns and related tables), not in uploaded CSV files. Git does **not** back up the database.

1. **Automated (recommended)** — The app ships `php artisan app:backup-database`, which copies the SQLite file (default) into `storage/app/database-backups/` with a timestamped name, then deletes backups older than `APP_DB_BACKUP_RETENTION_DAYS` (default 30). A daily run is registered at `APP_DB_BACKUP_DAILY_AT` (default `03:15`). **You must run the Laravel scheduler in production**, for example:
   - Cron (every minute): `* * * * * cd /path/to/08-MLB-Draft && php artisan schedule:run >> /dev/null 2>&1`
   - Or a supervisor/systemd service running `php artisan schedule:work`
   - Manual anytime: `php artisan app:backup-database` or `composer backup-db` (add `--dry-run` to inspect paths only; `--no-prune` to skip deleting old files)
2. **Off-server copy** — Sync or copy `storage/app/database-backups/` (and/or `database/database.sqlite`) to another disk, cloud object storage, or your host’s backup system so a server failure does not take everything with it.
3. **MySQL / MariaDB** — If `DB_CONNECTION` is `mysql` or `mariadb`, the same command runs `mysqldump` when the client binary is on `PATH` (override with `MYSQLDUMP_PATH`) and writes a gzipped `.sql.gz` into the same backup directory. Large DBs are held in memory during gzip; for huge databases prefer your host’s native backup tools.

Set `APP_DB_BACKUP_SCHEDULE_ENABLED=false` in CI or local environments where the DB is in-memory only.

**After a DB reset, orphan CSVs:** persisted uploads use a UUID filename under `storage/app/private/data-source-uploads/`. To recreate `data_source_uploads` rows from those files only, run `php artisan app:recover-orphan-data-source-uploads` (use `--dry-run` first). That does **not** restore heat rules, browse settings, profile-feed slot checkboxes, or any player notes/grades—only a SQLite backup can.

**Perfect Game / HS Stats – Performance:** the “Perfect Game” profile tables and the derived **HS Stats - Perfect Game Career** dataset only appear when an HS upload has the **Performance → Perfect Game** checkbox (`performance_pg`) saved on it. After a reset, run `php artisan app:recover-hs-perfect-game-performance-slot` (optional `--dry-run`) to auto-detect PG-style yearly CSVs (headers with YEAR + slash-line columns + ISO or BB%/K%), assign that slot to the **largest** matching upload by stored `row_count`, then rebuild the career master. If recovery attached the wrong sheet (e.g. a small “overall” file) so you see career stats but not full yearly PG rows, run again with **`--reassign`** to clear the slot from every HS file upload and re-pick the largest PG-shaped CSV. If your sheet uses unusual headers, re-check **Performance → Perfect Game** manually on HS Data for the right file.

**Do not use `php artisan migrate:fresh` to fix login problems** unless you accept **complete loss** of all app data (players, notes, uploads, board state). `migrate:fresh` drops every table and rebuilds an empty schema. To restore only the bundled aggregate player list after a reset, run `php artisan db:seed --class=AggregatesPlayerSeeder` (that does not recover uploads or in-app notes). Prefer `php artisan tinker` or a password reset flow to fix credentials.

(Local dev uses `SESSION_DRIVER=file` in `.env` by default in this project so sessions do not depend on the DB.)

### Fonts

Carbon Regular is loaded from `public/fonts/Carbon-Regular.ttf` (bundled in this repo).

---

*Last updated: May 2026*
