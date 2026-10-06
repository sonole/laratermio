<?php

namespace App\Support;

use Transliterator;

class Text
{
    /** @var array<string, Transliterator|null> */
    private static array $upperCasers = [];

    /**
     * Locale-correct uppercase. `mb_strtoupper()` keeps Greek accents (ΑΝΤΙΚΕΊΜΕΝΟ) where Greek
     * convention drops them (ΑΝΤΙΚΕΙΜΕΝΟ), and turns Turkish `i` into `I` instead of `İ`. ICU
     * knows both rules; Greek is always applied since it only touches Greek letters.
     */
    public static function upper(string $text, ?string $locale = null): string
    {
        $caser = self::upperCaser($locale === 'tr' ? 'tr-Upper' : 'el-Upper');

        if ($caser !== null) {
            $text = $caser->transliterate($text) ?: $text;
        }

        return mb_strtoupper($text);
    }

    /**
     * Whether every character survives a round trip through Windows-1252, the only encoding
     * the built-in PDF fonts can draw.
     */
    public static function isWindows1252Safe(string $text): bool
    {
        $encoded = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');

        return mb_convert_encoding($encoded, 'UTF-8', 'Windows-1252') === $text;
    }

    private static function upperCaser(string $id): ?Transliterator
    {
        if (! array_key_exists($id, self::$upperCasers)) {
            self::$upperCasers[$id] = Transliterator::create($id);
        }

        return self::$upperCasers[$id];
    }
}
