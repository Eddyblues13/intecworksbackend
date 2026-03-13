<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('artisan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('subcategories')->nullOnDelete();
            $table->enum('job_type', ['fixedPrice', 'custom'])->default('custom');
            $table->text('description');
            $table->json('images')->nullable();
            $table->string('location');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->enum('status', [
                'created', 'open', 'pending', 'matched', 'accepted',
                'inspectionScheduled', 'inspected', 'scopeClassified',
                'quoted', 'quoteAdminReview', 'quoteReady',
                'quoteApproved', 'quoteRejected',
                'escrowFunded', 'workInProgress', 'completionPending',
                'completionApproved', 'completed', 'closed',
                'cancelled', 'disputed', 'referred',
            ])->default('created');
            $table->string('scope_classification')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_jobs');
    }
};
