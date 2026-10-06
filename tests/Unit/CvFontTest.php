<?php

use App\Enums\CvFont;

it('offers every font as a select option with a label', function () {
    $options = CvFont::options();

    expect($options)->toHaveCount(count(CvFont::cases()));

    foreach (CvFont::cases() as $font) {
        expect($options)->toHaveKey($font->value)
            ->and($options[$font->value])->toBe($font->label());
    }
});

it('has a Unicode-capable equivalent for every font', function () {
    foreach (CvFont::cases() as $font) {
        expect($font->unicodeEquivalent()->supportsUnicode())->toBeTrue();
    }
});

it('keeps the chosen font when the text is Latin', function (CvFont $font) {
    expect($font->resolveFor('Senior Developer – Zürich'))->toBe($font);
})->with(CvFont::cases());

it('swaps Latin-only fonts for the matching DejaVu font when the text needs it', function (CvFont $font, CvFont $expected) {
    expect($font->resolveFor('Μηχανικός Λογισμικού'))->toBe($expected);
})->with([
    [CvFont::Helvetica, CvFont::DejaVuSans],
    [CvFont::Times, CvFont::DejaVuSerif],
    [CvFont::Courier, CvFont::DejaVuSansMono],
]);

it('never swaps a font that already supports Unicode', function (CvFont $font) {
    expect($font->resolveFor('Привет Αθήνα'))->toBe($font);
})->with([CvFont::DejaVuSans, CvFont::DejaVuSerif, CvFont::DejaVuSansMono]);

it('exposes a CSS font stack for every font', function () {
    foreach (CvFont::cases() as $font) {
        expect($font->cssStack())->not->toBeEmpty();
    }
});
