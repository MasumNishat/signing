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
            // PDF conversion tracking
            $table->string('pdf_path', 500)->nullable()->after('file_path');
            $table->string('file_hash', 64)->nullable()->after('pdf_path');
            $table->string('conversion_status', 20)->default('pending')->after('file_hash');
            $table->text('conversion_error')->nullable()->after('conversion_status');
            $table->timestamp('converted_at')->nullable()->after('conversion_error');

            // Add index for conversion status
            $table->index('conversion_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('envelope_documents', function (Blueprint $table) {
            $table->dropColumn([
                'pdf_path',
                'file_hash',
                'conversion_status',
                'conversion_error',
                'converted_at',
            ]);
        });
    }
};
