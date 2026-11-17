<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->string('timezone', 64)->default('Asia/Jakarta')->after('theme_choice');
            $table->string('date_format', 20)->default('d M Y')->after('timezone');
        });

        Schema::table('monthly_budgets', function (Blueprint $table) {
            $table->string('allocation_basis', 30)->default('total_available')->after('emergency_percent');
            $table->string('status', 20)->default('active')->after('allocation_basis');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('transaction_type', 20)->default('expense')->after('bucket');
            $table->boolean('is_active')->default(true)->after('is_default');

            $table->index(['user_id', 'transaction_type', 'is_active'], 'categories_user_type_active_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('income_source');
            $table->string('source', 30)->default('web')->after('notes');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['notes', 'source']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_user_type_active_idx');
            $table->dropColumn(['transaction_type', 'is_active']);
        });

        Schema::table('monthly_budgets', function (Blueprint $table) {
            $table->dropColumn(['allocation_basis', 'status']);
        });

        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'date_format']);
        });
    }
};
