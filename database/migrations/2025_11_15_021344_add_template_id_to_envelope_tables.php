<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds template_id support to envelope_documents, envelope_recipients,
     * envelope_tabs, and envelope_custom_fields tables, allowing these tables to be
     * used for both envelopes and templates.
     */
    public function up(): void
    {
        // Add template_id to envelope_documents
        Schema::table('envelope_documents', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->after('id')
                ->constrained('templates')->cascadeOnDelete();
            $table->foreignId('envelope_id')->nullable()->change();

            $table->index('template_id');
        });

        // Add template_id to envelope_recipients
        Schema::table('envelope_recipients', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->after('id')
                ->constrained('templates')->cascadeOnDelete();
            $table->foreignId('envelope_id')->nullable()->change();

            $table->index('template_id');
        });

        // Add template_id to envelope_tabs
        Schema::table('envelope_tabs', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->after('id')
                ->constrained('templates')->cascadeOnDelete();
            $table->foreignId('envelope_id')->nullable()->change();

            $table->index('template_id');
        });

        // Add template_id to envelope_custom_fields
        Schema::table('envelope_custom_fields', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->after('id')
                ->constrained('templates')->cascadeOnDelete();
            $table->foreignId('envelope_id')->nullable()->change();

            $table->index('template_id');
        });

        // Add check constraints (PostgreSQL)
        // Ensure either envelope_id OR template_id is present (but not both, not neither)
        DB::statement('ALTER TABLE envelope_documents ADD CONSTRAINT envelope_documents_parent_check CHECK ((envelope_id IS NOT NULL AND template_id IS NULL) OR (envelope_id IS NULL AND template_id IS NOT NULL))');
        DB::statement('ALTER TABLE envelope_recipients ADD CONSTRAINT envelope_recipients_parent_check CHECK ((envelope_id IS NOT NULL AND template_id IS NULL) OR (envelope_id IS NULL AND template_id IS NOT NULL))');
        DB::statement('ALTER TABLE envelope_tabs ADD CONSTRAINT envelope_tabs_parent_check CHECK ((envelope_id IS NOT NULL AND template_id IS NULL) OR (envelope_id IS NULL AND template_id IS NOT NULL))');
        DB::statement('ALTER TABLE envelope_custom_fields ADD CONSTRAINT envelope_custom_fields_parent_check CHECK ((envelope_id IS NOT NULL AND template_id IS NULL) OR (envelope_id IS NULL AND template_id IS NOT NULL))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop check constraints first
        DB::statement('ALTER TABLE envelope_documents DROP CONSTRAINT IF EXISTS envelope_documents_parent_check');
        DB::statement('ALTER TABLE envelope_recipients DROP CONSTRAINT IF EXISTS envelope_recipients_parent_check');
        DB::statement('ALTER TABLE envelope_tabs DROP CONSTRAINT IF EXISTS envelope_tabs_parent_check');
        DB::statement('ALTER TABLE envelope_custom_fields DROP CONSTRAINT IF EXISTS envelope_custom_fields_parent_check');

        // Remove template_id from envelope_documents
        Schema::table('envelope_documents', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn('template_id');
            $table->foreignId('envelope_id')->nullable(false)->change();
        });

        // Remove template_id from envelope_recipients
        Schema::table('envelope_recipients', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn('template_id');
            $table->foreignId('envelope_id')->nullable(false)->change();
        });

        // Remove template_id from envelope_tabs
        Schema::table('envelope_tabs', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn('template_id');
            $table->foreignId('envelope_id')->nullable(false)->change();
        });

        // Remove template_id from envelope_custom_fields
        Schema::table('envelope_custom_fields', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn('template_id');
            $table->foreignId('envelope_id')->nullable(false)->change();
        });
    }
};
