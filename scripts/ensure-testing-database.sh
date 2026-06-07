#!/usr/bin/env bash
# Creates amsaas_testing if missing (for existing Postgres volumes where init scripts did not run).
set -euo pipefail

CONTAINER="${POSTGRES_CONTAINER:-saas-postgres-db}"
DB_USER="${POSTGRES_USER:-cloud_finops_admin}"
DB_NAME="${TEST_DATABASE:-amsaas_testing}"

exists="$(docker exec "$CONTAINER" psql -U "$DB_USER" -d property_saas_ledger -tAc \
  "SELECT 1 FROM pg_database WHERE datname = '${DB_NAME}'" 2>/dev/null || true)"

if [[ "$exists" != "1" ]]; then
  echo "Creating database ${DB_NAME}..."
  docker exec "$CONTAINER" psql -U "$DB_USER" -d property_saas_ledger -c "CREATE DATABASE ${DB_NAME};"
else
  echo "Database ${DB_NAME} already exists."
fi

echo "Done. Run tests from laravel container:"
echo "  docker exec -it saas-laravel-engine php artisan test --filter=Tenancy"
