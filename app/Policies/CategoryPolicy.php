<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function view(User $user, Category $category): bool
    {
        return $user->id === $category->user_id;
    }

    public function update(User $user, Category $category): bool
    {
        return $user->id === $category->user_id;
    }

    public function delete(User $user, Category $category): bool
    {
        // Must belong to user and not be currently associated with transactions
        if ($user->id !== $category->user_id) {
            return false;
        }

        return $category->transactions()->count() === 0;
    }
}
