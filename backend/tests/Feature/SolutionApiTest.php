<?php

namespace Tests\Feature;

use App\Models\Solution;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolutionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_published_solutions_by_title_ignoring_case(): void
    {
        foreach (['World resets', 'crash on start', 'Bedrock lag'] as $title) {
            Solution::factory()->create(['title' => $title]);
        }

        $titles = collect($this->getJson('/api/solutions')->assertOk()->json('data'))->pluck('title')->all();

        $this->assertSame(['Bedrock lag', 'crash on start', 'World resets'], $titles);
    }

    public function test_a_solution_has_the_expected_shape(): void
    {
        $tag = Tag::factory()->create(['name' => 'Forge', 'slug' => 'forge']);
        $solution = Solution::factory()->hasAttached($tag)->create([
            'title' => 'Out of memory',
            'edition' => 'java',
            'versions' => '1.20–1.21',
            'last_verified_on' => '2026-11-01',
        ]);

        $this->getJson("/api/solutions/{$solution->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', 'out-of-memory')
            ->assertJsonPath('data.edition', 'java')
            ->assertJsonPath('data.versions', '1.20–1.21')
            ->assertJsonPath('data.last_verified_on', '2026-11-01')
            ->assertJsonPath('data.tags', [['name' => 'Forge', 'slug' => 'forge']])
            ->assertJsonStructure(['data' => ['slug', 'title', 'symptoms', 'cause', 'fix', 'edition', 'versions', 'tags', 'last_verified_on', 'updated_at']]);
    }

    public function test_markdown_is_rendered_with_html_and_script_links_neutralised(): void
    {
        $solution = Solution::factory()->create([
            'symptoms' => null,
            'fix' => "Set **Xmx** lower.\n\n<script>alert(1)</script>\n\n[click](javascript:alert(1))",
        ]);

        $html = $this->getJson("/api/solutions/{$solution->slug}")->assertJsonPath('data.html.symptoms', null)->json('data.html.fix');

        $this->assertStringContainsString('<strong>Xmx</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_signatures_and_drafts_flag_are_not_exposed(): void
    {
        $solution = Solution::factory()->create();

        foreach (['signatures', 'is_published', 'id'] as $field) {
            $this->getJson("/api/solutions/{$solution->slug}")->assertJsonMissingPath("data.{$field}");
        }
    }

    public function test_drafts_are_hidden_everywhere(): void
    {
        $draft = Solution::factory()->draft()->create(['title' => 'Draft fix']);

        $this->getJson('/api/solutions')->assertJsonCount(0, 'data');
        $this->getJson('/api/solutions?search=draft')->assertJsonCount(0, 'data');
        $this->getJson("/api/solutions/{$draft->slug}")->assertNotFound();
    }

    public function test_search_matches_words_in_the_title_and_body(): void
    {
        Solution::factory()->create(['title' => 'Server crashes on start', 'fix' => 'Lower the view distance.']);
        Solution::factory()->create(['title' => 'Players cannot join', 'fix' => 'Check the whitelist.']);

        $this->getJson('/api/solutions?search=crashes')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Server crashes on start');
        $this->getJson('/api/solutions?search=whitelist')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Players cannot join');
    }

    public function test_search_tolerates_typos(): void
    {
        Solution::factory()->create(['title' => 'Server crashes with modpacks']);

        $this->getJson('/api/solutions?search=modpakcs')->assertJsonCount(1, 'data');
    }

    public function test_search_ranks_title_matches_above_body_matches(): void
    {
        Solution::factory()->create(['title' => 'Players cannot join', 'fix' => 'Usually a whitelist problem.']);
        Solution::factory()->create(['title' => 'Whitelist does not save']);

        $this->getJson('/api/solutions?search=whitelist')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Whitelist does not save');
    }

    public function test_it_filters_by_tag_and_edition(): void
    {
        $forge = Tag::factory()->create(['slug' => 'forge']);
        Solution::factory()->hasAttached($forge)->create(['title' => 'Forge java', 'edition' => 'java']);
        Solution::factory()->create(['title' => 'Plain bedrock', 'edition' => 'bedrock']);
        Solution::factory()->create(['title' => 'Everywhere', 'edition' => 'both']);

        $this->getJson('/api/solutions?tag=forge')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Forge java');

        // "both" applies to either edition, so it shows up under each.
        $java = collect($this->getJson('/api/solutions?edition=java')->json('data'))->pluck('title')->all();
        $this->assertSame(['Everywhere', 'Forge java'], $java);
        $bedrock = collect($this->getJson('/api/solutions?edition=bedrock')->json('data'))->pluck('title')->all();
        $this->assertSame(['Everywhere', 'Plain bedrock'], $bedrock);
    }

    public function test_invalid_list_parameters_are_rejected(): void
    {
        $this->getJson('/api/solutions?tag=does-not-exist')->assertUnprocessable()->assertJsonValidationErrors('tag');
        $this->getJson('/api/solutions?edition=pocket')->assertUnprocessable()->assertJsonValidationErrors('edition');
        $this->getJson('/api/solutions?per_page=101')->assertUnprocessable()->assertJsonValidationErrors('per_page');
        $this->getJson('/api/solutions?page=0')->assertUnprocessable()->assertJsonValidationErrors('page');
    }

    public function test_listing_does_not_lazy_load_tags(): void
    {
        Solution::factory()->count(3)->hasAttached(Tag::factory()->create())->create();
        Model::preventLazyLoading();

        try {
            $this->getJson('/api/solutions')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_the_api_is_otherwise_read_only(): void
    {
        $solution = Solution::factory()->create();

        $this->postJson('/api/solutions', [])->assertMethodNotAllowed();
        $this->putJson("/api/solutions/{$solution->slug}", [])->assertMethodNotAllowed();
        $this->deleteJson("/api/solutions/{$solution->slug}")->assertMethodNotAllowed();
    }
}
