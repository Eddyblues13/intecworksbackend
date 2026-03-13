<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('service_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artisan_id')->constrained('users')->cascadeOnDelete();
            $table->text('findings');
            $table->json('images')->default('[]');
            $table->string('condition_rating')->default('fair'); // poor, fair, good
            $table->text('recommended_scope')->nullable();
            $table->boolean('requires_materials')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_reports');
    }
};
