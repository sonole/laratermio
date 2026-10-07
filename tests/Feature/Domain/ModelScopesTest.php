<?php

use App\Models\ContactItem;
use App\Models\Education;
use App\Models\Experience;
use App\Models\NavItem;
use App\Models\Project;
use App\Models\Setting;
use App\Models\SkillCategory;
use App\Models\TerminalCommand;

describe('HasActive (TerminalCommand)', function () {
    it('returns only active records', function () {
        $active = TerminalCommand::factory()->create(['name' => 'alpha']);
        TerminalCommand::factory()->inactive()->create(['name' => 'beta']);

        expect(TerminalCommand::active()->pluck('id')->all())->toBe([$active->id]);
    });

    it('returns nothing when every record is inactive', function () {
        TerminalCommand::factory()->inactive()->count(2)->create();

        expect(TerminalCommand::active()->get())->toBeEmpty();
    });

    it('can be chained onto other constraints', function () {
        TerminalCommand::factory()->create(['name' => 'alpha']);
        TerminalCommand::factory()->create(['name' => 'beta']);

        expect(TerminalCommand::active()->where('name', 'beta')->count())->toBe(1);
    });
});

describe('HasActiveOrder', function () {
    it('scopes active records on every model that uses it', function (string $model) {
        $model::factory()->create(['sort_order' => 1]);
        $model::factory()->inactive()->create(['sort_order' => 0]);

        expect($model::active()->count())->toBe(1)
            ->and($model::query()->count())->toBe(2);
    })->with([Experience::class, Education::class, Project::class, SkillCategory::class, ContactItem::class, NavItem::class]);

    it('orders by sort_order, including inactive records', function () {
        $third = Experience::factory()->create(['sort_order' => 30]);
        $first = Experience::factory()->inactive()->create(['sort_order' => 10]);
        $second = Experience::factory()->create(['sort_order' => 20]);

        expect(Experience::ordered()->pluck('id')->all())->toBe([$first->id, $second->id, $third->id]);
    });

    it('combines both with activeOrdered()', function () {
        $later = Experience::factory()->create(['sort_order' => 5]);
        Experience::factory()->inactive()->create(['sort_order' => 1]);
        $earlier = Experience::factory()->create(['sort_order' => 2]);

        expect(Experience::activeOrdered()->pluck('id')->all())->toBe([$earlier->id, $later->id]);
    });

    it('lists only the visible, ordered skill categories', function () {
        SkillCategory::factory()->create(['name' => 'Second', 'sort_order' => 2]);
        SkillCategory::factory()->create(['name' => 'First', 'sort_order' => 1]);
        SkillCategory::factory()->inactive()->create(['name' => 'Hidden', 'sort_order' => 0]);

        expect(SkillCategory::activeOrdered()->pluck('name')->all())->toBe(['First', 'Second']);
    });
});

describe('HasOrder (Setting)', function () {
    it('orders by sort_order ascending', function () {
        $keys = ['scope_b' => 2, 'scope_c' => 3, 'scope_a' => 1];

        foreach ($keys as $key => $order) {
            Setting::query()->create([
                'group' => 'Test', 'key' => $key, 'label' => $key, 'type' => 'string', 'sort_order' => $order,
            ]);
        }

        $ordered = Setting::ordered()->whereIn('key', array_keys($keys))->pluck('key')->all();

        expect($ordered)->toBe(['scope_a', 'scope_b', 'scope_c']);
    });
});
