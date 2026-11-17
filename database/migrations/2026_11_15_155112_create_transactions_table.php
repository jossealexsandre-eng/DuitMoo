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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monthly_budget_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // 'income', 'expense'
            $table->string('bucket', 20)->nullable(); // 'needs', 'wants', 'emergency' (null for income)
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('transaction_date');
            $table->string('description')->nullable();
            $table->string('income_source')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['monthly_budget_id', 'type']);
            $table->index(['user_id', 'bucket']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
