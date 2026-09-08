<?php

declare(strict_types=1);

namespace Domain\Shop\Infrastructure\Laravel;

use Domain\Shop\Application\Contracts\ActiveShopSessionStore;
use Illuminate\Support\Facades\Session;

final class LaravelActiveShopSessionStore implements ActiveShopSessionStore
{
    private const SESSION_KEY = 'active_shop_id';

    public function setActiveShopId(int $shopId): void
    {
        Session::put(self::SESSION_KEY, $shopId);
    }

    public function getActiveShopId(): ?int
    {
        /** @var int|null $value */
        $value = Session::get(self::SESSION_KEY);

        return $value;
    }
}
