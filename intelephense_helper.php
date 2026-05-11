<?php

namespace Illuminate\Contracts\Auth;

/**
 * This file exists to override Intelephense's type hints for Laravel's helper functions,
 * which are too complex for it to understand, leading to false "undefined method" errors.
 */

interface Guard
{
    /**
     * Get the currently authenticated user.
     *
     * @return \App\Models\User|null
     */
    public function user();

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|string|null
     */
    public function id();
}

interface StatefulGuard
{
    /**
     * Get the currently authenticated user.
     *
     * @return \App\Models\User|null
     */
    public function user();

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|string|null
     */
    public function id();
}

interface Factory
{
    /**
     * Get the currently authenticated user.
     *
     * @return \App\Models\User|null
     */
    public function user();
}