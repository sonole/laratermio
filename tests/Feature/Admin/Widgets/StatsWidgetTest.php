<?php

use App\Filament\Widgets\StatsWidget;
use App\Models\ContactMessage;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\SkillCategory;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Livewire;

/**
 * The widget's stats as `label => [value, description]`.
 *
 * @return array<string, array{0: mixed, 1: ?string}>
 */
function adminStatsWidgetStats(): array
{
    $widget = Livewire::test(StatsWidget::class)->instance();
    $method = new ReflectionMethod($widget, 'getStats');

    return collect($method->invoke($widget))
        ->mapWithKeys(fn (Stat $stat) => [(string) $stat->getLabel() => [$stat->getValue(), $stat->getDescription() === null ? null : (string) $stat->getDescription()]])
        ->all();
}

beforeEach(function () {
    actingAsAdmin();
});

describe('stats widget', function () {
    it('shows a stat for each content type, all zero on an empty site', function () {
        $stats = adminStatsWidgetStats();

        expect(array_keys($stats))->toBe(['Experiences', 'Education', 'Skill Categories', 'Projects', 'Messages'])
            ->and(collect($stats)->map(fn (array $stat) => $stat[0])->all())->each->toBe(0);
    });

    it('counts the records of each type', function () {
        Experience::factory()->count(3)->create();
        Education::factory()->count(2)->create();
        SkillCategory::factory()->count(4)->create();
        Project::factory()->count(5)->create();
        ContactMessage::factory()->count(6)->create();

        $stats = adminStatsWidgetStats();

        expect($stats['Experiences'][0])->toBe(3)
            ->and($stats['Education'][0])->toBe(2)
            ->and($stats['Skill Categories'][0])->toBe(4)
            ->and($stats['Projects'][0])->toBe(5)
            ->and($stats['Messages'][0])->toBe(6);
    });

    it('only counts active content', function () {
        Experience::factory()->count(2)->create();
        Experience::factory()->inactive()->count(3)->create();
        Education::factory()->inactive()->create();
        SkillCategory::factory()->create();
        SkillCategory::factory()->inactive()->create();
        Project::factory()->inactive()->count(2)->create();

        $stats = adminStatsWidgetStats();

        expect($stats['Experiences'][0])->toBe(2)
            ->and($stats['Education'][0])->toBe(0)
            ->and($stats['Skill Categories'][0])->toBe(1)
            ->and($stats['Projects'][0])->toBe(0);
    });

    it('counts every message, and how many arrived today', function () {
        ContactMessage::factory()->count(2)->create();
        ContactMessage::factory()->create(['created_at' => now()->subDays(3)]);

        $messages = adminStatsWidgetStats()['Messages'];

        expect($messages[0])->toBe(3)
            ->and($messages[1])->toBe('2 today');
    });

    it('renders the labels and values', function () {
        Experience::factory()->count(7)->create();

        Livewire::test(StatsWidget::class)
            ->assertSee('Experiences')
            ->assertSee('Skill Categories')
            ->assertSee('Messages')
            ->assertSee('0 today')
            ->assertSeeInOrder(['Experiences', '7']);
    });
});
