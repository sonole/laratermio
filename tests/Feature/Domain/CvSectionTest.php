<?php

use App\Enums\CvSection;

describe('CvSection::defaultTitle()', function () {
    it('returns the built-in English titles', function (CvSection $section, string $title) {
        expect($section->defaultTitle('en'))->toBe($title);
    })->with([
        [CvSection::Objective, 'Objective'],
        [CvSection::Experience, 'Experience'],
        [CvSection::Education, 'Education'],
        [CvSection::Skills, 'Skills'],
        [CvSection::Projects, 'Projects'],
    ]);

    it('returns the title in the requested language', function () {
        expect(CvSection::Skills->defaultTitle('el'))->toBe('Δεξιότητες')
            ->and(CvSection::Skills->defaultTitle('el'))->not->toBe(CvSection::Skills->defaultTitle('en'));
    });

    it('has a real title for every section in every offered CV language', function () {
        foreach (array_keys(config('portfolio.cv_locales')) as $locale) {
            foreach (CvSection::cases() as $section) {
                expect($section->defaultTitle($locale))
                    ->not->toBeEmpty()
                    ->not->toStartWith('cv.sections.', "$locale is missing the {$section->value} title");
            }
        }
    });
});
