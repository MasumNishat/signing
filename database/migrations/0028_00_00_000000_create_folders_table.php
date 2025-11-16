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
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('folder_id', 100)->unique();

            $table->string('folder_name');
            $table->string('folder_type', 50)->nullable()->comment('normal, inbox, sentitems, draft, trash, recyclebin, custom');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_folder_id')->nullable()->constrained('folders')->nullOnDelete();

            // Folder metadata
            $table->string('filter')->nullable()->comment('Folder filter criteria');
            $table->string('uri')->nullable()->comment('Folder URI path');
            $table->integer('item_count')->default(0);
            $table->integer('sub_folder_count')->default(0);
            $table->boolean('has_sub_folders')->default(false);
            $table->text('error_details')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('folder_id');
            $table->index('owner_user_id');
            $table->index('parent_folder_id');
            $table->index('folder_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
