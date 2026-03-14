<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('plan_id');
            $table->string('plan_name');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('NGN');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->string('payment_reference')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('expires_at');
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
