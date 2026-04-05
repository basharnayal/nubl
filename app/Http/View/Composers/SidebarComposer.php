<?php

namespace App\Http\View\Composers;

use App\Support\SidebarPanel;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        $route = request()->route();
        if (!$route) {
            $view->with('sidebarMenu', ['title' => '', 'items' => [[]]]);
            $view->with('pageName', '');
            $view->with('routePrefix', '');
            $view->with('dashboardUrl', route('dashboard'));
            return;
        }

        $pageName = $route->getName() ?? '';
        $routePrefix = $pageName !== '' ? explode('.', $pageName)[0] : '';

        $actor = $this->resolveActor();
        $sidebarMenu = SidebarPanel::forActor($actor);
        $dashboardUrl = match ($actor) {
            'admin' => route('admin.dashboard'),
            'provider' => route('provider.dashboard'),
            'recipient' => route('recipient.dashboard'),
            'donor' => route('donor.dashboard'),
            default => route('dashboard'),
        };

        $view->with('sidebarMenu', $sidebarMenu);
        $view->with('pageName', $pageName);
        $view->with('routePrefix', $routePrefix);
        $view->with('dashboardUrl', $dashboardUrl);
    }

    private function resolveActor(): ?string
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        if ($user->hasRole('admin')) {
            return 'admin';
        }
        if ($user->hasRole('provider')) {
            return 'provider';
        }
        if ($user->hasRole('recipient')) {
            return 'recipient';
        }
        if ($user->hasRole('donor')) {
            return 'donor';
        }

        return null;
    }
}
