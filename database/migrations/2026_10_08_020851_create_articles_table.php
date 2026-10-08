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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained();
            $table->foreignId('crawl_job_id')->constrained();
            $table->string('url', 1024);
            $table->string('url_hash', 64)->unique();
            $table->string('title', 1024);
            $table->string('author', 255)->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->string('published_at_raw', 100)->nullable();
            $table->date('event_date')->nullable();
            $table->string('event_date_confidence', 10)->nullable()->comment('high|med|low');
            $table->string('category_primary', 30)->default('unclear')->comment('commodity|policy|logistics|other|unclear');
            $table->jsonb('category_secondary')->nullable();
            $table->string('category_confidence', 10)->nullable()->comment('high|med|low');
            $table->boolean('is_addon')->default(false);
            $table->string('period_target', 7)->nullable()->comment('YYYY-MM');
            $table->string('content_hash', 64)->nullable();
            $table->unsignedInteger('content_length')->default(0);
            $table->string('language', 10)->default('id');
            $table->text('summary')->nullable();
            $table->string('sentiment', 10)->nullable()->comment('pos|neg|neu|mixed');
            $table->jsonb('quality_flags')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->foreignId('duplicate_of')->nullable()->constrained('articles')->nullOnDelete();
            $table->timestamps();

            $table->index('content_hash');
            $table->index('published_at');
            $table->index('period_target');
            $table->index('category_primary');
            $table->index(['source_id', 'published_at']);
            $table->index('needs_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
