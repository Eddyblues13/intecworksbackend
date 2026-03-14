<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add 'admin' to the users.role ENUM ──
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('client','artisan','supplier','admin') DEFAULT 'client'");

        // ── 2. Disputes table ──
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->nullable()->constrained('service_jobs')->nullOnDelete();
            $table->foreignId('reported_by_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('against_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->enum('status', ['open', 'under_review', 'resolved', 'dismissed'])->default('open');
            $table->text('resolution')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // ── 3. Admin activity logs ──
        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('action');            // e.g. 'approved_verification', 'suspended_user'
            $table->string('target_type')->nullable(); // e.g. 'user', 'job', 'dispute'
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });

        // ── 4. Platform settings (key-value) ──
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->timestamps();
        });

        // ── 5. Broadcast notifications ──
        Schema::create('broadcast_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('target_role')->nullable();  // null = all users
            $table->json('target_user_ids')->nullable();
            $table->timestamps();
        });

        // ── 6. Add flagged_reason to service_jobs ──
        if (!Schema::hasColumn('service_jobs', 'flagged_reason')) {
            Schema::table('service_jobs', function (Blueprint $table) {
                $table->text('flagged_reason')->nullable()->after('status');
                $table->boolean('is_flagged')->default(false)->after('flagged_reason');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_notifications');
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('admin_activity_logs');
        Schema::dropIfExists('disputes');

        if (Schema::hasColumn('service_jobs', 'flagged_reason')) {
            Schema::table('service_jobs', function (Blueprint $table) {
                $table->dropColumn(['flagged_reason', 'is_flagged']);
            });
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('client','artisan','supplier') DEFAULT 'client'");
    }
};
