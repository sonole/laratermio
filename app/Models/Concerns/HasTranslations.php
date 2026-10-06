<?php

namespace App\Models\Concerns;

/**
 * Lets a model keep extra-language versions of some of its text fields, for the CV.
 *
 * The model's own columns always hold the main language (`app.locale`), so the public site and
 * existing data are untouched. Other languages live in a `translations` JSON column shaped
 * `{ "el": { "title": "…", "bullets": ["…"] } }`. A language with no text for a field falls back
 * to the main language, so a half-translated record still produces a complete CV.
 *
 * @property array<string, array<string, mixed>>|null $translations
 */
trait HasTranslations
{
    /**
     * Columns that can be translated.
     *
     * @return list<string>
     */
    abstract public static function translatableFields(): array;

    public function initializeHasTranslations(): void
    {
        $this->mergeFillable(['translations']);
        $this->mergeCasts(['translations' => 'array']);
    }

    /** The stored translation of a field, or null when there is none (blank counts as none). */
    public function translation(string $field, string $locale): mixed
    {
        return self::withoutBlanks($this->translations[$locale][$field] ?? null);
    }

    /** The field in the given language, falling back to the main language. */
    public function translated(string $field, string $locale): mixed
    {
        if ($locale === config('app.locale')) {
            return $this->getAttribute($field);
        }

        return $this->translation($field, $locale) ?? $this->getAttribute($field);
    }

    /**
     * An unsaved copy with every translatable field replaced by its value in `$locale`, so views
     * can read `$record->title` as usual.
     */
    public function localized(string $locale): static
    {
        $copy = $this->replicate();

        foreach (static::translatableFields() as $field) {
            $copy->setAttribute($field, $this->translated($field, $locale));
        }

        // The other languages' text must not leak into the copy: it is serialised to pick the CV
        // font, and Greek in an English copy would force a Unicode font for nothing.
        $copy->setAttribute('translations', null);

        return $copy;
    }

    /** Null for empty text; for lists, the list without its empty entries (null if none remain). */
    private static function withoutBlanks(mixed $value): mixed
    {
        if (is_array($value)) {
            $value = array_values(array_filter($value, fn ($item) => ! blank($item)));
        }

        return blank($value) ? null : $value;
    }
}
