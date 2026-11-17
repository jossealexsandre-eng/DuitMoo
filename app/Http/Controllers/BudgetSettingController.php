<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MonthlyBudget;
use App\Models\UserPreference;
use App\Services\BudgetCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BudgetSettingController extends Controller
{
    public function index(Request $request, BudgetCalculatorService $calculator): View
    {
        $user = Auth::user();

        $selectedMonth = $request->query('month', Carbon::now()->format('Y-m'));
        $firstDay = Carbon::parse($selectedMonth . '-01')->format('Y-m-d');

        // Preferences
        $prefs = $user->preferences ?? UserPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'default_needs_percent' => 60,
                'default_wants_percent' => 30,
                'default_emergency_percent' => 10,
            ]
        );

        // Specific Monthly Budget Snapshot
        $monthlyBudget = MonthlyBudget::where('user_id', $user->id)
            ->whereDate('budget_month', $firstDay)
            ->first();

        if (!$monthlyBudget) {
            $monthlyBudget = MonthlyBudget::create([
                'user_id' => $user->id,
                'budget_month' => $firstDay,
                'opening_balance' => 0,
                'needs_percent' => $prefs->default_needs_percent,
                'wants_percent' => $prefs->default_wants_percent,
                'emergency_percent' => $prefs->default_emergency_percent,
            ]);
        }

        $categories = Category::where('user_id', $user->id)->get()->groupBy('bucket');

        // Available months list
        $availableMonths = [];
        $today = Carbon::now();
        for ($m = -5; $m <= 6; $m++) {
            $opt = $today->copy()->addMonths($m);
            $availableMonths[$opt->format('Y-m')] = $opt->translatedFormat('F Y');
        }

        return view('budget.settings', [
            'prefs' => $prefs,
            'monthlyBudget' => $monthlyBudget,
            'selectedMonth' => $selectedMonth,
            'monthLabel' => Carbon::parse($firstDay)->translatedFormat('F Y'),
            'availableMonths' => $availableMonths,
            'categories' => $categories,
        ]);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'default_needs_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'default_wants_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'default_emergency_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $total = $validated['default_needs_percent'] + $validated['default_wants_percent'] + $validated['default_emergency_percent'];
        if ($total !== 100) {
            return back()->withErrors(['default_percent' => "Total persentase harus tepat 100%. Saat ini: {$total}%."])->withInput();
        }

        UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return back()->with('success', 'Preferensi persentase default berhasil diperbarui untuk bulan-bulan baru mendatang.');
    }

    public function updateMonthlySnapshot(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'budget_id' => ['required', 'exists:monthly_budgets,id'],
            'opening_balance' => ['required', 'integer', 'min:0'],
            'needs_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'wants_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'emergency_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $total = $validated['needs_percent'] + $validated['wants_percent'] + $validated['emergency_percent'];
        if ($total !== 100) {
            return back()->withErrors(['percent' => "Total persentase bulan ini harus tepat 100%. Saat ini: {$total}%."])->withInput();
        }

        $budget = MonthlyBudget::where('id', $validated['budget_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $budget->update([
            'opening_balance' => $validated['opening_balance'],
            'needs_percent' => $validated['needs_percent'],
            'wants_percent' => $validated['wants_percent'],
            'emergency_percent' => $validated['emergency_percent'],
        ]);

        return back()->with('success', "Anggaran untuk bulan {$budget->budget_month->translatedFormat('F Y')} berhasil diperbarui!");
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'bucket' => ['required', 'in:needs,wants,emergency,income'],
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        Category::create([
            'user_id' => $user->id,
            'bucket' => $validated['bucket'],
            'transaction_type' => $validated['bucket'] === 'income' ? 'income' : 'expense',
            'name' => $validated['name'],
            'color' => $validated['color'] ?: match($validated['bucket']) {
                'needs' => '#45C486',
                'wants' => '#FFD54F',
                'emergency' => '#9B7EDE',
                'income' => '#407BFF',
            },
            'is_default' => false,
            'is_active' => true,
        ]);

        return back()->with('success', "Kategori baru '{$validated['name']}' berhasil ditambahkan!");
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $user = Auth::user();

        if ($category->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $category->update($validated);

        return back()->with('success', "Kategori '{$category->name}' berhasil diperbarui!");
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        $user = Auth::user();

        if ($category->user_id !== $user->id) {
            abort(403);
        }

        if ($category->transactions()->count() > 0) {
            return back()->withErrors(['category' => "Kategori '{$category->name}' tidak dapat dihapus karena telah digunakan pada catatan transaksi."]);
        }

        $category->delete();

        return back()->with('success', "Kategori berhasil dihapus.");
    }
}
