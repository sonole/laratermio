<?php

use App\Enums\ContactMessageStatus;
use App\Enums\CvSection;
use App\Enums\InteractionType;
use App\Enums\NavItemType;
use App\Enums\SettingKey;
use App\Enums\SettingType;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

describe('ContactMessageStatus', function () {
    it('has the three delivery states', function () {
        expect(array_column(ContactMessageStatus::cases(), 'value'))->toBe(['pending', 'sent', 'failed']);
    });

    it('is usable as a Filament label and colour', function () {
        expect(ContactMessageStatus::Pending)->toBeInstanceOf(HasLabel::class)->toBeInstanceOf(HasColor::class);
    });

    it('labels each status', function (ContactMessageStatus $status, string $label) {
        expect($status->getLabel())->toBe($label);
    })->with([
        [ContactMessageStatus::Pending, 'Pending'],
        [ContactMessageStatus::Sent, 'Sent'],
        [ContactMessageStatus::Failed, 'Failed'],
    ]);

    it('colours each status', function (ContactMessageStatus $status, string $color) {
        expect($status->getColor())->toBe($color);
    })->with([
        [ContactMessageStatus::Pending, 'gray'],
        [ContactMessageStatus::Sent, 'success'],
        [ContactMessageStatus::Failed, 'danger'],
    ]);

    it('is built from its stored value', function () {
        expect(ContactMessageStatus::from('sent'))->toBe(ContactMessageStatus::Sent)
            ->and(ContactMessageStatus::tryFrom('bounced'))->toBeNull();
    });
});

describe('InteractionType', function () {
    it('has the two interaction modes', function () {
        expect(array_column(InteractionType::cases(), 'value'))->toBe(['paginate', 'selector']);
    });

    it('labels each mode', function () {
        expect(InteractionType::Paginate->label())->toBe('Paginate (one at a time)')
            ->and(InteractionType::Selector->label())->toBe('Selector (arrow keys)');
    });

    it('is built from its stored value', function () {
        expect(InteractionType::from('selector'))->toBe(InteractionType::Selector)
            ->and(InteractionType::tryFrom('none'))->toBeNull();
    });
});

describe('NavItemType', function () {
    it('has the three button kinds', function () {
        expect(array_column(NavItemType::cases(), 'value'))->toBe(['command', 'link', 'cv']);
    });

    it('labels each kind', function (NavItemType $type, string $label) {
        expect($type->label())->toBe($label);
    })->with([
        [NavItemType::Command, 'Command button'],
        [NavItemType::Link, 'Link button'],
        [NavItemType::Cv, 'CV button'],
    ]);
});

describe('SettingType', function () {
    it('covers every kind of settings input', function () {
        expect(array_column(SettingType::cases(), 'value'))
            ->toBe(['string', 'text', 'color', 'switch', 'number', 'file', 'select', 'multiselect']);
    });
});

describe('SettingKey', function () {
    it('has a unique, snake_case value per key', function () {
        $values = array_column(SettingKey::cases(), 'value');

        expect($values)->toBe(array_unique($values));

        foreach ($values as $value) {
            expect($value)->toMatch('/^[a-z]+(_[a-z]+)*$/');
        }
    });

    it('lists the settings that can be translated', function () {
        expect(SettingKey::translatable())->toBe([
            SettingKey::Name,
            SettingKey::Role,
            SettingKey::About,
            SettingKey::CvTitleObjective,
            SettingKey::CvTitleExperience,
            SettingKey::CvTitleEducation,
            SettingKey::CvTitleSkills,
            SettingKey::CvTitleProjects,
        ]);
    });

    it('agrees with isTranslatable() for every key', function () {
        foreach (SettingKey::cases() as $key) {
            expect($key->isTranslatable())->toBe(in_array($key, SettingKey::translatable(), true));
        }

        expect(SettingKey::Name->isTranslatable())->toBeTrue()
            ->and(SettingKey::CvFont->isTranslatable())->toBeFalse()
            ->and(SettingKey::CvLocales->isTranslatable())->toBeFalse()
            ->and(SettingKey::PromptUsername->isTranslatable())->toBeFalse();
    });

    it('links each CV title setting to its section', function (SettingKey $key, CvSection $section) {
        expect($key->cvSection())->toBe($section);
    })->with([
        [SettingKey::CvTitleObjective, CvSection::Objective],
        [SettingKey::CvTitleExperience, CvSection::Experience],
        [SettingKey::CvTitleEducation, CvSection::Education],
        [SettingKey::CvTitleSkills, CvSection::Skills],
        [SettingKey::CvTitleProjects, CvSection::Projects],
    ]);

    it('has no CV section for the other settings', function () {
        foreach ([SettingKey::Name, SettingKey::About, SettingKey::CvFont, SettingKey::CvLocales, SettingKey::SeoTitle] as $key) {
            expect($key->cvSection())->toBeNull();
        }
    });

    it('is built from its stored value', function () {
        expect(SettingKey::tryFrom('cv_font'))->toBe(SettingKey::CvFont)
            ->and(SettingKey::tryFrom('nope'))->toBeNull();
    });
});

describe('CvSection', function () {
    it('has the five titled CV sections', function () {
        expect(array_column(CvSection::cases(), 'value'))->toBe(['objective', 'experience', 'education', 'skills', 'projects']);
    });

    it('maps each section to a title setting that points back at it', function () {
        foreach (CvSection::cases() as $section) {
            expect($section->settingKey()->cvSection())->toBe($section)
                ->and($section->settingKey()->isTranslatable())->toBeTrue();
        }
    });
});
