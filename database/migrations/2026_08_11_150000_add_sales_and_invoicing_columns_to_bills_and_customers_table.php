<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->string('invoice_number', 50)->nullable()->unique()->after('id');
            $table->enum('payment_method', ['cash', 'transfer', 'credit'])->default('cash')->after('status');
            $table->date('payment_due_date')->nullable()->after('payment_method');
            $table->decimal('total_amount', 12, 2)->default(0)->after('payment_due_date');
            $table->text('annulled_reason')->nullable()->after('total_amount');
            $table->foreignId('annulled_by')->nullable()->after('annulled_reason')->constrained('users');
            $table->timestamp('annulled_at')->nullable()->after('annulled_by');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('credit_limit', 12, 2)->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('credit_limit');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['annulled_by']);
            $table->dropColumn([
                'invoice_number',
                'payment_method',
                'payment_due_date',
                'total_amount',
                'annulled_reason',
                'annulled_by',
                'annulled_at',
            ]);
        });
    }
};
