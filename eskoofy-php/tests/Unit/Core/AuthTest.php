<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Auth;
use App\Core\Session;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_check_returns_false_when_not_logged_in(): void
    {
        $this->assertFalse(Auth::check());
    }

    public function test_login_sets_session(): void
    {
        Auth::login(['id' => 1, 'role' => 'admin']);
        $this->assertTrue(Auth::check());
        $this->assertSame(1, Auth::id());
        $this->assertSame('admin', Auth::role());
    }

    public function test_logout_clears_session(): void
    {
        Auth::login(['id' => 1, 'role' => 'admin']);
        $this->assertTrue(Auth::check());

        Auth::logout();
        $this->assertFalse(Auth::check());
        $this->assertNull(Auth::id());
        $this->assertNull(Auth::role());
    }

    public function test_has_role(): void
    {
        Auth::login(['id' => 1, 'role' => 'admin']);
        $this->assertTrue(Auth::hasRole('admin'));
        $this->assertTrue(Auth::hasRole('admin', 'super_admin'));
        $this->assertFalse(Auth::hasRole('teacher'));
    }

    public function test_hash_password(): void
    {
        $hash = Auth::hashPassword('password123');
        $this->assertNotSame('password123', $hash);
        $this->assertTrue(password_verify('password123', $hash));
        $this->assertTrue(str_starts_with($hash, '$2y$'));
    }

    public function test_create_token(): void
    {
        $token1 = Auth::createToken();
        $token2 = Auth::createToken();

        $this->assertIsString($token1);
        $this->assertNotSame($token1, $token2);
        $this->assertSame(64, strlen($token1));
    }

    public function test_user_returns_null_when_not_logged_in(): void
    {
        $this->assertNull(Auth::user());
    }
}
