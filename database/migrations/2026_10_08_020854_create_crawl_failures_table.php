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
        Schema::create('crawl_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crawl_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('url', 1024)->nullable();
            $table->string('error_type', 50)->comment('timeout|selector|http_4xx|http_5xx|js|parse|other');
            $table->text('error_message')->nullable();
            $table->text('stack_trace')->nullable();
            $table->timestampTz('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['crawl_job_id', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_failures');
    }
};
