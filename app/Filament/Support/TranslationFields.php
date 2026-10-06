<?php

namespace App\Filament\Support;

use App\Services\CvLocales;
use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * The "CV translations" block shown on content forms: one tab per extra CV language, holding an
 * input for each translatable field. Extra languages are switched on in Settings; with none
 * switched on there is nothing to translate, so no block is shown at all.
 */
class TranslationFields
{
    /**
     * @param  array<string, Closure(string $statePath): Component>  $fields  field name => factory
     *                                                                        receiving the input's
     *                                                                        state path, which
     *                                                                        must be used as its name
     * @return list<Section>
     */
    public static function make(array $fields): array
    {
        $locales = app(CvLocales::class);

        if ($locales->extra() === []) {
            return [];
        }

        $tabs = array_map(
            fn (string $locale): Tab => Tab::make($locales->name($locale))
                ->schema(array_map(
                    fn (Closure $factory, string $field): Component => $factory("translations.$locale.$field"),
                    $fields,
                    array_keys($fields),
                )),
            $locales->extra(),
        );

        return [
            Section::make('CV translations')
                ->description('Optional. Anything left empty falls back to the text above. Only used when you download the CV in that language.')
                ->schema([Tabs::make('cv-translations')->tabs($tabs)])
                ->collapsible(),
        ];
    }
}
