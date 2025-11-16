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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('brand_id', 100)->unique();
            $table->string('brand_name');
            $table->string('brand_company')->nullable();

            $table->boolean('is_sending_default')->default(false);
            $table->boolean('is_signing_default')->default(false);
            $table->boolean('is_overriding_company_name')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('account_id');
            $table->index('brand_id');
            $table->index(['account_id', 'is_sending_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
