<?php

namespace App\Livewire;

use App\Models\MonthlyBudget;
use App\Models\UserPreference;
use App\Services\BudgetCalculatorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OnboardingWizard extends Component
{
    public string $budgetMonth;
    public string $openingBalance = '5000000';
    public int $needsPercent = 60;
    public int $wantsPercent = 30;
    public int $emergencyPercent = 10;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->budgetMonth = Carbon::now()->format('Y-m');

        $user = Auth::user();
        if ($user && $user->preferences) {
            $this->needsPercent = $user->preferences->default_needs_percent;
            $this->wantsPercent = $user->preferences->default_wants_percent;
            $this->emergencyPercent = $user->preferences->default_emergency_percent;
        }
    }

    public function setPreset(int $needs, int $wants, int $emergency): void
    {
        $this->needsPercent = $needs;
        $this->wantsPercent = $wants;
        $this->emergencyPercent = $emergency;
        $this->validatePercentages();
    }

    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['needsPercent', 'wantsPercent', 'emergencyPercent'])) {
            $this->validatePercentages();
        }
    }

    public function validatePercentages(): bool
    {
        $total = (int) $this->needsPercent + (int) $this->wantsPercent + (int) $this->emergencyPercent;
        if ($total !== 100) {
            $this->errorMessage = "Total persentase saat ini {$total}%. Wajib tepat 100%!";
            return false;
        }

        $this->errorMessage = null;
        return true;
    }

    public function saveBudget(BudgetCalculatorService $calculator)
    {
        if (!$this->validatePercentages()) {
            return;
        }

        // Clean opening balance from dots or formatting
        $numericBalance = (int) preg_replace('/[^0-9]/', '', $this->openingBalance);

        $user = Auth::user();

        // 1. Save preferences
        UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'default_needs_percent' => $this->needsPercent,
                'default_wants_percent' => $this->wantsPercent,
                'default_emergency_percent' => $this->emergencyPercent,
            ]
        );

        // 2. Create or update monthly budget
        $firstDayOfMonth = Carbon::parse($this->budgetMonth . '-01')->format('Y-m-d');

        MonthlyBudget::updateOrCreate(
            [
                'user_id' => $user->id,
                'budget_month' => $firstDayOfMonth,
            ],
            [
                'opening_balance' => $numericBalance,
                'needs_percent' => $this->needsPercent,
                'wants_percent' => $this->wantsPercent,
                'emergency_percent' => $this->emergencyPercent,
            ]
        );

        session()->flash('success', 'Anggaran berhasil dibuat! Selamat datang di dashboard DuitMoo.');
        return redirect()->route('dashboard', ['month' => $this->budgetMonth]);
    }

    public function render(BudgetCalculatorService $calculator)
    {
        $numericBalance = (int) preg_replace('/[^0-9]/', '', $this->openingBalance);

        $allocation = null;
        if ($this->needsPercent + $this->wantsPercent + $this->emergencyPercent === 100) {
            $allocation = $calculator->calculateAllocation(
                $numericBalance,
                $this->needsPercent,
                $this->wantsPercent,
                $this->emergencyPercent
            );
        }

        return view('livewire.onboarding-wizard', [
            'allocation' => $allocation,
            'numericBalance' => $numericBalance,
        ])->layout('layouts.guest', ['title' => 'Setup Anggaran - DuitMoo']);
    }
}
