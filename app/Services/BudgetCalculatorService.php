<?php

namespace App\Services;

use App\Models\MonthlyBudget;
use App\Models\Transaction;

class BudgetCalculatorService
{
    /**
     * Compute allocations strictly using integer math and floor,
     * assigning exact remainder to Emergency so sum of allocations === totalAllocatable.
     *
     * @return array{needs: int, wants: int, emergency: int, total: int}
     */
    public function calculateAllocation(
        int $totalAllocatable,
        int $needsPercent,
        int $wantsPercent,
        int $emergencyPercent
    ): array {
        if ($needsPercent + $wantsPercent + $emergencyPercent !== 100) {
            throw new \InvalidArgumentException('Total alokasi persentase harus tepat 100%.');
        }

        if ($totalAllocatable <= 0) {
            return [
                'needs' => 0,
                'wants' => 0,
                'emergency' => 0,
                'total' => 0,
            ];
        }

        $needs = (int) floor(($totalAllocatable * $needsPercent) / 100);
        $wants = (int) floor(($totalAllocatable * $wantsPercent) / 100);
        // Emergency receives the exact remainder to ensure zero rupiah loss/gain
        $emergency = $totalAllocatable - $needs - $wants;

        return [
            'needs' => $needs,
            'wants' => $wants,
            'emergency' => $emergency,
            'total' => $totalAllocatable,
        ];
    }

    /**
     * Compute full financial breakdown for a specific monthly budget.
     */
    public function getMonthlySummary(MonthlyBudget $monthlyBudget): array
    {
        $openingBalance = (int) $monthlyBudget->opening_balance;

        $income = (int) Transaction::where('monthly_budget_id', $monthlyBudget->id)
            ->where('type', 'income')
            ->sum('amount');

        $totalAllocatable = $openingBalance + $income;

        $allocations = $this->calculateAllocation(
            $totalAllocatable,
            $monthlyBudget->needs_percent,
            $monthlyBudget->wants_percent,
            $monthlyBudget->emergency_percent
        );

        $spentNeeds = (int) Transaction::where('monthly_budget_id', $monthlyBudget->id)
            ->where('type', 'expense')
            ->where('bucket', 'needs')
            ->sum('amount');

        $spentWants = (int) Transaction::where('monthly_budget_id', $monthlyBudget->id)
            ->where('type', 'expense')
            ->where('bucket', 'wants')
            ->sum('amount');

        $spentEmergency = (int) Transaction::where('monthly_budget_id', $monthlyBudget->id)
            ->where('type', 'expense')
            ->where('bucket', 'emergency')
            ->sum('amount');

        $totalExpenses = $spentNeeds + $spentWants + $spentEmergency;
        $estimatedRemainingCash = $totalAllocatable - $totalExpenses;
        $profitIncome = $income - $totalExpenses;
        $profitMarginPercent = $income > 0 ? round(($profitIncome / $income) * 100, 1) : 0.0;

        $needsRemaining = $allocations['needs'] - $spentNeeds;
        $wantsRemaining = $allocations['wants'] - $spentWants;
        $emergencyRemaining = $allocations['emergency'] - $spentEmergency;

        // Progress percentages for progress bars (capped at 100% or above for overspend)
        $needsProgress = $allocations['needs'] > 0 ? round(($spentNeeds / $allocations['needs']) * 100, 1) : 0;
        $wantsProgress = $allocations['wants'] > 0 ? round(($spentWants / $allocations['wants']) * 100, 1) : 0;
        $emergencyProgress = $allocations['emergency'] > 0 ? round(($spentEmergency / $allocations['emergency']) * 100, 1) : 0;

        return [
            'opening_balance' => $openingBalance,
            'income' => $income,
            'total_allocatable' => $totalAllocatable,
            'total_expenses' => $totalExpenses,
            'profit_income' => $profitIncome,
            'profit_margin_percent' => $profitMarginPercent,
            'is_profitable' => $profitIncome >= 0,
            'estimated_remaining_cash' => $estimatedRemainingCash,
            'buckets' => [
                'needs' => [
                    'label' => 'Kebutuhan',
                    'percent' => $monthlyBudget->needs_percent,
                    'allocated' => $allocations['needs'],
                    'spent' => $spentNeeds,
                    'remaining' => $needsRemaining,
                    'is_overspent' => $needsRemaining < 0,
                    'progress' => $needsProgress,
                    'color' => '#45C486',
                ],
                'wants' => [
                    'label' => 'Keinginan',
                    'percent' => $monthlyBudget->wants_percent,
                    'allocated' => $allocations['wants'],
                    'spent' => $spentWants,
                    'remaining' => $wantsRemaining,
                    'is_overspent' => $wantsRemaining < 0,
                    'progress' => $wantsProgress,
                    'color' => '#FFD54F',
                ],
                'emergency' => [
                    'label' => 'Dana Darurat',
                    'percent' => $monthlyBudget->emergency_percent,
                    'allocated' => $allocations['emergency'],
                    'spent' => $spentEmergency,
                    'remaining' => $emergencyRemaining,
                    'is_overspent' => $emergencyRemaining < 0,
                    'progress' => $emergencyProgress,
                    'color' => '#9B7EDE',
                ],
            ],
        ];
    }
}
