<?php

use App\Models\Education;
use App\Models\Experience;

describe('Experience::period', function () {
    it('shows the start and end month for a finished job', function () {
        $experience = Experience::factory()->make(['start_date' => '2019-01-15', 'end_date' => '2022-02-01']);

        expect($experience->period)->toBe('Jan 2019 – Feb 2022');
    });

    it('ends with "Present" for a job without an end date', function () {
        $experience = Experience::factory()->current()->make(['start_date' => '2020-03-01']);

        expect($experience->period)->toBe('Mar 2020 – Present');
    });

    it('shows a single month range when start and end are in the same month', function () {
        $experience = Experience::factory()->make(['start_date' => '2021-06-01', 'end_date' => '2021-06-30']);

        expect($experience->period)->toBe('Jun 2021 – Jun 2021');
    });

    it('is derived from the end date, not the is_current flag', function () {
        $experience = Experience::factory()->make(['start_date' => '2020-03-01', 'end_date' => '2021-04-01', 'is_current' => true]);

        expect($experience->period)->toBe('Mar 2020 – Apr 2021');
    });

    it('uses the active translation language for "Present"', function () {
        app('translator')->setLocale('el');
        $experience = Experience::factory()->current()->make(['start_date' => '2020-03-01']);

        expect($experience->period)->toEndWith(' – '.trans('cv.present', [], 'el'));
    });
});

describe('Education::period', function () {
    it('shows the start and end month for a degree', function () {
        $education = Education::factory()->make(['start_date' => '2012-09-01', 'end_date' => '2017-06-01']);

        expect($education->period)->toBe('Sep 2012 – Jun 2017');
    });

    it('ends with "Present" for an unfinished degree', function () {
        $education = Education::factory()->make(['start_date' => '2023-09-01', 'end_date' => null, 'is_certification' => false]);

        expect($education->period)->toBe('Sep 2023 – Present');
    });

    it('shows the issue date for a certification without an end date', function () {
        $education = Education::factory()->certification()->make(['start_date' => '2022-11-01']);

        expect($education->period)->toBe('Issued on Nov 2022');
    });

    it('shows a range for a certification that has an end date', function () {
        $education = Education::factory()->certification()->make(['start_date' => '2022-11-01', 'end_date' => '2024-11-01']);

        expect($education->period)->toBe('Nov 2022 – Nov 2024');
    });

    it('translates the issue wording', function () {
        app('translator')->setLocale('el');
        $education = Education::factory()->certification()->make(['start_date' => '2022-11-01']);

        expect($education->period)->toStartWith('Εκδόθηκε:');
    });
});
