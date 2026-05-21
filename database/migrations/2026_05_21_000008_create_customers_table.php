<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('nit', 20);
            $table->tinyInteger('dv');
            $table->string('name', 255);
            $table->string('city', 100);
            $table->string('address', 255);
            $table->string('phone_number', 20);
            $table->string('email', 255);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
