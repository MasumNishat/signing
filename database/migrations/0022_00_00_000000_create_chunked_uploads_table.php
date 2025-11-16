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
        Schema::create('chunked_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('chunked_upload_id', 100)->unique();

            $table->text('chunked_upload_uri')->nullable();
            $table->boolean('committed')->default(false);
            $table->timestamp('expires_date_time')->nullable();
            $table->bigInteger('max_chunk_size')->nullable();
            $table->integer('max_chunks')->nullable();
            $table->integer('total_parts')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('chunked_upload_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chunked_uploads');
    }
};
