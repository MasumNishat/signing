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
        Schema::create('tab_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->unique()->constrained('accounts')->cascadeOnDelete();

            $table->boolean('text_tabs_enabled')->default(true);
            $table->boolean('radio_tabs_enabled')->default(true);
            $table->boolean('checkbox_tabs_enabled')->default(true);
            $table->boolean('list_tabs_enabled')->default(true);
            $table->boolean('approve_decline_tabs_enabled')->default(true);
            $table->boolean('note_tabs_enabled')->default(true);

            $table->boolean('data_field_regex_enabled')->default(false);
            $table->boolean('data_field_size_enabled')->default(false);
            $table->boolean('tab_location_enabled')->default(true);
            $table->boolean('tab_scale_enabled')->default(true);
            $table->boolean('tab_locking_enabled')->default(false);

            $table->boolean('saving_custom_tabs_enabled')->default(false);
            $table->boolean('tab_text_formatting_enabled')->default(true);
            $table->boolean('shared_custom_tabs_enabled')->default(false);
            $table->boolean('sender_to_change_tab_assignments_enabled')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tab_settings');
    }
};
