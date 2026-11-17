<?php

namespace Database\Seeders;

use App\Models\MonthlyBudget;
use App\Models\Transaction;
use App\Models\User;
use App\Services\UserInitializationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(UserInitializationService $initService): void
    {
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@duitmoo.test'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'is_active' => true,
            ]
        );

        $initService->initialize($demoUser);

        // Fetch user categories grouped by bucket
        $categories = $demoUser->categories()->get()->groupBy('bucket');

        // Seed 6 months of realistic budgeting history (June 2026 to Nov 2026)
        $months = [
            ['month' => '2026-06-01', 'opening' => 4500000, 'income' => 5000000],
            ['month' => '2026-07-01', 'opening' => 5000000, 'income' => 5500000],
            ['month' => '2026-08-01', 'opening' => 4800000, 'income' => 6000000],
            ['month' => '2026-09-01', 'opening' => 5200000, 'income' => 5000000],
            ['month' => '2026-10-01', 'opening' => 5000000, 'income' => 6500000],
            ['month' => '2026-11-01', 'opening' => 5000000, 'income' => 6000000],
        ];

        foreach ($months as $m) {
            $budget = MonthlyBudget::updateOrCreate(
                [
                    'user_id' => $demoUser->id,
                    'budget_month' => $m['month'],
                ],
                [
                    'opening_balance' => $m['opening'],
                    'needs_percent' => 60,
                    'wants_percent' => 30,
                    'emergency_percent' => 10,
                ]
            );

            // Clean existing transactions for this month if any
            Transaction::where('monthly_budget_id', $budget->id)->delete();

            $dateBase = Carbon::parse($m['month']);

            // 1. Income: Gaji
            Transaction::create([
                'user_id' => $demoUser->id,
                'monthly_budget_id' => $budget->id,
                'type' => 'income',
                'amount' => $m['income'],
                'transaction_date' => $dateBase->copy()->addDays(2),
                'income_source' => 'Gaji Bulanan',
                'description' => 'Transfer gaji bulanan kantor',
            ]);

            // Additional freelance income in some months
            if ($m['income'] > 5000000) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'monthly_budget_id' => $budget->id,
                    'type' => 'income',
                    'amount' => 1500000,
                    'transaction_date' => $dateBase->copy()->addDays(15),
                    'income_source' => 'Freelance Web Design',
                    'description' => 'Pelunasan landing page client',
                ]);
            }

            // 2. Expenses - Needs
            $needsCats = $categories->get('needs', collect());
            $foodCat = $needsCats->firstWhere('name', 'Makanan');
            $transCat = $needsCats->firstWhere('name', 'Transportasi');
            $billCat = $needsCats->firstWhere('name', 'Tagihan & Utilitas');

            if ($foodCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'monthly_budget_id' => $budget->id,
                    'type' => 'expense',
                    'bucket' => 'needs',
                    'category_id' => $foodCat->id,
                    'amount' => 1800000,
                    'transaction_date' => $dateBase->copy()->addDays(5),
                    'description' => 'Belanja mingguan supermarket & makan harian',
                ]);
            }

            if ($transCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'monthly_budget_id' => $budget->id,
                    'type' => 'expense',
                    'bucket' => 'needs',
                    'category_id' => $transCat->id,
                    'amount' => 500000,
                    'transaction_date' => $dateBase->copy()->addDays(8),
                    'description' => 'Isi bensin motor & e-toll',
                ]);
            }

            if ($billCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'monthly_budget_id' => $budget->id,
                    'type' => 'expense',
                    'bucket' => 'needs',
                    'category_id' => $billCat->id,
                    'amount' => 650000,
                    'transaction_date' => $dateBase->copy()->addDays(10),
                    'description' => 'Listrik PLN & langganan internet WiFi',
                ]);
            }

            // 3. Expenses - Wants
            $wantsCats = $categories->get('wants', collect());
            $coffeeCat = $wantsCats->firstWhere('name', 'Nongkrong & Kuliner');
            $shopCat = $wantsCats->firstWhere('name', 'Belanja & Shopping');
            $streamCat = $wantsCats->firstWhere('name', 'Hiburan & Streaming');

            if ($coffeeCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'monthly_budget_id' => $budget->id,
                    'type' => 'expense',
                    'bucket' => 'wants',
                    'category_id' => $coffeeCat->id,
                    'amount' => 450000,
                    'transaction_date' => $dateBase->copy()->addDays(12),
                    'description' => 'Kopi sore & nongkrong weekend bareng teman',
                ]);
            }

            if ($shopCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'monthly_budget_id' => $budget->id,
                    'type' => 'expense',
                    'bucket' => 'wants',
                    'category_id' => $shopCat->id,
                    'amount' => 600000,
                    'transaction_date' => $dateBase->copy()->addDays(18),
                    'description' => 'Beli kaos baru & perlengkapan hobi',
                ]);
            }

            if ($streamCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'monthly_budget_id' => $budget->id,
                    'type' => 'expense',
                    'bucket' => 'wants',
                    'category_id' => $streamCat->id,
                    'amount' => 186000,
                    'transaction_date' => $dateBase->copy()->addDays(20),
                    'description' => 'Langganan Netflix & Spotify Family',
                ]);
            }

            // 4. Expenses - Emergency
            $emergCats = $categories->get('emergency', collect());
            $medCat = $emergCats->firstWhere('name', 'Darurat Medis');
            $repairCat = $emergCats->firstWhere('name', 'Perbaikan Darurat');

            if ($m['month'] === '2026-08-01' && $repairCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'monthly_budget_id' => $budget->id,
                    'type' => 'expense',
                    'bucket' => 'emergency',
                    'category_id' => $repairCat->id,
                    'amount' => 350000,
                    'transaction_date' => $dateBase->copy()->addDays(22),
                    'description' => 'Ganti ban bocor & servis rem darurat',
                ]);
            }

            if ($m['month'] === '2026-10-01' && $medCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'monthly_budget_id' => $budget->id,
                    'type' => 'expense',
                    'bucket' => 'emergency',
                    'category_id' => $medCat->id,
                    'amount' => 450000,
                    'transaction_date' => $dateBase->copy()->addDays(25),
                    'description' => 'Klinik gigi darurat & resep obat',
                ]);
            }
        }
    }
}
