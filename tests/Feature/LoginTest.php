<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function createRolesAndPermissions(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'nombre' => 'Superadmin', 'descripcion' => 'Acceso total'],
        ]);
    }

    public function test_login_form_is_displayed(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->createRolesAndPermissions();

        $user = User::factory()->admin()->create([
            'password' => bcrypt('Password1'),
        ]);

        $response = $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'Password1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $this->createRolesAndPermissions();

        User::factory()->admin()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Password1'),
        ]);

        $response = $this->post(route('login'), [
            'login' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->createRolesAndPermissions();

        $user = User::factory()->admin()->inactive()->create([
            'password' => bcrypt('Password1'),
        ]);

        $response = $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'Password1',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_rate_limiter_blocks_after_5_attempts(): void
    {
        $this->createRolesAndPermissions();

        $user = User::factory()->admin()->create([
            'password' => bcrypt('Password1'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit('login:' . strtolower($user->email), 900);
        }

        $response = $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'Password1',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }
}
