<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('withdrawal_date');
            $table->text('reason');
            $table->boolean('tc_issued')->default(false);
            $table->string('tc_number')->nullable();
            $table->date('tc_date')->nullable();
            $table->boolean('security_refunded')->default(false);
            $table->decimal('security_amount', 10, 2)->nullable();
            $table->string('security_receipt_number')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->date('refund_date')->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('utr_number')->nullable();
            $table->string('cheque_number')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_withdrawals');
    }
};
