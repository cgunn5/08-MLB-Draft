# Laravel Cloud setup (draft-app on *.laravel.cloud)

**SQLite does not work on Laravel Cloud.** The server disk is wiped on every deploy, so your login, notes, and grades disappear and you see “Create your admin account” again.

Use a **hosted database** instead. You only configure this once in the [Laravel Cloud dashboard](https://cloud.laravel.com).

## One-time setup (click through — no SSH)

### 1. Add a database

1. Go to [cloud.laravel.com](https://cloud.laravel.com) and open your application.
2. Open the environment (e.g. `main`).
3. **Resources** → **Add database** → **Laravel MySQL** (simplest) or **Serverless Postgres**.
4. Wait until it shows as attached, then **Redeploy**.

Laravel Cloud injects `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` automatically.

### 2. Environment variables

In **Environment** → **Environment variables**:

| Action | Variable |
|--------|----------|
| **Delete** if present | `DB_CONNECTION=sqlite` |
| **Add** | `ADMIN_EMAIL=cgunn@texasrangers.com` |
| **Add** | `ADMIN_PASSWORD=your-password` |
| **Add** | `ADMIN_NAME=C. Gunn` |
| **Leave unset for now** | `SESSION_DRIVER` (defaults to `file` — safe before migrations) |
| **Leave unset for now** | `CACHE_STORE` (defaults to `file`) |

After the first successful deploy with a database attached, you may set `SESSION_DRIVER=database` and `CACHE_STORE=database` if you want sessions in MySQL (optional).

Do **not** commit passwords to GitHub — only set them in Laravel Cloud.

### 3. Deploy command

In **Environment** → **Deploy command**, set:

```bash
php artisan app:laravel-cloud-bootstrap
```

Save and **Redeploy**.

This runs migrations and creates your admin login from `ADMIN_EMAIL` / `ADMIN_PASSWORD` if no users exist yet.

> **Note:** Do not run `php artisan config:cache` in build commands before the database is attached — it can lock in `sqlite` as the default. The app now overrides this at runtime when `DB_HOST` is injected.

### 4. Log in

Open your site → **Login** (not setup):

- Email: `cgunn@texasrangers.com`
- Password: whatever you set in `ADMIN_PASSWORD`

After this, redeploys keep your account and data in the hosted database.

## CSV uploads (stats files)

Uploaded CSV files are stored on disk today. On Laravel Cloud that disk is also temporary. For long-term stat file storage, add **Object Storage** under Resources and set `FILESYSTEM_DISK` to the disk name Laravel Cloud injects. Notes and grades in the database will persist once step 1–3 are done.

## Local development

Keep using SQLite locally (default `.env.example`). Laravel Cloud settings only apply on `*.laravel.cloud`.
