<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique();
            $table->string('display_name')->default('Razorpay');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('test_mode')->default(true);
            $table->string('key_id')->nullable();
            $table->string('key_secret')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->string('currency', 3)->default('INR');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
    }
};
