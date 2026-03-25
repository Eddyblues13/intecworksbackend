<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('purpose')->nullable()->after('reference'); // 'deposit' or 'remaining'
        });

        // Expand the method enum to include paystack and korapay
        DB::statement("ALTER TABLE payments MODIFY COLUMN method VARCHAR(20) DEFAULT 'card'");
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });
    }
};
