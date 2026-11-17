<?php

namespace App\Policies;

use App\Models\MonthlyBudget;
use App\Models\User;

class MonthlyBudgetPolicy
{
    public function view(User $user, MonthlyBudget $budget): bool
    {
        return $user->id === $budget->user_id;
    }

    public function update(User $user, MonthlyBudget $budget): bool
    {
        return $user->id === $budget->user_id;
    }
}
