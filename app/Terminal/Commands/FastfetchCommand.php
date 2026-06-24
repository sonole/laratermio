<?php

namespace App\Terminal\Commands;

use App\Facades\Settings;
use App\Models\Setting;
use App\Terminal\TerminalResponse;
use Carbon\Carbon;

class FastfetchCommand extends BaseCommand
{
    public function name(): string
    {
        return 'fastfetch';
    }

    public function helpGroup(): ?string
    {
        return null;
    }

    protected function execute(?string $arg): TerminalResponse
    {
        $phpVersion = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
        $username = Settings::getPromptUsername();
        $hostname = Settings::getPromptHostname();
        $identity = $username.'@'.$hostname;

        $logo = <<<'ASCII'
          ██████╗  ██████╗ ██████╗ ████████╗███████╗ ██████╗ ██╗     ██╗ ██████╗
          ██╔══██╗██╔═══██╗██╔══██╗╚══██╔══╝██╔════╝██╔═══██╗██║     ██║██╔═══██╗
          ██████╔╝██║   ██║██████╔╝   ██║   █████╗  ██║   ██║██║     ██║██║   ██║
          ██╔═══╝ ██║   ██║██╔══██╗   ██║   ██╔══╝  ██║   ██║██║     ██║██║   ██║
          ██║     ╚██████╔╝██║  ██║   ██║   ██║     ╚██████╔╝███████╗██║╚██████╔╝
          ╚═╝      ╚═════╝ ╚═╝  ╚═╝   ╚═╝   ╚═╝      ╚═════╝ ╚══════╝╚═╝ ╚═════╝
        ASCII;

        $logoHtml = '<pre class="t-fastfetch-logo">'.e($logo).'</pre>';

        $rows = [
            ['key' => $identity,       'val' => null, 'separator' => true],
            ['key' => 'Terminal',      'val' => 'laratermio'],
            ['key' => 'Framework',     'val' => 'Laravel '.$this->composerVersion('laravel/framework')],
            ['key' => 'PHP',           'val' => $phpVersion],
            ['key' => 'Arch',          'val' => php_uname('m')],
            ['key' => 'Shell',         'val' => 'jQuery Terminal '.$this->npmVersion('jquery.terminal')],
            ['key' => 'CSS',           'val' => 'Tailwind '.$this->npmVersion('tailwindcss')],
            ['key' => 'UI',            'val' => 'Livewire '.$this->composerVersion('livewire/livewire')],
            ['key' => 'Admin',         'val' => 'Filament '.$this->composerVersion('filament/filament')],
            ['key' => 'OPcache',       'val' => extension_loaded('Zend OPcache') ? 'enabled' : 'disabled'],
            ['key' => 'Memory Usage',  'val' => round(memory_get_usage(true) / 1024 / 1024, 1).' MiB'],
            ['key' => 'Uptime',        'val' => $this->resolveUptime()],
        ];

        $infoHtml = '';
        foreach ($rows as $row) {
            if ($row['separator'] ?? false) {
                $label = e($row['key']);
                $sep = str_repeat('─', strlen($row['key']));
                $infoHtml .= "<p class=\"t-fastfetch-user\">$label</p><p class=\"t-dim\">$sep</p>";
            } else {
                $key = e($row['key']);
                $val = e($row['val']);
                $infoHtml .= "<p class=\"t-fastfetch-row\"><span class=\"t-accent\">$key</span><span class=\"t-dim\">: </span>$val</p>";
            }
        }

        return TerminalResponse::echo(<<<HTML
        <div class="t-block t-fastfetch">
            {$logoHtml}
            <div class="t-fastfetch-info">
                {$infoHtml}
            </div>
        </div>
        HTML);
    }

    private function resolveUptime(): string
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/uptime')) {
            $contents = file_get_contents('/proc/uptime');
            $seconds = $contents ? (int) explode(' ', $contents)[0] : 0;
            $days = intdiv($seconds, 86400);
            $hours = intdiv($seconds % 86400, 3600);
            $mins = intdiv($seconds % 3600, 60);

            $parts = array_filter([
                $days ? $days.'d' : null,
                $hours ? $hours.'h' : null,
                $mins ? $mins.'m' : null,
            ]);

            return $parts ? implode(' ', $parts) : '< 1m';
        }

        $launchedAt = Setting::query()->min('created_at');
        $days = $launchedAt ? (int) Carbon::parse($launchedAt)->diffInDays(now()) : null;

        return match (true) {
            $days === null => 'unknown',
            $days === 0 => '< 1 day',
            $days === 1 => '1 day',
            default => $days.' days',
        };
    }

    private function npmVersion(string $package): string
    {
        $lockPath = base_path('package-lock.json');

        if (! file_exists($lockPath)) {
            return 'unknown';
        }

        $contents = file_get_contents($lockPath);
        $lock = $contents ? json_decode($contents, true) : [];

        return $lock['packages']['node_modules/'.$package]['version'] ?? 'unknown';
    }

    private function composerVersion(string $package): string
    {
        $lockPath = base_path('composer.lock');

        if (! file_exists($lockPath)) {
            return 'unknown';
        }

        $contents = file_get_contents($lockPath);
        $lock = $contents ? json_decode($contents, true) : [];

        foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $pkg) {
            if ($pkg['name'] === $package) {
                return ltrim($pkg['version'], 'v');
            }
        }

        return 'unknown';
    }
}
