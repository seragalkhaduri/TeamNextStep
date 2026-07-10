-- =============================================================================
-- UIMP: Audit Logs DB-Level Immutability (SDD §3.4, FR-AUD-002)
-- =============================================================================
--
-- This script creates a restricted PostgreSQL user for the application's
-- connection that only has INSERT and SELECT on the audit_logs table.
-- No UPDATE, DELETE, or TRUNCATE is allowed.
--
-- Run this AFTER migrations, as a superuser / DB owner:
--   psql -U postgres -d uimp -f database/scripts/restrict_audit_logs.sql
--
-- Prerequisites:
--   - The 'uimp' database must exist
--   - The 'audit_logs' table must be created (run migrations first)
--   - The 'uimp_app' user must exist (created below if not)
-- =============================================================================

-- Step 1: Ensure the application user exists
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = 'uimp_app') THEN
        CREATE ROLE uimp_app WITH LOGIN PASSWORD 'secret';
    END IF;
END
$$;

-- Step 2: Grant normal access to all tables EXCEPT audit_logs
GRANT USAGE ON SCHEMA public TO uimp_app;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO uimp_app;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO uimp_app;

-- Step 3: Revoke UPDATE, DELETE, TRUNCATE on audit_logs specifically
REVOKE UPDATE, DELETE, TRUNCATE ON audit_logs FROM uimp_app;

-- Step 4: Ensure future tables also get normal access
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT ALL PRIVILEGES ON TABLES TO uimp_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT ALL PRIVILEGES ON SEQUENCES TO uimp_app;

-- Verify: This should show only INSERT, SELECT for audit_logs
-- SELECT privilege_type FROM information_schema.table_privileges
-- WHERE table_name = 'audit_logs' AND grantee = 'uimp_app';

-- =============================================================================
-- NOTE: The application-level defense (AuditLog model throwing on update/delete)
-- is the first line of protection. This DB-level restriction is defense-in-depth.
-- =============================================================================
