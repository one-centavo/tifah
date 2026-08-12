<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expiration_alert_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('dismissed_date');
            $table->timestamps();

            $table->unique(['user_id', 'dismissed_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expiration_alert_dismissals');
    }
};
