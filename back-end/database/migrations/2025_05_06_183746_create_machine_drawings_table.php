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
        Schema::create('machine_drawings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('file_path');
            $table->string('drawing_type'); // 'exploded', 'assembly', 'schematic'
            $table->integer('page_number')->nullable();
            $table->json('clickable_areas')->nullable(); // JSON for component positions
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_drawings');
    }
};
