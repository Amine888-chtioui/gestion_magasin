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
        Schema::create('component_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained()->onDelete('cascade');
            $table->string('spec_key');
            $table->string('spec_value');
            $table->string('spec_unit')->nullable();
            $table->timestamps();
            
            $table->index(['component_id', 'spec_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_specifications');
    }
};
