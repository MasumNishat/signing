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
        Schema::create('billing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('payment_id', 100)->unique();
            $table->foreignId('invoice_id')->nullable()->constrained('billing_invoices')->nullOnDelete();

            $table->date('payment_date');
            $table->decimal('payment_amount', 12, 2);
            $table->string('payment_method', 50)->nullable()->comment('credit_card, ach, wire');

            $table->string('status', 50)->default('pending')->comment('pending, completed, failed');
            $table->string('transaction_id')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('invoice_id');
            $table->index('payment_id');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_payments');
    }
};
