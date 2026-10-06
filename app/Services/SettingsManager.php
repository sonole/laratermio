<?php

namespace App\Services;

use App\Enums\CvFont;
use App\Enums\SettingKey;
use App\Facades\Upload;
use App\Models\Setting;
use Illuminate\Support\Collection;

class SettingsManager
{
    /** @var Collection<string, Setting>|null */
    private ?Collection $rows = null;

    /** @return Collection<string, Setting> */
    private function rows(): Collection
    {
        return $this->rows ??= Setting::query()->get()->keyBy('key');
    }

    public function get(SettingKey $key, ?string $default = null): ?string
    {
        $setting = $this->rows()->get($key->value);

        return $setting === null ? $default : ($setting->value ?? $default);
    }

    /**
     * The setting's text in a CV language, or null when none was entered. There is no fallback
     * here because the right one depends on the setting: a missing translated name falls back to
     * the main-language name, but a missing section title falls back to the built-in title.
     */
    public function getTranslated(SettingKey $key, string $locale): ?string
    {
        if ($locale === config('app.locale')) {
            return blank($value = $this->get($key)) ? null : $value;
        }

        return $this->rows()->get($key->value)?->translation($locale);
    }

    /** The setting in a CV language, falling back to the main-language value, then `$default`. */
    private function getForLocale(SettingKey $key, ?string $locale, string $default): string
    {
        $value = $locale === null ? null : $this->getTranslated($key, $locale);

        return $value ?? $this->get($key, $default) ?? $default;
    }

    public function getBool(SettingKey $key, bool $default = false): bool
    {
        $value = $this->get($key);

        return $value === null ? $default : $value === '1';
    }

    public function getFloat(SettingKey $key, ?float $default = null): ?float
    {
        return is_numeric($value = $this->get($key)) ? (float) $value : $default;
    }

    public function faviconUrl(): string
    {
        return Upload::resolveUrl($this->get(SettingKey::Favicon, '/favicon.ico'));
    }

    public function ogImageUrl(): ?string
    {
        return ! empty($raw = $this->get(SettingKey::SeoOgImage))
            ? rtrim(config('app.url'), '/').Upload::resolveUrl($raw)
            : null;
    }

    public function getName(?string $locale = null): string
    {
        return $this->getForLocale(SettingKey::Name, $locale, 'Dev McDevface');
    }

    public function getRole(?string $locale = null): string
    {
        return $this->getForLocale(SettingKey::Role, $locale, 'Developer');
    }

    public function getAbout(?string $locale = null): string
    {
        return $this->getForLocale(SettingKey::About, $locale, '');
    }

    public function getCvFont(): CvFont
    {
        return CvFont::tryFrom($this->get(SettingKey::CvFont, '')) ?? CvFont::default();
    }

    public function getPromptUsername(): string
    {
        return $this->get(SettingKey::PromptUsername, 'visitor');
    }

    public function getPromptUsernameColor(): string
    {
        return $this->get(SettingKey::PromptUsernameColor, '#4ade80');
    }

    public function getPromptHostname(): string
    {
        return $this->get(SettingKey::PromptHostname, 'localhost');
    }

    public function getPromptHostnameColor(): string
    {
        return $this->get(SettingKey::PromptHostnameColor, '#60a5fa');
    }

    public function getPromptSeparatorColor(): string
    {
        return $this->get(SettingKey::PromptSeparatorColor, '#6b7280');
    }

    public function getPromptSuffix(?string $cwd = null): string
    {
        return ':'.($cwd ?? '~').'$';
    }

    public function getPrompt(?string $cwd = null, bool $pretty = true): string
    {
        $username = $this->getPromptUsername();
        $hostname = $this->getPromptHostname();
        $suffix = $this->getPromptSuffix($cwd);

        if (! $pretty) {
            return "$username@$hostname $suffix ";
        }

        $usernameColor = $this->getPromptUsernameColor();
        $hostnameColor = $this->getPromptHostnameColor();
        $sepColor = $this->getPromptSeparatorColor();

        return "[[b;$usernameColor;]$username][[;$sepColor;]@][[b;$hostnameColor;]$hostname][[;$sepColor;]$suffix] ";
    }
}
