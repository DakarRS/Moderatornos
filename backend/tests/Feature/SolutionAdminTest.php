<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Solutions\Pages\CreateSolution;
use App\Filament\Resources\Solutions\Pages\EditSolution;
use App\Filament\Resources\Tags\Pages\ManageTags;
use App\Models\Solution;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class SolutionAdminTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::factory()->create(['role' => UserRole::Editor]);
    }

    public function test_editors_can_write_a_solution_with_tags_and_signatures(): void
    {
        $this->actingAs($this->editor());
        $tag = Tag::factory()->create();

        Livewire::test(CreateSolution::class)
            ->fillForm([
                'title' => 'Out of memory',
                'fix' => 'Remove some mods.',
                'edition' => 'java',
                'tags' => [$tag->id],
                'signatures' => [['pattern' => 'java.lang.OutOfMemoryError']],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $solution = Solution::firstWhere('title', 'Out of memory');
        $this->assertSame('out-of-memory', $solution->slug);
        $this->assertFalse($solution->is_published);
        $this->assertTrue($solution->tags->contains($tag));
        $this->assertSame(['java.lang.OutOfMemoryError'], $solution->signatures->pluck('pattern')->all());
    }

    public function test_duplicate_titles_get_unique_slugs(): void
    {
        $first = Solution::factory()->create(['title' => 'World resets']);
        $second = Solution::factory()->create(['title' => 'World resets']);

        $this->assertSame('world-resets', $first->slug);
        $this->assertSame('world-resets-2', $second->slug);
    }

    public function test_renaming_keeps_the_slug_unless_it_is_edited(): void
    {
        $this->actingAs($this->editor());
        $solution = Solution::factory()->create(['title' => 'World resets']);

        // Links shared in Discord keep working after a rename.
        Livewire::test(EditSolution::class, ['record' => $solution->getRouteKey()])
            ->fillForm(['title' => 'World resets after restart'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('world-resets', $solution->fresh()->slug);

        Livewire::test(EditSolution::class, ['record' => $solution->getRouteKey()])
            ->fillForm(['slug' => 'world-resets-after-restart'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('world-resets-after-restart', $solution->fresh()->slug);
    }

    public function test_mark_verified_sets_today(): void
    {
        Carbon::setTestNow('2026-11-15');
        $this->actingAs($this->editor());
        $solution = Solution::factory()->create(['last_verified_on' => '2025-01-01']);

        Livewire::test(EditSolution::class, ['record' => $solution->getRouteKey()])
            ->callAction('markVerified');

        $this->assertSame('2026-11-15', $solution->fresh()->last_verified_on->toDateString());
    }

    public function test_editors_cannot_manage_tags(): void
    {
        $editor = $this->editor();
        $this->actingAs($editor);
        $tag = Tag::factory()->create();

        Livewire::test(ManageTags::class)->assertForbidden();
        foreach (['viewAny', 'create', 'deleteAny'] as $ability) {
            $this->assertFalse($editor->can($ability, Tag::class), "tags {$ability}");
        }
        foreach (['update', 'delete'] as $ability) {
            $this->assertFalse($editor->can($ability, $tag), "tag {$ability}");
        }
    }

    public function test_tags_get_a_slug_from_their_name(): void
    {
        $this->assertSame('modded-servers', Tag::create(['name' => 'Modded servers'])->slug);
    }
}
