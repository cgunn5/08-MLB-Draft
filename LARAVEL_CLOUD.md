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

## CSV stat files (HS DATA / NCAA DATA) — required for sheets

Player notes and grades live in **MySQL** and survive redeploys. **CSV stat files do not** unless you add object storage.

Laravel Cloud wipes the server disk on every deploy. Dataset tabs stay in the app (names/settings in MySQL) but the actual `.csv` files disappear → **“That dataset file is missing on the server.”**

### Fix (one time)

1. **Resources** → **Add bucket** → **Laravel Object Storage** → **Private**
2. Disk name: e.g. `r2` (remember this name)
3. Check **Use as default filesystem disk** (or set `DATA_SOURCE_UPLOADS_DISK=r2` in environment variables)
4. **Redeploy**
5. Re-import your data:
   - **SYNC DATA** → upload your bundle from your Mac, **or**
   - Re-upload each CSV under HS DATA / NCAA DATA

After this, new uploads and bundle imports are stored in the bucket and **persist permanently**.

## Local development

Keep using SQLite locally (default `.env.example`). Laravel Cloud settings only apply on `*.laravel.cloud`.
