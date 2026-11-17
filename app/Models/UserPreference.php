<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'default_needs_percent',
        'default_wants_percent',
        'default_emergency_percent',
        'theme_choice',
        'timezone',
        'date_format',
    ];

    protected function casts(): array
    {
        return [
            'default_needs_percent' => 'integer',
            'default_wants_percent' => 'integer',
            'default_emergency_percent' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
