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
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('pos_number');
            $table->integer('quantity');
            $table->string('unit');
            $table->string('name_de');
            $table->string('name_en')->nullable();
            $table->string('sap_number');
            $table->text('description')->nullable();
            $table->boolean('is_spare_part')->default(false);
            $table->boolean('is_wearing_part')->default(false);
            $table->timestamps();
            
            $table->index(['machine_id', 'pos_number']);
            $table->index('sap_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
