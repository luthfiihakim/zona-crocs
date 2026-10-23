<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => 'penebak@example.com', 'password' => 'salah'])->assertStatus(302);
        }

        $this->post('/login', ['email' => 'penebak@example.com', 'password' => 'salah'])->assertStatus(429);
    }

    public function test_user_input_is_escaped_in_pages(): void
    {
        $payload = '<script>alert(1)</script>';
        $this->makeProduct(['name' => $payload]);

        $this->actingAs($this->makeUser('staff'))->get(route('products.index'))
            ->assertOk()
            ->assertDontSee($payload, false)
            ->assertSee(e($payload), false);

        $this->actingAs($this->makeUser('customer'))->get(route('catalog'))
            ->assertOk()
            ->assertDontSee($payload, false);
    }

    public function test_passwords_are_stored_hashed(): void
    {
        $user = $this->makeUser('customer');
        $stored = $user->fresh()->password;

        $this->assertNotSame('password123', $stored);
        $this->assertTrue(Hash::check('password123', $stored));
    }

    public function test_env_example_contains_no_secrets(): void
    {
        $content = file_get_contents(base_path('.env.example'));

        $this->assertStringNotContainsString('Mid-server-', $content);
        $this->assertStringNotContainsString('Mid-client-', $content);
        $this->assertMatchesRegularExpression('/^DB_PASSWORD=\s*$/m', $content);
        $this->assertMatchesRegularExpression('/^MIDTRANS_SERVER_KEY=\s*$/m', $content);
        $this->assertMatchesRegularExpression('/^APP_DEBUG=false\s*$/m', $content);
    }
}