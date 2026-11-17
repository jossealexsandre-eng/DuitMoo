<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_sheet_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('spreadsheet_id', 191);
            $table->string('spreadsheet_name', 191)->nullable();
            $table->string('income_sheet_name', 100)->default('Pemasukan');
            $table->string('expense_sheet_name', 100)->default('Pengeluaran');
            $table->json('income_mapping')->nullable();
            $table->json('expense_mapping')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('google_sheet_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 20)->default('push');
            $table->string('status', 20)->default('pending');
            $table->string('idempotency_key', 120)->unique();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('transaction_external_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('google_sheet_connection_id')->constrained()->cascadeOnDelete();
            $table->string('external_row_key', 120);
            $table->string('sheet_name', 100);
            $table->string('last_synced_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['google_sheet_connection_id', 'external_row_key'], 'tx_ext_conn_row_unique');
            $table->unique(['google_sheet_connection_id', 'transaction_id'], 'tx_ext_conn_tx_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_external_mappings');
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('google_sheet_connections');
    }
};
