<?php

namespace App\Filament\Pages;

use App\Enums\SettingType;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $slug = 'settings';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $values = Setting::pluck('value', 'key')->toArray();

        // Convert stored '1'/'0' strings to booleans for Toggle fields
        Setting::where('type', SettingType::Switch)->pluck('key')->each(function (string $key) use (&$values): void {
            if (array_key_exists($key, $values)) {
                $values[$key] = $values[$key] === '1';
            }
        });

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        // Order by sort_order only so groups appear in the intended sequence
        // (Identity 0-9, About 10-19, Terminal 19-29, SEO 30+)
        $sections = Setting::query()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group')
            ->map(fn ($settings, string $group) => Section::make($group)
                ->columns(2)
                ->schema($settings->map(fn (Setting $s) => $s->toFormComponent())->toArray())
            )
            ->values()
            ->toArray();

        return $schema->components($sections)->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            // Convert boolean back to '1'/'0' for uniform string storage
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            Setting::query()->where('key', $key)->update(['value' => $value]);
        }

        Notification::make()->title('Settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
