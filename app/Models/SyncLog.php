<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    protected $fillable = [
        'user_id',
        'google_sheet_connection_id',
        'transaction_id',
        'direction',
        'status',
        'idempotency_key',
        'attempts',
        'error_message',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(GoogleSheetConnection::class, 'google_sheet_connection_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
