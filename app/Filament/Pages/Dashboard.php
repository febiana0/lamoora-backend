<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DashboardStats;

class Dashboard extends Page
{
    protected static string $view = 'filament.pages.custom_dashboard';

    protected static string $routeName = 'filament.admin.pages.custom_dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardStats::class,
        ];
    }
}
