<?php

namespace App\Livewire;

use App\Enums\NavItemType;
use App\Enums\SettingKey;
use App\Facades\Settings;
use App\Models\NavItem;
use App\Models\TerminalCommand;
use App\Services\CvService;
use App\Terminal\CommandRegistry;
use App\Terminal\Concerns\RendersHtml;
use App\Terminal\Contracts\HasStructuredData;
use App\Terminal\TerminalContext;
use App\Terminal\TerminalResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class Terminal extends Component
{
    use RendersHtml;

    public bool $navCommandsDisabled = false;

    public string $cwd = '~';

    private CommandRegistry $registry;

    public function boot(): void
    {
        $this->registry = app(CommandRegistry::class);
    }

    /**
     * @return array{type: string, html?: string, url?: string, key?: string, path?: string}
     */
    #[Renderless]
    public function execute(string $command): array
    {
        $parts = preg_split('/\s+/', trim($command), 2);
        $cmd = strtolower($parts[0] ?? '');
        $arg = $parts[1] ?? null;

        if (empty($cmd)) {
            return ['type' => 'echo', 'html' => ''];
        }

        $context = app(TerminalContext::class);
        $context->setCwd($this->cwd);

        $response = $this->registry->dispatch($cmd, $arg)
            ?? TerminalResponse::echo($this->renderNotFound($command));

        $this->cwd = $context->getCwd();

        return $response->toArray();
    }

    #[Renderless]
    public function getStructuredData(string $type): array
    {
        $command = $this->registry->resolve($type);

        if ($command instanceof HasStructuredData) {
            return $command->structuredData();
        }

        return [];
    }

    /** @return string[] */
    public function commandNames(): array
    {
        return TerminalCommand::query()
            ->where('is_enabled', true)
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
    }

    /** @return string[] */
    public function filesystemRoots(): array
    {
        return TerminalContext::FILESYSTEM_ROOTS;
    }

    /**
     * @return array<int, array{exec: string, label: string}>
     */
    public function navCommandItems(): array
    {
        return NavItem::query()
            ->with('terminalCommand')
            ->where('type', NavItemType::Command)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (NavItem $item) => [
                'exec' => ($item->terminalCommand->name).($item->command_args ? ' '.$item->command_args : ''),
                'label' => $item->terminalCommand->display_label,
            ])
            ->all();
    }

    /**
     * @return array<int, array{url: string, target: string, label: string}>
     */
    public function navLinkItems(): array
    {
        $items = NavItem::query()
            ->whereIn('type', [NavItemType::Link, NavItemType::Cv])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $cvExists = $items->contains('type', NavItemType::Cv)
            && app(CvService::class)->exists();

        return $items
            ->filter(fn (NavItem $item) => $item->type !== NavItemType::Cv || $cvExists)
            ->map(fn (NavItem $item) => [
                'url' => $item->type === NavItemType::Cv ? route('cv') : ($item->url ?? ''),
                'target' => $item->target,
                'label' => $item->getDisplayLabel(),
            ])
            ->values()
            ->all();
    }

    /** @return string[] */
    private function asciiArtLines(): array
    {
        if (! Settings::getBool(SettingKey::AsciiArtEnabled)) {
            return [];
        }

        if (empty($value = Settings::get(SettingKey::AsciiArt))) {
            return [];
        }

        $content = Storage::disk('public')->get($value);

        if (is_null($content)) {
            return [];
        }

        return explode("\n", $content);
    }

    public function render(): View
    {
        return view('livewire.terminal', [
            'nav' => [
                'commandItems' => $this->navCommandItems(),
                'linkItems' => $this->navLinkItems(),
            ],
            'asciiArt' => [
                'lines' => $this->asciiArtLines(),
                'size' => Settings::getFloat(SettingKey::AsciiArtSize, 0.15).'em',
                'color' => Settings::get(SettingKey::AsciiArtColor, '#4ade80'),
            ],
            'greeting' => [
                'name' => Settings::getName(),
                'role' => Settings::getRole(),
            ],
            'terminalPrompt' => Settings::getPrompt(),
            'headerTemplate' => $this->header('__TITLE__'),
            'commandNames' => $this->commandNames(),
            'filesystemRoots' => $this->filesystemRoots(),
        ]);
    }
}
