<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use App\Models\UserPreference;

class UserInitializationService
{
    /**
     * Initialize default preferences and categories for a user.
     */
    public function initialize(User $user): void
    {
        // 1. Preferences
        if (!$user->preferences()->exists()) {
            UserPreference::create([
                'user_id' => $user->id,
                'default_needs_percent' => 60,
                'default_wants_percent' => 30,
                'default_emergency_percent' => 10,
                'theme_choice' => 'toy-brick',
                'timezone' => 'Asia/Jakarta',
                'date_format' => 'd M Y',
            ]);
        }

        $now = now();
        $batch = [];

        // 2. Default expense categories
        if ($user->categories()->where('transaction_type', 'expense')->count() === 0) {
            $defaultCategories = [
                // Needs
                ['bucket' => 'needs', 'transaction_type' => 'expense', 'name' => 'Makanan', 'icon' => 'utensils', 'color' => '#45C486'],
                ['bucket' => 'needs', 'transaction_type' => 'expense', 'name' => 'Transportasi', 'icon' => 'car', 'color' => '#45C486'],
                ['bucket' => 'needs', 'transaction_type' => 'expense', 'name' => 'Tagihan & Utilitas', 'icon' => 'receipt', 'color' => '#45C486'],
                ['bucket' => 'needs', 'transaction_type' => 'expense', 'name' => 'Pendidikan', 'icon' => 'book-open', 'color' => '#45C486'],
                ['bucket' => 'needs', 'transaction_type' => 'expense', 'name' => 'Kesehatan', 'icon' => 'heart-pulse', 'color' => '#45C486'],

                // Wants
                ['bucket' => 'wants', 'transaction_type' => 'expense', 'name' => 'Hiburan & Streaming', 'icon' => 'film', 'color' => '#FFD54F'],
                ['bucket' => 'wants', 'transaction_type' => 'expense', 'name' => 'Belanja & Shopping', 'icon' => 'shopping-bag', 'color' => '#FFD54F'],
                ['bucket' => 'wants', 'transaction_type' => 'expense', 'name' => 'Nongkrong & Kuliner', 'icon' => 'coffee', 'color' => '#FFD54F'],
                ['bucket' => 'wants', 'transaction_type' => 'expense', 'name' => 'Hobi & Game', 'icon' => 'gamepad-2', 'color' => '#FFD54F'],
                ['bucket' => 'wants', 'transaction_type' => 'expense', 'name' => 'Keinginan Lainnya', 'icon' => 'sparkles', 'color' => '#FFD54F'],

                // Emergency
                ['bucket' => 'emergency', 'transaction_type' => 'expense', 'name' => 'Keperluan Mendesak', 'icon' => 'alert-triangle', 'color' => '#9B7EDE'],
                ['bucket' => 'emergency', 'transaction_type' => 'expense', 'name' => 'Darurat Medis', 'icon' => 'shield-alert', 'color' => '#9B7EDE'],
                ['bucket' => 'emergency', 'transaction_type' => 'expense', 'name' => 'Perbaikan Darurat', 'icon' => 'wrench', 'color' => '#9B7EDE'],
            ];

            foreach ($defaultCategories as $cat) {
                $batch[] = array_merge($cat, [
                    'user_id' => $user->id,
                    'is_default' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 3. Default income categories
        if ($user->categories()->where('transaction_type', 'income')->count() === 0) {
            $incomeCategories = [
                ['bucket' => 'income', 'transaction_type' => 'income', 'name' => 'Gaji', 'icon' => 'briefcase', 'color' => '#45C486'],
                ['bucket' => 'income', 'transaction_type' => 'income', 'name' => 'Freelance', 'icon' => 'laptop', 'color' => '#407BFF'],
                ['bucket' => 'income', 'transaction_type' => 'income', 'name' => 'Bonus', 'icon' => 'award', 'color' => '#FFD54F'],
                ['bucket' => 'income', 'transaction_type' => 'income', 'name' => 'Hadiah', 'icon' => 'gift', 'color' => '#9B7EDE'],
                ['bucket' => 'income', 'transaction_type' => 'income', 'name' => 'Lainnya', 'icon' => 'plus-circle', 'color' => '#45C486'],
            ];

            foreach ($incomeCategories as $cat) {
                $batch[] = array_merge($cat, [
                    'user_id' => $user->id,
                    'is_default' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (!empty($batch)) {
            Category::insert($batch);
        }
    }
}
