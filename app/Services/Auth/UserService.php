<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\NotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * Paginate users with advanced filtering
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->when(isset($filters['is_active']), function ($q) use ($filters) {
                return $q->where('is_active', $filters['is_active']);
            })
            // 🔥 Fix: Group search conditions in a nested where to avoid OR-leakage
            ->when(filled($filters['search'] ?? null), function ($q) use ($filters) {
                $search = trim($filters['search']);
                return $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a user or throw exception
     */
    public function find(int $id): User
    {
        return User::find($id) ?? throw new NotFoundException('User not found.');
    }

    public function findWithTrashed(int $id): User
    {
        // fail-safe check: findOrFail already throws an exception,
        // but we'll use find + throw for consistency with your custom exception
        return User::withTrashed()->find($id)
            ?? throw new NotFoundException('User not found.');
    }

    /**
     * Create a new user
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $user;
        });
    }

    /**
     * Update existing user
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Filter out password so we don't accidentally update it to null
            $user->update(collect($data)->except('password')->toArray());

            if (!empty($data['password'])) {
                $user->update(['password' => Hash::make($data['password'])]);
            }

            return $user->refresh();
        });
    }

    public function delete(User $user)
    {
        $user->delete();
    }

    public function restore(User $user): void
    {
        if (!$user->trashed()) {
            throw new NotFoundException('User is not deleted.');
        }

        $user->restore();
    }

    public function forceDelete(User $user): void
    {
        $user->forceDelete();
    }
}
