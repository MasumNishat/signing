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
        Schema::create('workspace_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('workspace_folders')->cascadeOnDelete();
            $table->string('file_id', 100)->unique();

            $table->string('file_name');
            $table->text('file_uri')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('content_type', 100)->nullable();

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index('folder_id');
            $table->index('file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_files');
    }
};
