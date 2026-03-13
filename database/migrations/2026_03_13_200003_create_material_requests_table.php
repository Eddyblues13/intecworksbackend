<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('service_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artisan_id')->constrained('users')->cascadeOnDelete();
            $table->json('items'); // [{name, quantity, unit, specs}]
            $table->string('status')->default('pending'); // pending, quoted, supplier_selected, ordered, delivered, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('material_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('material_request_id');
            $table->foreign('material_request_id')->references('id')->on('material_requests')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('status')->default('pending'); // pending, confirmed, dispatched, delivered, cancelled
            $table->json('quote_items')->default('[]');
            $table->text('delivery_notes')->nullable();
            $table->json('delivery_proof_images')->default('[]');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_orders');
        Schema::dropIfExists('material_requests');
    }
};
