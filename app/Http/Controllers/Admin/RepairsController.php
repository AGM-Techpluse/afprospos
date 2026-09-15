<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Repairs\AssignTechnicianRequest;
use App\Http\Requests\Admin\Repairs\AuthorizeRepairRequest;
use App\Http\Requests\Admin\Repairs\CompleteRepairRequest;
use App\Http\Requests\Admin\Repairs\CreateRepairJobRequest;
use App\Http\Requests\Admin\Repairs\FailRepairRequest;
use App\Http\Requests\Admin\Repairs\MarkRepairUnrepairableRequest;
use App\Http\Requests\Admin\Repairs\RecordDiagnosisRequest;
use App\Http\Requests\Admin\Repairs\ReserveRepairPartsRequest;
use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Identity\Application\Queries\StaffDirectoryQuery;
use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Repair\Application\Commands\AssignTechnicianCommand;
use Domain\Repair\Application\Commands\AuthorizeRepairCommand;
use Domain\Repair\Application\Commands\CompleteRepairCommand;
use Domain\Repair\Application\Commands\ConfirmDownPaymentCommand;
use Domain\Repair\Application\Commands\CreateRepairJobCommand;
use Domain\Repair\Application\Commands\FailRepairCommand;
use Domain\Repair\Application\Commands\InstallRepairPartCommand;
use Domain\Repair\Application\Commands\MarkRepairUnrepairableCommand;
use Domain\Repair\Application\Commands\RecordDiagnosisCommand;
use Domain\Repair\Application\Commands\ReserveRepairPartsCommand;
use Domain\Repair\Application\Commands\ResumeRepairCommand;
use Domain\Repair\Application\Commands\StartRepairCommand;
use Domain\Repair\Application\Handlers\AssignTechnicianHandler;
use Domain\Repair\Application\Handlers\AuthorizeRepairHandler;
use Domain\Repair\Application\Handlers\CompleteRepairHandler;
use Domain\Repair\Application\Handlers\ConfirmDownPaymentHandler;
use Domain\Repair\Application\Handlers\CreateRepairJobHandler;
use Domain\Repair\Application\Handlers\FailRepairHandler;
use Domain\Repair\Application\Handlers\InstallRepairPartHandler;
use Domain\Repair\Application\Handlers\MarkRepairUnrepairableHandler;
use Domain\Repair\Application\Handlers\RecordDiagnosisHandler;
use Domain\Repair\Application\Handlers\ReserveRepairPartsHandler;
use Domain\Repair\Application\Handlers\ResumeRepairHandler;
use Domain\Repair\Application\Handlers\StartRepairHandler;
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

final class RepairsController
{
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
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Repairs/Index', [
            'repairs' => $this->list->paginate(
                status: $request->string('status')->toString() ?: null,
                shopId: $request->integer('shop_id') ?: null,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'status' => $request->string('status')->toString(),
                'shop_id' => $request->integer('shop_id') ?: null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Repairs/Create', [
            'shops' => $this->shops->all(),
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
            labourChargeMinor: $request->integer('labour_charge_minor'),
            createdByStaffId: $actor->staffId->value,
        ));

        return redirect()->route('admin.repairs.show', ['repair' => $id]);
    }

    public function show(int $repair): Response
    {
        $data = $this->detail->find($repair);

        abort_if($data === null, 404);

        return Inertia::render('Admin/Repairs/Show', [
            'repair' => $data,
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

    public function assignTechnician(AssignTechnicianRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->assignTechnician->handle(new AssignTechnicianCommand(
            repairJobId: $repair,
            technicianStaffId: $request->integer('technician_staff_id'),
            assignedByStaffId: $actor->staffId->value,
        )));

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
            component: $request->string('component')->toString(),
            condition: $request->string('condition')->toString(),
            notes: $request->string('notes')->toString() ?: null,
            outcome: $request->string('outcome')->toString() ?: null,
            diagnosedByStaffId: $actor->staffId->value,
        )));

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

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function confirmDownPayment(int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->confirmDownPayment->handle(new ConfirmDownPaymentCommand($repair, $actor->staffId->value)));

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function parts(int $repair): Response
    {
        $data = $this->detail->find($repair);

        abort_if($data === null, 404);

        return Inertia::render('Admin/Repairs/Parts', ['repair' => $data]);
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

        return redirect()->route('admin.repairs.parts', ['repair' => $repair]);
    }

    public function installPart(int $repair, int $reservation): RedirectResponse
    {
        $this->installPart->handle(new InstallRepairPartCommand($reservation));

        return redirect()->route('admin.repairs.parts', ['repair' => $repair]);
    }

    public function start(int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->startRepair->handle(new StartRepairCommand($repair, $actor->staffId->value)));

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function complete(CompleteRepairRequest $request, int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->completeRepair->handle(new CompleteRepairCommand(
            repairJobId: $repair,
            financialStatus: $request->string('financial_status')->toString(),
            completedByStaffId: $actor->staffId->value,
        )));

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

        return redirect()->route('admin.repairs.show', ['repair' => $repair]);
    }

    public function resume(int $repair): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->resumeRepair->handle(new ResumeRepairCommand($repair, $actor->staffId->value)));

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
