<?php

use App\Enums\SettingKey;
use App\Enums\SettingType;
use App\Models\Setting;
use App\Terminal\CommandRegistry;
use App\Terminal\Commands\BaseCommand;
use App\Terminal\TerminalResponse;

/*
| Shared helpers for the terminal-core tests. Not a test file (Pest only collects *Test.php); each
| test file loads it with require_once so it also works when a single file is run on its own.
*/

/**
 * Run a command the way the terminal does: through the registry, by its database row.
 * Returns the JSON-ready response, or null when the registry could not dispatch it.
 *
 * @return array{type: string, html?: string, url?: string, key?: string, path?: string}|null
 */
function terminalCoreRun(string $name, ?string $arg = null): ?array
{
    return app(CommandRegistry::class)->dispatch($name, $arg)?->toArray();
}

/** The html of a command's response (fails the test when the response carries none). */
function terminalCoreHtml(string $name, ?string $arg = null): string
{
    $response = terminalCoreRun($name, $arg);

    expect($response)->not->toBeNull()
        ->and($response)->toHaveKey('html');

    return $response['html'];
}

/** Store a setting. Call before anything reads settings: they are cached in memory per request. */
function terminalCoreSetting(SettingKey $key, ?string $value): Setting
{
    return Setting::query()->updateOrCreate(['key' => $key->value], [
        'group' => 'Terminal',
        'label' => $key->value,
        'type' => SettingType::String,
        'value' => $value,
        'sort_order' => 0,
    ]);
}

/**
 * A minimal command that records what it was asked to execute and echoes it back. Static
 * properties are reset by the tests that use them.
 */
class TerminalCoreProbeCommand extends BaseCommand
{
    /** @var array<int, string|null> */
    public static array $received = [];

    /** @var array<int, array{option: string, description: string}> */
    public static array $options = [];

    public function name(): string
    {
        return 'probe';
    }

    public function helpOptions(): array
    {
        return self::$options;
    }

    protected function execute(?string $arg): TerminalResponse
    {
        self::$received[] = $arg;

        return TerminalResponse::echo('probe:'.var_export($arg, true));
    }
}
