<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->json('before_photos')->default('[]')->after('images');
            $table->json('after_photos')->default('[]')->after('before_photos');
            $table->text('completion_notes')->nullable()->after('scope_classification');
            $table->integer('progress_percent')->default(0)->after('completion_notes');
            $table->text('progress_notes')->nullable()->after('progress_percent');
            $table->timestamp('inspection_submitted_at')->nullable()->after('closed_at');
            $table->timestamp('scope_classified_at')->nullable()->after('inspection_submitted_at');
            $table->timestamp('quote_submitted_at')->nullable()->after('scope_classified_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'before_photos', 'after_photos', 'completion_notes',
                'progress_percent', 'progress_notes',
                'inspection_submitted_at', 'scope_classified_at', 'quote_submitted_at',
            ]);
        });
    }
};
