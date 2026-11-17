<?php

namespace Tests\Feature;

use App\Models\RecurringBill;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsAndBillsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_separated_celengan_and_bills_pages(): void
    {
        $user = User::factory()->create();

        $goalsResponse = $this->actingAs($user)->get(route('goals.index'));
        $goalsResponse->assertStatus(200);
        $goalsResponse->assertSee('Celengan Impian');

        $billsResponse = $this->actingAs($user)->get(route('bills.index'));
        $billsResponse->assertStatus(200);
        $billsResponse->assertSee('Pengingat Tagihan Rutin');
    }

    public function test_user_can_create_and_deposit_to_savings_goal(): void
    {
        $user = User::factory()->create();

        $createResponse = $this->actingAs($user)->post(route('goals.store'), [
            'name' => 'Beli MacBook Pro',
            'target_amount' => 20000000,
            'current_amount' => 2000000,
            'icon' => '💻',
            'color' => 'blue',
        ]);

        $createResponse->assertRedirect();
        $this->assertDatabaseHas('savings_goals', [
            'user_id' => $user->id,
            'name' => 'Beli MacBook Pro',
            'target_amount' => 20000000,
            'current_amount' => 2000000,
        ]);

        $goal = SavingsGoal::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(10, $goal->progress_percent);

        // Deposit 3,000,000 and record transaction in emergency bucket
        $depositResponse = $this->actingAs($user)->post(route('goals.deposit', $goal), [
            'amount' => 3000000,
            'record_transaction' => '1',
        ]);

        $depositResponse->assertRedirect();
        $goal->refresh();
        $this->assertEquals(5000000, $goal->current_amount);
        $this->assertEquals(25, $goal->progress_percent);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'expense',
            'bucket' => 'emergency',
            'amount' => 3000000,
            'description' => 'Tabungan Celengan: Beli MacBook Pro',
        ]);
    }

    public function test_user_can_create_and_pay_recurring_bill(): void
    {
        $user = User::factory()->create();
        $currentMonth = Carbon::now()->format('Y-m');

        $createResponse = $this->actingAs($user)->post(route('bills.store'), [
            'name' => 'WiFi Rumah',
            'amount' => 350000,
            'due_day' => 15,
            'bucket' => 'needs',
            'icon' => '📶',
        ]);

        $createResponse->assertRedirect();
        $bill = RecurringBill::where('user_id', $user->id)->firstOrFail();
        $this->assertFalse($bill->isPaidForMonth($currentMonth));

        $payResponse = $this->actingAs($user)->post(route('bills.pay', $bill), [
            'month' => $currentMonth,
            'record_transaction' => '1',
        ]);

        $payResponse->assertRedirect();
        $bill->refresh();
        $this->assertTrue($bill->isPaidForMonth($currentMonth));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'expense',
            'bucket' => 'needs',
            'amount' => 350000,
            'description' => 'Tagihan Rutin: WiFi Rumah',
        ]);
    }

    public function test_anti_idor_prevents_other_user_modifying_goals_or_bills(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $goal = SavingsGoal::create([
            'user_id' => $owner->id,
            'name' => 'Rahasia',
            'target_amount' => 1000000,
            'current_amount' => 0,
        ]);

        $bill = RecurringBill::create([
            'user_id' => $owner->id,
            'name' => 'Listrik',
            'amount' => 500000,
            'due_day' => 20,
            'bucket' => 'needs',
        ]);

        $this->actingAs($intruder)
            ->post(route('goals.deposit', $goal), ['amount' => 50000])
            ->assertStatus(403);

        $this->actingAs($intruder)
            ->delete(route('goals.destroy', $goal))
            ->assertStatus(403);

        $this->actingAs($intruder)
            ->post(route('bills.pay', $bill))
            ->assertStatus(403);

        $this->actingAs($intruder)
            ->delete(route('bills.destroy', $bill))
            ->assertStatus(403);
    }

    public function test_dashboard_renders_privacy_toggle_receipt_slip_and_mobile_bottom_nav(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Slip Struk');
        $response->assertSee('Celengan Impian');
        $response->assertSee('Tagihan Rutin');
        $response->assertSee('Navigasi Mobile');
    }
}
