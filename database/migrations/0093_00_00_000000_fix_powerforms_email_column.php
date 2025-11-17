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
        Schema::table('powerforms', function (Blueprint $table) {
            $table->renameColumn('email_body', 'email_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('powerforms', function (Blueprint $table) {
            $table->renameColumn('email_message', 'email_body');
        });
    }
};
