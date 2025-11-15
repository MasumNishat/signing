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
        Schema::table('envelope_recipients', function (Blueprint $table) {
            // Consumer disclosure acceptance tracking
            $table->boolean('consumer_disclosure_accepted')->default(false)->after('note');
            $table->timestamp('consumer_disclosure_accepted_at')->nullable()->after('consumer_disclosure_accepted');
            $table->string('consumer_disclosure_ip_address', 45)->nullable()->after('consumer_disclosure_accepted_at');
            $table->text('consumer_disclosure_user_agent')->nullable()->after('consumer_disclosure_ip_address');

            $table->index(['envelope_id', 'consumer_disclosure_accepted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('envelope_recipients', function (Blueprint $table) {
            $table->dropColumn([
                'consumer_disclosure_accepted',
                'consumer_disclosure_accepted_at',
                'consumer_disclosure_ip_address',
                'consumer_disclosure_user_agent',
            ]);
        });
    }
};
