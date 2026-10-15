<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\Repairs\AssignTechnicianRequest;
use App\Http\Requests\Admin\Repairs\AuthorizeRepairRequest;
use App\Http\Requests\Admin\Repairs\CancelRepairRequest;
use App\Http\Requests\Admin\Repairs\CompleteRepairRequest;
use App\Http\Requests\Admin\Repairs\CreateRepairJobRequest;
use App\Http\Requests\Admin\Repairs\DecideDiagnosisFeeRequest;
use App\Http\Requests\Admin\Repairs\DecideRepairDownPaymentApprovalRequest;
use App\Http\Requests\Admin\Repairs\DecideRepairPaymentApprovalRequest;
use App\Http\Requests\Admin\Repairs\FailRepairRequest;
use App\Http\Requests\Admin\Repairs\MarkRepairUnrepairableRequest;
use App\Http\Requests\Admin\Repairs\RecordDiagnosisConclusionRequest;
use App\Http\Requests\Admin\Repairs\RecordDiagnosisRequest;
use App\Http\Requests\Admin\Repairs\RecordQualityCheckRequest;
use App\Http\Requests\Admin\Repairs\RequestDiagnosisFeeRequest;
use App\Http\Requests\Admin\Repairs\RequestRepairDownPaymentApprovalRequest;
use App\Http\Requests\Admin\Repairs\RequestRepairPaymentApprovalRequest;
use App\Http\Requests\Admin\Repairs\ReserveRepairPartsRequest;
use App\Http\Requests\Admin\Repairs\SetDiagnosisFeeRequest;
use App\Http\Requests\Admin\Repairs\SetRepairCostRequest;
use App\Http\Requests\Admin\Repairs\TransferRepairRequest;
use App\Http\Requests\Admin\Repairs\UpdateDeviceLockRequest;
use App\Http\Requests\Admin\Repairs\UploadRepairPhotoRequest;
use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Identity\Application\Queries\StaffDirectoryQuery;
use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Repair\Application\Commands\AssignTechnicianCommand;
use Domain\Repair\Application\Commands\AuthorizeRepairCommand;
use Domain\Repair\Application\Commands\CancelRepairCommand;
use Domain\Repair\Application\Commands\CompleteRepairCommand;
use Domain\Repair\Application\Commands\CreateRepairJobCommand;
use Domain\Repair\Application\Commands\DecideDiagnosisFeeCommand;
use Domain\Repair\Application\Commands\DecideRepairDownPaymentApprovalCommand;
use Domain\Repair\Application\Commands\DecideRepairPaymentApprovalCommand;
use Domain\Repair\Application\Commands\DeleteRepairPhotoCommand;
use Domain\Repair\Application\Commands\FailRepairCommand;
use Domain\Repair\Application\Commands\InstallRepairPartCommand;
use Domain\Repair\Application\Commands\MarkRepairUnrepairableCommand;
use Domain\Repair\Application\Commands\RecordDiagnosisCommand;
use Domain\Repair\Application\Commands\RecordDiagnosisConclusionCommand;
use Domain\Repair\Application\Commands\RecordQualityCheckCommand;
use Domain\Repair\Application\Commands\RequestDiagnosisFeeCommand;
use Domain\Repair\Application\Commands\RequestRepairDownPaymentApprovalCommand;
use Domain\Repair\Application\Commands\RequestRepairPaymentApprovalCommand;
use Domain\Repair\Application\Commands\ReserveRepairPartsCommand;
use Domain\Repair\Application\Commands\ResumeRepairCommand;
use Domain\Repair\Application\Commands\SendToQuoteCommand;
use Domain\Repair\Application\Commands\SetDiagnosisFeeCommand;
use Domain\Repair\Application\Commands\SetRepairCostCommand;
use Domain\Repair\Application\Commands\StartRepairCommand;
use Domain\Repair\Application\Commands\TransferRepairJobCommand;
use Domain\Repair\Application\Commands\UpdateDeviceLockCommand;
use Domain\Repair\Application\Commands\UploadRepairPhotoCommand;
use Domain\Repair\Application\Contracts\RepairJobLookup;
use Domain\Repair\Application\Handlers\AssignTechnicianHandler;
use Domain\Repair\Application\Handlers\AuthorizeRepairHandler;
use Domain\Repair\Application\Handlers\CancelRepairHandler;
use Domain\Repair\Application\Handlers\CompleteRepairHandler;
use Domain\Repair\Application\Handlers\CreateRepairJobHandler;
use Domain\Repair\Application\Handlers\DecideDiagnosisFeeHandler;
use Domain\Repair\Application\Handlers\DecideRepairDownPaymentApprovalHandler;
use Domain\Repair\Application\Handlers\DecideRepairPaymentApprovalHandler;
use Domain\Repair\Application\Handlers\DeleteRepairPhotoHandler;
use Domain\Repair\Application\Handlers\FailRepairHandler;
use Domain\Repair\Application\Handlers\InstallRepairPartHandler;
use Domain\Repair\Application\Handlers\MarkRepairUnrepairableHandler;
use Domain\Repair\Application\Handlers\RecordDiagnosisConclusionHandler;
use Domain\Repair\Application\Handlers\RecordDiagnosisHandler;
use Domain\Repair\Application\Handlers\RecordQualityCheckHandler;
use Domain\Repair\Application\Handlers\RequestDiagnosisFeeHandler;
use Domain\Repair\Application\Handlers\RequestRepairDownPaymentApprovalHandler;
use Domain\Repair\Application\Handlers\RequestRepairPaymentApprovalHandler;
use Domain\Repair\Application\Handlers\ReserveRepairPartsHandler;
use Domain\Repair\Application\Handlers\ResumeRepairHandler;
use Domain\Repair\Application\Handlers\SendToQuoteHandler;
use Domain\Repair\Application\Handlers\SetDiagnosisFeeHandler;
use Domain\Repair\Application\Handlers\SetRepairCostHandler;
use Domain\Repair\Application\Handlers\StartRepairHandler;
use Domain\Repair\Application\Handlers\TransferRepairJobHandler;
use Domain\Repair\Application\Handlers\UpdateDeviceLockHandler;
use Domain\Repair\Application\Handlers\UploadRepairPhotoHandler;
use Domain\Repair\Application\Queries\DeviceCatalogQuery;
use Domain\Repair\Application\Queries\RepairJobDetailQuery;
use Domain\Repair\Application\Queries\RepairsListQuery;
use Domain\Repair\Domain\Exceptions\WarehouseCannotTakeRepairs;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisCaseFaultRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisCaseRecord;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shared\Domain\Exceptions\DomainException;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
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
        private readonly RepairJobLookup $repairJobLookup,
        private readonly ShopDirectoryQuery $shops,
        private readonly AccessibleShopsQuery $accessibleShops,
        private readonly CustomerDirectoryQuery $customers,
        private readonly InventoryCatalogQuery $catalog,
        private readonly CreateRepairJobHandler $createRepairJob,
        private readonly RecordDiagnosisHandler $recordDiagnosis,
        private readonly RecordDiagnosisConclusionHandler $recordDiagnosisConclusion,
        private readonly AuthorizeRepairHandler $authorizeRepair,
        private readonly SendToQuoteHandler $sendToQuote,
        private readonly SetRepairCostHandler $setRepairCost,
        private readonly TransferRepairJobHandler $transferRepair,
        private readonly RequestRepairDownPaymentApprovalHandler $requestDownPaymentApproval,
        private readonly DecideRepairDownPaymentApprovalHandler $decideDownPaymentApproval,
        private readonly ReserveRepairPartsHandler $reserveParts,
        private readonly InstallRepairPartHandler $installPart,
        private readonly StartRepairHandler $startRepair,
        private readonly CompleteRepairHandler $completeRepair,
        private readonly RequestRepairPaymentApprovalHandler $requestPaymentApproval,
        private readonly DecideRepairPaymentApprovalHandler $decidePaymentApproval,
        private readonly FailRepairHandler $failRepair,
        private readonly ResumeRepairHandler $resumeRepair,
        private readonly MarkRepairUnrepairableHandler $markUnrepairable,
        private readonly AssignTechnicianHandler $assignTechnician,
        private readonly StaffDirectoryQuery $staff,
        private readonly DeviceCatalogQuery $deviceCatalog,
        private readonly UpdateDeviceLockHandler $updateDeviceLock,
        private readonly UploadRepairPhotoHandler $uploadPhoto,
        private readonly DeleteRepairPhotoHandler $deletePhoto,
        private readonly RecordQualityCheckHandler $recordQualityCheck,
        private readonly CancelRepairHandler $cancelRepair,
        private readonly RequestDiagnosisFeeHandler $requestDiagnosisFee,
        private readonly SetDiagnosisFeeHandler $setDiagnosisFee,
        private readonly DecideDiagnosisFeeHandler $decideDiagnosisFee,
    ) {}

    public function index(Request $request): Response
    {
        $actor = app(ActorContext::class);

        // No explicit ?shop_id= at all (a fresh nav from the sidebar) defaults
        // to whatever shop is active in the switcher; an explicit empty value
        // (picking "All shops" in the filter) is a deliberate override.
        $shopId = $request->has('shop_id') ? ($request->integer('shop_id') ?: null) : $actor->activeShopId;
        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        return Inertia::render('Admin/Repairs/Index', [
            'repairs' => $this->list->paginate(
                status: $request->string('status')->toString() ?: null,
                shopId: $shopId,
                page: $request->integer('page', 1),
                restrictToShopIds: $actor->isOwner ? null : $actor->shopIds,
            ),
            'filters' => [
                'status' => $request->string('status')->toString(),
                'shop_id' => $shopId,
            ],
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
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
        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        $rows = $this->list->exportRows(
            status: $ids === null ? ($request->string('status')->toString() ?: null) : null,
            shopId: $ids === null ? $shopId : null,
            ids: $ids,
            restrictToShopIds: $actor->isOwner ? null : $actor->shopIds,
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

    public function create(Request $request): Response
    {
        $actor = app(ActorContext::class);
        $shopId = $request->has('shop_id') ? ($request->integer('shop_id') ?: null) : ($actor->activeShopId ?? ($actor->shopIds[0] ?? null));

        return Inertia::render('Admin/Repairs/Create', [
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
            'deviceCatalog' => $this->deviceCatalog->structure(),
            'diagnosisCases' => $this->repairableDiagnosisCases($actor, $shopId),
            'filters' => ['shop_id' => $shopId],
        ]);
    }

    public function store(CreateRepairJobRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $shopId = $request->integer('shop_id');
        abort_unless($actor->canAccessShop($shopId), 403);

        try {
            $id = $this->createRepairJob->handle(new CreateRepairJobCommand(
                shopId: $shopId,
                customerId: $request->integer('customer_id') ?: null,
                walkInName: $request->string('walk_in_name')->toString() ?: null,
                walkInPhone: $request->string('walk_in_phone')->toString() ?: null,
                walkInEmail: $request->string('walk_in_email')->toString() ?: null,
                deviceMake: $request->string('device_make')->toString(),
                deviceModel: $request->string('device_model')->toString(),
                reportedIssue: $request->string('reported_issue')->toString() ?: null,
                deviceImeiSerial: $request->string('device_imei_serial')->toString() ?: null,
                deviceLockType: $request->string('device_lock_type')->toString() ?: 'none',
                deviceLockValue: $request->string('device_lock_value')->toString() ?: null,
                problemTagIds: array_map('intval', $request->array('problem_tag_ids') ?? []),
                labourChargeMinor: $request->filled('labour_charge_minor') ? $request->integer('labour_charge_minor') : null,
                createdByStaffId: $actor->staffId->value,
            ));
        } catch (WarehouseCannotTakeRepairs $exception) {
            throw ValidationException::withMessages(['shop_id' => $exception->getMessage()]);
        }

        $this->flashSuccess('Repair job created');

        return redirect()->route('admin.repairs.show', ['repair' => $id]);
    }

    public function show(int $repair): Response
    {
        $actor = app(ActorContext::class);
        $data = $this->detail->find($repair, $actor->staffId->value, $actor->isOwner);

        abort_if($data === null, 404);
        abort_unless($actor->canAccessShop((int) $data['shop_id']), 403);

        return Inertia::render('Admin/Repairs/Show', [
            'repair' => $data,
            'shop' => $this->shops->find((int) $data['shop_id']),
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
            'canApprovePayment' => $actor->hasPermission('repairs.approve-payment'),
            'canAuthorize' => $actor->hasPermission('repairs.authorize'),
            'canTransferRepair' => $actor->isOwner || $actor->hasPermission('repairs.transfer') || $actor->hasPermission('repairs.authorize') || $actor->hasPermission('repairs.assign'),
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

    public function receipt(int $repair): Response
    {
        $actor = app(ActorContext::class);
        $data = $this->detail->find($repair, $actor->staffId->value, $actor->isOwner);

        abort_if($data === null, 404);
        abort_unless($actor->canAccessShop((int) $data['shop_id']), 403);

        return Inertia::render('Admin/Repairs/Receipt', [
            'repair' => $data,
            'shop' => $this->shops->find((int) $data['shop_id']),
        ]);
    }

    public function transfer(TransferRepairRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);
        $targetShopId = $request->integer('target_shop_id');
        abort_unless($actor->canAccessShop($targetShopId), 403);

        $this->guardTransition(fn () => $this->transferRepair->handle(new TransferRepairJobCommand(
            repairJobId: $repair,
            targetShopId: $targetShopId,
            transferredByStaffId: $actor->staffId->value,
            releaseReservedParts: $request->boolean('release_reserved_parts'),
            reason: $request->string('reason')->toString() ?: null,
        )));

        $this->flashSuccess('Repair transferred');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function updateDeviceLock(UpdateDeviceLockRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

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
        $this->assertRepairShopAccess($actor, $repair);
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
        $this->assertRepairShopAccess($actor, $repair);

        $this->deletePhoto->handle(new DeleteRepairPhotoCommand($photo, $actor->staffId->value));

        $this->flashSuccess('Photo deleted');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function assignTechnician(AssignTechnicianRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

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
        $actor = app(ActorContext::class);
        $data = $this->detail->find($repair);

        abort_if($data === null, 404);
        abort_unless($actor->canAccessShop((int) $data['shop_id']), 403);

        return Inertia::render('Admin/Repairs/Diagnosis', ['repair' => $data]);
    }

    public function recordDiagnosis(RecordDiagnosisRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->recordDiagnosis->handle(new RecordDiagnosisCommand(
            repairJobId: $repair,
            component: $request->string('component')->toString(),
            condition: $request->string('condition')->toString(),
            notes: $request->string('notes')->toString() ?: null,
            diagnosedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Component recorded');

        return redirect()->route('admin.repairs.diagnosis', ['repair' => $repair]);
    }

    public function recordDiagnosisConclusion(RecordDiagnosisConclusionRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->recordDiagnosisConclusion->handle(new RecordDiagnosisConclusionCommand(
            repairJobId: $repair,
            rootCause: $request->string('root_cause')->toString(),
            outcome: $request->string('outcome')->toString(),
            diagnosedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Diagnosis recorded');

        return redirect()->route('admin.repairs.diagnosis', ['repair' => $repair]);
    }

    public function authorize(AuthorizeRepairRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->authorizeRepair->handle(new AuthorizeRepairCommand(
            repairJobId: $repair,
            downPaymentRequiredMinor: $request->integer('down_payment_required_minor'),
            authorizedByStaffId: $actor->staffId->value,
            repairCostMinor: $request->integer('repair_cost_minor'),
            diagnosisFeeMinor: $request->filled('diagnosis_fee_minor') ? $request->integer('diagnosis_fee_minor') : null,
            specialAgreementNote: $request->string('special_agreement_note')->toString() ?: null,
            downPaymentMethod: $request->string('down_payment_method')->toString() ?: null,
        )));

        $this->flashSuccess('Repair authorized');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function setRepairCost(SetRepairCostRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->setRepairCost->handle(new SetRepairCostCommand(
            repairJobId: $repair,
            repairCostMinor: $request->integer('repair_cost_minor'),
            setByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Repair cost updated');

        return redirect()->route('admin.repairs.diagnosis', ['repair' => $repair]);
    }

    public function requestDownPaymentApproval(RequestRepairDownPaymentApprovalRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->requestDownPaymentApproval->handle(new RequestRepairDownPaymentApprovalCommand(
            repairJobId: $repair,
            amountMinor: $request->integer('amount_minor'),
            method: $request->string('method')->toString(),
            requestedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Down payment approval requested');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function decideDownPaymentApproval(DecideRepairDownPaymentApprovalRequest $request, int $repair, int $approval): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->decideDownPaymentApproval->handle(new DecideRepairDownPaymentApprovalCommand(
            approvalId: $approval,
            approved: $request->boolean('approved'),
            decidedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess($request->boolean('approved') ? 'Down payment approved' : 'Down payment request rejected');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function parts(int $repair): Response
    {
        $actor = app(ActorContext::class);
        $data = $this->detail->find($repair);

        abort_if($data === null, 404);
        abort_unless($actor->canAccessShop((int) $data['shop_id']), 403);

        return Inertia::render('Admin/Repairs/Parts', [
            'repair' => $data,
            'suggestedParts' => $this->deviceCatalog->suggestedPartsForRepair($repair, (int) $data['shop_id']),
        ]);
    }

    public function collection(int $repair): Response
    {
        $actor = app(ActorContext::class);
        $data = $this->detail->find($repair);

        abort_if($data === null, 404);
        abort_unless($actor->canAccessShop((int) $data['shop_id']), 403);

        return Inertia::render('Admin/Repairs/Collection', ['repair' => $data]);
    }

    public function reserveParts(ReserveRepairPartsRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->reserveParts->handle(new ReserveRepairPartsCommand(
            repairJobId: $repair,
            skuId: $request->integer('sku_id'),
            quantity: $request->integer('quantity'),
            reservedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Part reserved');

        return redirect()->back();
    }

    public function installPart(int $repair, int $reservation): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->installPart->handle(new InstallRepairPartCommand($reservation));

        $this->flashSuccess('Part installed');

        return redirect()->back();
    }

    public function start(int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->startRepair->handle(new StartRepairCommand($repair, $actor->staffId->value)));

        $this->flashSuccess('Repair started');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function complete(CompleteRepairRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->completeRepair->handle(new CompleteRepairCommand(
            repairJobId: $repair,
            resolutionNotes: $request->string('resolution_notes')->toString() ?: null,
            fixedFaults: $request->array('fixed_faults'),
            completedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Repair completed');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function requestPaymentApproval(RequestRepairPaymentApprovalRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->requestPaymentApproval->handle(new RequestRepairPaymentApprovalCommand(
            repairJobId: $repair,
            requestedFinancialStatus: $request->string('financial_status')->toString(),
            amountMinor: $request->integer('amount_minor'),
            method: $request->string('method')->toString(),
            requestedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Payment approval requested');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function decidePaymentApproval(DecideRepairPaymentApprovalRequest $request, int $repair, int $approval): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->decidePaymentApproval->handle(new DecideRepairPaymentApprovalCommand(
            approvalId: $approval,
            approved: $request->boolean('approved'),
            decidedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess($request->boolean('approved') ? 'Payment approved' : 'Payment request rejected');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function fail(FailRepairRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->failRepair->handle(new FailRepairCommand(
            repairJobId: $repair,
            reason: $request->string('reason')->toString(),
            failedByStaffId: $actor->staffId->value,
            serviceChargeMinor: $request->integer('service_charge_minor'),
        )));

        $this->flashWarning('Repair marked failed');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function resume(int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->resumeRepair->handle(new ResumeRepairCommand($repair, $actor->staffId->value)));

        $this->flashSuccess('Repair resumed');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function markUnrepairable(MarkRepairUnrepairableRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->markUnrepairable->handle(new MarkRepairUnrepairableCommand(
            repairJobId: $repair,
            settlementState: $request->string('settlement_state')->toString(),
            markedByStaffId: $actor->staffId->value,
        )));

        $this->flashWarning('Repair marked unrepairable');

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    /** QC: passed, not testable (note required), or failed (note required; the admin's policy decides what follows). */
    public function recordQualityCheck(RecordQualityCheckRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->recordQualityCheck->handle(new RecordQualityCheckCommand(
            repairJobId: $repair,
            outcome: $request->string('outcome')->toString(),
            notes: $request->string('notes')->toString() ?: null,
            checkedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Quality check recorded');

        return redirect()->back();
    }

    /** Cancels an open repair (e.g. the customer declined the quote). Reserved parts go back to stock. */
    public function cancel(CancelRepairRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->cancelRepair->handle(new CancelRepairCommand(
            repairJobId: $repair,
            reason: $request->string('reason')->toString(),
            cancelledByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Repair cancelled');

        return redirect()->back();
    }

    /** Frontdesk sends a saved, repairable diagnosis to the manager for the quote and authorization. */
    public function sendToQuote(int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->sendToQuote->handle(new SendToQuoteCommand(
            repairJobId: $repair,
            sentByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Sent to quote & authorization');

        return redirect()->back();
    }

    /** Frontdesk sets the diagnosis fee after diagnosis. The manager still has to approve it. */
    public function setDiagnosisFee(SetDiagnosisFeeRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->setDiagnosisFee->handle(new SetDiagnosisFeeCommand(
            repairJobId: $repair,
            amountMinor: $request->integer('amount_minor'),
            setByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Diagnosis fee set. The manager needs to approve it before it can be collected.');

        return redirect()->back();
    }

    /** The manager approves the diagnosis fee (it becomes collectable) or declines it (it is not due). */
    public function decideDiagnosisFee(DecideDiagnosisFeeRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $approve = $request->string('decision')->toString() === 'approve';

        $this->guardTransition(fn () => $this->decideDiagnosisFee->handle(new DecideDiagnosisFeeCommand(
            repairJobId: $repair,
            approve: $approve,
            decidedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess($approve ? 'Diagnosis fee approved' : 'Diagnosis fee declined — it is not due');

        return redirect()->back();
    }

    /** The manager declines the quote. The repair is closed, as the customer does not agree to pay. */
    public function declineQuote(int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->cancelRepair->handle(new CancelRepairCommand(
            repairJobId: $repair,
            reason: 'Quote declined by manager',
            cancelledByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Quote declined — repair cancelled');

        return redirect()->back();
    }

    /** The diagnosis fee is a separate payment, never added to the repair quote. */
    public function requestDiagnosisFee(RequestDiagnosisFeeRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertRepairShopAccess($actor, $repair);

        $this->guardTransition(fn () => $this->requestDiagnosisFee->handle(new RequestDiagnosisFeeCommand(
            repairJobId: $repair,
            method: $request->string('method')->toString(),
            requestedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Diagnosis fee payment opened. Confirm it under Payments once it is received.');

        return redirect()->back();
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
        $actor = app(ActorContext::class);
        $data = $this->detail->find($repair);
        abort_if($data === null, 404);
        abort_unless($actor->canAccessShop((int) $data['shop_id']), 403);

        $term = $request->string('q')->toString();

        return response()->json([
            'results' => $term !== '' ? $this->catalog->search($term, (int) $data['shop_id']) : [],
        ]);
    }

    /** BRD RBAC-05: every action on one specific repair is blocked unless the acting staff member (or the Shop Owner, who bypasses scope entirely) was actually granted that repair's shop — the permission string alone only proves they hold the action in general, never that this particular record is theirs to touch. */
    private function assertRepairShopAccess(ActorContext $actor, int $repairId): void
    {
        $repair = $this->repairJobLookup->find($repairId);
        abort_if($repair === null, 404);
        abort_unless($actor->canAccessShop($repair['shop_id']), 403);
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

    /** @return array<int, array<string, mixed>> */
    private function repairableDiagnosisCases(ActorContext $actor, ?int $shopId): array
    {
        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        return DiagnosisCaseRecord::query()
            ->where('status', 'diagnosed')
            ->where('outcome', 'repairable')
            ->when($shopId !== null, fn ($query) => $query->where('shop_id', $shopId))
            ->when(! $actor->isOwner, fn ($query) => $query->whereIn('shop_id', $actor->shopIds))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(function (DiagnosisCaseRecord $case): array {
                $customer = $case->customer_id !== null ? $this->customers->find($case->customer_id) : null;

                return [
                    'id' => $case->id,
                    'shop_id' => $case->shop_id,
                    'customer_name' => $customer['name'] ?? null,
                    'walk_in_name' => $case->walk_in_name,
                    'device' => trim($case->device_make.' '.$case->device_model),
                    'device_imei_serial' => $case->device_imei_serial,
                    'reported_issue' => $case->reported_issue,
                    'faults' => DiagnosisCaseFaultRecord::query()
                        ->where('diagnosis_case_id', $case->id)
                        ->orderBy('id')
                        ->pluck('label_snapshot')
                        ->all(),
                ];
            })->all();
    }
}
