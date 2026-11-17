<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleSheetConnection extends Model
{
    protected $fillable = [
        'user_id',
        'spreadsheet_id',
        'spreadsheet_name',
        'income_sheet_name',
        'expense_sheet_name',
        'income_mapping',
        'expense_mapping',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'last_synced_at',
        'last_error',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'income_mapping' => 'array',
            'expense_mapping' => 'array',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    public function externalMappings(): HasMany
    {
        return $this->hasMany(TransactionExternalMapping::class);
    }
}
