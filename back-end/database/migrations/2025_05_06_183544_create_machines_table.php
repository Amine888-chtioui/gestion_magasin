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
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('model');
            $table->string('sap_number')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('company')->default('Schunk Sonosystems GmbH');
            $table->timestamps();
            
            $table->index('model');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
