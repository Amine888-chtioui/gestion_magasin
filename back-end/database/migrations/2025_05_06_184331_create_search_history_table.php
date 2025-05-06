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
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->string('search_query');
            $table->string('search_type'); // 'component' or 'machine'
            $table->integer('results_count');
            $table->timestamp('searched_at');
            $table->string('user_id')->nullable();
            $table->index('searched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_history');
    }
};
