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
        Schema::create('connect_oauth_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->unique()->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('connect_id')->nullable()->constrained('connect_configurations')->nullOnDelete();

            $table->string('oauth_client_id')->nullable();
            $table->text('oauth_token_endpoint')->nullable();
            $table->text('oauth_authorization_endpoint')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connect_oauth_config');
    }
};
