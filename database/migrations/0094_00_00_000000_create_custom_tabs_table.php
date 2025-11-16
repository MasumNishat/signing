<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the custom_tabs table for managing reusable field templates.
     * Custom tabs are organization-level templates for commonly used fields
     * (e.g., Employee ID, Department, Manager Name).
     */
    public function up(): void
    {
        Schema::create('custom_tabs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('custom_tab_id', 100)->unique(); // UUID for API
            $table->string('name', 255); // Tab template name
            $table->string('type', 50); // Tab type (text, checkbox, date, etc.)
            $table->text('label')->nullable(); // Field label
            $table->boolean('required')->default(false);
            $table->text('value')->nullable(); // Default value
            $table->string('font', 50)->nullable();
            $table->integer('font_size')->nullable();
            $table->string('font_color', 20)->nullable(); // hex color
            $table->boolean('bold')->default(false);
            $table->boolean('italic')->default(false);
            $table->boolean('underline')->default(false);
            $table->integer('width')->nullable(); // in pixels
            $table->integer('height')->nullable(); // in pixels
            $table->string('validation_type', 50)->nullable(); // email, phone, etc.
            $table->string('validation_pattern', 255)->nullable(); // regex pattern
            $table->text('validation_message')->nullable();
            $table->text('tooltip')->nullable(); // Help text
            $table->json('list_items')->nullable(); // For list/dropdown types
            $table->boolean('shared')->default(false); // Shared across account
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes
            $table->index(['account_id', 'shared']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_tabs');
    }
};
