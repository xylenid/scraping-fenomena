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
        Schema::create('article_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->longText('content_raw')->nullable();
            $table->longText('content_cleaned');
            $table->longText('content_html')->nullable();
            $table->string('extracted_by', 30)->default('readability')->comment('rss|readability|domcrawler|playwright');
            $table->timestamps();

            $table->unique('article_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_contents');
    }
};
