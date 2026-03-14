<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('avatar_url')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->rememberToken();
            $table->timestamps();
        });

        // Update admin_activity_logs to reference admins table
        // (admin_id now points to admins.id, not users.id)

        // Update broadcast_notifications admin_id to reference admins table
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
