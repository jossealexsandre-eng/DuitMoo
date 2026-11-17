<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionExternalMapping extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'google_sheet_connection_id',
        'external_row_key',
        'sheet_name',
        'last_synced_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(GoogleSheetConnection::class, 'google_sheet_connection_id');
    }
}
