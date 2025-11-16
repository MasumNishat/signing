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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Notification Settings
            $table->boolean('email_notifications')->default(true);
            $table->boolean('envelope_complete_notifications')->default(true);
            $table->boolean('envelope_declined_notifications')->default(true);
            $table->boolean('envelope_voided_notifications')->default(true);
            $table->boolean('comment_notifications')->default(true);

            // Display Settings
            $table->string('default_language', 10)->default('en');
            $table->string('default_timezone', 50)->default('UTC');
            $table->string('date_format', 50)->default('MM/dd/yyyy');
            $table->string('time_format', 50)->default('hh:mm a');

            // Signing Settings
            $table->boolean('attach_completed_envelope')->default(false);
            $table->boolean('self_sign_documents')->default(false);
            $table->string('default_signature_font')->nullable();

            // Envelope Settings
            $table->integer('envelope_expiration_days')->default(120);
            $table->integer('reminder_frequency_days')->default(0);
            $table->boolean('reminder_enabled')->default(false);

            // Privacy Settings
            $table->boolean('hide_from_directory')->default(false);
            $table->boolean('allow_delegate_access')->default(false);

            // API Settings
            $table->boolean('api_access_enabled')->default(false);
            $table->json('api_scopes')->nullable();

            $table->timestamps();

            // Index
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
