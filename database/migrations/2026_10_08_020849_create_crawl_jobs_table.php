<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crawl_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->comment('YYYY-MM target analysis period');
            $table->string('window_type', 20)->default('main')->comment('main|addon');
            $table->string('triggered_by', 20)->default('cron')->comment('cron|manual');
            $table->string('status', 20)->default('queued')->comment('queued|running|completed|partial|failed');
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->jsonb('stats')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['period', 'window_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_jobs');
    }
};
