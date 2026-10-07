<?php

use App\Models\ContactItem;

describe('iconAliases()', function () {
    it('gives every icon a terminal key and an admin label', function () {
        $aliases = ContactItem::iconAliases();

        expect($aliases)->not->toBeEmpty();

        foreach ($aliases as $icon => $alias) {
            expect($icon)->toStartWith('fa-')
                ->and($alias)->toHaveKeys(['key', 'label'])
                ->and($alias['key'])->not->toBeEmpty()
                ->and($alias['label'])->not->toBeEmpty();
        }
    });

    it('maps the common contact methods', function () {
        $aliases = ContactItem::iconAliases();

        expect($aliases['fa-solid fa-envelope'])->toBe(['key' => 'email', 'label' => 'Email'])
            ->and($aliases['fa-solid fa-phone']['key'])->toBe('phone')
            ->and($aliases['fa-brands fa-github'])->toBe(['key' => 'github', 'label' => 'GitHub'])
            ->and($aliases['fa-brands fa-linkedin']['key'])->toBe('linkedin');
    });

    it('maps both Twitter icons to the same terminal key', function () {
        $aliases = ContactItem::iconAliases();

        expect($aliases['fa-brands fa-x-twitter']['key'])->toBe('twitter')
            ->and($aliases['fa-brands fa-twitter']['key'])->toBe('twitter');
    });
});

describe('iconAlias()', function () {
    it('returns the terminal key for a known icon', function (string $icon, string $key) {
        expect(ContactItem::factory()->make(['icon' => $icon])->iconAlias())->toBe($key);
    })->with([
        ['fa-solid fa-envelope', 'email'],
        ['fa-solid fa-location-dot', 'location'],
        ['fa-brands fa-github', 'github'],
        ['fa-brands fa-stack-overflow', 'stackoverflow'],
    ]);

    it('returns null for an unknown icon', function () {
        expect(ContactItem::factory()->make(['icon' => 'fa-solid fa-star'])->iconAlias())->toBeNull();
    });

    it('returns null when there is no icon', function (?string $icon) {
        expect(ContactItem::factory()->make(['icon' => $icon])->iconAlias())->toBeNull();
    })->with([[null], ['']]);
});

describe('translatable fields', function () {
    it('only translates the label', function () {
        expect(ContactItem::translatableFields())->toBe(['label']);
    });
});
