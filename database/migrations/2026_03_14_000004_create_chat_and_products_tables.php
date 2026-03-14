<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Chat Threads ──
        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->nullable()->constrained('service_jobs')->nullOnDelete();
            $table->foreignId('participant_a')->constrained('users')->onDelete('cascade');
            $table->foreignId('participant_b')->constrained('users')->onDelete('cascade');
            $table->string('last_message')->nullable();
            $table->dateTime('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['participant_a', 'participant_b', 'service_job_id'], 'thread_unique');
        });

        // ── Chat Messages ──
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_thread_id')->constrained('chat_threads')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->string('type')->default('text'); // text, image, file
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_flagged')->default(false);
            $table->timestamps();

            $table->index(['chat_thread_id', 'created_at']);
        });

        // ── Supplier Products ──
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('unit')->default('piece'); // piece, kg, bag, etc.
            $table->string('category')->nullable();
            $table->json('images')->nullable();
            $table->boolean('in_stock')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->timestamps();

            $table->index(['supplier_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_threads');
        Schema::dropIfExists('supplier_products');
    }
};
