<?php

namespace App\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SidebarItem extends Component
{
    /**
     * Data menu.
     */
    public array $menu;

    /**
     * Create a new component instance.
     */
    public function __construct(array $menu)
    {
        $this->menu = $menu;
    }

    /**
     * Determine whether current route is active.
     */
    public function isActive(): bool
    {
        $routes = $this->menu['active'] ?? [
            $this->menu['route']
        ];

        foreach ($routes as $route) {

            if (request()->routeIs($route)) {

                return true;

            }

        }

        return false;
    }

    /**
     * Render component.
     */
    public function render(): View
    {
        return view('components.admin.sidebar-item', [
            'active' => $this->isActive(),
        ]);
    }
}