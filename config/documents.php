<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Document Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for document storage and management.
    |
    */

    'storage' => [
        // Maximum file size in bytes (25MB default)
        'max_file_size' => env('MAX_DOCUMENT_SIZE', 25000000),

        // Maximum documents per envelope
        'max_documents_per_envelope' => env('MAX_DOCUMENTS_PER_ENVELOPE', 50),

        // Storage disk for documents
        'disk' => env('DOCUMENTS_DISK', 'documents'),

        // Temporary file lifetime (hours)
        'temp_file_lifetime' => env('TEMP_FILE_LIFETIME', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Conversion
    |--------------------------------------------------------------------------
    |
    | Configuration for document to PDF conversion.
    | Backends: 'libreoffice', 'mock' (development)
    |
    */

    'conversion' => [
        // Conversion backend
        'backend' => env('DOCUMENT_CONVERSION_BACKEND', 'mock'),

        // Queue conversion jobs (recommended for production)
        'use_queue' => env('DOCUMENT_CONVERSION_QUEUE', false),

        // Queue name for conversion jobs
        'queue_name' => 'document-processing',

        // Conversion timeout (seconds)
        'timeout' => env('DOCUMENT_CONVERSION_TIMEOUT', 300),

        // Retry failed conversions
        'max_retries' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    |
    | MIME types allowed for document uploads.
    |
    */

    'allowed_types' => explode(',', env('ALLOWED_DOCUMENT_TYPES', 'pdf,doc,docx')),

    'allowed_mime_types' => [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain',
        'html' => 'text/html',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Security
    |--------------------------------------------------------------------------
    |
    | Security settings for document handling.
    |
    */

    'security' => [
        // Encrypt documents at rest
        'encrypt_at_rest' => env('DOCUMENT_ENCRYPTION', true),

        // Add watermark to non-completed documents
        'watermark_drafts' => env('DOCUMENT_WATERMARK_DRAFTS', true),

        // Watermark text
        'watermark_text' => env('DOCUMENT_WATERMARK_TEXT', 'DRAFT - NOT FOR SIGNATURE'),

        // Log all document access
        'log_access' => env('DOCUMENT_LOG_ACCESS', true),

        // Temporary URL expiration (minutes)
        'temp_url_expiration' => env('DOCUMENT_TEMP_URL_EXPIRATION', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Processing
    |--------------------------------------------------------------------------
    |
    | Settings for document processing operations.
    |
    */

    'processing' => [
        // Generate thumbnails for documents
        'generate_thumbnails' => env('DOCUMENT_GENERATE_THUMBNAILS', true),

        // Thumbnail dimensions
        'thumbnail_width' => 200,
        'thumbnail_height' => 260,

        // Extract text from PDFs for search
        'extract_text' => env('DOCUMENT_EXTRACT_TEXT', true),

        // Generate document hash for integrity checking
        'generate_hash' => true,

        // Hash algorithm
        'hash_algorithm' => 'sha256',
    ],

    /*
    |--------------------------------------------------------------------------
    | CDN & Delivery
    |--------------------------------------------------------------------------
    |
    | Content delivery network configuration.
    |
    */

    'cdn' => [
        // Enable CDN for document delivery
        'enabled' => env('DOCUMENT_CDN_ENABLED', false),

        // CDN URL
        'url' => env('DOCUMENT_CDN_URL'),

        // Cache-Control header value
        'cache_control' => 'private, max-age=3600',
    ],

];
