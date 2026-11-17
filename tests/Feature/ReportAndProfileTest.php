<?php

namespace Tests\Feature;

use App\Models\MonthlyBudget;
use App\Models\Transaction;
use App\Models\User;
use App\Services\UserInitializationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportAndProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'password' => Hash::make('oldpassword123'),
        ]);

        $init = new UserInitializationService();
        $init->initialize($this->user);
    }

    public function test_reports_page_and_csv_export(): void
    {
        $budget = MonthlyBudget::create([
            'user_id' => $this->user->id,
            'budget_month' => '2026-11-01',
            'opening_balance' => 5000000,
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'monthly_budget_id' => $budget->id,
            'type' => 'income',
            'amount' => 3000000,
            'transaction_date' => '2026-11-03',
            'income_source' => 'Gaji Pokok',
        ]);

        $response = $this->actingAs($this->user)->get('/reports?year=2026');
        $response->assertStatus(200);
        $response->assertSee('Laporan');

        // CSV Export
        $csvResponse = $this->actingAs($this->user)->get('/reports/export?year=2026');
        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_profile_update_and_password_change(): void
    {
        // Update profile
        $response = $this->actingAs($this->user)->patch('/settings/profile', [
            'name' => 'Nama Baru DuitMoo',
            'email' => 'namabaru@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        $this->user->refresh();
        $this->assertEquals('Nama Baru DuitMoo', $this->user->name);
        $this->assertEquals('namabaru@example.com', $this->user->email);

        // Update password
        $pwdResponse = $this->actingAs($this->user)->put('/settings/profile/password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $pwdResponse->assertSessionHasNoErrors();
        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword456', $this->user->password));
    }

    public function test_delete_account_cascades_all_data(): void
    {
        $budget = MonthlyBudget::create([
            'user_id' => $this->user->id,
            'budget_month' => '2026-11-01',
            'opening_balance' => 5000000,
        ]);

        $tx = Transaction::create([
            'user_id' => $this->user->id,
            'monthly_budget_id' => $budget->id,
            'type' => 'income',
            'amount' => 1000000,
            'transaction_date' => '2026-11-03',
        ]);

        $response = $this->actingAs($this->user)->delete('/settings/profile', [
            'password' => 'oldpassword123',
        ]);

        $response->assertRedirect('/');
        $this->assertGuest();

        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
        $this->assertDatabaseMissing('monthly_budgets', ['id' => $budget->id]);
        $this->assertDatabaseMissing('transactions', ['id' => $tx->id]);
    }
}
