<?php

namespace Tests\Feature;

use App\Models\MonthlyBudget;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BudgetCalculatorService;
use App\Services\UserInitializationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private UserInitializationService $initService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);
        $this->initService = new UserInitializationService();
        $this->initService->initialize($this->user);
    }

    public function test_5_million_opening_balance_allocation_and_custom_ratio_50_30_20(): void
    {
        $calculator = new BudgetCalculatorService();

        // 1. Initial 5 Million with 60/30/10
        $budget = MonthlyBudget::create([
            'user_id' => $this->user->id,
            'budget_month' => '2026-11-01',
            'opening_balance' => 5000000,
            'needs_percent' => 60,
            'wants_percent' => 30,
            'emergency_percent' => 10,
        ]);

        $summary = $calculator->getMonthlySummary($budget);

        $this->assertEquals(5000000, $summary['total_allocatable']);
        $this->assertEquals(3000000, $summary['buckets']['needs']['allocated']);
        $this->assertEquals(1500000, $summary['buckets']['wants']['allocated']);
        $this->assertEquals(500000, $summary['buckets']['emergency']['allocated']);
        $this->assertEquals(5000000, $summary['estimated_remaining_cash']);

        // 2. Change ratio to 50/30/20
        $response = $this->actingAs($this->user)->post('/budget/settings/snapshot', [
            'budget_id' => $budget->id,
            'opening_balance' => 5000000,
            'needs_percent' => 50,
            'wants_percent' => 30,
            'emergency_percent' => 20,
        ]);

        $response->assertSessionHasNoErrors();
        $budget->refresh();

        $updatedSummary = $calculator->getMonthlySummary($budget);
        $this->assertEquals(2500000, $updatedSummary['buckets']['needs']['allocated']);
        $this->assertEquals(1500000, $updatedSummary['buckets']['wants']['allocated']);
        $this->assertEquals(1000000, $updatedSummary['buckets']['emergency']['allocated']);
        $this->assertEquals(5000000, $updatedSummary['buckets']['needs']['allocated'] + $updatedSummary['buckets']['wants']['allocated'] + $updatedSummary['buckets']['emergency']['allocated']);
    }

    public function test_invalid_ratio_rejected(): void
    {
        $budget = MonthlyBudget::create([
            'user_id' => $this->user->id,
            'budget_month' => '2026-11-01',
            'opening_balance' => 5000000,
            'needs_percent' => 60,
            'wants_percent' => 30,
            'emergency_percent' => 10,
        ]);

        $response = $this->actingAs($this->user)->post('/budget/settings/snapshot', [
            'budget_id' => $budget->id,
            'opening_balance' => 5000000,
            'needs_percent' => 60,
            'wants_percent' => 30,
            'emergency_percent' => 20, // Total 110 != 100
        ]);

        $response->assertSessionHasErrors('percent');
    }

    public function test_transaction_crud_and_overspending(): void
    {
        $calculator = new BudgetCalculatorService();

        $budget = MonthlyBudget::create([
            'user_id' => $this->user->id,
            'budget_month' => '2026-11-01',
            'opening_balance' => 5000000,
            'needs_percent' => 60,
            'wants_percent' => 30,
            'emergency_percent' => 10,
        ]);

        // 1. Add income Rp 1.000.000
        $resp = $this->actingAs($this->user)->post('/transactions', [
            'type' => 'income',
            'amount' => 1000000,
            'transaction_date' => '2026-11-02',
            'income_source' => 'Bonus Project',
        ]);
        $resp->assertSessionHasNoErrors();
        $this->assertEquals(1, Transaction::count());

        // Total allocatable is now 5M + 1M = 6M
        $summaryAfterIncome = $calculator->getMonthlySummary($budget->fresh());
        $this->assertEquals(6000000, $summaryAfterIncome['total_allocatable']);
        $this->assertEquals(3600000, $summaryAfterIncome['buckets']['needs']['allocated']);
        $this->assertEquals(1800000, $summaryAfterIncome['buckets']['wants']['allocated']);
        $this->assertEquals(600000, $summaryAfterIncome['buckets']['emergency']['allocated']);

        // 2. Add expense for Needs: Rp 500.000
        $this->actingAs($this->user)->post('/transactions', [
            'type' => 'expense',
            'amount' => 500000,
            'transaction_date' => '2026-11-05',
            'bucket' => 'needs',
            'description' => 'Belanja Supermarket',
        ]);

        $summaryAfterExpense = $calculator->getMonthlySummary($budget->fresh());
        $this->assertEquals(5500000, $summaryAfterExpense['estimated_remaining_cash']);
        $this->assertEquals(3100000, $summaryAfterExpense['buckets']['needs']['remaining']);
        $this->assertFalse($summaryAfterExpense['buckets']['needs']['is_overspent']);

        // 3. Overspend test: add massive wants expense (Rp 2.500.000 while wants allocated is 1.800.000)
        $this->actingAs($this->user)->post('/transactions', [
            'type' => 'expense',
            'amount' => 2500000,
            'transaction_date' => '2026-11-10',
            'bucket' => 'wants',
            'description' => 'Beli Gadget Baru',
        ]);

        $summaryAfterOverspend = $calculator->getMonthlySummary($budget->fresh());
        // Remaining wants is 1.800.000 - 2.500.000 = -700.000
        $this->assertEquals(-700000, $summaryAfterOverspend['buckets']['wants']['remaining']);
        $this->assertTrue($summaryAfterOverspend['buckets']['wants']['is_overspent']);
    }

    public function test_monthly_snapshot_isolation(): void
    {
        // October Budget
        $octBudget = MonthlyBudget::create([
            'user_id' => $this->user->id,
            'budget_month' => '2026-10-01',
            'opening_balance' => 4000000,
            'needs_percent' => 60,
            'wants_percent' => 30,
            'emergency_percent' => 10,
        ]);

        // November Budget
        $novBudget = MonthlyBudget::create([
            'user_id' => $this->user->id,
            'budget_month' => '2026-11-01',
            'opening_balance' => 5000000,
            'needs_percent' => 60,
            'wants_percent' => 30,
            'emergency_percent' => 10,
        ]);

        // Update November ratio to 70/20/10
        $this->actingAs($this->user)->post('/budget/settings/snapshot', [
            'budget_id' => $novBudget->id,
            'opening_balance' => 5000000,
            'needs_percent' => 70,
            'wants_percent' => 20,
            'emergency_percent' => 10,
        ]);

        $novBudget->refresh();
        $octBudget->refresh();

        // November is updated
        $this->assertEquals(70, $novBudget->needs_percent);
        $this->assertEquals(20, $novBudget->wants_percent);

        // October snapshot remains untouched
        $this->assertEquals(60, $octBudget->needs_percent);
        $this->assertEquals(30, $octBudget->wants_percent);
    }

    public function test_income_transaction_with_category(): void
    {
        $incomeCategory = $this->user->categories()->where('transaction_type', 'income')->first();
        $this->assertNotNull($incomeCategory, 'User must have default income categories initialized');

        $expenseCategory = $this->user->categories()->where('transaction_type', 'expense')->first();
        $this->assertNotNull($expenseCategory);

        // 1. Success saving income with income category
        $resp = $this->actingAs($this->user)->post('/transactions', [
            'type' => 'income',
            'amount' => 2500000,
            'transaction_date' => '2026-11-17',
            'category_id' => $incomeCategory->id,
        ]);
        $resp->assertSessionHasNoErrors();

        $tx = Transaction::where('amount', 2500000)->first();
        $this->assertNotNull($tx);
        $this->assertEquals($incomeCategory->id, $tx->category_id);
        $this->assertEquals($incomeCategory->name, $tx->income_source);

        // 2. Rejecting income with expense category
        $invalidResp = $this->actingAs($this->user)->post('/transactions', [
            'type' => 'income',
            'amount' => 500000,
            'transaction_date' => '2026-11-17',
            'category_id' => $expenseCategory->id,
        ]);
        $invalidResp->assertSessionHasErrors(['category_id']);

        // 3. Rejecting expense with income category
        $invalidResp2 = $this->actingAs($this->user)->post('/transactions', [
            'type' => 'expense',
            'bucket' => 'needs',
            'amount' => 100000,
            'transaction_date' => '2026-11-17',
            'category_id' => $incomeCategory->id,
        ]);
        $invalidResp2->assertSessionHasErrors(['category_id']);
    }
}
