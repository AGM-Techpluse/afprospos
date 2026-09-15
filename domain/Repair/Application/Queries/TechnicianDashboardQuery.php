<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Queries;

use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;

/** Backs the technician's own worklist — jobs assigned to them, or unassigned and awaiting_parts/in_progress at their shop. */
final class TechnicianDashboardQuery
{
    /** @return array<int, array<string, mixed>> */
    public function forTechnician(int $technicianStaffId, int $limit = 50): array
    {
        return RepairJobRecord::query()
            ->where('technician_staff_id', $technicianStaffId)
            ->whereIn('repair_status', ['awaiting_parts', 'in_progress', 'failed_requires_resolution'])
            ->orderBy('created_at')
            ->limit($limit)
            ->get()
            ->map(static fn (RepairJobRecord $job): array => [
                'id' => $job->id,
                'device_make' => $job->device_make,
                'device_model' => $job->device_model,
                'repair_status' => $job->repair_status,
                'created_at' => $job->created_at->toIso8601String(),
            ])->all();
    }
}
