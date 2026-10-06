<?php

use App\Support\Text;

describe('Text::upper', function () {
    it('drops Greek accents the way Greek convention requires', function (string $input, string $expected) {
        expect(Text::upper($input))->toBe($expected);
    })->with([
        'single word' => ['Αντικείμενο', 'ΑΝΤΙΚΕΙΜΕΝΟ'],
        'phrase' => ['Μηχανικός Λογισμικού', 'ΜΗΧΑΝΙΚΟΣ ΛΟΓΙΣΜΙΚΟΥ'],
        'final sigma' => ['Δεξιότητες', 'ΔΕΞΙΟΤΗΤΕΣ'],
    ]);

    it('uppercases Latin and other scripts normally', function (string $input, string $expected) {
        expect(Text::upper($input))->toBe($expected);
    })->with([
        'english' => ['Experience', 'EXPERIENCE'],
        'accented latin' => ['Éducation', 'ÉDUCATION'],
        'german sharp s' => ['Straße', 'STRASSE'],
        'mixed greek and latin' => ['Laravel Μηχανικός', 'LARAVEL ΜΗΧΑΝΙΚΟΣ'],
    ]);
});

describe('Text::isWindows1252Safe', function () {
    it('accepts text the built-in PDF fonts can draw', function (string $input) {
        expect(Text::isWindows1252Safe($input))->toBeTrue();
    })->with([
        'ascii' => ['Software Engineer'],
        'western european' => ['Ångström Düsseldorf façade'],
        'typographic punctuation' => ['Jan 2020 – Present • “quoted” €'],
        'question marks are not mistaken for lost characters' => ['Why? Because.'],
        'empty' => [''],
    ]);

    it('rejects text outside Windows-1252', function (string $input) {
        expect(Text::isWindows1252Safe($input))->toBeFalse();
    })->with([
        'greek' => ['Αλέξανδρος'],
        'cyrillic' => ['Привет'],
        'greek mixed into latin' => ['Laravel Μηχανικός'],
        'arrow' => ['A → B'],
    ]);
});
