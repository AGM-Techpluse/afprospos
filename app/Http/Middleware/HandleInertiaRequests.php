<?php

namespace App\Http\Middleware;

use Domain\RBAC\Application\Queries\EffectivePermissionsQuery;
use Domain\Shop\Application\Contracts\ActiveShopSessionStore;
use Domain\Shop\Application\Handlers\EnsureActiveShopSelectedHandler;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly EffectivePermissionsQuery $permissions,
        private readonly AccessibleShopsQuery $accessibleShops,
        private readonly ActiveShopSessionStore $activeShopSession,
        private readonly EnsureActiveShopSelectedHandler $ensureActiveShop,
    ) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $staff = $request->user('staff');
        $customer = $request->user('customer');

        return [
            ...parent::share($request),
            'app_name' => config('app.name'),
            'app_logo' => asset('logo.jpg'), // Fallback until the database settings module is built
            'auth' => [
                'staff' => $staff !== null ? [
                    'name' => $staff->name,
                    'email' => $staff->email,
                ] : null,
                'customer' => $customer !== null ? [
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ] : null,
            ],
            // Deliberately NOT named "shops" — several controllers already pass
            // their own page-specific "shops" prop (a plain array for a form
            // dropdown, e.g. RepairsController), and Inertia lets page props
            // silently shadow shared ones with the same key. A collision here
            // fed AdminShell an array instead of {accessible, active} and
            // crashed ShopSwitcher on every page that also shares that name.
            'shopSwitcher' => $staff !== null ? $this->shareShops((int) $staff->id) : null,
        ];
    }

    /**
     * Powers the Admin shell's shop switcher on every page (ShopController's
     * own switch() route is what actually changes it). Computed independently
     * of ResolveShopContext's ActorContext — this middleware runs before that
     * one in the stack (see bootstrap/app.php), so ActorContext isn't bound
     * into the container yet at this point.
     *
     * @return array{accessible: array<int, array{id:int, name:string, sku_prefix_code:string}>, active: array{id:int, name:string, sku_prefix_code:string}|null}
     */
    private function shareShops(int $staffId): array
    {
        $isOwner = $this->permissions->staffHasRole($staffId, (string) config('afprospos.owner_role_name'));
        $accessible = $this->accessibleShops->forStaff($staffId, $isOwner);
        $activeShopId = $this->ensureActiveShop->resolve(
            $isOwner,
            array_column($accessible, 'id'),
            $this->activeShopSession->getActiveShopId(),
        );

        return [
            'accessible' => $accessible,
            'active' => $activeShopId !== null
                ? (collect($accessible)->firstWhere('id', $activeShopId) ?? null)
                : null,
        ];
    }
}
