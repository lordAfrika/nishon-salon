# Nishon Salon — Booking System

This repository contains a small PHP booking application intended for educational/demo use.

Contents
- `index.php` — main frontend and booking flow
- `client.php` — DB helpers and business logic
- `database.php` — admin panel
- `create_admin.php` — create initial admin user
- `format.sql` — SQL schema and triggers
- `css/`, `js/`, `img/` — static assets
- `Dockerfile` — container image for running with Apache+PHP

Quick start (local with XAMPP)
1. Copy the repo into `c:/xampp/htdocs/`.
2. Edit `client.php` to set your database credentials.
3. Import `format.sql` into MySQL.
4. Visit `http://localhost/Bben/index.php`.

Pushing to GitHub
1. Initialize git and commit:

```powershell
cd c:\xampp\htdocs\Bben
git init
git add .
git commit -m "Initial commit: Nishon Salon booking app"
```

2. Create a GitHub repo (via web or `gh` CLI) and push:

```powershell
gh repo create YOUR_USERNAME/nishon-salon --public --source=. --remote=origin --push
```

Deployment options
- Shared hosting (cPanel): upload files and import `format.sql`.
- VPS (DigitalOcean/Lightsail): deploy via Docker (see `Dockerfile`) or configure LAMP.
- Render/Railway: connect GitHub repo and deploy using the provided `Dockerfile`.

If you want, I can prepare a GitHub Actions workflow or help you create the remote repo — provide access token or let me guide you through the steps.
