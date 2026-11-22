<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_blocked_after_twenty_requests(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->get('/login')->assertOk();
        }

        $this->get('/login')->assertStatus(429);
    }

    public function test_authenticated_user_is_blocked_after_sixty_requests(): void
    {
        $user = User::factory()->create(['role' => 'paciente']);

        $this->actingAs($user);

        for ($i = 0; $i < 60; $i++) {
            $this->get('/profile/edit')->assertOk();
        }

        $this->get('/profile/edit')->assertStatus(429);
    }

    public function test_admin_is_exempt_from_rate_limit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);

        for ($i = 0; $i < 65; $i++) {
            $this->get('/profile/edit')->assertOk();
        }
    }
}
