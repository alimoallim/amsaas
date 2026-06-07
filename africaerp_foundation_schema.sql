-- =============================================================================
-- AfricaERP / AMSAAS — Foundation Module Schema
-- Covers: A-01 Company · A-02 Building · A-03 Apartment · A-04 Tenant
--         A-05 Rental Agreement · A-06 Utility Meters · A-07 Meter Readings
--
-- PostgreSQL 16+  |  UUID-first  |  Multi-tenant  |  Audit-complete
-- Generated: June 2025
-- =============================================================================

-- ---------------------------------------------------------------------------
-- EXTENSIONS
-- ---------------------------------------------------------------------------
CREATE EXTENSION IF NOT EXISTS "pgcrypto";      -- gen_random_uuid()
CREATE EXTENSION IF NOT EXISTS "pg_trgm";       -- trigram indexes for search
CREATE EXTENSION IF NOT EXISTS "btree_gin";     -- composite GIN indexes

-- ---------------------------------------------------------------------------
-- CUSTOM ENUM TYPES
-- ---------------------------------------------------------------------------

-- Company
CREATE TYPE company_status        AS ENUM ('ACTIVE', 'SUSPENDED', 'CANCELLED');
CREATE TYPE subscription_plan     AS ENUM ('STARTER', 'PROFESSIONAL', 'ENTERPRISE');

-- Building
CREATE TYPE building_type         AS ENUM ('RESIDENTIAL', 'COMMERCIAL', 'MIXED_USE', 'INDUSTRIAL');
CREATE TYPE building_status       AS ENUM ('ACTIVE', 'UNDER_CONSTRUCTION', 'INACTIVE', 'ARCHIVED');

-- Apartment / Unit
CREATE TYPE apartment_type        AS ENUM ('STUDIO', 'ONE_BED', 'TWO_BED', 'THREE_BED', 'FOUR_BED_PLUS', 'PENTHOUSE', 'COMMERCIAL_UNIT', 'SHOP', 'OFFICE', 'WAREHOUSE');
CREATE TYPE availability_status   AS ENUM ('AVAILABLE', 'RESERVED', 'RENTAL_ACTIVE', 'UNDER_CONTRACT', 'SOLD', 'MAINTENANCE', 'INACTIVE');
CREATE TYPE furnishing_status     AS ENUM ('UNFURNISHED', 'SEMI_FURNISHED', 'FULLY_FURNISHED');

-- Tenant
CREATE TYPE tenant_status         AS ENUM ('ACTIVE', 'INACTIVE', 'BLACKLISTED');
CREATE TYPE id_document_type      AS ENUM ('NATIONAL_ID', 'PASSPORT', 'DRIVING_LICENSE', 'ALIEN_CARD', 'OTHER');
CREATE TYPE tenant_type           AS ENUM ('INDIVIDUAL', 'CORPORATE');

-- Rental Agreement
CREATE TYPE agreement_status      AS ENUM ('DRAFT', 'PENDING', 'ACTIVE', 'TERMINATED', 'EXPIRED');
CREATE TYPE rent_frequency        AS ENUM ('MONTHLY', 'QUARTERLY', 'BIANNUAL', 'ANNUAL');
CREATE TYPE termination_reason    AS ENUM ('TENANT_REQUEST', 'LANDLORD_REQUEST', 'NON_PAYMENT', 'BREACH_OF_CONTRACT', 'MUTUAL_AGREEMENT', 'EXPIRY', 'OTHER');

-- Meter
CREATE TYPE utility_type          AS ENUM ('ELECTRICITY', 'WATER', 'GAS', 'INTERNET', 'PARKING', 'WASTE', 'OTHER');
CREATE TYPE meter_status          AS ENUM ('ACTIVE', 'INACTIVE', 'FAULTY', 'REPLACED');
CREATE TYPE meter_ownership       AS ENUM ('BUILDING', 'APARTMENT', 'SHARED');

-- Meter Reading
CREATE TYPE reading_status        AS ENUM ('DRAFT', 'ANOMALY_FLAGGED', 'ANOMALY_ACKNOWLEDGED', 'APPROVED', 'REJECTED');
CREATE TYPE reading_entry_method  AS ENUM ('MANUAL', 'AUTOMATED', 'ESTIMATED');
CREATE TYPE anomaly_type          AS ENUM ('HIGH_CONSUMPTION', 'ZERO_CONSUMPTION', 'NEGATIVE_CONSUMPTION', 'EXCEEDS_THRESHOLD', 'METER_ROLLOVER');

-- Users / Auth
CREATE TYPE user_role             AS ENUM ('SUPERADMIN', 'ADMIN', 'MANAGER', 'SUPERVISOR', 'ACCOUNTANT', 'TECHNICIAN', 'VIEWER');
CREATE TYPE user_status           AS ENUM ('ACTIVE', 'INACTIVE', 'INVITED', 'SUSPENDED');


-- =============================================================================
-- BLOCK 1 — PLATFORM USERS (pre-tenant, used in auth + audit)
-- =============================================================================

CREATE TABLE users (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    email               VARCHAR(255)    NOT NULL,
    email_verified_at   TIMESTAMPTZ,
    password_hash       TEXT            NOT NULL,
    full_name           VARCHAR(255)    NOT NULL,
    phone               VARCHAR(30),
    avatar_url          TEXT,
    status              user_status     NOT NULL DEFAULT 'INVITED',
    last_login_at       TIMESTAMPTZ,
    last_login_ip       INET,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_users_email UNIQUE (email)
);

COMMENT ON TABLE  users                  IS 'Platform-level user accounts. One user can belong to multiple companies.';
COMMENT ON COLUMN users.password_hash    IS 'Bcrypt/Argon2 hash — never store plaintext.';
COMMENT ON COLUMN users.last_login_ip    IS 'INET type supports both IPv4 and IPv6.';

CREATE INDEX idx_users_email  ON users (email);
CREATE INDEX idx_users_status ON users (status);


-- =============================================================================
-- BLOCK 2 — A-01 COMPANY (Multi-Tenancy Root)
-- =============================================================================

CREATE TABLE companies (
    id                      UUID            PRIMARY KEY DEFAULT gen_random_uuid(),

    -- Identity
    name                    VARCHAR(255)    NOT NULL,
    legal_name              VARCHAR(255),
    registration_number     VARCHAR(100),
    tax_id                  VARCHAR(100),
    logo_url                TEXT,
    website                 TEXT,

    -- Contact
    email                   VARCHAR(255)    NOT NULL,
    phone                   VARCHAR(30),
    address_line1           VARCHAR(255),
    address_line2           VARCHAR(255),
    city                    VARCHAR(100),
    state_province          VARCHAR(100),
    postal_code             VARCHAR(20),
    country_code            CHAR(2)         NOT NULL DEFAULT 'SO', -- ISO 3166-1 alpha-2

    -- Operational settings
    operating_currency      CHAR(3)         NOT NULL DEFAULT 'USD', -- ISO 4217
    timezone                VARCHAR(64)     NOT NULL DEFAULT 'Africa/Mogadishu',
    fiscal_year_start_month SMALLINT        NOT NULL DEFAULT 1
                                            CHECK (fiscal_year_start_month BETWEEN 1 AND 12),
    date_format             VARCHAR(20)     NOT NULL DEFAULT 'DD/MM/YYYY',

    -- Financial thresholds
    auto_approval_threshold NUMERIC(14,4)   NOT NULL DEFAULT 0,      -- charges below this auto-approve
    min_down_payment_pct    NUMERIC(5,2)    NOT NULL DEFAULT 20.00   -- for installment sales
                                            CHECK (min_down_payment_pct BETWEEN 0 AND 100),
    late_payment_grace_days SMALLINT        NOT NULL DEFAULT 5,
    invoice_due_days        SMALLINT        NOT NULL DEFAULT 30,

    -- Subscription
    subscription_plan       subscription_plan NOT NULL DEFAULT 'STARTER',
    subscription_expires_at TIMESTAMPTZ,
    max_buildings           SMALLINT        NOT NULL DEFAULT 5,
    max_users               SMALLINT        NOT NULL DEFAULT 10,

    -- Status & audit
    status                  company_status  NOT NULL DEFAULT 'ACTIVE',
    onboarded_at            TIMESTAMPTZ,
    created_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    deleted_at              TIMESTAMPTZ,    -- soft delete

    CONSTRAINT uq_companies_email UNIQUE (email)
);

COMMENT ON TABLE  companies                        IS 'A-01: Root multi-tenancy entity. Every business record in the system carries company_id FK pointing here.';
COMMENT ON COLUMN companies.auto_approval_threshold IS 'Charges with gross_amount <= this value are auto-approved without manager review.';
COMMENT ON COLUMN companies.operating_currency      IS 'ISO 4217 currency code used for all financial display and calculations in this company.';

CREATE INDEX idx_companies_status     ON companies (status) WHERE deleted_at IS NULL;
CREATE INDEX idx_companies_deleted_at ON companies (deleted_at);


-- =============================================================================
-- Company ↔ User membership (many-to-many with role per company)
-- =============================================================================

CREATE TABLE company_users (
    id              UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id      UUID        NOT NULL REFERENCES companies (id) ON DELETE CASCADE,
    user_id         UUID        NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    role            user_role   NOT NULL DEFAULT 'VIEWER',
    is_primary      BOOLEAN     NOT NULL DEFAULT FALSE,  -- user's "home" company
    invited_by      UUID        REFERENCES users (id),
    accepted_at     TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_company_users UNIQUE (company_id, user_id)
);

COMMENT ON TABLE company_users IS 'Maps users to companies with role. A user may belong to multiple companies (e.g. freelance property manager).';

CREATE INDEX idx_company_users_company ON company_users (company_id);
CREATE INDEX idx_company_users_user    ON company_users (user_id);


-- =============================================================================
-- BLOCK 3 — A-02 BUILDING MANAGEMENT
-- =============================================================================

CREATE TABLE buildings (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id          UUID            NOT NULL REFERENCES companies (id) ON DELETE RESTRICT,

    -- Identity
    name                VARCHAR(255)    NOT NULL,
    code                VARCHAR(50)     NOT NULL,   -- short internal reference e.g. BLD-001
    building_type       building_type   NOT NULL DEFAULT 'RESIDENTIAL',
    status              building_status NOT NULL DEFAULT 'ACTIVE',

    -- Location
    address_line1       VARCHAR(255)    NOT NULL,
    address_line2       VARCHAR(255),
    district            VARCHAR(100),
    city                VARCHAR(100)    NOT NULL,
    state_province      VARCHAR(100),
    postal_code         VARCHAR(20),
    country_code        CHAR(2)         NOT NULL DEFAULT 'SO',
    latitude            NUMERIC(10,7),
    longitude           NUMERIC(10,7),
    google_place_id     VARCHAR(255),

    -- Physical
    total_floors        SMALLINT        NOT NULL DEFAULT 1 CHECK (total_floors > 0),
    year_built          SMALLINT        CHECK (year_built > 1800 AND year_built <= EXTRACT(YEAR FROM NOW())::INT + 5),
    total_area_sqm      NUMERIC(10,2),
    plot_area_sqm       NUMERIC(10,2),
    parking_spaces      SMALLINT        NOT NULL DEFAULT 0,
    has_elevator        BOOLEAN         NOT NULL DEFAULT FALSE,
    has_generator       BOOLEAN         NOT NULL DEFAULT FALSE,
    has_borehole        BOOLEAN         NOT NULL DEFAULT FALSE,
    has_security        BOOLEAN         NOT NULL DEFAULT FALSE,

    -- Financial
    operating_currency  CHAR(3)         NOT NULL DEFAULT 'USD',
    timezone            VARCHAR(64)     NOT NULL DEFAULT 'Africa/Mogadishu',

    -- Media & docs
    cover_image_url     TEXT,
    documents           JSONB           NOT NULL DEFAULT '[]',  -- [{type, url, uploaded_at}]

    -- Audit
    created_by          UUID            REFERENCES users (id),
    updated_by          UUID            REFERENCES users (id),
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ,

    CONSTRAINT uq_buildings_company_code UNIQUE (company_id, code)
);

COMMENT ON TABLE  buildings             IS 'A-02: Physical building owned/managed by a company.';
COMMENT ON COLUMN buildings.code        IS 'Human-readable short code unique within the company. Used in invoice references.';
COMMENT ON COLUMN buildings.documents   IS 'JSONB array of document metadata. Binary stored in object storage (S3 / R2).';

CREATE INDEX idx_buildings_company        ON buildings (company_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_buildings_status         ON buildings (company_id, status) WHERE deleted_at IS NULL;
CREATE INDEX idx_buildings_location       ON buildings USING GIST (point(longitude, latitude)) WHERE deleted_at IS NULL;
CREATE INDEX idx_buildings_deleted_at     ON buildings (deleted_at);
CREATE INDEX idx_buildings_name_trgm      ON buildings USING GIN (name gin_trgm_ops);


-- =============================================================================
-- BLOCK 4 — A-03 APARTMENT / UNIT MANAGEMENT
-- =============================================================================

CREATE TABLE apartments (
    id                      UUID                PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id              UUID                NOT NULL REFERENCES companies (id) ON DELETE RESTRICT,
    building_id             UUID                NOT NULL REFERENCES buildings (id) ON DELETE RESTRICT,

    -- Identity
    unit_number             VARCHAR(50)         NOT NULL,   -- e.g. "A101", "3B", "PH-1"
    unit_name               VARCHAR(100),                   -- optional human name
    floor_number            SMALLINT            NOT NULL DEFAULT 1,

    -- Classification
    apartment_type          apartment_type      NOT NULL DEFAULT 'ONE_BED',
    furnishing_status       furnishing_status   NOT NULL DEFAULT 'UNFURNISHED',
    availability_status     availability_status NOT NULL DEFAULT 'AVAILABLE',

    -- Physical
    bedrooms                SMALLINT            NOT NULL DEFAULT 1 CHECK (bedrooms >= 0),
    bathrooms               SMALLINT            NOT NULL DEFAULT 1 CHECK (bathrooms >= 0),
    area_sqm                NUMERIC(8,2),
    balcony_area_sqm        NUMERIC(8,2),
    has_parking             BOOLEAN             NOT NULL DEFAULT FALSE,
    parking_spot            VARCHAR(20),

    -- Pricing (list prices — actual billing via ChargeModel)
    listed_rent_amount      NUMERIC(14,4),      -- display/marketing price only
    listed_sale_price       NUMERIC(14,4),      -- display/marketing price only
    currency                CHAR(3)             NOT NULL DEFAULT 'USD',

    -- Availability tracking
    last_vacated_at         TIMESTAMPTZ,
    last_occupied_at        TIMESTAMPTZ,

    -- Media
    cover_image_url         TEXT,
    images                  JSONB               NOT NULL DEFAULT '[]',
    floor_plan_url          TEXT,
    documents               JSONB               NOT NULL DEFAULT '[]',
    amenities               JSONB               NOT NULL DEFAULT '[]',  -- ["AC","heater","dishwasher"]

    -- Optimistic lock for concurrent reservation
    lock_version            INTEGER             NOT NULL DEFAULT 0,

    -- Audit
    created_by              UUID                REFERENCES users (id),
    updated_by              UUID                REFERENCES users (id),
    created_at              TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    deleted_at              TIMESTAMPTZ,

    CONSTRAINT uq_apartments_building_unit UNIQUE (building_id, unit_number)
);

COMMENT ON TABLE  apartments                   IS 'A-03: Individual rentable or saleable unit within a building.';
COMMENT ON COLUMN apartments.availability_status IS 'Authoritative availability state. Single source of truth — no module duplicates this field.';
COMMENT ON COLUMN apartments.lock_version        IS 'Optimistic concurrency lock — incremented on every status change. Prevents concurrent reservations.';
COMMENT ON COLUMN apartments.listed_rent_amount  IS 'Marketing display price only. Actual billing always comes from ChargeModel.';

CREATE INDEX idx_apartments_company          ON apartments (company_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_apartments_building         ON apartments (building_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_apartments_status           ON apartments (company_id, availability_status) WHERE deleted_at IS NULL;
CREATE INDEX idx_apartments_type             ON apartments (company_id, apartment_type) WHERE deleted_at IS NULL;
CREATE INDEX idx_apartments_floor            ON apartments (building_id, floor_number) WHERE deleted_at IS NULL;
CREATE INDEX idx_apartments_deleted_at       ON apartments (deleted_at);
CREATE INDEX idx_apartments_unit_number_trgm ON apartments USING GIN (unit_number gin_trgm_ops);


-- =============================================================================
-- BLOCK 5 — A-04 TENANT PROFILES
-- =============================================================================

CREATE TABLE tenants (
    id                      UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id              UUID            NOT NULL REFERENCES companies (id) ON DELETE RESTRICT,

    -- Type
    tenant_type             tenant_type     NOT NULL DEFAULT 'INDIVIDUAL',

    -- Individual fields
    first_name              VARCHAR(100),
    last_name               VARCHAR(100),
    date_of_birth           DATE,
    gender                  VARCHAR(20),

    -- Corporate fields
    company_name            VARCHAR(255),
    company_reg_number      VARCHAR(100),

    -- Contact
    primary_phone           VARCHAR(30)     NOT NULL,
    secondary_phone         VARCHAR(30),
    email                   VARCHAR(255),
    whatsapp                VARCHAR(30),

    -- Identification
    id_document_type        id_document_type,
    id_document_number      VARCHAR(100),
    id_document_expiry      DATE,
    id_document_url         TEXT,           -- S3 / R2 path to scanned doc

    -- Address (prior/permanent)
    permanent_address       TEXT,
    permanent_city          VARCHAR(100),
    permanent_country_code  CHAR(2),

    -- Emergency contact
    emergency_name          VARCHAR(200),
    emergency_phone         VARCHAR(30),
    emergency_relation      VARCHAR(50),

    -- Employment
    employer_name           VARCHAR(255),
    employer_phone          VARCHAR(30),
    occupation              VARCHAR(100),
    monthly_income          NUMERIC(14,4),
    income_currency         CHAR(3)         DEFAULT 'USD',

    -- Status & risk
    status                  tenant_status   NOT NULL DEFAULT 'ACTIVE',
    blacklist_reason        TEXT,
    blacklisted_at          TIMESTAMPTZ,
    blacklisted_by          UUID            REFERENCES users (id),

    -- Credit notes
    credit_balance          NUMERIC(14,4)   NOT NULL DEFAULT 0.0000
                                            CHECK (credit_balance >= 0),
    credit_currency         CHAR(3)         NOT NULL DEFAULT 'USD',

    -- Media
    avatar_url              TEXT,
    notes                   TEXT,

    -- Audit
    created_by              UUID            REFERENCES users (id),
    updated_by              UUID            REFERENCES users (id),
    created_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    deleted_at              TIMESTAMPTZ
);

COMMENT ON TABLE  tenants               IS 'A-04: Person or corporate entity that rents a unit. Distinct from Buyer (Sales domain).';
COMMENT ON COLUMN tenants.credit_balance IS 'Overpayment credit held on account. Applied to future invoices, never auto-refunded.';
COMMENT ON COLUMN tenants.tenant_type    IS 'INDIVIDUAL: personal lease. CORPORATE: business tenancy with company_name required.';

CREATE INDEX idx_tenants_company         ON tenants (company_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_tenants_status          ON tenants (company_id, status) WHERE deleted_at IS NULL;
CREATE INDEX idx_tenants_email           ON tenants (company_id, email) WHERE deleted_at IS NULL AND email IS NOT NULL;
CREATE INDEX idx_tenants_phone           ON tenants (company_id, primary_phone) WHERE deleted_at IS NULL;
CREATE INDEX idx_tenants_id_document     ON tenants (company_id, id_document_number) WHERE deleted_at IS NULL AND id_document_number IS NOT NULL;
CREATE INDEX idx_tenants_deleted_at      ON tenants (deleted_at);
CREATE INDEX idx_tenants_name_trgm       ON tenants USING GIN ((first_name || ' ' || COALESCE(last_name,'')) gin_trgm_ops);
CREATE INDEX idx_tenants_company_name_trgm ON tenants USING GIN (company_name gin_trgm_ops) WHERE company_name IS NOT NULL;

-- Enforce: corporate tenants must have company_name; individuals must have first_name
ALTER TABLE tenants ADD CONSTRAINT chk_tenant_type_fields CHECK (
    (tenant_type = 'CORPORATE' AND company_name IS NOT NULL) OR
    (tenant_type = 'INDIVIDUAL' AND first_name  IS NOT NULL)
);


-- =============================================================================
-- BLOCK 6 — A-05 RENTAL AGREEMENTS
-- =============================================================================

CREATE TABLE rental_agreements (
    id                      UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id              UUID            NOT NULL REFERENCES companies (id) ON DELETE RESTRICT,
    apartment_id            UUID            NOT NULL REFERENCES apartments (id) ON DELETE RESTRICT,
    tenant_id               UUID            NOT NULL REFERENCES tenants (id) ON DELETE RESTRICT,

    -- Agreement reference
    agreement_number        VARCHAR(100)    NOT NULL,   -- e.g. AGR-2025-00042
    status                  agreement_status NOT NULL DEFAULT 'DRAFT',

    -- Terms
    start_date              DATE            NOT NULL,
    end_date                DATE            NOT NULL,
    monthly_rent            NUMERIC(14,4)   NOT NULL CHECK (monthly_rent > 0),
    rent_currency           CHAR(3)         NOT NULL DEFAULT 'USD',
    rent_frequency          rent_frequency  NOT NULL DEFAULT 'MONTHLY',
    security_deposit        NUMERIC(14,4)   NOT NULL DEFAULT 0.0000,
    deposit_paid_at         TIMESTAMPTZ,
    deposit_payment_ref     VARCHAR(255),

    -- Billing configuration
    billing_day_of_month    SMALLINT        NOT NULL DEFAULT 1
                                            CHECK (billing_day_of_month BETWEEN 1 AND 28),
    payment_due_days        SMALLINT        NOT NULL DEFAULT 5,     -- days after billing date
    late_fee_grace_days     SMALLINT        NOT NULL DEFAULT 3,

    -- Renewal
    auto_renew              BOOLEAN         NOT NULL DEFAULT FALSE,
    renewal_notice_days     SMALLINT        NOT NULL DEFAULT 30,
    renewal_count           SMALLINT        NOT NULL DEFAULT 0,
    parent_agreement_id     UUID            REFERENCES rental_agreements (id),  -- link to previous agreement on renewal

    -- Termination
    termination_reason      termination_reason,
    termination_date        DATE,
    termination_notes       TEXT,
    early_termination_fee   NUMERIC(14,4)   DEFAULT 0.0000,

    -- Approval workflow
    submitted_at            TIMESTAMPTZ,
    submitted_by            UUID            REFERENCES users (id),
    approved_at             TIMESTAMPTZ,
    approved_by             UUID            REFERENCES users (id),
    activated_at            TIMESTAMPTZ,    -- when status became ACTIVE

    -- Documents
    contract_document_url   TEXT,
    documents               JSONB           NOT NULL DEFAULT '[]',

    -- Notes
    special_conditions      TEXT,
    internal_notes          TEXT,

    -- Audit
    created_by              UUID            REFERENCES users (id),
    updated_by              UUID            REFERENCES users (id),
    created_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    deleted_at              TIMESTAMPTZ,

    CONSTRAINT uq_agreement_number UNIQUE (company_id, agreement_number),
    CONSTRAINT chk_agreement_dates CHECK (end_date > start_date),
    CONSTRAINT chk_termination_date CHECK (
        termination_date IS NULL OR termination_date BETWEEN start_date AND end_date
    )
);

COMMENT ON TABLE  rental_agreements                  IS 'A-05: Governs the tenancy relationship. Status controls whether charges/invoices can be generated.';
COMMENT ON COLUMN rental_agreements.billing_day_of_month IS 'Day of month rent charge is generated. Capped at 28 to avoid Feb-29 issues.';
COMMENT ON COLUMN rental_agreements.parent_agreement_id  IS 'On renewal, the new agreement references the old one here. Builds audit chain.';

-- Only one ACTIVE agreement per apartment at a time
CREATE UNIQUE INDEX uq_active_agreement_per_apartment
    ON rental_agreements (apartment_id)
    WHERE status = 'ACTIVE' AND deleted_at IS NULL;

CREATE INDEX idx_ra_company          ON rental_agreements (company_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_ra_apartment        ON rental_agreements (apartment_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_ra_tenant           ON rental_agreements (tenant_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_ra_status           ON rental_agreements (company_id, status) WHERE deleted_at IS NULL;
CREATE INDEX idx_ra_dates            ON rental_agreements (start_date, end_date) WHERE deleted_at IS NULL;
CREATE INDEX idx_ra_billing_day      ON rental_agreements (billing_day_of_month, status) WHERE status = 'ACTIVE' AND deleted_at IS NULL;
CREATE INDEX idx_ra_auto_renew       ON rental_agreements (auto_renew, end_date) WHERE auto_renew = TRUE AND status = 'ACTIVE' AND deleted_at IS NULL;
CREATE INDEX idx_ra_deleted_at       ON rental_agreements (deleted_at);


-- Vacancy tracking — computed when gap exists between agreements
CREATE TABLE vacancy_records (
    id                  UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id          UUID        NOT NULL REFERENCES companies (id) ON DELETE RESTRICT,
    apartment_id        UUID        NOT NULL REFERENCES apartments (id) ON DELETE RESTRICT,
    vacated_at          TIMESTAMPTZ NOT NULL,    -- when previous agreement ended
    occupied_at         TIMESTAMPTZ,             -- when next agreement started (null = still vacant)
    vacancy_days        INTEGER GENERATED ALWAYS AS (
                            EXTRACT(DAY FROM (COALESCE(occupied_at, NOW()) - vacated_at))::INTEGER
                        ) STORED,
    prev_agreement_id   UUID        REFERENCES rental_agreements (id),
    next_agreement_id   UUID        REFERENCES rental_agreements (id),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE vacancy_records IS 'Auto-populated when an agreement expires or is terminated. Tracks revenue loss from vacant periods.';

CREATE INDEX idx_vacancy_apartment ON vacancy_records (apartment_id);
CREATE INDEX idx_vacancy_company   ON vacancy_records (company_id, vacated_at DESC);
CREATE INDEX idx_vacancy_open      ON vacancy_records (company_id) WHERE occupied_at IS NULL;


-- =============================================================================
-- BLOCK 7 — A-06 UTILITY METERS
-- =============================================================================

CREATE TABLE meters (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id          UUID            NOT NULL REFERENCES companies (id) ON DELETE RESTRICT,
    building_id         UUID            NOT NULL REFERENCES buildings (id) ON DELETE RESTRICT,
    apartment_id        UUID            REFERENCES apartments (id) ON DELETE SET NULL,  -- NULL = building-level/shared meter

    -- Identity
    meter_number        VARCHAR(100)    NOT NULL,   -- physical meter serial / utility company ID
    meter_name          VARCHAR(150),               -- human label e.g. "Block A Main Water"
    utility_type        utility_type    NOT NULL,
    meter_ownership     meter_ownership NOT NULL DEFAULT 'APARTMENT',
    status              meter_status    NOT NULL DEFAULT 'ACTIVE',

    -- Metering configuration
    unit_of_measure     VARCHAR(20)     NOT NULL DEFAULT 'kWh', -- kWh, m³, litres, etc.
    multiplier_factor   NUMERIC(10,4)   NOT NULL DEFAULT 1.0000
                                        CHECK (multiplier_factor > 0),
    max_reading_value   NUMERIC(14,4),  -- rollover point (e.g. 9999.99 for a 4-digit meter)
    anomaly_threshold_pct NUMERIC(5,2)  NOT NULL DEFAULT 200.00
                                        CHECK (anomaly_threshold_pct > 0),  -- % above avg triggers anomaly

    -- Initial reading
    initial_reading     NUMERIC(14,4)   NOT NULL DEFAULT 0.0000,
    initial_reading_date DATE           NOT NULL DEFAULT CURRENT_DATE,

    -- Last known reading (denormalized for fast anomaly detection)
    last_reading_value  NUMERIC(14,4),
    last_reading_date   DATE,

    -- Physical location
    location_description TEXT,

    -- Installation
    installed_at        DATE,
    installed_by        UUID            REFERENCES users (id),
    replaced_by_meter_id UUID           REFERENCES meters (id),  -- when meter is replaced

    -- Audit
    created_by          UUID            REFERENCES users (id),
    updated_by          UUID            REFERENCES users (id),
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ,

    CONSTRAINT uq_meter_number_company UNIQUE (company_id, meter_number),
    CONSTRAINT chk_meter_building_apartment CHECK (
        meter_ownership = 'BUILDING' OR apartment_id IS NOT NULL
    )
);

COMMENT ON TABLE  meters                      IS 'A-06: Utility meter installed at building or apartment level.';
COMMENT ON COLUMN meters.multiplier_factor     IS 'Applied to raw consumption before charge calculation. E.g. 1.0 for direct read, 2.0 for CT meter.';
COMMENT ON COLUMN meters.anomaly_threshold_pct IS 'If consumption deviates > N% from 12-month avg, flag as anomaly on reading submission.';
COMMENT ON COLUMN meters.max_reading_value     IS 'Meter rollover detection. If current < previous but within rollover range, recalculate consumption.';
COMMENT ON COLUMN meters.last_reading_value    IS 'Denormalized from latest APPROVED reading for fast anomaly detection on new submissions.';

CREATE INDEX idx_meters_company        ON meters (company_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_meters_building       ON meters (building_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_meters_apartment      ON meters (apartment_id) WHERE deleted_at IS NULL AND apartment_id IS NOT NULL;
CREATE INDEX idx_meters_type           ON meters (company_id, utility_type) WHERE deleted_at IS NULL;
CREATE INDEX idx_meters_status         ON meters (company_id, status) WHERE deleted_at IS NULL;
CREATE INDEX idx_meters_deleted_at     ON meters (deleted_at);
CREATE INDEX idx_meters_number_trgm    ON meters USING GIN (meter_number gin_trgm_ops);


-- =============================================================================
-- BLOCK 8 — A-07 METER READINGS & APPROVAL PIPELINE
-- =============================================================================

CREATE TABLE meter_readings (
    id                      UUID                PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id              UUID                NOT NULL REFERENCES companies (id) ON DELETE RESTRICT,
    meter_id                UUID                NOT NULL REFERENCES meters (id) ON DELETE RESTRICT,
    rental_agreement_id     UUID                REFERENCES rental_agreements (id) ON DELETE RESTRICT,

    -- Reading data
    reading_date            DATE                NOT NULL,
    current_reading         NUMERIC(14,4)       NOT NULL CHECK (current_reading >= 0),
    previous_reading        NUMERIC(14,4)       NOT NULL CHECK (previous_reading >= 0),
    consumption             NUMERIC(14,4)       GENERATED ALWAYS AS (
                                GREATEST(current_reading - previous_reading, 0)
                            ) STORED,
    -- Consumption after rollover adjustment (set by service if rollover detected)
    adjusted_consumption    NUMERIC(14,4),
    billable_consumption    NUMERIC(14,4) GENERATED ALWAYS AS (
                                COALESCE(adjusted_consumption, GREATEST(current_reading - previous_reading, 0))
                            ) STORED,
    unit_of_measure         VARCHAR(20)         NOT NULL DEFAULT 'kWh',

    -- Entry metadata
    entry_method            reading_entry_method NOT NULL DEFAULT 'MANUAL',
    entry_source            VARCHAR(100),       -- 'mobile_app', 'web', 'import', 'iot_device_id'
    reading_image_url       TEXT,               -- photo of meter face

    -- Billing period
    billing_period_start    DATE,               -- set on approval
    billing_period_end      DATE,               -- set on approval

    -- Status machine
    status                  reading_status      NOT NULL DEFAULT 'DRAFT',

    -- Anomaly detection
    is_anomaly              BOOLEAN             NOT NULL DEFAULT FALSE,
    anomaly_type            anomaly_type,
    anomaly_description     TEXT,
    anomaly_acknowledged_at TIMESTAMPTZ,
    anomaly_acknowledged_by UUID                REFERENCES users (id),
    anomaly_acknowledgement_note TEXT,

    -- Approval workflow
    submitted_at            TIMESTAMPTZ,
    submitted_by            UUID                REFERENCES users (id),
    approved_at             TIMESTAMPTZ,
    approved_by             UUID                REFERENCES users (id),
    rejected_at             TIMESTAMPTZ,
    rejected_by             UUID                REFERENCES users (id),
    rejection_reason        TEXT,

    -- Downstream processing (set by charge generation pipeline)
    charge_generated_at     TIMESTAMPTZ,
    charge_generation_error TEXT,

    -- Audit
    notes                   TEXT,
    created_by              UUID                REFERENCES users (id),
    updated_by              UUID                REFERENCES users (id),
    created_at              TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    deleted_at              TIMESTAMPTZ,

    CONSTRAINT chk_reading_date_order CHECK (reading_date >= (SELECT initial_reading_date FROM meters WHERE id = meter_id)),
    CONSTRAINT chk_billing_period     CHECK (
        (billing_period_start IS NULL AND billing_period_end IS NULL) OR
        (billing_period_start IS NOT NULL AND billing_period_end IS NOT NULL AND billing_period_end >= billing_period_start)
    )
);

COMMENT ON TABLE  meter_readings                  IS 'A-07: Individual meter reading submission. On APPROVED, fires event that triggers charge generation pipeline.';
COMMENT ON COLUMN meter_readings.consumption       IS 'Generated column: current - previous. Always >= 0 (GREATEST used).';
COMMENT ON COLUMN meter_readings.billable_consumption IS 'Consumption used for billing. Uses adjusted_consumption if rollover detected, else raw consumption.';
COMMENT ON COLUMN meter_readings.is_anomaly        IS 'Set by AnomalyDetectionService on submission. Does NOT block approval — creates advisory flag.';
COMMENT ON COLUMN meter_readings.charge_generated_at IS 'Timestamp set by GenerateChargeService after successful charge creation. Used to detect pipeline failures.';

-- Prevent duplicate reading for same meter on same date
CREATE UNIQUE INDEX uq_meter_reading_per_date
    ON meter_readings (meter_id, reading_date)
    WHERE status != 'REJECTED' AND deleted_at IS NULL;

CREATE INDEX idx_mr_company          ON meter_readings (company_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_mr_meter            ON meter_readings (meter_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_mr_agreement        ON meter_readings (rental_agreement_id) WHERE deleted_at IS NULL AND rental_agreement_id IS NOT NULL;
CREATE INDEX idx_mr_status           ON meter_readings (company_id, status) WHERE deleted_at IS NULL;
CREATE INDEX idx_mr_date             ON meter_readings (meter_id, reading_date DESC) WHERE deleted_at IS NULL;
CREATE INDEX idx_mr_anomaly          ON meter_readings (company_id, is_anomaly) WHERE is_anomaly = TRUE AND deleted_at IS NULL;
CREATE INDEX idx_mr_pending_charges  ON meter_readings (company_id) WHERE status = 'APPROVED' AND charge_generated_at IS NULL AND deleted_at IS NULL;
CREATE INDEX idx_mr_billing_period   ON meter_readings (company_id, billing_period_start, billing_period_end) WHERE deleted_at IS NULL;
CREATE INDEX idx_mr_deleted_at       ON meter_readings (deleted_at);


-- =============================================================================
-- AUDIT & ACTIVITY INFRASTRUCTURE
-- =============================================================================

-- Generic audit log — append-only, covers all tables
CREATE TABLE audit_logs (
    id              BIGSERIAL       PRIMARY KEY,  -- sequential int for audit — NOT a business key
    company_id      UUID            REFERENCES companies (id) ON DELETE SET NULL,
    user_id         UUID            REFERENCES users (id) ON DELETE SET NULL,
    table_name      VARCHAR(100)    NOT NULL,
    record_id       UUID            NOT NULL,
    action          VARCHAR(20)     NOT NULL,     -- INSERT, UPDATE, DELETE, STATUS_CHANGE, APPROVE, VOID
    old_values      JSONB,
    new_values      JSONB,
    changed_fields  TEXT[],                       -- list of changed column names (UPDATE only)
    ip_address      INET,
    user_agent      TEXT,
    request_id      UUID,                         -- correlates with API gateway request ID
    occurred_at     TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE  audit_logs         IS 'Immutable append-only audit trail for all business record mutations. Never update or delete rows here.';
COMMENT ON COLUMN audit_logs.id      IS 'BIGSERIAL (not UUID) — audit logs are sequential by design for forensic investigation.';

CREATE INDEX idx_audit_company    ON audit_logs (company_id, occurred_at DESC);
CREATE INDEX idx_audit_record     ON audit_logs (table_name, record_id, occurred_at DESC);
CREATE INDEX idx_audit_user       ON audit_logs (user_id, occurred_at DESC);
CREATE INDEX idx_audit_action     ON audit_logs (action, occurred_at DESC);
-- Partition hint: for high-volume production, partition audit_logs by occurred_at RANGE monthly.


-- State transition history — detailed status machine log per record
CREATE TABLE status_transitions (
    id              UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id      UUID        REFERENCES companies (id) ON DELETE SET NULL,
    table_name      VARCHAR(100) NOT NULL,
    record_id       UUID        NOT NULL,
    from_status     VARCHAR(50),    -- NULL for initial status set
    to_status       VARCHAR(50)     NOT NULL,
    transitioned_by UUID        REFERENCES users (id) ON DELETE SET NULL,
    reason          TEXT,
    metadata        JSONB,
    transitioned_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE status_transitions IS 'Dedicated log for all state machine transitions. Provides a clean timeline view separate from the full audit log.';

CREATE INDEX idx_st_record    ON status_transitions (table_name, record_id, transitioned_at DESC);
CREATE INDEX idx_st_company   ON status_transitions (company_id, transitioned_at DESC);


-- =============================================================================
-- ROW-LEVEL SECURITY POLICIES (PostgreSQL RLS)
-- =============================================================================
-- RLS is a defence-in-depth layer ON TOP of Laravel's CompanyScope.
-- It ensures no raw query (psql, data tools, bugs) can leak cross-tenant data.

ALTER TABLE buildings          ENABLE ROW LEVEL SECURITY;
ALTER TABLE apartments         ENABLE ROW LEVEL SECURITY;
ALTER TABLE tenants            ENABLE ROW LEVEL SECURITY;
ALTER TABLE rental_agreements  ENABLE ROW LEVEL SECURITY;
ALTER TABLE meters             ENABLE ROW LEVEL SECURITY;
ALTER TABLE meter_readings     ENABLE ROW LEVEL SECURITY;
ALTER TABLE vacancy_records    ENABLE ROW LEVEL SECURITY;

-- Laravel application connects as 'app_user'.
-- The session variable 'app.current_company_id' is set by Laravel middleware on each connection.
-- SUPERUSER bypass allows migrations and admin tooling to run without RLS.

CREATE POLICY tenant_isolation_buildings
    ON buildings FOR ALL TO app_user
    USING (company_id = current_setting('app.current_company_id', TRUE)::UUID);

CREATE POLICY tenant_isolation_apartments
    ON apartments FOR ALL TO app_user
    USING (company_id = current_setting('app.current_company_id', TRUE)::UUID);

CREATE POLICY tenant_isolation_tenants
    ON tenants FOR ALL TO app_user
    USING (company_id = current_setting('app.current_company_id', TRUE)::UUID);

CREATE POLICY tenant_isolation_rental_agreements
    ON rental_agreements FOR ALL TO app_user
    USING (company_id = current_setting('app.current_company_id', TRUE)::UUID);

CREATE POLICY tenant_isolation_meters
    ON meters FOR ALL TO app_user
    USING (company_id = current_setting('app.current_company_id', TRUE)::UUID);

CREATE POLICY tenant_isolation_meter_readings
    ON meter_readings FOR ALL TO app_user
    USING (company_id = current_setting('app.current_company_id', TRUE)::UUID);

CREATE POLICY tenant_isolation_vacancy_records
    ON vacancy_records FOR ALL TO app_user
    USING (company_id = current_setting('app.current_company_id', TRUE)::UUID);

-- Note: companies and users tables are NOT RLS-restricted — the app layer
-- controls access based on company_users membership.


-- =============================================================================
-- TRIGGERS
-- =============================================================================

-- ── 1. updated_at auto-maintenance ──────────────────────────────────────────

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_users_updated_at              BEFORE UPDATE ON users              FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_companies_updated_at          BEFORE UPDATE ON companies          FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_company_users_updated_at      BEFORE UPDATE ON company_users      FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_buildings_updated_at          BEFORE UPDATE ON buildings          FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_apartments_updated_at         BEFORE UPDATE ON apartments         FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_tenants_updated_at            BEFORE UPDATE ON tenants            FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_rental_agreements_updated_at  BEFORE UPDATE ON rental_agreements  FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_meters_updated_at             BEFORE UPDATE ON meters             FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_meter_readings_updated_at     BEFORE UPDATE ON meter_readings     FOR EACH ROW EXECUTE FUNCTION set_updated_at();


-- ── 2. Apartment availability_status guard on status transition ──────────────
-- Prevents concurrent reservations using optimistic locking.

CREATE OR REPLACE FUNCTION check_apartment_lock_version()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
    -- Only increment lock_version on availability_status changes
    IF OLD.availability_status IS DISTINCT FROM NEW.availability_status THEN
        -- Caller must supply the expected lock_version; if mismatch → concurrent update
        IF NEW.lock_version != OLD.lock_version + 1 THEN
            RAISE EXCEPTION 'Concurrent modification detected on apartment %. Expected lock_version %, got %.',
                OLD.id, OLD.lock_version + 1, NEW.lock_version
                USING ERRCODE = '40001'; -- serialization_failure
        END IF;
    END IF;
    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_apartments_lock_version
    BEFORE UPDATE OF availability_status ON apartments
    FOR EACH ROW EXECUTE FUNCTION check_apartment_lock_version();


-- ── 3. Rental agreement status machine enforcement ──────────────────────────

CREATE OR REPLACE FUNCTION enforce_agreement_status_machine()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
DECLARE
    allowed_transitions TEXT[][] := ARRAY[
        ARRAY['DRAFT',   'PENDING'],
        ARRAY['DRAFT',   'ACTIVE'],     -- direct activation by admin
        ARRAY['PENDING', 'ACTIVE'],
        ARRAY['PENDING', 'DRAFT'],      -- send back to draft
        ARRAY['ACTIVE',  'TERMINATED'],
        ARRAY['ACTIVE',  'EXPIRED'],
        ARRAY['ACTIVE',  'PENDING']     -- re-review edge case
    ];
    transition TEXT[];
BEGIN
    IF OLD.status IS DISTINCT FROM NEW.status THEN
        FOREACH transition SLICE 1 IN ARRAY allowed_transitions LOOP
            IF transition[1] = OLD.status::TEXT AND transition[2] = NEW.status::TEXT THEN
                RETURN NEW;
            END IF;
        END LOOP;
        RAISE EXCEPTION 'Invalid rental agreement status transition: % → %. Record: %',
            OLD.status, NEW.status, OLD.id
            USING ERRCODE = 'P0001';
    END IF;
    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_agreement_status_machine
    BEFORE UPDATE OF status ON rental_agreements
    FOR EACH ROW EXECUTE FUNCTION enforce_agreement_status_machine();


-- ── 4. Meter reading status machine enforcement ──────────────────────────────

CREATE OR REPLACE FUNCTION enforce_reading_status_machine()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
DECLARE
    allowed TEXT[][] := ARRAY[
        ARRAY['DRAFT',              'ANOMALY_FLAGGED'],
        ARRAY['DRAFT',              'APPROVED'],           -- no anomaly path
        ARRAY['DRAFT',              'REJECTED'],
        ARRAY['ANOMALY_FLAGGED',    'ANOMALY_ACKNOWLEDGED'],
        ARRAY['ANOMALY_FLAGGED',    'REJECTED'],
        ARRAY['ANOMALY_ACKNOWLEDGED','APPROVED'],
        ARRAY['ANOMALY_ACKNOWLEDGED','REJECTED']
    ];
    t TEXT[];
BEGIN
    IF OLD.status IS DISTINCT FROM NEW.status THEN
        FOREACH t SLICE 1 IN ARRAY allowed LOOP
            IF t[1] = OLD.status::TEXT AND t[2] = NEW.status::TEXT THEN
                RETURN NEW;
            END IF;
        END LOOP;
        RAISE EXCEPTION 'Invalid meter reading status transition: % → %. Record: %',
            OLD.status, NEW.status, OLD.id
            USING ERRCODE = 'P0001';
    END IF;
    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_reading_status_machine
    BEFORE UPDATE OF status ON meter_readings
    FOR EACH ROW EXECUTE FUNCTION enforce_reading_status_machine();


-- ── 5. Immutability guard on APPROVED meter readings ─────────────────────────
-- Once APPROVED, financial fields cannot be altered.

CREATE OR REPLACE FUNCTION guard_approved_reading_immutability()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
    IF OLD.status = 'APPROVED' THEN
        IF (OLD.current_reading     IS DISTINCT FROM NEW.current_reading  OR
            OLD.previous_reading    IS DISTINCT FROM NEW.previous_reading  OR
            OLD.reading_date        IS DISTINCT FROM NEW.reading_date      OR
            OLD.meter_id            IS DISTINCT FROM NEW.meter_id          OR
            OLD.rental_agreement_id IS DISTINCT FROM NEW.rental_agreement_id) THEN
            RAISE EXCEPTION 'APPROVED meter reading % is immutable. Financial fields cannot be altered.',
                OLD.id USING ERRCODE = 'P0001';
        END IF;
    END IF;
    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_approved_reading_immutable
    BEFORE UPDATE ON meter_readings
    FOR EACH ROW EXECUTE FUNCTION guard_approved_reading_immutability();


-- ── 6. Sync meter last_reading on approval ───────────────────────────────────

CREATE OR REPLACE FUNCTION sync_meter_last_reading()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
    IF NEW.status = 'APPROVED' AND (OLD.status IS NULL OR OLD.status != 'APPROVED') THEN
        UPDATE meters
        SET    last_reading_value = NEW.current_reading,
               last_reading_date  = NEW.reading_date,
               updated_at         = NOW()
        WHERE  id = NEW.meter_id
          AND  (last_reading_date IS NULL OR last_reading_date <= NEW.reading_date);
    END IF;
    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_sync_meter_last_reading
    AFTER UPDATE OF status ON meter_readings
    FOR EACH ROW EXECUTE FUNCTION sync_meter_last_reading();


-- ── 7. Auto-populate vacancy_records when agreement expires/terminates ────────

CREATE OR REPLACE FUNCTION create_vacancy_on_agreement_end()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
    IF NEW.status IN ('EXPIRED', 'TERMINATED') AND OLD.status = 'ACTIVE' THEN
        INSERT INTO vacancy_records (company_id, apartment_id, vacated_at, prev_agreement_id)
        VALUES (NEW.company_id, NEW.apartment_id, NOW(), NEW.id);

        -- Release apartment back to AVAILABLE
        UPDATE apartments
        SET    availability_status = 'AVAILABLE',
               lock_version        = lock_version + 1,
               last_vacated_at     = NOW(),
               updated_at          = NOW()
        WHERE  id = NEW.apartment_id;
    END IF;
    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_vacancy_on_agreement_end
    AFTER UPDATE OF status ON rental_agreements
    FOR EACH ROW EXECUTE FUNCTION create_vacancy_on_agreement_end();


-- ── 8. Generic audit log trigger factory ─────────────────────────────────────

CREATE OR REPLACE FUNCTION write_audit_log()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
DECLARE
    v_company_id UUID;
    v_user_id    UUID;
    v_action     TEXT;
    v_changed    TEXT[];
    v_old        JSONB := NULL;
    v_new        JSONB := NULL;
BEGIN
    -- Resolve company_id from record (if column exists)
    BEGIN v_company_id := NEW.company_id; EXCEPTION WHEN OTHERS THEN v_company_id := NULL; END;
    BEGIN v_company_id := COALESCE(v_company_id, OLD.company_id); EXCEPTION WHEN OTHERS THEN NULL; END;

    -- Resolve current user from session setting (set by Laravel service provider)
    BEGIN
        v_user_id := current_setting('app.current_user_id', TRUE)::UUID;
    EXCEPTION WHEN OTHERS THEN
        v_user_id := NULL;
    END;

    IF    TG_OP = 'INSERT' THEN v_action := 'INSERT'; v_new := to_jsonb(NEW);
    ELSIF TG_OP = 'DELETE' THEN v_action := 'DELETE'; v_old := to_jsonb(OLD);
    ELSIF TG_OP = 'UPDATE' THEN
        v_action  := 'UPDATE';
        v_old     := to_jsonb(OLD);
        v_new     := to_jsonb(NEW);
        -- Build list of changed fields
        SELECT ARRAY_AGG(key)
        INTO   v_changed
        FROM   jsonb_each(v_new) AS n(key, val)
        WHERE  val IS DISTINCT FROM (v_old->key)
          AND  key NOT IN ('updated_at');  -- exclude noise
    END IF;

    INSERT INTO audit_logs (company_id, user_id, table_name, record_id, action,
                            old_values, new_values, changed_fields, occurred_at)
    VALUES (v_company_id, v_user_id, TG_TABLE_NAME,
            COALESCE((NEW.id)::UUID, (OLD.id)::UUID),
            v_action, v_old, v_new, v_changed, NOW());

    RETURN COALESCE(NEW, OLD);
END;
$$;

-- Attach audit triggers to all core tables
CREATE TRIGGER audit_companies
    AFTER INSERT OR UPDATE OR DELETE ON companies
    FOR EACH ROW EXECUTE FUNCTION write_audit_log();

CREATE TRIGGER audit_buildings
    AFTER INSERT OR UPDATE OR DELETE ON buildings
    FOR EACH ROW EXECUTE FUNCTION write_audit_log();

CREATE TRIGGER audit_apartments
    AFTER INSERT OR UPDATE OR DELETE ON apartments
    FOR EACH ROW EXECUTE FUNCTION write_audit_log();

CREATE TRIGGER audit_tenants
    AFTER INSERT OR UPDATE OR DELETE ON tenants
    FOR EACH ROW EXECUTE FUNCTION write_audit_log();

CREATE TRIGGER audit_rental_agreements
    AFTER INSERT OR UPDATE OR DELETE ON rental_agreements
    FOR EACH ROW EXECUTE FUNCTION write_audit_log();

CREATE TRIGGER audit_meters
    AFTER INSERT OR UPDATE OR DELETE ON meters
    FOR EACH ROW EXECUTE FUNCTION write_audit_log();

CREATE TRIGGER audit_meter_readings
    AFTER INSERT OR UPDATE OR DELETE ON meter_readings
    FOR EACH ROW EXECUTE FUNCTION write_audit_log();


-- =============================================================================
-- USEFUL VIEWS
-- =============================================================================

-- Active tenancy summary — used by dashboard and API resource
CREATE VIEW v_active_tenancies AS
SELECT
    ra.id               AS agreement_id,
    ra.company_id,
    ra.agreement_number,
    ra.status           AS agreement_status,
    ra.start_date,
    ra.end_date,
    ra.monthly_rent,
    ra.rent_currency,
    ra.billing_day_of_month,
    ra.auto_renew,
    b.id                AS building_id,
    b.name              AS building_name,
    b.code              AS building_code,
    a.id                AS apartment_id,
    a.unit_number,
    a.apartment_type,
    a.floor_number,
    t.id                AS tenant_id,
    t.tenant_type,
    CASE t.tenant_type
        WHEN 'INDIVIDUAL' THEN TRIM(t.first_name || ' ' || COALESCE(t.last_name, ''))
        ELSE t.company_name
    END                 AS tenant_name,
    t.primary_phone     AS tenant_phone,
    t.email             AS tenant_email,
    t.status            AS tenant_status
FROM   rental_agreements  ra
JOIN   apartments          a  ON a.id = ra.apartment_id
JOIN   buildings           b  ON b.id = a.building_id
JOIN   tenants             t  ON t.id = ra.tenant_id
WHERE  ra.status      = 'ACTIVE'
  AND  ra.deleted_at  IS NULL
  AND  a.deleted_at   IS NULL
  AND  b.deleted_at   IS NULL
  AND  t.deleted_at   IS NULL;

COMMENT ON VIEW v_active_tenancies IS 'Flat join of all currently ACTIVE rental agreements with building, apartment, and tenant details.';


-- Building occupancy snapshot
CREATE VIEW v_building_occupancy AS
SELECT
    b.id                                                                AS building_id,
    b.company_id,
    b.name                                                              AS building_name,
    b.code                                                              AS building_code,
    COUNT(a.id)                                                         AS total_units,
    COUNT(a.id) FILTER (WHERE a.availability_status = 'AVAILABLE')      AS available_units,
    COUNT(a.id) FILTER (WHERE a.availability_status = 'RENTAL_ACTIVE')  AS rented_units,
    COUNT(a.id) FILTER (WHERE a.availability_status = 'MAINTENANCE')    AS maintenance_units,
    COUNT(a.id) FILTER (WHERE a.availability_status = 'SOLD')           AS sold_units,
    ROUND(
        COUNT(a.id) FILTER (WHERE a.availability_status = 'RENTAL_ACTIVE')::NUMERIC
        / NULLIF(COUNT(a.id), 0) * 100, 2
    )                                                                   AS occupancy_pct
FROM   buildings  b
JOIN   apartments a ON a.building_id = b.id AND a.deleted_at IS NULL
WHERE  b.deleted_at IS NULL
GROUP BY b.id, b.company_id, b.name, b.code;

COMMENT ON VIEW v_building_occupancy IS 'Per-building occupancy statistics used by OccupancyReportService and dashboard widgets.';


-- Pending meter readings awaiting charge generation (charge pipeline health)
CREATE VIEW v_pending_charge_generation AS
SELECT
    mr.id               AS reading_id,
    mr.company_id,
    mr.meter_id,
    mr.reading_date,
    mr.billable_consumption,
    mr.approved_at,
    mr.approved_by,
    m.utility_type,
    m.unit_of_measure,
    a.id                AS apartment_id,
    a.unit_number,
    b.name              AS building_name,
    EXTRACT(EPOCH FROM (NOW() - mr.approved_at)) / 3600 AS hours_since_approval
FROM   meter_readings mr
JOIN   meters          m  ON m.id = mr.meter_id
JOIN   apartments      a  ON a.id = m.apartment_id
JOIN   buildings       b  ON b.id = a.building_id
WHERE  mr.status             = 'APPROVED'
  AND  mr.charge_generated_at IS NULL
  AND  mr.deleted_at          IS NULL
ORDER BY mr.approved_at ASC;

COMMENT ON VIEW v_pending_charge_generation IS 'Approved readings where charge generation has not yet completed. Used by GenerateChargeService and pipeline monitoring.';


-- =============================================================================
-- DATABASE ROLES & PERMISSIONS
-- =============================================================================

-- app_user: Laravel application role (restricted, RLS-bound)
DO $$ BEGIN
    CREATE ROLE app_user WITH LOGIN PASSWORD 'CHANGE_IN_PRODUCTION';
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

GRANT CONNECT ON DATABASE africaerp TO app_user;
GRANT USAGE   ON SCHEMA public       TO app_user;

GRANT SELECT, INSERT, UPDATE, DELETE ON
    users, companies, company_users,
    buildings, apartments, tenants,
    rental_agreements, vacancy_records,
    meters, meter_readings,
    audit_logs, status_transitions
TO app_user;

GRANT SELECT ON
    v_active_tenancies,
    v_building_occupancy,
    v_pending_charge_generation
TO app_user;

GRANT USAGE, SELECT ON SEQUENCE audit_logs_id_seq TO app_user;


-- readonly_user: analytics, reporting, BI tools
DO $$ BEGIN
    CREATE ROLE readonly_user WITH LOGIN PASSWORD 'CHANGE_IN_PRODUCTION';
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

GRANT CONNECT ON DATABASE africaerp TO readonly_user;
GRANT USAGE   ON SCHEMA public       TO readonly_user;
GRANT SELECT  ON ALL TABLES IN SCHEMA public TO readonly_user;


-- =============================================================================
-- SEED DATA — Charge Type defaults (injected on company creation)
-- =============================================================================
-- Note: This is reference seed data only. In production, seeding runs via
-- Laravel's CompanyCreatedListener — not directly against this schema.

-- These rows are the platform-level defaults; company_id is set at insert time.
-- Keeping them here as documentation of the required taxonomy.

/*
INSERT INTO charge_types (id, company_id, name, code, category, is_active) VALUES
    (gen_random_uuid(), :company_id, 'Monthly Rent',       'RENT',        'RENT',    TRUE),
    (gen_random_uuid(), :company_id, 'Electricity',        'ELECTRICITY', 'UTILITY', TRUE),
    (gen_random_uuid(), :company_id, 'Water',              'WATER',       'UTILITY', TRUE),
    (gen_random_uuid(), :company_id, 'Management Fee',     'MGMT_FEE',    'FEE',     TRUE),
    (gen_random_uuid(), :company_id, 'Service Charge',     'SERVICE',     'FEE',     TRUE),
    (gen_random_uuid(), :company_id, 'Late Payment Fee',   'LATE_FEE',    'FEE',     TRUE),
    (gen_random_uuid(), :company_id, 'Maintenance Levy',   'MAINTENANCE', 'FEE',     TRUE),
    (gen_random_uuid(), :company_id, 'Security Deposit',   'DEPOSIT',     'OTHER',   TRUE);
*/


-- =============================================================================
-- SCHEMA VERSION TRACKING
-- =============================================================================

CREATE TABLE schema_migrations (
    version         VARCHAR(50)  PRIMARY KEY,
    description     TEXT         NOT NULL,
    applied_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    applied_by      VARCHAR(100) NOT NULL DEFAULT CURRENT_USER,
    checksum        TEXT
);

INSERT INTO schema_migrations (version, description) VALUES
    ('1.0.0', 'Foundation Module — A-01 Company, A-02 Building, A-03 Apartment, A-04 Tenant, A-05 Rental Agreement, A-06 Meters, A-07 Meter Readings');


-- =============================================================================
-- END OF FOUNDATION MODULE SCHEMA
-- =============================================================================
