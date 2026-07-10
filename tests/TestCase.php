<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function actingAsApiUser(?User $user = null): static
    {
        $user ??= User::factory()->create();
        $token = Auth::guard('api')->login($user);

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    protected function actingAsAdmin(?User $admin = null): static
    {
        $admin ??= User::factory()->create(['role' => User::ROLE_ADMIN]);
        $token = Auth::guard('api')->login($admin);

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
