<?php

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\SkillCategory;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Experiences', Experience::active()->count())
                ->icon('heroicon-o-briefcase'),
            Stat::make('Education', Education::active()->count())
                ->icon('heroicon-o-academic-cap'),
            Stat::make('Skill Categories', SkillCategory::active()->count())
                ->icon('heroicon-o-cpu-chip'),
            Stat::make('Projects', Project::active()->count())
                ->icon('heroicon-o-code-bracket'),
            Stat::make('Messages', ContactMessage::query()->count())
                ->description(ContactMessage::query()->whereDate('created_at', today())->count().' today')
                ->icon('heroicon-o-envelope'),
        ];
    }
}
