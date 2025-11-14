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
            $table->string('lang_code', 10)->default('en');

            // Company Info
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_phone', 50)->nullable();
            $table->string('company_email')->nullable();

            // PDF
            $table->string('pdf_id', 100)->nullable();
            $table->boolean('use_brand')->default(false);

            // Withdrawal Options
            $table->string('withdraw_address_line1')->nullable();
            $table->string('withdraw_address_line2')->nullable();
            $table->boolean('withdraw_by_email')->default(false);
            $table->boolean('withdraw_by_mail')->default(false);
            $table->boolean('withdraw_by_phone')->default(false);
            $table->text('withdraw_consequences')->nullable();

            // Disclosure Text
            $table->text('change_email')->nullable();
            $table->text('custom_disclosure_text')->nullable();
            $table->boolean('enable_esign')->default(true);
            $table->text('esign_agreement_text')->nullable();
            $table->text('esign_text')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('lang_code');
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
