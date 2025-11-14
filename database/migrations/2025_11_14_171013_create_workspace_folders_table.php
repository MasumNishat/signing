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
        Schema::create('workspace_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('folder_id', 100)->unique();
            $table->foreignId('parent_folder_id')->nullable()->constrained('workspace_folders')->cascadeOnDelete();

            $table->string('folder_name');

            $table->timestamps();

            // Indexes
            $table->index('workspace_id');
            $table->index('parent_folder_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_folders');
    }
};
