<?php

use App\Filament\Widgets\CvWidget;
use App\Filament\Widgets\RecentMessagesWidget;
use App\Filament\Widgets\StatsWidget;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;

describe('dashboard', function () {
    it('mounts the CV generator, the stats and the recent messages widgets', function () {
        actingAsAdmin();

        $this->get('/admin')
            ->assertOk()
            ->assertSeeLivewire(CvWidget::class)
            ->assertSeeLivewire(StatsWidget::class)
            ->assertSeeLivewire(RecentMessagesWidget::class);
    });

    it('discovers exactly the three app widgets', function () {
        $widgets = array_values(Filament::getPanel('admin')->getWidgets());

        expect($widgets)->toEqualCanonicalizing([CvWidget::class, StatsWidget::class, RecentMessagesWidget::class]);
    });

    it('is the panel dashboard', function () {
        expect(Filament::getPanel('admin')->getPages())->toContain(Dashboard::class);

        actingAsAdmin();
        $this->get(Dashboard::getUrl())->assertOk();
    });
});
