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

**If login fails after setup:** use the same host as `APP_URL` in `.env` (e.g. open `http://127.0.0.1:8000`, not `http://localhost:8000`, when `APP_URL` is `http://127.0.0.1:8000`). Recreate the admin with:

```bash
php artisan migrate:fresh --seed
```

(Local dev uses `SESSION_DRIVER=file` in `.env` by default in this project so sessions do not depend on the DB.)

### Fonts

Carbon Regular is loaded from `public/fonts/Carbon-Regular.ttf` (bundled in this repo).

---

*Last updated: April 2026*
