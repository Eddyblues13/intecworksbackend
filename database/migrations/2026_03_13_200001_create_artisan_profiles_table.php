<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artisan_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('gov_id_url')->nullable();
            $table->json('skill_proof_urls')->default('[]');
            $table->string('verification_status')->default('draft');
            $table->text('verification_notes')->nullable();
            $table->json('skill_categories')->default('[]');
            $table->decimal('service_radius', 8, 2)->default(15.0);
            $table->decimal('trust_score', 5, 2)->default(0.0);
            $table->string('skill_badge')->nullable();
            $table->string('tier')->default('bronze');
            $table->integer('current_active_jobs')->default(0);
            $table->integer('current_scheduled_jobs')->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artisan_profiles');
    }
};
