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
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('domain', 150);
            $table->string('base_url', 255)->nullable();
            $table->jsonb('rss_feeds')->nullable();
            $table->jsonb('api_config')->nullable();
            $table->jsonb('html_selectors')->nullable();
            $table->boolean('playwright_enabled')->default(false);
            $table->string('robots_policy', 20)->default('respect');
            $table->unsignedInteger('rate_limit_per_min')->default(30);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
