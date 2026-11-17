<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedBigInteger('target_amount');
            $table->unsignedBigInteger('current_amount')->default(0);
            $table->string('icon', 20)->default('🐮');
            $table->string('color', 30)->default('yellow');
            $table->date('target_date')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('recurring_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedBigInteger('amount');
            $table->unsignedTinyInteger('due_day')->default(1);
            $table->string('bucket', 20)->default('needs');
            $table->string('icon', 20)->default('⚡');
            $table->string('last_paid_month', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'due_day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_bills');
        Schema::dropIfExists('savings_goals');
    }
};
