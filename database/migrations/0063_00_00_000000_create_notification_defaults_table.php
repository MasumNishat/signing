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
        Schema::create('notification_defaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->unique()->constrained('accounts')->cascadeOnDelete();

            $table->boolean('api_email_notifications')->default(true);
            $table->boolean('bulk_email_notifications')->default(true);
            $table->boolean('reminder_email_notifications')->default(true);

            $table->text('email_subject_template')->nullable();
            $table->text('email_body_template')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_defaults');
    }
};
