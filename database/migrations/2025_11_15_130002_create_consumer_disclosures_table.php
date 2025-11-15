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
        Schema::create('consumer_disclosures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();

            // Language Code
            $table->string('language_code', 10)->default('en');

            // Disclosure Content
            $table->text('esign_text')->nullable();
            $table->text('esign_agreement')->nullable();
            $table->text('withdrawal_text')->nullable();
            $table->text('acceptance_text')->nullable();

            // Configuration
            $table->boolean('allow_cd_withdraw')->default(false);
            $table->string('withdraw_address_line_1')->nullable();
            $table->string('withdraw_address_line_2')->nullable();
            $table->string('withdraw_city')->nullable();
            $table->string('withdraw_state')->nullable();
            $table->string('withdraw_postal_code')->nullable();
            $table->string('withdraw_country')->nullable();
            $table->string('withdraw_email')->nullable();
            $table->string('withdraw_phone')->nullable();
            $table->string('withdraw_fax')->nullable();

            // Metadata
            $table->boolean('use_brand')->default(false);
            $table->boolean('enable_esign')->default(true);
            $table->string('pdf_id')->nullable();

            $table->timestamps();

            // Unique constraint on account_id + language_code
            $table->unique(['account_id', 'language_code'], 'idx_account_language_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumer_disclosures');
    }
};
