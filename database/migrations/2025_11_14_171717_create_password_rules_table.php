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
        Schema::create('password_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->unique()->constrained('accounts')->cascadeOnDelete();

            $table->string('password_strength_type', 50)->default('medium')->comment('weak, medium, strong');
            $table->integer('minimum_password_length')->default(8);
            $table->integer('maximum_password_age_days')->default(90);
            $table->integer('minimum_password_age_days')->default(0);

            $table->boolean('password_include_digit')->default(true);
            $table->boolean('password_include_lower_case')->default(true);
            $table->boolean('password_include_upper_case')->default(true);
            $table->boolean('password_include_special_character')->default(true);
            $table->boolean('password_include_digit_or_special_character')->default(false);

            $table->integer('lockout_duration_minutes')->default(30);
            $table->string('lockout_duration_type', 50)->default('minutes');
            $table->integer('failed_login_attempts')->default(5);

            $table->integer('questions_required')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_rules');
    }
};
