<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('medicines');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->string('batch_number', 255);
            $table->date('expiration_date');
            $table->integer('current_quantity');
            $table->integer('initial_quantity');
            $table->date('reception_date');
            $table->decimal('unit_purchase_price', 10, 2);
            $table->enum('status', ['active', 'blocked', 'damaged'])->default('active');
            $table->timestamps();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
