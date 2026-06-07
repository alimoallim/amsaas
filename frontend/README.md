# AMSAAS frontend (Vue 3 + Vite)

This folder is the **property ERP SPA**. It is separate from `laravel/` (API only).

## Do not confuse with Laravel on port 8080

| URL | What you see |
|-----|----------------|
| `http://localhost:8080` | Laravel API / default welcome page — **not** this Vue app |
| `http://localhost:5173` | Vue ERP UI (after `npm run dev`) |

Running `npm run dev` inside **`laravel/`** only builds Laravel’s minimal assets, not this app.

## Quick start (local)

```bash
cd frontend
cp .env.example .env   # optional; defaults work with Vite proxy
npm install
npm run dev
```

Open **http://localhost:5173**, log in, then use the sidebar.

Ensure the API is up (Docker or local Laravel), e.g. `docker compose up -d` so `http://localhost:8080/api/v1` responds.

## API base URL

- Default: `/api/v1` (proxied to `http://localhost:8080` by Vite — see `vite.config.js`)
- Override: set `VITE_API_BASE_URL` in `frontend/.env`, e.g. `http://localhost:8080/api/v1`

## Docker (optional UI container)

From repo root:

```bash
docker compose up -d frontend-dev
```

Then open **http://localhost:5173**.

## New ERP design system (partial rollout)

Redesigned pages use components under `src/components/erp/` and `src/styles/erp.css`.

**Already migrated:** Charge Types, Charge Models, Invoices (list).

**Still legacy layout:** Dashboard, Buildings, Tenants, Meters, most create/edit screens.

To verify the new UI, go to **Finance → Charge Types** (`/charge-types`) or **Invoices**.

See `docs/refactoring/UI_DESIGN_SYSTEM.md`.

## Production build

```bash
cd frontend
npm run build
```

Output: `frontend/dist/` (not served by default nginx; integrate separately if needed).
