<?php

namespace App\Services;

use App\Enums\SettingKey;
use App\Facades\Settings;

/**
 * Which languages the CV can be written in.
 *
 * The base language is `app.locale`: the language the main content fields are written in and the
 * only one the public site and the public `/cv` PDF use. Extra languages are switched on in
 * Settings and only add translation inputs plus a CV that can be downloaded from the admin.
 */
class CvLocales
{
    public function base(): string
    {
        return config('app.locale');
    }

    /**
     * Every language that can be switched on, as locale => native name.
     *
     * @return array<string, string>
     */
    public function available(): array
    {
        return config('portfolio.cv_locales', []);
    }

    /**
     * Languages that can be switched on as additional CV languages (everything but the base).
     *
     * @return array<string, string>
     */
    public function selectable(): array
    {
        return array_diff_key($this->available(), [$this->base() => true]);
    }

    /**
     * The additional languages the admin switched on, in the order they are listed in config.
     *
     * @return list<string>
     */
    public function extra(): array
    {
        $stored = json_decode(Settings::get(SettingKey::CvLocales, '[]') ?? '[]', true);

        if (! is_array($stored) || ! array_is_list($stored)) {
            return [];
        }

        return $this->only($stored);
    }

    /**
     * Narrow a selection (stored, or live from the Settings form) to the languages that can be
     * switched on, in config order. Unknown languages, the base language and duplicates drop out.
     *
     * @return list<string>
     */
    public function only(mixed $selection): array
    {
        if (! is_array($selection)) {
            return [];
        }

        return array_values(array_intersect(array_keys($this->selectable()), array_filter($selection, is_string(...))));
    }

    /**
     * The base language followed by every additional language.
     *
     * @return list<string>
     */
    public function all(): array
    {
        return [$this->base(), ...$this->extra()];
    }

    public function isEnabled(string $locale): bool
    {
        return in_array($locale, $this->all(), true);
    }

    public function isBase(string $locale): bool
    {
        return $locale === $this->base();
    }

    /** Native name of a language, falling back to its code. */
    public function name(string $locale): string
    {
        return $this->available()[$locale] ?? $locale;
    }
}
