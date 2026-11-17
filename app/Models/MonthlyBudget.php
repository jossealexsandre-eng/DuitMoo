<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'budget_month',
        'opening_balance',
        'needs_percent',
        'wants_percent',
        'emergency_percent',
        'allocation_basis',
        'status',
    ];

    protected static function booted(): void
    {
        static::saved(function (MonthlyBudget $budget) {
            $budget->syncAllocations();
        });
    }

    public function syncAllocations(): void
    {
        $map = [
            'needs' => $this->needs_percent ?? 60,
            'wants' => $this->wants_percent ?? 30,
            'emergency' => $this->emergency_percent ?? 10,
        ];

        foreach ($map as $bucket => $percentage) {
            BudgetAllocation::updateOrCreate(
                ['monthly_budget_id' => $this->id, 'bucket' => $bucket],
                ['percentage' => (int) $percentage]
            );
        }
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BudgetAllocation::class);
    }

    protected function casts(): array
    {
        return [
            'budget_month' => 'date:Y-m-d',
            'opening_balance' => 'integer',
            'needs_percent' => 'integer',
            'wants_percent' => 'integer',
            'emergency_percent' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
