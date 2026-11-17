<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'current_amount',
        'icon',
        'color',
        'target_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'integer',
            'current_amount' => 'integer',
            'target_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->current_amount / $this->target_amount) * 100));
    }

    public function getRemainingAmountAttribute(): int
    {
        return max(0, $this->target_amount - $this->current_amount);
    }

    public function getIsAchievedAttribute(): bool
    {
        return $this->target_amount > 0 && $this->current_amount >= $this->target_amount;
    }
}
