<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_phone', 50);
            $table->string('device_details');
            $table->text('issue_description');
            $table->enum('status', ['pending', 'diagnosing', 'waiting_parts', 'ready', 'completed'])->default('pending');
            $table->decimal('labor_charge', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('work_orders');
    }
};
