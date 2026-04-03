<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->string('payment_location', 20)->nullable()->after('payment_method');
            $table->string('payment_channel', 20)->nullable()->after('payment_location');
            $table->string('utr_number')->nullable()->after('transaction_id');
            $table->string('cheque_number')->nullable()->after('utr_number');
        });

        Schema::create('fee_discount_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_payment_id')->constrained('fee_payments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount_amount', 10, 2);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_discount_records');

        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_location',
                'payment_channel',
                'utr_number',
                'cheque_number',
            ]);
        });
    }
};
