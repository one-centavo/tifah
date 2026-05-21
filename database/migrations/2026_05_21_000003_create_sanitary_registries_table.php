<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanitary_registries', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number', 50)->unique();
            $table->foreignId('laboratory_id')->constrained('laboratories');
            $table->date('expiration_date');
            $table->enum('status', ['expired', 'valid', 'under_renewal'])->default('valid');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanitary_registries');
    }
};
