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
        Schema::create('protector_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->string('glass_type');
            $table->string('screen_size_inch')->nullable();
            $table->string('dimensions_mm')->nullable();
            $table->integer('stock_qty')->default(0);
            $table->integer('min_threshold')->default(3);
            $table->string('bin_location')->nullable();
            $table->timestamps();

            // Composite unique index to prevent duplicate variant & model combinations
            $table->unique(['brand', 'model', 'glass_type'], 'unique_brand_model_glass_variant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('protector_stocks');
    }
};
