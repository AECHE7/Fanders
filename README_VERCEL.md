Vercel deployment notes
======================

This repository is a PHP monolith that requires a PHP runtime and a MySQL database. Vercel is optimized for static sites and serverless functions and does not run full PHP applications natively.

What this file provides
- `vercel.json` — configuration to deploy the `public` directory as a static site using `@vercel/static`.

Important limitations
- PHP code will NOT execute on Vercel. This means all dynamic pages (login, database-driven pages, admin UI, etc.) will not work.
- Database connections are not supported for static-only deployments.

When to use Vercel
- Use Vercel only if you want to publish the static assets from `public/` (CSS, JS, images) — useful for marketing pages or static previews.

How to deploy the `public` folder to Vercel
1. Install the Vercel CLI and log in (optional):

```bash
npm i -g vercel
vercel login
```

2. From the repo root, run:

```bash
vercel --prod --confirm --build-target=public
```

Or let Vercel detect the project via the web UI and set the Output Directory to `public`.

3. After deploy, Vercel will host static files only. Any PHP endpoints will return the PHP source or 404 — they will not be executed.

Recommended alternative for the full app: deploy the whole project to Render (Docker) or another host that supports PHP + MySQL. See README for Render instructions (or ask me and I will add a `render.yaml` and deployment guide).
