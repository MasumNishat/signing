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
        Schema::table('envelopes', function (Blueprint $table) {
            $table->foreignId('bulk_batch_id')
                ->nullable()
                ->after('folder_id')
                ->constrained('bulk_send_batches')
                ->nullOnDelete();

            $table->index('bulk_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('envelopes', function (Blueprint $table) {
            $table->dropForeign(['bulk_batch_id']);
            $table->dropColumn('bulk_batch_id');
        });
    }
};
