<?php

declare(strict_types=1);

namespace Domain\Identity\Infrastructure\Laravel;

use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1). */
final class EloquentCustomerDirectoryQuery implements CustomerDirectoryQuery
{
    public function search(string $term, int $limit = 10): array
    {
        $customers = CustomerRecord::query()
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return $customers->map(static fn (CustomerRecord $customer): array => [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
        ])->all();
    }

    public function find(int $id): ?array
    {
        $customer = CustomerRecord::query()->find($id);

        if ($customer === null) {
            return null;
        }

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'notification_preferences' => $customer->notification_preferences ?? [],
            'marketing_opt_out' => $customer->marketing_opt_out,
        ];
    }

    public function findMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return CustomerRecord::query()
            ->whereIn('id', array_unique($ids))
            ->get()
            ->mapWithKeys(static fn (CustomerRecord $customer): array => [
                $customer->id => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'notification_preferences' => $customer->notification_preferences ?? [],
                    'marketing_opt_out' => $customer->marketing_opt_out,
                ],
            ])
            ->all();
    }
}
