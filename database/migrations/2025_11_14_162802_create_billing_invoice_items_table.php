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
        Schema::create('billing_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('billing_invoices')->cascadeOnDelete();
            $table->string('charge_type', 100);
            $table->string('charge_name');

            $table->decimal('unit_price', 10, 2)->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_invoice_items');
    }
};
