<?php

namespace Tests\Feature;

use App\Models\Solution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SolutionLogMatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function matchLog(string $log, string $link = 'https://mclo.gs/abc123'): TestResponse
    {
        Http::fake(['https://api.mclo.gs/1/raw/abc123' => Http::response($log)]);

        return $this->postJson('/api/solutions/match', ['url' => $link]);
    }

    public function test_an_mclogs_link_finds_solutions_whose_signatures_the_log_contains_ignoring_case(): void
    {
        Solution::factory()->withSignatures(['java.lang.OutOfMemoryError'])->create(['title' => 'Out of memory']);
        Solution::factory()->withSignatures(['Mixin apply failed'])->create(['title' => 'Mixin crash']);

        $this->matchLog("[12:00:00] ERROR\njava.lang.outofmemoryerror: Java heap space")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Out of memory');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.mclo.gs/1/raw/abc123');
    }

    public function test_common_ways_of_writing_the_link_are_accepted(): void
    {
        Solution::factory()->withSignatures(['boom'])->create();

        foreach (['https://mclo.gs/abc123', 'http://mclo.gs/abc123/', 'mclo.gs/abc123', '  https://www.mclo.gs/abc123  '] as $link) {
            $this->matchLog('boom', $link)->assertOk()->assertJsonCount(1, 'data');
        }
    }

    public function test_other_sites_are_refused_without_fetching_anything(): void
    {
        Http::fake();

        foreach (['https://pastebin.com/abc123', 'https://mclo.gs.evil.com/abc123', 'https://mclo.gs/abc/../x', 'http://localhost/abc123', ''] as $link) {
            $this->postJson('/api/solutions/match', ['url' => $link])->assertUnprocessable()->assertJsonValidationErrors('url');
        }

        Http::assertNothingSent();
    }

    public function test_a_log_missing_on_mclogs_is_reported_on_the_link(): void
    {
        Http::fake(['https://api.mclo.gs/*' => Http::response(['success' => false, 'error' => 'Log not found.'], 404)]);

        $this->postJson('/api/solutions/match', ['url' => 'https://mclo.gs/gone'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url' => 'couldn’t find that log']);
    }

    public function test_mclogs_being_down_returns_a_bad_gateway(): void
    {
        Http::fake(['https://api.mclo.gs/*' => fn () => throw new ConnectionException('timed out')]);
        $this->postJson('/api/solutions/match', ['url' => 'https://mclo.gs/abc123'])->assertStatus(502);

        Http::fake(['https://api.mclo.gs/*' => Http::response('oops', 500)]);
        $this->postJson('/api/solutions/match', ['url' => 'https://mclo.gs/abc123'])->assertStatus(502);
    }

    public function test_solutions_matching_more_signatures_come_first(): void
    {
        Solution::factory()->withSignatures(['Mixin apply failed'])->create(['title' => 'One hit']);
        Solution::factory()->withSignatures(['Mixin apply failed', 'sodium'])->create(['title' => 'Two hits']);

        $this->matchLog('Mixin apply failed sodium.mixins.json')
            ->assertJsonPath('data.0.title', 'Two hits')
            ->assertJsonPath('data.1.title', 'One hit');
    }

    public function test_signatures_are_plain_text_not_patterns(): void
    {
        Solution::factory()->withSignatures(['.*'])->create();

        $this->matchLog('nothing special here')->assertJsonCount(0, 'data');
    }

    public function test_drafts_are_never_matched(): void
    {
        Solution::factory()->draft()->withSignatures(['OutOfMemoryError'])->create();

        $this->matchLog('java.lang.OutOfMemoryError')->assertJsonCount(0, 'data');
    }
}
