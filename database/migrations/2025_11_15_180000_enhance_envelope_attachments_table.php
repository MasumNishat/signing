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
        Schema::table('envelope_attachments', function (Blueprint $table) {
            // Rename 'data' to 'data_base64' for clarity
            $table->renameColumn('data', 'data_base64');

            // Add new fields
            $table->string('remote_url')->nullable()->after('data_base64');
            $table->string('file_extension', 10)->nullable()->after('remote_url');
            $table->string('name')->nullable()->after('file_extension');
            $table->string('access_control', 20)->default('all')->after('name')->comment('signer, sender, all');
            $table->string('display', 50)->nullable()->after('access_control')->comment('inline, modal');
            $table->bigInteger('size_bytes')->nullable()->after('display');

            // Add updated_at and soft deletes
            $table->timestamp('updated_at')->nullable()->after('created_at');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('envelope_attachments', function (Blueprint $table) {
            $table->dropColumn([
                'remote_url',
                'file_extension',
                'name',
                'access_control',
                'display',
                'size_bytes',
                'updated_at',
                'deleted_at',
            ]);

            $table->renameColumn('data_base64', 'data');
        });
    }
};
