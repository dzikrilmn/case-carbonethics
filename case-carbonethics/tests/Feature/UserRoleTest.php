<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_defaults_to_user_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_factory_can_create_admin_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isUser());
    }
}
