<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetAllocation extends Model
{
    protected $fillable = [
        'monthly_budget_id',
        'bucket',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'integer',
        ];
    }

    public function monthlyBudget(): BelongsTo
    {
        return $this->belongsTo(MonthlyBudget::class);
    }
}
