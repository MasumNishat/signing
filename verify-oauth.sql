-- OAuth Configuration Verification
-- Run this in your PostgreSQL client (psql, pgAdmin, etc.)
-- Usage: psql -U postgres -d signing_api -f verify-oauth.sql

\echo '=== OAuth Tables Check ==='
\echo ''

-- Check if all required tables exist
SELECT
    tablename,
    CASE
        WHEN tablename IN ('oauth_auth_codes', 'oauth_access_tokens', 'oauth_refresh_tokens', 'oauth_clients', 'oauth_device_codes')
        THEN '✓ Required'
        ELSE 'Optional'
    END as status
FROM pg_tables
WHERE schemaname = 'public'
  AND tablename LIKE 'oauth_%'
ORDER BY tablename;

\echo ''
\echo '=== OAuth Clients ==='
\echo ''

-- List all OAuth clients
SELECT
    id,
    name,
    CASE WHEN secret IS NOT NULL THEN 'Yes' ELSE 'No (public)' END as has_secret,
    redirect,
    CASE WHEN personal_access_client THEN 'Yes' ELSE 'No' END as personal,
    CASE WHEN password_client THEN 'Yes' ELSE 'No' END as password,
    CASE WHEN revoked THEN 'Yes' ELSE 'No' END as revoked,
    created_at
FROM oauth_clients
ORDER BY id;

\echo ''
\echo '=== OAuth Client Count by Type ==='
\echo ''

-- Count clients by type
SELECT
    CASE
        WHEN personal_access_client THEN 'Personal Access'
        WHEN password_client THEN 'Password Grant'
        WHEN redirect = '' OR redirect IS NULL THEN 'Client Credentials'
        ELSE 'Authorization Code'
    END as client_type,
    COUNT(*) as count
FROM oauth_clients
WHERE revoked = false
GROUP BY client_type;

\echo ''
\echo '=== Recent Access Tokens (Last 10) ==='
\echo ''

-- Show recent tokens
SELECT
    id,
    user_id,
    client_id,
    name,
    LEFT(scopes::text, 50) as scopes,
    CASE WHEN revoked THEN 'Revoked' ELSE 'Active' END as status,
    expires_at,
    created_at
FROM oauth_access_tokens
ORDER BY created_at DESC
LIMIT 10;

\echo ''
\echo '=== Token Statistics ==='
\echo ''

-- Token statistics
SELECT
    COUNT(*) as total_tokens,
    COUNT(CASE WHEN revoked = false THEN 1 END) as active_tokens,
    COUNT(CASE WHEN revoked = true THEN 1 END) as revoked_tokens,
    COUNT(CASE WHEN expires_at > NOW() AND revoked = false THEN 1 END) as valid_tokens
FROM oauth_access_tokens;

\echo ''
\echo '=== Expected Setup ==='
\echo ''
\echo 'For a fresh install, you should have:'
\echo '  - 2 OAuth clients (Personal Access + Password Grant)'
\echo '  - 5 tables: oauth_auth_codes, oauth_access_tokens, oauth_refresh_tokens, oauth_clients, oauth_device_codes'
\echo '  - NOTE: oauth_personal_access_clients table does NOT exist in Passport 13.x (this is correct!)'
\echo ''
