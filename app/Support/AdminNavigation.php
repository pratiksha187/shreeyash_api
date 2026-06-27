<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AdminNavigation
{
    /**
     * @return array<int, array{label: string, active: bool, items: array<int, array<string, mixed>>}>
     */
    public function groups(?Request $request = null): array
    {
        $request ??= request();

        return collect(config('admin.navigation', []))
            ->map(function (array $group) use ($request) {
                $items = collect($group['items'] ?? [])
                    ->filter(fn (array $item) => $this->can((string) $item['key']))
                    ->map(function (array $item) use ($request) {
                        $item['url'] = route($item['route']);
                        $item['active'] = $request->routeIs($item['active']);

                        return $item;
                    })
                    ->values()
                    ->all();

                return [
                    'label' => $group['label'],
                    'active' => collect($items)->contains(fn (array $item) => (bool) $item['active']),
                    'items' => $items,
                ];
            })
            ->filter(fn (array $group) => count($group['items']) > 0)
            ->values()
            ->all();
    }

    public function can(string $permission): bool
    {
        if (session('admin_role') === 'super_admin') {
            return in_array($permission, config('admin.super_admin_permissions', []), true);
        }

        if (session('admin_role') === 'company_admin' && $permission === 'companies') {
            return false;
        }

        $permissions = $this->permissions();

        if ($permissions === []) {
            $permissions = config('admin.company_admin_permissions', []);
        }

        if (in_array('*', $permissions, true)) {
            return true;
        }

        if (in_array($permission, $permissions, true)) {
            return true;
        }

        if (in_array($permission, ['product_purchases', 'material_stock'], true) && in_array('diesel_purchases', $permissions, true)) {
            return true;
        }

        $groupPermission = $this->groupPermissionForModule($permission);
        $fallbackGroupPermissions = config('admin.company_admin_permissions', []);

        if ($groupPermission && (in_array($groupPermission, $permissions, true) || in_array($groupPermission, $fallbackGroupPermissions, true))) {
            return true;
        }

        return false;
    }

    public function permissionForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        foreach (config('admin.route_permissions', []) as $pattern => $permission) {
            if (Str::is($pattern, $routeName)) {
                return $permission;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public function configuredPermissions(): array
    {
        $permissions = config('admin.permissions', '*');

        if ($permissions === '*' || $permissions === ['*']) {
            return ['*'];
        }

        if (is_string($permissions)) {
            return collect(explode(',', $permissions))
                ->map(fn (string $permission) => trim($permission))
                ->filter()
                ->values()
                ->all();
        }

        return array_values(array_filter(Arr::wrap($permissions)));
    }

    /**
     * @return array<int, string>
     */
    private function permissions(): array
    {
        if (session()->has('admin_permissions')) {
            $permissions = session('admin_permissions');

            return is_array($permissions)
                ? array_values(array_filter($permissions))
                : [];
        }

        $permissions = session('admin_permissions');

        if (! is_array($permissions) || $permissions === []) {
            return $this->configuredPermissions();
        }

        return array_values(array_filter($permissions));
    }

    private function groupPermissionForModule(string $permission): ?string
    {
        return match ($permission) {
            'employees', 'attendance_reports', 'missed_requests', 'leave_requests', 'labour_attendance', 'payments', 'site_master', 'contractor_master', 'labour_master', 'dpr_reports', 'challans', 'complaints' => 'hr',
            'diesel_purchases', 'product_purchases', 'material_stock' => 'purchase',
            default => null,
        };
    }
}
