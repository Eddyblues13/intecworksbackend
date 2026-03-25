<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('service_jobs')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', [
                'not_started',
                'deposit_required',
                'partially_funded',
                'fully_funded',
                'awaiting_quote_approval',
                'awaiting_remaining_balance',
                'held_in_escrow',
                'ready_for_release',
                'partially_released',
                'released',
                'refunded',
                'disputed',
            ])->default('not_started');
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->decimal('material_amount', 12, 2)->default(0);
            $table->decimal('total_funded', 12, 2)->default(0);
            $table->decimal('total_released', 12, 2)->default(0);
            $table->decimal('total_refunded', 12, 2)->default(0);
            $table->timestamp('fully_funded_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique('job_id');
        });

        Schema::create('escrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escrow_id')->constrained('escrows')->cascadeOnDelete();
            $table->foreignId('job_id')->constrained('service_jobs');
            $table->enum('type', [
                'deposit',
                'remaining_balance',
                'material_funding',
                'release_artisan',
                'release_supplier',
                'refund',
                'commission_deduction',
            ]);
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['escrow_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_transactions');
        Schema::dropIfExists('escrows');
    }
};
