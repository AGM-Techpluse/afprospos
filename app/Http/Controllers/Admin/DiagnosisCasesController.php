<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\Repairs\CreateDiagnosisCaseRequest;
use App\Http\Requests\Admin\Repairs\CreateRepairFromDiagnosisRequest;
use App\Http\Requests\Admin\Repairs\SaveDiagnosisCaseResultRequest;
use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Identity\Application\Queries\StaffDirectoryQuery;
use Domain\Repair\Application\Commands\AssignDiagnosisTechnicianCommand;
use Domain\Repair\Application\Commands\AssignTechnicianCommand;
use Domain\Repair\Application\Commands\CreateRepairJobCommand;
use Domain\Repair\Application\Commands\SetDiagnosisFeeCommand;
use Domain\Repair\Application\Handlers\AssignDiagnosisTechnicianHandler;
use Domain\Repair\Application\Handlers\AssignTechnicianHandler;
use Domain\Repair\Application\Handlers\CreateRepairJobHandler;
use Domain\Repair\Application\Handlers\SetDiagnosisFeeHandler;
use Domain\Repair\Application\Queries\CommonComponentCatalogQuery;
use Domain\Repair\Application\Queries\DeviceCatalogQuery;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceProblemTagRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisCaseFaultRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisCaseRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobProblemTagRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shared\Domain\Exceptions\DomainException;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Domain\Shop\Application\Queries\ShopDirectoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class DiagnosisCasesController
{
    use FlashesToast;

    public function __construct(
        private readonly AccessibleShopsQuery $accessibleShops,
        private readonly ShopDirectoryQuery $shops,
        private readonly CustomerDirectoryQuery $customers,
        private readonly StaffDirectoryQuery $staff,
        private readonly DeviceCatalogQuery $deviceCatalog,
        private readonly CommonComponentCatalogQuery $commonComponents,
        private readonly CreateRepairJobHandler $createRepairJob,
        private readonly AssignTechnicianHandler $assignTechnician,
        private readonly SetDiagnosisFeeHandler $setDiagnosisFee,
        private readonly AssignDiagnosisTechnicianHandler $assignDiagnosisTechnician,
    ) {}

    public function index(Request $request): Response
    {
        $actor = app(ActorContext::class);
        $shopId = $request->has('shop_id') ? ($request->integer('shop_id') ?: null) : $actor->activeShopId;
        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $outcome = $request->string('outcome')->toString();
        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        $customerIds = $search !== ''
            ? collect($this->customers->search($search, 50))->pluck('id')->map(static fn (int $id): int => $id)->all()
            : [];

        $query = DiagnosisCaseRecord::query()
            ->when($shopId !== null, fn ($query) => $query->where('shop_id', $shopId))
            ->when(! $actor->isOwner, fn ($query) => $query->whereIn('shop_id', $actor->shopIds))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($outcome !== '', fn ($query) => $query->where('outcome', $outcome))
            ->when($search !== '', function ($query) use ($search, $customerIds): void {
                $query->where(function ($query) use ($search, $customerIds): void {
                    $query->where('device_make', 'like', "%{$search}%")
                        ->orWhere('device_model', 'like', "%{$search}%")
                        ->orWhere('device_imei_serial', 'like', "%{$search}%")
                        ->orWhere('reported_issue', 'like', "%{$search}%")
                        ->orWhereIn(
                            'id',
                            DiagnosisCaseFaultRecord::query()
                                ->select('diagnosis_case_id')
                                ->where('label_snapshot', 'like', "%{$search}%")
                        );

                    if ($customerIds !== []) {
                        $query->orWhereIn('customer_id', $customerIds);
                    }
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $cases = $query->paginate(15)->withQueryString();
        $items = collect($cases->items());
        $customers = $this->customers->findMany(
            $items->pluck('customer_id')->filter()->map(static fn (int $id): int => $id)->all()
        );

        return Inertia::render('Admin/DiagnosisCases/Index', [
            'cases' => [
                'data' => $items->map(fn (DiagnosisCaseRecord $case): array => $this->summary($case, $customers))->all(),
                'current_page' => $cases->currentPage(),
                'last_page' => $cases->lastPage(),
                'per_page' => $cases->perPage(),
                'total' => $cases->total(),
            ],
            'filters' => ['search' => $search, 'status' => $status, 'outcome' => $outcome, 'shop_id' => $shopId],
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
        ]);
    }

    public function create(): Response
    {
        $actor = app(ActorContext::class);
        $shopId = $actor->activeShopId ?? ($actor->shopIds[0] ?? null);

        return Inertia::render('Admin/DiagnosisCases/Create', [
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
            'deviceCatalog' => $this->deviceCatalog->structure(),
            'commonComponents' => $this->commonComponents->list(),
            'technicians' => $shopId !== null ? $this->technicians($shopId) : [],
        ]);
    }

    public function store(CreateDiagnosisCaseRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $shopId = $request->integer('shop_id');
        abort_unless($actor->canAccessShop($shopId), 403);

        $case = DiagnosisCaseRecord::query()->create([
            'shop_id' => $shopId,
            'customer_id' => $request->integer('customer_id') ?: null,
            'walk_in_name' => $request->string('walk_in_name')->toString() ?: null,
            'walk_in_phone' => $request->string('walk_in_phone')->toString() ?: null,
            'walk_in_email' => $request->string('walk_in_email')->toString() ?: null,
            'device_model_id' => $request->integer('device_model_id') ?: null,
            'diagnosis_technician_staff_id' => $request->integer('diagnosis_technician_staff_id') ?: null,
            'created_by_staff_id' => $actor->staffId->value,
            'device_make' => $request->string('device_make')->toString(),
            'device_model' => $request->string('device_model')->toString(),
            'reported_issue' => $request->string('reported_issue')->toString() ?: null,
            'device_imei_serial' => $request->string('device_imei_serial')->toString() ?: null,
            'device_lock_type' => $request->string('device_lock_type')->toString() ?: 'none',
            'device_lock_value' => $request->string('device_lock_value')->toString() ?: null,
        ]);

        foreach ($request->array('revealed_faults') as $fault) {
            $label = trim((string) ($fault['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            DiagnosisCaseFaultRecord::query()->create([
                'diagnosis_case_id' => $case->id,
                'device_problem_tag_id' => isset($fault['device_problem_tag_id']) ? (int) $fault['device_problem_tag_id'] : null,
                'common_component_id' => isset($fault['common_component_id']) ? (int) $fault['common_component_id'] : null,
                'label_snapshot' => $label,
                'notes' => isset($fault['notes']) && trim((string) $fault['notes']) !== '' ? trim((string) $fault['notes']) : null,
            ]);
        }

        $this->flashSuccess('Diagnosis case created');

        return redirect()->route('admin.diagnosis-cases.show', ['diagnosisCase' => $case->id]);
    }

    public function show(int $diagnosisCase): Response
    {
        $actor = app(ActorContext::class);
        $case = DiagnosisCaseRecord::query()->findOrFail($diagnosisCase);
        abort_unless($actor->canAccessShop($case->shop_id), 403);

        return Inertia::render('Admin/DiagnosisCases/Show', [
            'diagnosisCase' => $this->detail($case, $actor),
            'shop' => $this->shops->find($case->shop_id),
            'technicians' => $this->technicians($case->shop_id),
            'faultCatalog' => $this->faultCatalog(),
            'relatedRepairs' => $this->relatedRepairs($case->id),
        ]);
    }

    public function assignTechnician(Request $request, int $diagnosisCase): RedirectResponse
    {
        $request->validate(['diagnosis_technician_staff_id' => ['required', 'integer', 'exists:staff,id']]);
        $actor = app(ActorContext::class);
        $case = DiagnosisCaseRecord::query()->findOrFail($diagnosisCase);
        abort_unless($actor->canAccessShop($case->shop_id), 403);

        try {
            $this->assignDiagnosisTechnician->handle(new AssignDiagnosisTechnicianCommand(
                diagnosisCaseId: $diagnosisCase,
                technicianStaffId: $request->integer('diagnosis_technician_staff_id'),
                assignedByStaffId: $actor->staffId->value,
            ));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['diagnosis_technician_staff_id' => $exception->getMessage()]);
        }

        $this->flashSuccess('Diagnosis technician assigned');

        return redirect()->back();
    }

    public function saveResult(SaveDiagnosisCaseResultRequest $request, int $diagnosisCase): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $case = DiagnosisCaseRecord::query()->findOrFail($diagnosisCase);
        abort_unless($actor->canAccessShop($case->shop_id), 403);

        DB::transaction(function () use ($case, $request): void {
            $case->update([
                'status' => 'diagnosed',
                'outcome' => $request->string('outcome')->toString(),
                'diagnosis_notes' => $request->string('diagnosis_notes')->toString() ?: null,
                'diagnosis_fee_minor' => $request->filled('diagnosis_fee_minor') ? $request->integer('diagnosis_fee_minor') : null,
                'diagnosed_at' => Carbon::now(),
            ]);

            DiagnosisCaseFaultRecord::query()->where('diagnosis_case_id', $case->id)->delete();
            foreach ($request->array('revealed_faults') as $fault) {
                $label = trim((string) ($fault['label'] ?? ''));
                if ($label === '') {
                    continue;
                }

                DiagnosisCaseFaultRecord::query()->create([
                    'diagnosis_case_id' => $case->id,
                    'device_problem_tag_id' => isset($fault['device_problem_tag_id']) ? (int) $fault['device_problem_tag_id'] : null,
                    'common_component_id' => isset($fault['common_component_id']) ? (int) $fault['common_component_id'] : null,
                    'label_snapshot' => $label,
                    'notes' => isset($fault['notes']) && trim((string) $fault['notes']) !== '' ? trim((string) $fault['notes']) : null,
                ]);
            }
        });

        $this->flashSuccess('Diagnosis saved');

        return redirect()->back();
    }

    public function createRepair(CreateRepairFromDiagnosisRequest $request, int $diagnosisCase): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $case = DiagnosisCaseRecord::query()->findOrFail($diagnosisCase);
        abort_unless($actor->canAccessShop($case->shop_id), 403);

        $openRepair = RepairJobRecord::query()
            ->where('diagnosis_case_id', $case->id)
            ->whereNotIn('repair_status', ['completed', 'unrepairable', 'expired_cancelled', 'cancelled'])
            ->latest('id')
            ->first();

        if ($openRepair !== null) {
            throw ValidationException::withMessages(['status' => 'This diagnosis already has an open repair work order. Close it before opening another one.']);
        }

        $hasCompletedRepair = RepairJobRecord::query()
            ->where('diagnosis_case_id', $case->id)
            ->where('repair_status', 'completed')
            ->exists();

        if ($case->status !== 'diagnosed' && ! $hasCompletedRepair) {
            throw ValidationException::withMessages(['status' => 'Save the diagnosis before creating a repair work order.']);
        }

        $faults = DiagnosisCaseFaultRecord::query()
            ->where('diagnosis_case_id', $case->id)
            ->when(count($request->array('fault_ids')) > 0, fn ($query) => $query->whereIn('id', array_map('intval', $request->array('fault_ids'))))
            ->get();

        $problemTagIds = $faults->pluck('device_problem_tag_id')->filter()->unique()->values()->all();

        $repairId = $this->createRepairJob->handle(new CreateRepairJobCommand(
            shopId: $case->shop_id,
            customerId: $case->customer_id,
            walkInName: $case->walk_in_name,
            walkInPhone: $case->walk_in_phone,
            walkInEmail: $case->walk_in_email,
            deviceMake: $case->device_make,
            deviceModel: $case->device_model,
            reportedIssue: $case->reported_issue,
            deviceImeiSerial: $case->device_imei_serial,
            deviceLockType: $case->device_lock_type,
            deviceLockValue: $case->device_lock_value,
            problemTagIds: $problemTagIds,
            labourChargeMinor: null,
            createdByStaffId: $actor->staffId->value,
        ));

        RepairJobRecord::query()->whereKey($repairId)->update([
            'diagnosis_case_id' => $case->id,
            'reopened_from_repair_job_id' => $request->integer('reopened_from_repair_job_id') ?: null,
            'repair_status' => 'awaiting_authorization',
        ]);

        // Carries over the case's already-captured technician/fee as a starting
        // value on the repair job — still reassignable/editable there afterward.
        if ($case->diagnosis_technician_staff_id !== null) {
            $this->assignTechnician->handle(new AssignTechnicianCommand(
                repairJobId: $repairId,
                technicianStaffId: $case->diagnosis_technician_staff_id,
                assignedByStaffId: $actor->staffId->value,
            ));
        }

        if ($case->diagnosis_fee_minor !== null) {
            $this->setDiagnosisFee->handle(new SetDiagnosisFeeCommand(
                repairJobId: $repairId,
                amountMinor: $case->diagnosis_fee_minor,
                setByStaffId: $actor->staffId->value,
            ));
        }

        foreach ($faults as $fault) {
            if ($fault->device_problem_tag_id === null) {
                RepairJobProblemTagRecord::query()->create([
                    'repair_job_id' => $repairId,
                    'device_problem_tag_id' => null,
                    'common_component_id' => $fault->common_component_id,
                    'label_snapshot' => $fault->label_snapshot,
                ]);
            }
        }

        $case->update(['status' => 'called_up']);
        $this->flashSuccess('Repair work order created from diagnosis');

        return redirect()->route('admin.repairs.show', ['repair' => $repairId]);
    }

    public function docket(int $diagnosisCase): Response
    {
        $actor = app(ActorContext::class);
        $case = DiagnosisCaseRecord::query()->findOrFail($diagnosisCase);
        abort_unless($actor->canAccessShop($case->shop_id), 403);

        return Inertia::render('Admin/DiagnosisCases/Docket', [
            'diagnosisCase' => $this->detail($case, $actor),
            'shop' => $this->shops->find($case->shop_id),
        ]);
    }

    /** @return array<string, mixed> */
    /**
     * @param  array<int, array{id: int, name: string, email: ?string, phone: string, notification_preferences: array<string, bool>, marketing_opt_out: bool}>  $customers
     * @return array<string, mixed>
     */
    private function summary(DiagnosisCaseRecord $case, array $customers = []): array
    {
        $customer = $case->customer_id !== null ? ($customers[$case->customer_id] ?? $this->customers->find($case->customer_id)) : null;
        $openRepair = RepairJobRecord::query()
            ->where('diagnosis_case_id', $case->id)
            ->whereNotIn('repair_status', ['completed', 'unrepairable', 'expired_cancelled', 'cancelled'])
            ->latest('id')
            ->first();

        return [
            'id' => $case->id,
            'shop_id' => $case->shop_id,
            'customer_name' => $customer['name'] ?? null,
            'walk_in_name' => $case->walk_in_name,
            'device' => trim($case->device_make.' '.$case->device_model),
            'device_imei_serial' => $case->device_imei_serial,
            'status' => $case->status,
            'outcome' => $case->outcome,
            'open_repair_id' => $openRepair?->id,
            'open_repair_status' => $openRepair?->repair_status,
            'created_at' => $case->created_at->toIso8601String(),
            'faults' => DiagnosisCaseFaultRecord::query()
                ->where('diagnosis_case_id', $case->id)
                ->orderBy('id')
                ->pluck('label_snapshot')
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function detail(DiagnosisCaseRecord $case, ActorContext $actor): array
    {
        $customer = $case->customer_id !== null ? $this->customers->find($case->customer_id) : null;
        $canViewDeviceLock = $actor->isOwner || $actor->staffId->value === $case->diagnosis_technician_staff_id;

        return [
            ...$this->summary($case),
            'customer_id' => $case->customer_id,
            'customer_phone' => $customer['phone'] ?? null,
            'customer_email' => $customer['email'] ?? null,
            'walk_in_phone' => $case->walk_in_phone,
            'walk_in_email' => $case->walk_in_email,
            'device_make' => $case->device_make,
            'device_model' => $case->device_model,
            'reported_issue' => $case->reported_issue,
            'device_lock_type' => $case->device_lock_type,
            'device_lock_present' => $case->device_lock_type !== 'none',
            'device_lock_value' => $canViewDeviceLock ? $case->device_lock_value : null,
            'device_lock_visible_to_you' => $canViewDeviceLock,
            'diagnosis_technician_staff_id' => $case->diagnosis_technician_staff_id,
            'diagnosis_notes' => $case->diagnosis_notes,
            'diagnosis_fee_minor' => $case->diagnosis_fee_minor,
            'diagnosed_at' => $case->diagnosed_at?->toIso8601String(),
            'revealed_faults' => DiagnosisCaseFaultRecord::query()
                ->where('diagnosis_case_id', $case->id)
                ->orderBy('id')
                ->get()
                ->map(static fn (DiagnosisCaseFaultRecord $fault): array => [
                    'id' => $fault->id,
                    'device_problem_tag_id' => $fault->device_problem_tag_id,
                    'common_component_id' => $fault->common_component_id,
                    'label' => $fault->label_snapshot,
                    'notes' => $fault->notes,
                ])->all(),
        ];
    }

    /** @return array<int, array{id:int, label:string}> */
    private function faultCatalog(): array
    {
        return DeviceProblemTagRecord::query()
            ->orderBy('label')
            ->get()
            ->map(static fn (DeviceProblemTagRecord $tag): array => ['id' => $tag->id, 'label' => $tag->label])
            ->all();
    }

    /** @return array<int, array{id:int, name:string}> */
    private function technicians(int $shopId): array
    {
        return $this->staff->paginate(search: null, status: 'active', role: 'Technician', page: 1, perPage: 100, shopId: $shopId)['data'];
    }

    /** @return array<int, array{id:int, status:string, display_label:string, opened_at:string, created_at:string, reopened_from_repair_job_id:int|null}> */
    private function relatedRepairs(int $diagnosisCaseId): array
    {
        return RepairJobRecord::query()
            ->where('diagnosis_case_id', $diagnosisCaseId)
            ->orderByDesc('created_at')
            ->get()
            ->map(static fn (RepairJobRecord $repair): array => [
                'id' => $repair->id,
                'status' => $repair->repair_status,
                'display_label' => trim($repair->device_make.' '.$repair->device_model).' work order',
                'opened_at' => $repair->created_at->toFormattedDateString(),
                'created_at' => $repair->created_at->toIso8601String(),
                'reopened_from_repair_job_id' => $repair->reopened_from_repair_job_id,
            ])->all();
    }
}
