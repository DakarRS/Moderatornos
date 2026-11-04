<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    private const LIMIT = 120;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('');
    }

    private function hitApi(int $times, array $headers = []): int
    {
        $status = 0;
        for ($i = 0; $i < $times; $i++) {
            $status = $this->withHeaders($headers)->getJson('/api/categories')->getStatusCode();
        }

        return $status;
    }

    public function test_public_visitors_are_rate_limited(): void
    {
        $visitor = ['X-Forwarded-For' => '203.0.113.1'];

        $this->assertSame(200, $this->hitApi(self::LIMIT, $visitor));
        $this->assertSame(429, $this->hitApi(1, $visitor));
    }

    public function test_one_visitor_being_limited_does_not_affect_another(): void
    {
        $this->hitApi(self::LIMIT + 1, ['X-Forwarded-For' => '203.0.113.2']);

        $this->assertSame(200, $this->hitApi(1, ['X-Forwarded-For' => '203.0.113.3']));
    }

    public function test_the_frontend_server_is_not_rate_limited(): void
    {
        // Every page view reaches Laravel from the one frontend server, directly and without proxy headers.
        $this->assertSame(200, $this->hitApi(self::LIMIT + 5));
    }

    public function test_a_spoofed_cloudflare_header_cannot_dodge_the_limit(): void
    {
        for ($i = 0; $i < self::LIMIT; $i++) {
            $this->hitApi(1, ['X-Forwarded-For' => '198.51.100.7', 'CF-Connecting-IP' => "203.0.113.{$i}"]);
        }

        $this->assertSame(429, $this->hitApi(1, ['X-Forwarded-For' => '198.51.100.7', 'CF-Connecting-IP' => '192.0.2.1']));
    }

    public function test_https_from_the_proxy_is_respected_in_generated_urls(): void
    {
        $this->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'moderatornos.test'])
            ->get('/admin/login')
            ->assertOk()
            ->assertSee('https://moderatornos.test', false);
    }

    public function test_the_health_check_works_without_a_session(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_multi_factor_login_is_required_only_in_production(): void
    {
        $panel = Filament::getPanel('admin');
        $this->assertTrue($panel->hasMultiFactorAuthentication());
        $this->assertFalse($panel->isMultiFactorAuthenticationRequired());

        $this->app->detectEnvironment(fn () => 'production');
        $this->assertTrue($panel->isMultiFactorAuthenticationRequired());
    }

    public function test_multi_factor_secrets_are_encrypted_and_never_serialized(): void
    {
        $user = User::factory()->create();
        $user->saveAppAuthenticationSecret('SUPERSECRET');
        $user->saveAppAuthenticationRecoveryCodes(['code-one']);

        $row = DB::table('users')->find($user->id);
        $this->assertStringNotContainsString('SUPERSECRET', $row->app_authentication_secret);
        $this->assertStringNotContainsString('code-one', $row->app_authentication_recovery_codes);
        $this->assertSame('SUPERSECRET', $user->fresh()->getAppAuthenticationSecret());
        $this->assertArrayNotHasKey('app_authentication_secret', $user->fresh()->toArray());
        $this->assertArrayNotHasKey('app_authentication_recovery_codes', $user->fresh()->toArray());
    }

    public function test_the_seeder_creates_nothing_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        // Called directly, because db:seed quietly refuses to run in production without --force.
        (new DatabaseSeeder)->run();

        $this->assertSame(0, User::count());
        $this->assertSame(0, Person::count());
    }
}
