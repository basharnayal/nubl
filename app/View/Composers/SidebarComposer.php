<?php

namespace App\View\Composers;

use App\Support\SidebarPanel;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        $route = request()->route();
        if (! $route) {
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

        // Hide admin sidebar links when the user lacks the matching permission (defense-in-depth UX).
        if ($actor === 'admin') {
            $user = auth()->user();

            if (! $user->can('roles.manage')) {
                unset($sidebarMenu['items'][0]['admin_users']['submenu']['admin_roles_permissions']);
            }
            if (! $user->can('maintenance.manage')) {
                unset($sidebarMenu['items'][0]['admin_settings']['submenu']['admin_settings_maintenance']);
            }
            if (! $user->can('audit_logs.view')) {
                unset($sidebarMenu['items'][0]['admin_audit_logs']);
            }
            if (! $user->can('finance.manage')) {
                unset($sidebarMenu['items'][0]['admin_finances']);
            }
        }

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
        if (! $user) {
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
