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
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable(); // For guest users using IP or session
            $table->foreignId('machine_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('component_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('favorite_type'); // 'machine' or 'component'
            $table->timestamps();
            
            $table->index(['user_id', 'favorite_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
