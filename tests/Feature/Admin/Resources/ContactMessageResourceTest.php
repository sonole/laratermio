<?php

use App\Enums\ContactMessageStatus;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Models\ContactMessage;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Columns\TextColumn;
use Livewire\Livewire;

describe('access', function () {
    it('redirects guests to the admin login', function () {
        $this->get(ContactMessageResource::getUrl('index'))->assertRedirect('/admin/login');
    });

    it('lets an admin open the list page', function () {
        actingAsAdmin();

        $this->get(ContactMessageResource::getUrl('index'))->assertOk();
    });

    it('is a read-only list with no create or edit pages', function () {
        actingAsAdmin();
        $message = ContactMessage::factory()->create();

        expect(array_keys(ContactMessageResource::getPages()))->toBe(['index']);

        $this->get('/admin/contact-messages/create')->assertNotFound();
        $this->get("/admin/contact-messages/{$message->id}/edit")->assertNotFound();
    });
});

describe('list', function () {
    beforeEach(fn () => actingAsAdmin());

    it('shows the received messages', function () {
        $messages = ContactMessage::factory()->count(3)->create();

        Livewire::test(ListContactMessages::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($messages)
            ->assertCountTableRecords(3);
    });

    it('shows the columns', function () {
        ContactMessage::factory()->create();

        Livewire::test(ListContactMessages::class)
            ->assertTableColumnExists('email')
            ->assertTableColumnExists('message')
            ->assertTableColumnExists('visitor_status')
            ->assertTableColumnExists('admin_status')
            ->assertTableColumnExists('created_at');
    });

    it('lists the newest message first', function () {
        $old = ContactMessage::factory()->create(['created_at' => now()->subDays(3)]);
        $new = ContactMessage::factory()->create(['created_at' => now()->subHour()]);
        $middle = ContactMessage::factory()->create(['created_at' => now()->subDay()]);

        Livewire::test(ListContactMessages::class)
            ->assertCanSeeTableRecords([$new, $middle, $old], inOrder: true)
            ->sortTable('created_at')
            ->assertCanSeeTableRecords([$old, $middle, $new], inOrder: true);
    });

    it('labels and colours the status badges', function () {
        $pending = ContactMessage::factory()->create();
        $sent = ContactMessage::factory()->sent()->create();
        $failed = ContactMessage::factory()->failed()->create();

        Livewire::test(ListContactMessages::class)
            ->assertTableColumnFormattedStateSet('visitor_status', 'Pending', $pending)
            ->assertTableColumnFormattedStateSet('admin_status', 'Pending', $pending)
            ->assertTableColumnFormattedStateSet('visitor_status', 'Sent', $sent)
            ->assertTableColumnFormattedStateSet('admin_status', 'Sent', $sent)
            ->assertTableColumnFormattedStateSet('visitor_status', 'Failed', $failed)
            ->assertTableColumnFormattedStateSet('admin_status', 'Failed', $failed)
            ->assertTableColumnExists('visitor_status', fn (TextColumn $column): bool => $column->getColor($column->getState()) === 'gray', record: $pending)
            ->assertTableColumnExists('visitor_status', fn (TextColumn $column): bool => $column->getColor($column->getState()) === 'success', record: $sent)
            ->assertTableColumnExists('admin_status', fn (TextColumn $column): bool => $column->getColor($column->getState()) === 'danger', record: $failed);
    });

    it('shows the visitor and admin status independently', function () {
        $mixed = ContactMessage::factory()->create([
            'visitor_status' => ContactMessageStatus::Sent,
            'admin_status' => ContactMessageStatus::Failed,
        ]);

        Livewire::test(ListContactMessages::class)
            ->assertTableColumnFormattedStateSet('visitor_status', 'Sent', $mixed)
            ->assertTableColumnFormattedStateSet('admin_status', 'Failed', $mixed);
    });

    it('truncates long messages', function () {
        $long = ContactMessage::factory()->create(['message' => str_repeat('a', 120)]);

        Livewire::test(ListContactMessages::class)
            ->assertTableColumnFormattedStateSet('message', str_repeat('a', 60).'...', $long);
    });

    it('searches by email and by message', function () {
        $alice = ContactMessage::factory()->create(['email' => 'alice@example.com', 'message' => 'Interested in a project']);
        $bob = ContactMessage::factory()->create(['email' => 'bob@example.com', 'message' => 'Hello there']);

        Livewire::test(ListContactMessages::class)
            ->searchTable('alice@')
            ->assertCanSeeTableRecords([$alice])
            ->assertCanNotSeeTableRecords([$bob])
            ->searchTable('Hello')
            ->assertCanSeeTableRecords([$bob])
            ->assertCanNotSeeTableRecords([$alice]);
    });

    it('sorts by email and by status', function () {
        $a = ContactMessage::factory()->create(['email' => 'a@example.com', 'visitor_status' => ContactMessageStatus::Sent]);
        $b = ContactMessage::factory()->create(['email' => 'b@example.com', 'visitor_status' => ContactMessageStatus::Failed]);

        Livewire::test(ListContactMessages::class)
            ->sortTable('email')
            ->assertCanSeeTableRecords([$a, $b], inOrder: true)
            ->sortTable('email', 'desc')
            ->assertCanSeeTableRecords([$b, $a], inOrder: true)
            ->sortTable('visitor_status')
            ->assertCanSeeTableRecords([$b, $a], inOrder: true);
    });

    it('has no row actions', function () {
        $message = ContactMessage::factory()->create();

        Livewire::test(ListContactMessages::class)
            ->assertActionDoesNotExist(TestAction::make('edit')->table($message))
            ->assertActionDoesNotExist(TestAction::make('delete')->table($message));
    });

    it('deletes messages in bulk', function () {
        $messages = ContactMessage::factory()->count(3)->create();

        Livewire::test(ListContactMessages::class)
            ->selectTableRecords($messages->take(2)->pluck('id')->all())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified();

        expect(ContactMessage::query()->count())->toBe(1);
    });
});
