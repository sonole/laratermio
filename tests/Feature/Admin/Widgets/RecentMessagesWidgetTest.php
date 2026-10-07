<?php

use App\Filament\Widgets\RecentMessagesWidget;
use App\Models\ContactMessage;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    actingAsAdmin();
});

describe('recent messages widget', function () {
    it('has a heading and shows nothing for an empty inbox', function () {
        Livewire::test(RecentMessagesWidget::class)
            ->assertSee('Recent Messages')
            ->assertCountTableRecords(0);
    });

    it('lists the messages with their email and text', function () {
        $message = ContactMessage::factory()->create([
            'email' => 'visitor@example.com',
            'message' => 'Hello, I would like to hire you.',
        ]);

        Livewire::test(RecentMessagesWidget::class)
            ->assertCanSeeTableRecords([$message])
            ->assertSee('visitor@example.com')
            ->assertSee('Hello, I would like to hire you.');
    });

    it('lists the newest message first', function () {
        $oldest = ContactMessage::factory()->create(['created_at' => now()->subDays(3)]);
        $newest = ContactMessage::factory()->create(['created_at' => now()->subMinute()]);
        $middle = ContactMessage::factory()->create(['created_at' => now()->subDay()]);

        Livewire::test(RecentMessagesWidget::class)
            ->assertCanSeeTableRecords([$newest, $middle, $oldest], inOrder: true);
    });

    it('shows only the five most recent messages', function () {
        $old = collect(range(1, 3))->map(fn (int $day) => ContactMessage::factory()->create(['created_at' => now()->subDays(10 + $day)]));
        $recent = collect(range(1, 5))->map(fn (int $hour) => ContactMessage::factory()->create(['created_at' => now()->subHours($hour)]));

        $widget = Livewire::test(RecentMessagesWidget::class)
            ->assertCanSeeTableRecords($recent, inOrder: true)
            ->assertCanNotSeeTableRecords($old);

        expect($widget->instance()->getTableRecords())->toHaveCount(5);
    });

    it('truncates long messages to 80 characters', function () {
        $text = str_repeat('word ', 40);
        ContactMessage::factory()->create(['message' => $text]);

        Livewire::test(RecentMessagesWidget::class)
            ->assertSee(Str::limit($text, 80))
            ->assertDontSee(trim($text));
    });

    it('is not paginated', function () {
        ContactMessage::factory()->count(8)->create();

        $table = Livewire::test(RecentMessagesWidget::class)->instance()->getTable();

        expect($table->isPaginated())->toBeFalse();
    });

    it('lists messages whatever their delivery status', function () {
        $pending = ContactMessage::factory()->create();
        $sent = ContactMessage::factory()->sent()->create();
        $failed = ContactMessage::factory()->failed()->create();

        Livewire::test(RecentMessagesWidget::class)
            ->assertCanSeeTableRecords([$pending, $sent, $failed]);
    });
});
