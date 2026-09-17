<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\Repairs\AssignTechnicianRequest;
use App\Http\Requests\Admin\Repairs\AuthorizeRepairRequest;
use App\Http\Requests\Admin\Repairs\CompleteRepairRequest;
use App\Http\Requests\Admin\Repairs\CreateRepairJobRequest;
use App\Http\Requests\Admin\Repairs\FailRepairRequest;
use App\Http\Requests\Admin\Repairs\MarkRepairUnrepairableRequest;
use App\Http\Requests\Admin\Repairs\RecordDiagnosisRequest;
use App\Http\Requests\Admin\Repairs\ReserveRepairPartsRequest;
use App\Http\Requests\Admin\Repairs\UpdateDeviceLockRequest;
use App\Http\Requests\Admin\Repairs\UploadRepairPhotoRequest;
use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Identity\Application\Queries\StaffDirectoryQuery;
use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Repair\Application\Commands\AssignTechnicianCommand;
use Domain\Repair\Application\Commands\AuthorizeRepairCommand;
use Domain\Repair\Application\Commands\CompleteRepairCommand;
use Domain\Repair\Application\Commands\ConfirmDownPaymentCommand;
use Domain\Repair\Application\Commands\CreateRepairJobCommand;
use Domain\Repair\Application\Commands\DeleteRepairPhotoCommand;
use Domain\Repair\Application\Commands\FailRepairCommand;
use Domain\Repair\Application\Commands\InstallRepairPartCommand;
use Domain\Repair\Application\Commands\MarkRepairUnrepairableCommand;
use Domain\Repair\Application\Commands\RecordDiagnosisCommand;
use Domain\Repair\Application\Commands\ReserveRepairPartsCommand;
use Domain\Repair\Application\Commands\ResumeRepairCommand;
use Domain\Repair\Application\Commands\StartRepairCommand;
use Domain\Repair\Application\Commands\UpdateDeviceLockCommand;
use Domain\Repair\Application\Commands\UploadRepairPhotoCommand;
use Domain\Repair\Application\Handlers\AssignTechnicianHandler;
use Domain\Repair\Application\Handlers\AuthorizeRepairHandler;
use Domain\Repair\Application\Handlers\CompleteRepairHandler;
use Domain\Repair\Application\Handlers\ConfirmDownPaymentHandler;
use Domain\Repair\Application\Handlers\CreateRepairJobHandler;
use Domain\Repair\Application\Handlers\DeleteRepairPhotoHandler;
use Domain\Repair\Application\Handlers\FailRepairHandler;
use Domain\Repair\Application\Handlers\InstallRepairPartHandler;
use Domain\Repair\Application\Handlers\MarkRepairUnrepairableHandler;
use Domain\Repair\Application\Handlers\RecordDiagnosisHandler;
use Domain\Repair\Application\Handlers\ReserveRepairPartsHandler;
use Domain\Repair\Application\Handlers\ResumeRepairHandler;
use Domain\Repair\Application\Handlers\StartRepairHandler;
use Domain\Repair\Application\Handlers\UpdateDeviceLockHandler;
use Domain\Repair\Application\Handlers\UploadRepairPhotoHandler;
use Domain\Repair\Application\Queries\DeviceCatalogQuery;
use Domain\Repair\Application\Queries\RepairJobDetailQuery;
use Domain\Repair\Application\Queries\RepairsListQuery;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shared\Domain\Exceptions\DomainException;
use Domain\Shop\Application\Queries\ShopDirectoryQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RepairsController
{
    use FlashesToast;

    public function __construct(
        private readonly RepairsListQuery $list,
        private readonly RepairJobDetailQuery $detail,
        private readonly ShopDirectoryQuery $shops,
        private readonly CustomerDirectoryQuery $customers,
        private readonly InventoryCatalogQuery $catalog,
        private readonly CreateRepairJobHandler $createRepairJob,
        private readonly RecordDiagnosisHandler $recordDiagnosis,
        private readonly AuthorizeRepairHandler $authorizeRepair,
        private readonly ConfirmDownPaymentHandler $confirmDownPayment,
        private readonly ReserveRepairPartsHandler $reserveParts,
        private readonly InstallRepairPartHandler $installPart,
        private readonly StartRepairHandler $startRepair,
        private readonly CompleteRepairHandler $completeRepair,
        private readonly FailRepairHandler $failRepair,
        private readonly ResumeRepairHandler $resumeRepair,
        private readonly MarkRepairUnrepairableHandler $markUnrepairable,
        private readonly AssignTechnicianHandler $assignTechnician,
        private readonly StaffDirectoryQuery $staff,
        private readonly DeviceCatalogQuery $deviceCatalog,
        private readonly UpdateDeviceLockHandler $updateDeviceLock,
        private readonly UploadRepairPhotoHandler $uploadPhoto,
        private readonly DeleteRepairPhotoHandler $deletePhoto,
    ) {}

    public function index(Request $request): Response
    {
        $actor = app(ActorContext::class);

        // No explicit ?shop_id= at all (a fresh nav from the sidebar) defaults
        // to whatever shop is active in the switcher; an explicit empty value
        // (picking "All shops" in the filter) is a deliberate override.
        $shopId = $request->has('shop_id') ? ($request->integer('shop_id') ?: null) : $actor->activeShopId;

        return Inertia::render('Admin/Repairs/Index', [
            'repairs' => $this->list->paginate(
                status: $request->string('status')->toString() ?: null,
                shopId: $shopId,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'status' => $request->string('status')->toString(),
                'shop_id' => $shopId,
            ],
            'shops' => $this->shops->all(),
        ]);
    }

    /**
     * Two modes: `ids[]` given exports exactly those repairs ("export
     * selected"); no `ids[]` exports everything matching the current
     * `status`/`shop_id` filters ("export matching filter") — both are
     * offered on the Index page's toolbar, not one-or-the-other.
     */
    public function export(Request $request): StreamedResponse
    {
        $actor = app(ActorContext::class);
        $ids = $request->has('ids') ? array_map('intval', $request->array('ids')) : null;
        $shopId = $request->has('shop_id') ? ($request->integer('shop_id') ?: null) : $actor->activeShopId;

        $rows = $this->list->exportRows(
            status: $ids === null ? ($request->string('status')->toString() ?: null) : null,
            shopId: $ids === null ? $shopId : null,
            ids: $ids,
        );

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['ID', 'Device', 'Customer', 'Status', 'Technician', 'Cost (NGN)', 'Updated at']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['id'],
                    trim(($row['device_make'] ?? '').' '.($row['device_model'] ?? '')),
                    $row['customer_name'] ?? '',
                    $row['repair_status'],
                    $row['technician_name'] ?? 'Unassigned',
                    number_format($row['labour_charge_minor'] / 100, 2, '.', ''),
                    $row['updated_at'],
                ]);
            }

            fclose($handle);
        }, 'afprospos-repairs-export.csv', ['Content-Type' => 'text/csv']);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Repairs/Create', [
            'shops' => $this->shops->all(),
            'deviceCatalog' => $this->deviceCatalog->structure(),
        ]);
    }

    public function store(CreateRepairJobRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $id = $this->createRepairJob->handle(new CreateRepairJobCommand(
            shopId: $request->integer('shop_id'),
            customerId: $request->integer('customer_id'),
            deviceMake: $request->string('device_make')->toString(),
            deviceModel: $request->string('device_model')->toString(),
            reportedIssue: $request->string('reported_issue')->toString() ?: null,
            deviceImeiSerial: $request->string('device_imei_serial')->toString() ?: null,
            deviceLockType: $request->string('device_lock_type')->toString() ?: 'none',
            deviceLockValue: $request->string('device_lock_value')->toString() ?: null,
            problemTagIds: array_map('intval', $request->array('problem_tag_ids') ?? []),
            labourChargeMinor: $request->integer('labour_charge_minor'),
            createdByStaffId: $actor->staffId->value,
        ));

        $this->flashSuccess('Repair job created');

        return redirect()->route('admin.repairs.show', ['repair' => $id]);
    }

    public function show(int $repair): Response
    {
        $actor = app(ActorContext::class);
        $data = $this->detail->find($repair, $actor->staffId->value, $actor->isOwner);

        abort_if($data === null, 404);

        return Inertia::render('Admin/Repairs/Show', [
            'repair' => $data,
            'shop' => $this->shops->find((int) $data['shop_id']),
            'technicians' => $this->staff->paginate(
                search: null,
                status: 'active',
                role: 'Technician',
                page: 1,
                perPage: 100,
                shopId: (int) $data['shop_id'],
            )['data'],
        ]);
    }

    public function updateDeviceLock(UpdateDeviceLockRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->updateDeviceLock->handle(new UpdateDeviceLockCommand(
            repairJobId: $repair,
            deviceLockType: $request->string('device_lock_type')->toString(),
            deviceLockValue: $request->string('device_lock_value')->toString() ?: null,
            updatedByStaffId: $actor->staffId->value,
        ));

        $this->flashSuccess('Device lock updated');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    /** File handling is a Controller-level concern (CPNC §2.3) -- the Command carries only the already-stored path. */
    public function uploadPhoto(UploadRepairPhotoRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $path = $request->file('photo')->store("repairs/{$repair}", 'public');

        $this->uploadPhoto->handle(new UploadRepairPhotoCommand(
            repairJobId: $repair,
            path: $path,
            caption: $request->string('caption')->toString() ?: null,
            uploadedByStaffId: $actor->staffId->value,
        ));

        $this->flashSuccess('Photo uploaded');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function deletePhoto(int $repair, int $photo): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->deletePhoto->handle(new DeleteRepairPhotoCommand($photo, $actor->staffId->value));

        $this->flashSuccess('Photo deleted');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function assignTechnician(AssignTechnicianRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->assignTechnician->handle(new AssignTechnicianCommand(
            repairJobId: $repair,
            technicianStaffId: $request->integer('technician_staff_id'),
            assignedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Technician assigned');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function diagnosis(int $repair): Response
    {
        $data = $this->detail->find($repair);

        abort_if($data === null, 404);

        return Inertia::render('Admin/Repairs/Diagnosis', ['repair' => $data]);
    }

    public function recordDiagnosis(RecordDiagnosisRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->recordDiagnosis->handle(new RecordDiagnosisCommand(
            repairJobId: $repair,
            component: $request->string('component')->toString() ?: null,
            condition: $request->string('condition')->toString() ?: null,
            notes: $request->string('notes')->toString() ?: null,
            outcome: $request->string('outcome')->toString() ?: null,
            diagnosedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Diagnosis recorded');

        return redirect()->route('admin.repairs.diagnosis', ['repair' => $repair]);
    }

    public function authorize(AuthorizeRepairRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->authorizeRepair->handle(new AuthorizeRepairCommand(
            repairJobId: $repair,
            downPaymentRequiredMinor: $request->integer('down_payment_required_minor'),
            downPaymentMethod: $request->string('down_payment_method')->toString(),
            authorizedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Repair authorized');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function confirmDownPayment(int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->confirmDownPayment->handle(new ConfirmDownPaymentCommand($repair, $actor->staffId->value)));

        $this->flashSuccess('Down payment confirmed');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function parts(int $repair): Response
    {
        $data = $this->detail->find($repair);

        abort_if($data === null, 404);

        return Inertia::render('Admin/Repairs/Parts', [
            'repair' => $data,
            'suggestedParts' => $this->deviceCatalog->suggestedPartsForRepair($repair, (int) $data['shop_id']),
        ]);
    }

    public function collection(int $repair): Response
    {
        $data = $this->detail->find($repair);

        abort_if($data === null, 404);

        return Inertia::render('Admin/Repairs/Collection', ['repair' => $data]);
    }

    public function reserveParts(ReserveRepairPartsRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->reserveParts->handle(new ReserveRepairPartsCommand(
            repairJobId: $repair,
            skuId: $request->integer('sku_id'),
            quantity: $request->integer('quantity'),
            reservedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Part reserved');

        return redirect()->route('admin.repairs.parts', ['repair' => $repair]);
    }

    public function installPart(int $repair, int $reservation): RedirectResponse
    {
        $this->installPart->handle(new InstallRepairPartCommand($reservation));

        $this->flashSuccess('Part installed');

        return redirect()->route('admin.repairs.parts', ['repair' => $repair]);
    }

    public function start(int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->startRepair->handle(new StartRepairCommand($repair, $actor->staffId->value)));

        $this->flashSuccess('Repair started');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function complete(CompleteRepairRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->completeRepair->handle(new CompleteRepairCommand(
            repairJobId: $repair,
            financialStatus: $request->string('financial_status')->toString(),
            resolutionNotes: $request->string('resolution_notes')->toString() ?: null,
            completedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Repair completed');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function fail(FailRepairRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->failRepair->handle(new FailRepairCommand(
            repairJobId: $repair,
            reason: $request->string('reason')->toString(),
            failedByStaffId: $actor->staffId->value,
        )));

        $this->flashWarning('Repair marked failed');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function resume(int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->resumeRepair->handle(new ResumeRepairCommand($repair, $actor->staffId->value)));

        $this->flashSuccess('Repair resumed');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function markUnrepairable(MarkRepairUnrepairableRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->markUnrepairable->handle(new MarkRepairUnrepairableCommand(
            repairJobId: $repair,
            settlementState: $request->string('settlement_state')->toString(),
            markedByStaffId: $actor->staffId->value,
        )));

        $this->flashWarning('Repair marked unrepairable');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $term = $request->string('q')->toString();

        return response()->json([
            'results' => $term !== '' ? $this->customers->search($term) : [],
        ]);
    }

    public function searchParts(Request $request, int $repair): JsonResponse
    {
        $data = $this->detail->find($repair);
        abort_if($data === null, 404);

        $term = $request->string('q')->toString();

        return response()->json([
            'results' => $term !== '' ? $this->catalog->search($term, (int) $data['shop_id']) : [],
        ]);
    }

    /** An illegal transition (or a mutation attempt on a closed job) surfaces as a friendly validation message, not a 500 — mirrors PaymentController::guardTransition. */
    private function guardTransition(callable $action): void
    {
        try {
            $action();
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }
    }
}
