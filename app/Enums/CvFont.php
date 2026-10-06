<?php

namespace App\Enums;

use App\Support\Text;

/**
 * Fonts the CV can be rendered with.
 *
 * DomPDF can only draw Latin-1 text with the built-in PDF fonts (Helvetica, Times, Courier),
 * so every one of them has a bundled DejaVu counterpart that covers Greek, Cyrillic and most
 * Latin-extended scripts. {@see self::resolveFor()} swaps to it when the CV needs it.
 */
enum CvFont: string
{
    case Helvetica = 'helvetica';
    case Times = 'times';
    case Courier = 'courier';
    case DejaVuSans = 'dejavu-sans';
    case DejaVuSerif = 'dejavu-serif';
    case DejaVuSansMono = 'dejavu-sans-mono';

    public static function default(): self
    {
        return self::Helvetica;
    }

    /** @return array<string, string> value => label, for select inputs */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $font) {
            $options[$font->value] = $font->label();
        }

        return $options;
    }

    public function label(): string
    {
        return match ($this) {
            self::Helvetica => 'Helvetica — clean sans-serif (Latin only)',
            self::Times => 'Times — classic serif (Latin only)',
            self::Courier => 'Courier — typewriter (Latin only)',
            self::DejaVuSans => 'DejaVu Sans — clean sans-serif (all languages)',
            self::DejaVuSerif => 'DejaVu Serif — classic serif (all languages)',
            self::DejaVuSansMono => 'DejaVu Sans Mono — typewriter (all languages)',
        };
    }

    /** Whether the font can draw characters outside Windows-1252 (Greek, Cyrillic, …). */
    public function supportsUnicode(): bool
    {
        return match ($this) {
            self::DejaVuSans, self::DejaVuSerif, self::DejaVuSansMono => true,
            default => false,
        };
    }

    /** The Unicode-capable font with the closest look. */
    public function unicodeEquivalent(): self
    {
        return match ($this) {
            self::Helvetica => self::DejaVuSans,
            self::Times => self::DejaVuSerif,
            self::Courier => self::DejaVuSansMono,
            default => $this,
        };
    }

    /** This font, or its Unicode equivalent when `$text` contains characters it cannot draw. */
    public function resolveFor(string $text): self
    {
        if ($this->supportsUnicode() || Text::isWindows1252Safe($text)) {
            return $this;
        }

        return $this->unicodeEquivalent();
    }

    /** Value for the CSS `font-family` property, with fallbacks for the in-browser preview. */
    public function cssStack(): string
    {
        return match ($this) {
            self::Helvetica => 'Helvetica, Arial, sans-serif',
            self::Times => 'Times, "Times New Roman", serif',
            self::Courier => 'Courier, "Courier New", monospace',
            self::DejaVuSans => '"DejaVu Sans", Verdana, Arial, sans-serif',
            self::DejaVuSerif => '"DejaVu Serif", Georgia, "Times New Roman", serif',
            self::DejaVuSansMono => '"DejaVu Sans Mono", Menlo, "Courier New", monospace',
        };
    }
}
