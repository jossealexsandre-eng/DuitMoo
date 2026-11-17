<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MonthlyBudget;
use App\Models\Transaction;
use App\Models\User;
use App\Services\UserInitializationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $init = new UserInitializationService();

        $this->userA = User::factory()->create(['name' => 'User A', 'role' => 'user', 'is_active' => true]);
        $init->initialize($this->userA);

        $this->userB = User::factory()->create(['name' => 'User B', 'role' => 'user', 'is_active' => true]);
        $init->initialize($this->userB);

        $this->admin = User::factory()->create(['name' => 'Admin User', 'role' => 'admin', 'is_active' => true]);
    }

    public function test_user_a_cannot_update_or_delete_user_b_transaction(): void
    {
        $budgetB = MonthlyBudget::create([
            'user_id' => $this->userB->id,
            'budget_month' => '2026-11-01',
            'opening_balance' => 3000000,
        ]);

        $txB = Transaction::create([
            'user_id' => $this->userB->id,
            'monthly_budget_id' => $budgetB->id,
            'type' => 'expense',
            'bucket' => 'needs',
            'amount' => 150000,
            'transaction_date' => '2026-11-04',
            'description' => 'Makan Siang User B',
        ]);

        // User A attempts to edit User B's transaction
        $responseUpdate = $this->actingAs($this->userA)->put("/transactions/{$txB->id}", [
            'type' => 'expense',
            'amount' => 999999,
            'transaction_date' => '2026-11-04',
            'bucket' => 'needs',
            'description' => 'Hacked by User A',
        ]);

        $responseUpdate->assertStatus(403);
        $this->assertEquals(150000, $txB->fresh()->amount);

        // User A attempts to delete User B's transaction
        $responseDelete = $this->actingAs($this->userA)->delete("/transactions/{$txB->id}");
        $responseDelete->assertStatus(403);
        $this->assertDatabaseHas('transactions', ['id' => $txB->id]);
    }

    public function test_user_a_cannot_modify_user_b_monthly_budget(): void
    {
        $budgetB = MonthlyBudget::create([
            'user_id' => $this->userB->id,
            'budget_month' => '2026-11-01',
            'opening_balance' => 3000000,
            'needs_percent' => 60,
            'wants_percent' => 30,
            'emergency_percent' => 10,
        ]);

        $response = $this->actingAs($this->userA)->post('/budget/settings/snapshot', [
            'budget_id' => $budgetB->id,
            'opening_balance' => 99999999,
            'needs_percent' => 50,
            'wants_percent' => 30,
            'emergency_percent' => 20,
        ]);

        // Model query is scoped where id = ... and user_id = auth->id -> throws 404
        $response->assertStatus(404);
        $this->assertEquals(3000000, $budgetB->fresh()->opening_balance);
    }

    public function test_user_a_cannot_delete_user_b_category(): void
    {
        $catB = Category::create([
            'user_id' => $this->userB->id,
            'bucket' => 'wants',
            'name' => 'Kategori Rahasia B',
        ]);

        $response = $this->actingAs($this->userA)->delete("/budget/category/{$catB->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('categories', ['id' => $catB->id]);
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->userA)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_dashboard_and_toggle_status(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Daftar Pengguna Aplikasi');

        // Admin toggles User A status (deactivate)
        $toggleResponse = $this->actingAs($this->admin)->patch("/admin/users/{$this->userA->id}/toggle");
        $toggleResponse->assertRedirect();
        $this->assertFalse($this->userA->fresh()->is_active);

        // Deactivated User A tries to access dashboard and is kicked out
        $userAResponse = $this->actingAs($this->userA->fresh())->get('/dashboard');
        $userAResponse->assertRedirect('/login');
    }
}
