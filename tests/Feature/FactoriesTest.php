<?php

use App\Models\ContactItem;
use App\Models\ContactMessage;
use App\Models\Education;
use App\Models\Experience;
use App\Models\NavItem;
use App\Models\Project;
use App\Models\SkillCategory;
use App\Models\TerminalCommand;
use App\Models\User;

describe('model factories', function () {
    it('create a persisted record', function (string $model) {
        $record = $model::factory()->create();

        expect($record->exists)->toBeTrue()
            ->and($model::query()->count())->toBe(1);
    })->with([
        Experience::class,
        Education::class,
        Project::class,
        SkillCategory::class,
        ContactItem::class,
        ContactMessage::class,
        NavItem::class,
        TerminalCommand::class,
        User::class,
    ]);

    it('offer an inactive state for the content models', function (string $model) {
        expect($model::factory()->inactive()->create()->is_active)->toBeFalse();
    })->with([Experience::class, Education::class, Project::class, SkillCategory::class, ContactItem::class, NavItem::class, TerminalCommand::class]);
});

describe('test helpers', function () {
    it('seeds the shipped terminal commands', function () {
        seedSystem();

        expect(TerminalCommand::query()->where('name', 'help')->exists())->toBeTrue()
            ->and(NavItem::query()->count())->toBeGreaterThan(0);
    });

    it('signs in an admin that can use the panel', function () {
        $admin = actingAsAdmin();

        expect(auth()->id())->toBe($admin->id)
            ->and($admin->must_change_password)->toBeFalse();
    });
});
