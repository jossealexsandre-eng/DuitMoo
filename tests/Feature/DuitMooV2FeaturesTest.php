<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\BudgetAllocation;
use App\Models\GoogleSheetConnection;
use App\Models\MonthlyBudget;
use App\Models\SyncLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BudgetCalculatorService;
use App\Services\GoogleSheetsSyncService;
use App\Services\UserInitializationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class DuitMooV2FeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $init = new UserInitializationService();

        $this->user = User::factory()->create(['role' => 'user', 'is_active' => true]);
        $init->initialize($this->user);

        $this->otherUser = User::factory()->create(['role' => 'user', 'is_active' => true]);
        $init->initialize($this->otherUser);

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    public function test_budget_allocations_snapshot_synced_and_integer_rounding_exact(): void
    {
        $budget = MonthlyBudget::create([
            'user_id' => $this->user->id,
            'budget_month' => '2026-11-01',
            'opening_balance' => 1234567,
            'needs_percent' => 55,
            'wants_percent' => 33,
            'emergency_percent' => 12,
        ]);

        $this->assertEquals(3, BudgetAllocation::where('monthly_budget_id', $budget->id)->count());

        $calculator = new BudgetCalculatorService();
        $summary = $calculator->getMonthlySummary($budget);

        $sum = $summary['buckets']['needs']['allocated']
            + $summary['buckets']['wants']['allocated']
            + $summary['buckets']['emergency']['allocated'];

        $this->assertEquals(1234567, $sum);
    }

    public function test_income_categories_seeded_and_quick_add_handles_formatted_amount_and_overspend_warning(): void
    {
        $this->assertGreaterThan(0, $this->user->categories()->where('transaction_type', 'income')->count());

        MonthlyBudget::create([
            'user_id' => $this->user->id,
            'budget_month' => date('Y-m-01'),
            'opening_balance' => 1000000,
            'needs_percent' => 60,
            'wants_percent' => 30,
            'emergency_percent' => 10,
        ]);

        // Expense Rp 750.000 in Needs (allocated is 600.000 -> overspent by 150.000)
        $response = $this->actingAs($this->user)->post('/transactions', [
            'type' => 'expense',
            'amount' => '750.000',
            'transaction_date' => date('Y-m-d'),
            'bucket' => 'needs',
            'description' => 'Belanja Bulanan',
            'notes' => 'Dengan catatan tambahan',
            'source' => 'quick_add',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('warning');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => 750000,
            'notes' => 'Dengan catatan tambahan',
            'source' => 'quick_add',
        ]);
    }

    public function test_negative_and_zero_transaction_amounts_rejected(): void
    {
        $respZero = $this->actingAs($this->user)->post('/transactions', [
            'type' => 'expense',
            'amount' => 0,
            'transaction_date' => date('Y-m-d'),
            'bucket' => 'needs',
        ]);
        $respZero->assertSessionHasErrors('amount');

        $respNeg = $this->actingAs($this->user)->post('/transactions', [
            'type' => 'expense',
            'amount' => -50000,
            'transaction_date' => date('Y-m-d'),
            'bucket' => 'needs',
        ]);
        $respNeg->assertSessionHasErrors('amount');
    }

    public function test_edit_and_soft_delete_recalculate_accurately(): void
    {
        $budget = MonthlyBudget::create([
            'user_id' => $this->user->id,
            'budget_month' => '2026-11-01',
            'opening_balance' => 5000000,
            'needs_percent' => 60,
            'wants_percent' => 30,
            'emergency_percent' => 10,
        ]);

        $tx = Transaction::create([
            'user_id' => $this->user->id,
            'monthly_budget_id' => $budget->id,
            'type' => 'expense',
            'bucket' => 'needs',
            'amount' => 200000,
            'transaction_date' => '2026-11-05',
            'description' => 'Makan Siang',
        ]);

        $this->actingAs($this->user)->put("/transactions/{$tx->id}", [
            'type' => 'expense',
            'amount' => 350000,
            'transaction_date' => '2026-11-05',
            'bucket' => 'needs',
            'description' => 'Makan Siang Revisi',
        ])->assertSessionHasNoErrors();

        $calc = new BudgetCalculatorService();
        $this->assertEquals(4650000, $calc->getMonthlySummary($budget->fresh())['estimated_remaining_cash']);

        $this->actingAs($this->user)->delete("/transactions/{$tx->id}")->assertRedirect();
        $this->assertSoftDeleted('transactions', ['id' => $tx->id]);
        $this->assertEquals(5000000, $calc->getMonthlySummary($budget->fresh())['estimated_remaining_cash']);
    }

    public function test_reports_handle_zero_previous_period_without_division_by_zero_and_support_filters(): void
    {
        $budget = MonthlyBudget::create([
            'user_id' => $this->user->id,
            'budget_month' => date('Y-m-01'),
            'opening_balance' => 3000000,
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'monthly_budget_id' => $budget->id,
            'type' => 'expense',
            'bucket' => 'wants',
            'amount' => 120000,
            'transaction_date' => date('Y-m-d'),
            'description' => 'Kopi Sore',
        ]);

        $resp = $this->actingAs($this->user)->get('/reports?period=month');
        $resp->assertStatus(200);
        $resp->assertSee('Belum ada pembanding periode sebelumnya');

        $this->actingAs($this->user)->get('/transactions?period=today&search=kopi')
            ->assertStatus(200)
            ->assertSee('Kopi Sore');
    }

    public function test_forgot_and_reset_password_flow(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
        $this->post('/forgot-password', ['email' => $this->user->email])->assertSessionHas('status');

        $token = Password::createToken($this->user);

        $this->get("/reset-password/{$token}?email=" . urlencode($this->user->email))->assertStatus(200);

        $resetResp = $this->post('/reset-password', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'NewSecret123!',
            'password_confirmation' => 'NewSecret123!',
        ]);

        $resetResp->assertRedirect('/login');
        $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'NewSecret123!',
        ])->assertRedirect('/onboarding');
    }

    public function test_admin_detail_settings_and_registration_toggle(): void
    {
        // Regular user blocked
        $this->actingAs($this->user)->get("/admin/users/{$this->otherUser->id}")->assertStatus(403);
        $this->actingAs($this->user)->get('/admin/settings')->assertStatus(403);

        // Admin views non-sensitive metadata
        $detailResp = $this->actingAs($this->admin)->get("/admin/users/{$this->user->id}");
        $detailResp->assertStatus(200);
        $detailResp->assertSee($this->user->email);

        // Admin disables registration
        $this->actingAs($this->admin)->post('/admin/settings', [
            'registration_enabled' => '0',
            'app_announcement' => 'Maintenance Akhir Pekan',
            'support_email' => 'help@duitmoo.test',
        ])->assertRedirect();

        $this->assertEquals('0', AppSetting::getValue('registration_enabled'));

        // Log out admin before testing guest registration endpoint
        $this->post('/logout');

        $this->post('/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@duitmoo.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_google_sheets_config_encrypts_tokens_and_enforces_user_isolation(): void
    {
        $spreadsheetId = GoogleSheetsSyncService::extractSpreadsheetId('https://docs.google.com/spreadsheets/d/1AbCdEfGhIjKlMnOpQrStUvWxYz123456/edit#gid=0');
        $this->assertEquals('1AbCdEfGhIjKlMnOpQrStUvWxYz123456', $spreadsheetId);

        $this->actingAs($this->user)->post('/settings/google-sheets/config', [
            'spreadsheet_input' => 'https://docs.google.com/spreadsheets/d/1AbCdEfGhIjKlMnOpQrStUvWxYz123456/edit',
            'spreadsheet_name' => 'Sheet Saya',
            'income_sheet_name' => 'Pemasukan',
            'expense_sheet_name' => 'Pengeluaran',
        ])->assertSessionHas('success');

        $conn = GoogleSheetConnection::where('user_id', $this->user->id)->first();
        $this->assertNotNull($conn);
        $conn->update(['access_token' => 'secret-oauth-token-xyz']);

        // Raw database value must be encrypted, never plaintext
        $rawRow = DB::table('google_sheet_connections')->where('id', $conn->id)->first();
        $this->assertNotEquals('secret-oauth-token-xyz', $rawRow->access_token);
        $this->assertEquals('secret-oauth-token-xyz', $conn->fresh()->access_token);

        // Isolation: otherUser cannot retry user's sync log
        $log = SyncLog::create([
            'user_id' => $this->user->id,
            'google_sheet_connection_id' => $conn->id,
            'direction' => 'push',
            'status' => 'failed',
            'idempotency_key' => 'test-key-123',
        ]);

        $this->actingAs($this->otherUser)->post("/settings/google-sheets/retry/{$log->id}")->assertStatus(403);
    }

    public function test_pwa_manifest_and_service_worker_assets_exist_and_valid(): void
    {
        $manifestPath = public_path('manifest.webmanifest');
        $this->assertFileExists($manifestPath);
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icons/icon-192.png'));
        $this->assertFileExists(public_path('icons/icon-512.png'));
        $this->assertFileExists(public_path('icons/icon.svg'));

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $this->assertEquals('DuitMoo', $manifest['short_name']);
        $this->assertEquals('standalone', $manifest['display']);
        $this->assertEquals('#FFD54F', $manifest['theme_color']);
    }
}
