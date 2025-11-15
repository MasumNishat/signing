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
        Schema::create('billing_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('charge_type', 100)->comment('seat, envelope, storage');
            $table->string('charge_name');

            $table->decimal('unit_price', 10, 2)->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('incremental_quantity')->default(0);

            $table->boolean('blocked')->default(false);
            $table->jsonb('chargeable_items')->nullable();
            $table->jsonb('discount_information')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('charge_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_charges');
    }
};
