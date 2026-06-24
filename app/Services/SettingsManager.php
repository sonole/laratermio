<?php

namespace App\Services;

use App\Enums\SettingKey;
use App\Facades\Upload;
use App\Models\Setting;
use Illuminate\Support\Collection;

class SettingsManager
{
    /** @var Collection<string, string|null>|null */
    private ?Collection $cache = null;

    /** @return Collection<string, string|null> */
    private function all(): Collection
    {
        return $this->cache ??= Setting::query()->pluck('value', 'key');
    }

    public function get(SettingKey $key, ?string $default = null): ?string
    {
        return $this->all()->get($key->value) ?? $default;
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

    public function getName(): string
    {
        return $this->get(SettingKey::Name, 'Dev McDevface');
    }

    public function getRole(): string
    {
        return $this->get(SettingKey::Role, 'Developer');
    }

    public function getAbout(): string
    {
        return $this->get(SettingKey::About, '');
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
