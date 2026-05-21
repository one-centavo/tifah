<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('laboratory_id')->constrained('laboratories');
            $table->foreignId('sanitary_registry_id')->constrained('sanitary_registries');
            $table->string('name', 255);
            $table->string('generic_name', 255)->nullable();
            $table->decimal('concentration_value', 10, 2);
            $table->foreignId('concentration_unit_id')->constrained('concentration_units');
            $table->foreignId('container_id')->constrained('containers');
            $table->integer('content_quantity');
            $table->foreignId('content_unit_id')->constrained('content_units');
            $table->boolean('is_cold_chain')->default(0);
            $table->boolean('is_special_control')->default(0);
            $table->integer('min_stock')->default(5);
            $table->decimal('selling_price', 10, 2);
            $table->string('description', 255)->nullable();
            $table->timestamps();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
