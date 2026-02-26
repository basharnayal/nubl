<?php

namespace App\Http\View\Composers;

use App\Main\SidebarPanel;
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
            return;
        }

        $pageName = $route->getName();
        $routePrefix = explode('.', $pageName)[0] ?? '';

        $actor = $this->resolveActor();
        $sidebarMenu = SidebarPanel::forActor($actor);

        $view->with('sidebarMenu', $sidebarMenu);
        $view->with('pageName', $pageName);
        $view->with('routePrefix', $routePrefix);
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
