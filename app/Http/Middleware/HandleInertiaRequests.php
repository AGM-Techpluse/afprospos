<?php

namespace App\Http\Middleware;

use Domain\Notifications\Application\Queries\CustomerNotificationInboxQuery;
use Domain\Notifications\Application\Queries\StaffNotificationInboxQuery;
use Domain\RBAC\Application\Queries\EffectivePermissionsQuery;
use Domain\Sales\Application\Queries\CheckoutDetailQuery;
use Domain\Shared\Application\Queries\SiteLogoQuery;
use Domain\Shop\Application\Contracts\ActiveShopSessionStore;
use Domain\Shop\Application\Handlers\EnsureActiveShopSelectedHandler;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly EffectivePermissionsQuery $permissions,
        private readonly AccessibleShopsQuery $accessibleShops,
        private readonly ActiveShopSessionStore $activeShopSession,
        private readonly EnsureActiveShopSelectedHandler $ensureActiveShop,
        private readonly CustomerNotificationInboxQuery $customerNotifications,
        private readonly StaffNotificationInboxQuery $staffNotifications,
        private readonly CheckoutDetailQuery $checkoutDetail,
        private readonly SiteLogoQuery $siteLogo,
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
            'app_logo' => $this->siteLogo->url(),
            // Lets the landing page / nav links hide "Shop" rather than
            // link to a route that 404s while STOREFRONT_ENABLED=false.
            'storefrontEnabled' => (bool) config('afprospos.storefront_enabled'),
            'auth' => [
                'staff' => $staff !== null ? [
                    'name' => $staff->name,
                    'email' => $staff->email,
                ] : null,
                'customer' => $customer !== null ? [
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'avatar_url' => $customer->avatar_path !== null ? Storage::disk('public')->url($customer->avatar_path) : null,
                ] : null,
            ],
            // Deliberately NOT named "shops" — several controllers already pass
            // their own page-specific "shops" prop (a plain array for a form
            // dropdown, e.g. RepairsController), and Inertia lets page props
            // silently shadow shared ones with the same key. A collision here
            // fed AdminShell an array instead of {accessible, active} and
            // crashed ShopSwitcher on every page that also shares that name.
            'shopSwitcher' => $staff !== null ? $this->shareShops((int) $staff->id) : null,
            // Feeds Customer/NotificationDropdown.tsx on every Customer page. Lazy
            // so Admin requests (no customer session) never pay for the query.
            'notifications' => fn () => $customer !== null ? $this->customerNotifications->forCustomer((int) $customer->id) : null,
            // Feeds Admin/NotificationDropdown.tsx on every Admin page. Lazy so
            // Customer requests (no staff session) never pay for the query.
            'staffNotifications' => fn () => $staff !== null ? $this->staffNotifications->forStaff((int) $staff->id) : null,
            // Feeds Customer/ShopAside.tsx's reserved-items section on every
            // Customer page. Lazy so Admin requests never pay for the query,
            // and a logged-in customer with no open cart simply gets null.
            'cart' => fn () => $customer !== null ? $this->shareCart((int) $customer->id) : null,
            // Set by App\Http\Controllers\Concerns\FlashesToast; consumed by
            // resources/js/hooks/useFlashToast.ts. Lazy so it isn't evaluated
            // (and doesn't get pulled from the session) on requests that
            // don't read it.
            'flash' => fn () => $request->session()->get('flash'),
        ];
    }

    /**
     * A light summary, not the full `CheckoutDetailQuery` shape — this is
     * evaluated on every Customer page load, so only what the aside
     * actually renders is carried over.
     *
     * @return array<string, mixed>|null
     */
    private function shareCart(int $customerId): ?array
    {
        $checkout = $this->checkoutDetail->findOpenForCustomer($customerId);

        if ($checkout === null) {
            return null;
        }

        return [
            'id' => $checkout['id'],
            'shop_id' => $checkout['shop_id'],
            'shop_name' => $checkout['shop_name'],
            'item_count' => count($checkout['items']),
            'subtotal_minor' => $checkout['subtotal_minor'],
            'total_minor' => $checkout['total_minor'],
            'reservation_expires_at' => $checkout['reservation_expires_at'],
            'items' => array_map(static fn (array $item): array => [
                'id' => $item['id'],
                'sku_id' => $item['sku_id'],
                'product_name' => $item['product_name'],
                'variant_label' => $item['variant_label'],
                'image_url' => $item['image_url'],
                'quantity' => $item['quantity'],
                'unit_price_minor' => $item['unit_price_minor'],
            ], $checkout['items']),
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
