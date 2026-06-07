-- Runs once when the Postgres data volume is first created.
-- Separate DB so php artisan test (RefreshDatabase) does not touch property_saas_ledger.
CREATE DATABASE amsaas_testing;
