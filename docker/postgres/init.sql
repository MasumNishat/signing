-- PostgreSQL Initialization Script for DocuSign Signing API
-- This script runs automatically when the PostgreSQL container is first created

-- Create extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Set default configuration
ALTER DATABASE signing_api SET timezone TO 'UTC';

-- Create additional schemas if needed
-- CREATE SCHEMA IF NOT EXISTS audit;
-- CREATE SCHEMA IF NOT EXISTS archive;

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE signing_api TO postgres;

-- Log initialization
DO $$
BEGIN
    RAISE NOTICE 'DocuSign Signing API database initialized successfully';
END $$;
