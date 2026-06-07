# Refactoring — Start Here

Before adding features, follow the reference pack in **[`docs/refactoring/`](docs/refactoring/README.md)**.

| Priority | Document |
|----------|----------|
| **1** | [EXECUTION_PLAN.md](docs/refactoring/EXECUTION_PLAN.md) — Sprint 0 → Phase 0 order |
| **2** | [DEBT_REGISTER.md](docs/refactoring/DEBT_REGISTER.md) — what to fix |
| **3** | [STANDARDS.md](docs/refactoring/STANDARDS.md) — quality bar (PR gate) |

Product roadmap: [`project_document.md`](project_document.md).

## Running tenancy tests (Docker)

From the **Laravel** container (`/var/www/html` — not a `laravel/` subfolder):

```bash
docker exec -it saas-laravel-engine bash
# one-time if DB amsaas_testing does not exist yet:
# (from repo root on host) ./scripts/ensure-testing-database.sh
php artisan test --filter=Tenancy
```

PHPUnit uses host **`postgres-db`** (Docker service name), not `127.0.0.1`. See `laravel/phpunit.xml` and `laravel/.env.testing`.

If you see `Could not verify the hashed value's configuration`, ensure `UserFactory` uses a plain `password` (not a pre-baked bcrypt string) so it matches `BCRYPT_ROUNDS` in `phpunit.xml`.

## Phase 0

See [`docs/refactoring/PHASE0_PROGRESS.md`](docs/refactoring/PHASE0_PROGRESS.md). Run full suite: `php artisan test` from `/var/www/html`.

**G1–G3 closed** — see [`SPRINT2_PROGRESS.md`](docs/refactoring/SPRINT2_PROGRESS.md).

**UI redesign:** [`UI_DESIGN_MODEL.md`](docs/refactoring/UI_DESIGN_MODEL.md) (Fiori interaction) · [`UI_DESIGN_SYSTEM.md`](docs/refactoring/UI_DESIGN_SYSTEM.md) (components) · [`ERP_UI_STRATEGY.md`](docs/refactoring/ERP_UI_STRATEGY.md) (rollout).

**Next:** W0 shell breadcrumbs + W1 Worklist migrations (Buildings → Agreements), then Phase 1 charge engine.
