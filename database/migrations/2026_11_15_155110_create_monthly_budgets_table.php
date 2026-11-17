<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monthly_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('budget_month');
            $table->unsignedBigInteger('opening_balance')->default(0);
            $table->unsignedTinyInteger('needs_percent')->default(60);
            $table->unsignedTinyInteger('wants_percent')->default(30);
            $table->unsignedTinyInteger('emergency_percent')->default(10);
            $table->timestamps();

            $table->unique(['user_id', 'budget_month']);
            $table->index(['user_id', 'budget_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_budgets');
    }
};
