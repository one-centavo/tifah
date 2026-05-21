<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots');
            $table->enum('type', ['entry', 'exit', 'adjustment']);
            $table->integer('quantity');
            $table->integer('previous_balance');
            $table->integer('new_balance');
            $table->string('concept', 255);
            $table->bigInteger('reference_id');
            $table->timestamp('created_at');
            $table->foreignId('created_by')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
