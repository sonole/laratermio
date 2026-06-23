<?php

namespace App\Services;

use App\Facades\Upload;
use App\Models\Setting;
use Illuminate\Support\Collection;

class SettingsManager
{
    private ?Collection $cache = null;

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()->get($key) ?? $default;
    }

    private function all(): Collection
    {
        return $this->cache ??= Setting::query()->pluck('value', 'key');
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        return $value === null ? $default : $value === '1';
    }

    public function getFloat(string $key, ?float $default = null): ?float
    {
        return is_numeric($value = $this->get($key)) ? (float) $value : $default;
    }

    public function faviconUrl(): string
    {
        $value = $this->get('favicon', '/favicon.ico');

        return Upload::resolveUrl($value);
    }

    public function getName(): string
    {
        return $this->get('name', 'Dev McDevface');
    }

    public function getRole(): string
    {
        return $this->get('role', 'Developer');
    }

    public function getPromptHostname(): string
    {
        return $this->get('prompt_hostname', 'localhost');
    }

    public function getPrompt(bool $pretty = false): string
    {
        $username = $this->get('prompt_username', 'visitor');
        $hostname = $this->getPromptHostname();
        $suffix = $this->get('prompt_suffix', ':~$');

        if (! $pretty) {
            return "$username@$hostname $suffix ";
        }

        $usernameColor = $this->get('prompt_username_color', '#4ade80');
        $hostnameColor = $this->get('prompt_hostname_color', '#60a5fa');
        $sepColor = $this->get('prompt_separator_color', '#6b7280');

        return "[[b;$usernameColor;]$username][[;$sepColor;]@][[b;$hostnameColor;]$hostname][[;$sepColor;]$suffix] ";
    }

    public function getPromptWithCwd(string $cwd, bool $pretty = false): string
    {
        $username = $this->get('prompt_username', 'visitor');
        $hostname = $this->getPromptHostname();
        $suffix = ':'.$cwd.'$';

        if (! $pretty) {
            return "$username@$hostname$suffix ";
        }

        $usernameColor = $this->get('prompt_username_color', '#4ade80');
        $hostnameColor = $this->get('prompt_hostname_color', '#60a5fa');
        $sepColor = $this->get('prompt_separator_color', '#6b7280');

        return "[[b;$usernameColor;]$username][[;$sepColor;]@][[b;$hostnameColor;]$hostname][[;$sepColor;]$suffix] ";
    }
}
