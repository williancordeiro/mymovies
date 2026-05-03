<?php

namespace Tests\Unit\Lib\Authentication;

use App\Models\User;
use Core\Env\EnvLoader;
use Database\Populate\UsersPopulate;
use Lib\Authentication\Auth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private User $user;
    private User $admin;

    public function setUp(): void
    {
        parent::setUp();
        EnvLoader::init();
        UsersPopulate::populate();

        $this->user = User::findByEmail('example@email.com');
        $this->admin = User::findByEmail('admin@email.com');
    }

    public function test_should_generate_token_for_user(): void
    {
        $token = Auth::generateToken($this->user);
        $this->assertNotEmpty($token);
        $this->assertStringContainsString('.', $token);
    }

    public function test_should_validate_token(): void
    {
        $token = Auth::generateToken($this->user);
        $decoded = Auth::validateToken($token);
        $this->assertNotNull($decoded);
        $this->assertEquals($this->user->id, $decoded->sub);
        $this->assertEquals($this->user->email, $decoded->email);
    }

    public function test_should_return_null_for_invalid_token(): void
    {
        $decoded = Auth::validateToken('invalid.token.here');
        $this->assertNull($decoded);
    }

    public function test_should_return_user_from_token(): void
    {
        $token = Auth::generateToken($this->user);
        $user = Auth::user($token);
        $this->assertNotNull($user);
        $this->assertEquals($this->user->id, $user->id);
        $this->assertEquals($this->user->email, $user->email);
    }

    public function test_should_return_null_user_for_invalid_token(): void
    {
        $user = Auth::user('invalid.token.here');
        $this->assertNull($user);
    }

    public function test_token_should_contain_admin_email(): void
    {
        $token = Auth::generateToken($this->admin);
        $decoded = Auth::validateToken($token);
        $this->assertNotNull($decoded);
        $this->assertEquals($this->admin->email, $decoded->email);
    }
}
