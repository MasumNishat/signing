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
        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('invoice_id', 100)->unique();
            $table->string('invoice_number', 100)->unique();

            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_exempt_amount', 12, 2)->default(0);
            $table->decimal('non_tax_exempt_amount', 12, 2)->default(0);

            $table->string('currency_code', 10)->default('USD');
            $table->boolean('pdf_available')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('invoice_id');
            $table->index('invoice_number');
            $table->index('invoice_date');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_invoices');
    }
};
