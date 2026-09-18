<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use Domain\Repair\Application\Queries\CustomerRepairsQuery;
use Domain\Sales\Application\Queries\CustomerOrdersQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController
{
    /** Mirrors RepairsListQuery::countOpen's terminal-state definition — one open-repair concept, not redefined per page. */
    private const TERMINAL_REPAIR_STATUSES = ['completed', 'unrepairable', 'expired_cancelled'];

    private const HAPPY_PATH = ['received', 'diagnosing', 'awaiting_authorization', 'awaiting_parts', 'in_progress', 'completed'];

    public function __construct(
        private readonly CustomerRepairsQuery $repairs,
        private readonly CustomerOrdersQuery $orders,
    ) {}

    public function __invoke(Request $request): Response
    {
        $customerId = $request->user('customer')->id;

        return Inertia::render('Customer/Dashboard/Index', [
            'activeRepair' => $this->findActiveRepair($customerId),
            'recentOrders' => $this->orders->paginate($customerId, 1, 5)['data'],
        ]);
    }

    /** @return array<string, mixed>|null */
    private function findActiveRepair(int $customerId): ?array
    {
        $repairs = $this->repairs->paginate($customerId, 1, 5)['data'];

        foreach ($repairs as $repair) {
            if (in_array($repair['repair_status'], self::TERMINAL_REPAIR_STATUSES, true)) {
                continue;
            }

            $stepIndex = array_search($repair['repair_status'], self::HAPPY_PATH, true);
            $progressPercent = $stepIndex !== false
                ? (int) round($stepIndex / (count(self::HAPPY_PATH) - 1) * 100)
                : 25; // An exception state (e.g. payment_overdue) isn't on the happy path — a modest, non-overstated default.

            return [
                'device' => trim("{$repair['device_make']} {$repair['device_model']}"),
                'status_label' => ucwords(str_replace('_', ' ', $repair['repair_status'])),
                'description' => $repair['estimated_collection_date'] !== null
                    ? 'Ready by '.date('D, j M', strtotime($repair['estimated_collection_date']))
                    : "We'll update you as your repair progresses.",
                'progress_percent' => $progressPercent,
            ];
        }

        return null;
    }
}
