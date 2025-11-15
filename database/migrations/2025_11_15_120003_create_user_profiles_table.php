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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Profile Information
            $table->string('display_name')->nullable();
            $table->string('profile_image_uri')->nullable();
            $table->text('biography')->nullable();

            // Professional Information
            $table->string('company')->nullable();
            $table->string('department')->nullable();
            $table->string('office_location')->nullable();

            // Contact Information
            $table->string('work_phone')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('home_phone')->nullable();
            $table->string('fax')->nullable();

            // Address Information
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();

            // Social Links
            $table->json('social_links')->nullable()->comment('LinkedIn, Twitter, etc.');

            // Profile Metadata
            $table->timestamp('profile_last_modified')->nullable();

            $table->timestamps();

            // Index
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
