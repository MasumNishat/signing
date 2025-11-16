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
        // Notary sessions table
        Schema::create('notary_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('envelope_id')->nullable()->constrained('envelopes')->nullOnDelete();
            $table->string('signer_id');
            $table->string('notary_id');
            $table->string('session_type'); // in_person, remote_online
            $table->string('id_verification_method'); // knowledge_based, credential_analysis, remote_verification
            $table->string('jurisdiction', 10);
            $table->string('status', 50)->default('pending'); // pending, in_progress, completed, cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('video_recording_url')->nullable();
            $table->string('audio_recording_url')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['notary_id', 'created_at']);
        });

        // Notary journal entries table
        Schema::create('notary_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->string('envelope_id')->nullable();
            $table->string('signer_name');
            $table->string('signer_id_type'); // drivers_license, passport, state_id, military_id
            $table->string('signer_id_number')->nullable();
            $table->string('notary_id');
            $table->string('jurisdiction', 10);
            $table->string('notarization_type'); // acknowledgment, jurat, oath, affirmation
            $table->text('notary_seal_data')->nullable();
            $table->timestamp('notarized_at');
            $table->jsonb('metadata')->nullable();

            $table->timestamps();

            $table->index(['account_id', 'notarized_at']);
            $table->index(['notary_id', 'notarized_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notary_journal_entries');
        Schema::dropIfExists('notary_sessions');
    }
};
