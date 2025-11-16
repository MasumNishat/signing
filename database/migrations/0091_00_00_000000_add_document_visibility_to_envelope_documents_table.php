<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('envelope_documents', function (Blueprint $table) {
            // Document visibility settings
            $table->jsonb('visible_to_recipients')->nullable()->after('uri');
            $table->string('document_rights', 20)->default('view')->after('visible_to_recipients');

            $table->index(['envelope_id', 'visible_to_recipients']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('envelope_documents', function (Blueprint $table) {
            $table->dropColumn(['visible_to_recipients', 'document_rights']);
        });
    }
};
