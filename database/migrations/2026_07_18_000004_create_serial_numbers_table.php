<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->string('serial_number')->unique();
            $table->enum('status', ['in_stock', 'sold', 'in_repair', 'returned'])->default('in_stock');
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('serial_numbers');
    }
};
