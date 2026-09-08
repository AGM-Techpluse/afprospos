# AfProsPos — Codebase Principles & Naming Conventions Document (CPNC)

**System:** AfProsPos — Phone Sales, Repair & Business Management System
**Architecture style:** DDD Modular Monolith · Hexagonal/Clean Architecture · CQRS-lite · Event-Driven Integration
**Stack:** Laravel (PHP) · Inertia.js + React · Tailwind CSS · MySQL 8 / InnoDB
**Document status:** BINDING — Engineering Constitution
**Authority level:** Supersedes personal style preference, framework defaults, and AI-generated convenience code
**Companion documents:** Architecture Design Document (ADD) v1.0, Business Logic Document (BLD) v1.1, Business Requirements Document v1.0, Database Design Document (DBDD) v1.0, UI/UX Design System Document v1.0
**Prepared:** September 3, 2026

---

## How to Read This Document

This is not a style guide. It is a **conformance specification**. Every rule below is either a **MUST**, a **MUST NOT**, or a **SHOULD**, using RFC-2119 severity:

- **MUST / MUST NOT** — a violation is a defect. It blocks merge regardless of who or what wrote the code, including an AI assistant acting under instruction from a human reviewer who "just wants it to work."
- **SHOULD / SHOULD NOT** — a strong default that can only be overridden with an explicit, written justification in the pull request description, referencing the specific section of the ADD/BLD/DBDD that necessitates the deviation.

This document assumes the reader (human or AI) has access to and has read the ADD, BLD, and DBDD. Where this document repeats something from those documents, this document's phrasing is the enforceable one — the ADD explains the *design*, this document enforces the *discipline*.

**Every AI assistant instructed to write, edit, or refactor AfProsPos code MUST treat this document as a system prompt extension. If a request conflicts with this document, the AI MUST flag the conflict instead of silently complying.**

---

## Table of Contents

0. [The Five Prime Directives](#0-the-five-prime-directives)
1. [Zero Dead Code & File Reuse Directive](#1-zero-dead-code--file-reuse-directive)
2. [CQRS-lite & Architectural Flow Rules](#2-cqrs-lite--architectural-flow-rules)
3. [Strict Naming Conventions](#3-strict-naming-conventions)
4. [Domain vs. Infrastructure Boundaries](#4-domain-vs-infrastructure-boundaries)
5. [Frontend (React + Inertia.js) Constraints](#5-frontend-react--inertiajs-constraints)
6. [Testing Standards](#6-testing-standards)
7. [Enforcement, CI Gates & Governance](#7-enforcement-ci-gates--governance)
8. [Appendix A — AI Pre-Flight Checklist](#appendix-a--ai-pre-flight-checklist-run-before-writing-any-code)
9. [Appendix B — Master Naming Cheat Sheet](#appendix-b--master-naming-cheat-sheet)
10. [Appendix C — Canonical Module Skeleton](#appendix-c--canonical-module-skeleton-copy-this-not-a-blank-folder)

---

# 0. The Five Prime Directives

These five statements are the compression of the entire document. If an AI assistant can only retain one paragraph of context, it is this one.

1. **Reuse before you invent.** Search the codebase for an existing Contract, DTO, Value Object, Command, or Component before writing a new one. Duplication is a defect, not a shortcut.
2. **The Domain layer touches nothing external.** No Eloquent, no `Illuminate\Http`, no vendor SDK, no HTTP client, no queue facade, ever, inside `domain/<Module>/Domain/`.
3. **Every mutation flows through exactly one path:** `Route → Controller → Command DTO → Handler → Domain Aggregate → Repository/Gateway`. There is no second way to change state.
4. **Names describe business reality, not implementation mechanics.** `RepairCompleted`, not `UpdateRepairStatus`. `PaymentGateway`, not `PaymentGatewayInterface`. `PaymentTransactionRecord`, not `PaymentTransaction` (that name is reserved for the domain entity).
5. **Laravel owns navigation. React owns interaction.** `routes/*.php` is the only router. React components receive props from Inertia; they do not decide what page the user is on.

---

# 1. Zero Dead Code & File Reuse Directive

## 1.1 Rationale

AfProsPos will be built substantially by AI code generation. The single highest-probability failure mode of AI-assisted development on a large modular codebase is **silent duplication**: an assistant asked to "add discount validation" invents a second `DiscountPolicy` because it didn't look for the first one, or a refactor of `PaymentTransaction` leaves a dead `LegacyPaymentTransaction` behind because deleting felt riskier than leaving it. Both outcomes are strictly worse than doing nothing, because they create two conflicting sources of truth that a future reader — human or AI — cannot distinguish between without archaeology.

This section is therefore not a suggestion. It is a **mandatory pre-condition** to writing any new file.

## 1.2 The Reuse-First Mandate

Before creating **any** of the following, a discovery pass is mandatory and its result must inform the implementation:

| Artifact type | Where it lives | Discovery command (example) |
|---|---|---|
| Application Contract (interface) | `domain/<Module>/Application/Contracts/` | `grep -rn "interface.*Gateway\|interface.*Repository\|interface.*Reader" domain/` |
| Value Object | `domain/<Module>/Domain/ValueObjects/` and `domain/Shared/Domain/ValueObjects/` | `find domain -path "*/ValueObjects/*.php"` |
| DTO (Command/Query) | `domain/<Module>/Application/{Commands,Queries}/` | `find domain -path "*/Application/Commands/*.php" -o -path "*/Application/Queries/*.php"` |
| Domain Event | `domain/<Module>/Domain/Events/` | `find domain -path "*/Domain/Events/*.php"` |
| Domain Exception | `domain/<Module>/Domain/Exceptions/` and `domain/Shared/Domain/Exceptions/` | `find domain -path "*Exception*.php"` |
| React Component | `resources/js/Components/` | `grep -rln "export default function" resources/js/Components/` |
| React Feature | `resources/js/Features/<Module>/` | `find resources/js/Features -iname "*<Keyword>*"` |
| Design Token / Tailwind class | `resources/css/tokens.css`, `tailwind.config.js` | `grep -n "a-\|c-" resources/css/tokens.css` |
| Eloquent `*Record` model | `domain/<Module>/Infrastructure/Persistence/Eloquent/` | `find domain -iname "*Record.php"` |

**Rule:** If discovery finds a match that is *semantically* the same concept — even under a different name, even in a different module's Shared Kernel area — the existing artifact MUST be reused or explicitly extended. A new artifact MUST NOT be created merely because the existing one's name doesn't match what the current prompt happened to call it.

```text
DO:
  Task: "add a value object for phone IMEI"
  → search domain/Inventory/Domain/ValueObjects and domain/Shared/Domain/ValueObjects
  → find `SerializedUnitIdentifier` already exists and models this exact concept
  → reuse SerializedUnitIdentifier

DON'T:
  Task: "add a value object for phone IMEI"
  → invent `ImeiNumber.php` without searching
  → now two value objects represent the same domain concept
```

## 1.3 Forbidden: Duplicate Files & Shadow Implementations

The following patterns are **explicitly forbidden** and must be rejected on sight, whether proposed by a human or generated by an AI:

```text
FORBIDDEN — versioned/shadow files
    PaymentGatewayV2.php
    PaymentGatewayNew.php
    PaymentGateway_old.php
    PaymentGatewayFixed.php
    RepairJobRepository copy.php

FORBIDDEN — parallel implementations of the same contract
    app/Services/PaymentService.php        (ad-hoc, outside domain/)
    domain/Payments/Application/...        (the real one)

FORBIDDEN — re-declared DTOs with the same shape under a different name
    InitiatePaymentCommand
    StartPaymentCommand           ← same fields, same purpose, different author
```

**Rule:** If a class must change shape, it is **edited in place**, with git history as the record of change. A new file is only justified when it represents a genuinely new business concept, not a revision of an old one.

## 1.4 The "No Orphans" Refactoring Rule

When a class, method, route, migration, or React component is replaced during refactoring:

1. Every caller MUST be updated in the same change set — not scheduled for "later cleanup."
2. The old file MUST be deleted, not commented out, not renamed to `*.bak`, not left "just in case."
3. `grep -rn` for the old class/route/component name MUST return zero results outside of CHANGELOG entries or migration history before the change is considered complete.
4. Database columns/tables deprecated by a refactor MUST get an explicit deprecation migration (see DBDD §7, Delete Policy) — not silent abandonment. Financially/operationally significant rows are never hard-deleted per the ADD's non-goals (§1.2), but *unused schema* (an orphaned column, an orphaned table) is a defect and must be formally retired via migration, never just ignored.

```text
DO (refactor):
  1. Rename `ReservationPolicy::isAllowed()` → `ReservationPolicy::canReserve()`
  2. Update the one caller in ReserveCheckoutInventoryHandler
  3. Update the one test: ReservationPolicyTest
  4. Grep confirms zero remaining references to `isAllowed`
  5. Single commit contains all four changes

DON'T (refactor):
  1. Add `ReservationPolicy::canReserve()` alongside the old `isAllowed()`
  2. Leave callers on the old method "to avoid breaking things"
  3. Now two methods do the same thing and a future AI has to guess which is current
```

## 1.5 Pre-Generation Discovery Is Not Optional for AI Agents

Any AI assistant generating code for AfProsPos MUST, before writing a new class:

1. Run a repository search for the concept by business name (not by the technical pattern name).
2. Run a repository search for the exact proposed class name.
3. State, in its own output/commit message, what it searched for and what it found (even if "nothing found, proceeding to create").

This is codified further in **Appendix A**.

---

# 2. CQRS-lite & Architectural Flow Rules

## 2.1 The One True Flow

Every state-changing operation in AfProsPos — with zero exceptions — MUST follow this exact pipeline:

```text
Route  →  Controller  →  Command DTO  →  Handler  →  Domain Aggregate  →  Repository / Gateway (Infrastructure)
```

Every read-only operation follows the query-side equivalent:

```text
Route  →  Controller  →  Query DTO  →  Query Handler/Object  →  Read Model (Infrastructure query layer)  →  ViewModel/Inertia props
```

This is "CQRS-**lite**," not full event-sourced CQRS: there is one MySQL database, writes go through the domain model and its aggregates, and reads are permitted to bypass the aggregate and query Eloquent/query-builder directly **only** inside the Infrastructure query layer (ADD §39). Reads MUST NOT bypass the Application layer from the Controller — a Controller never issues raw Eloquent queries itself.

```php
// DO — controller only orchestrates
final class PaymentController
{
    public function __construct(
        private readonly InitiatePaymentHandler $handler,
    ) {}

    public function store(InitiatePaymentRequest $request): RedirectResponse
    {
        $command = new InitiatePaymentCommand(
            payableType: $request->string('payable_type')->toString(),
            payableId: $request->integer('payable_id'),
            method: PaymentMethod::from($request->string('method')->toString()),
            amountMinor: $request->integer('amount_minor'),
            idempotencyKey: $request->header('Idempotency-Key'),
        );

        $result = $this->handler->handle($command);

        return redirect($result->nextUrl);
    }
}
```

```php
// DON'T — controller reaches into Eloquent and business logic directly
final class PaymentController
{
    public function store(Request $request): RedirectResponse
    {
        $payment = PaymentTransactionRecord::create([          // ✗ Eloquent in Controller
            'amount_minor' => $request->amount_minor,
            'status' => 'pending',
        ]);

        if ($request->method === 'bank_transfer') {            // ✗ business rule in Controller
            $payment->status = 'payment_pending_confirmation';
            $payment->save();
        }

        return redirect()->route('payments.show', $payment);
    }
}
```

## 2.2 Laravel CLI Commands vs. DDD Application Commands — Disambiguation

This is the single most common naming collision in a Laravel + DDD codebase, and the one an AI is most likely to get wrong because both concepts are called "Command" by their respective ecosystems. AfProsPos resolves the ambiguity structurally, not just by convention:

| Aspect | Laravel Console Command | DDD Application Command |
|---|---|---|
| **Purpose** | Scheduled/CLI operational task (cron, artisan invocation) | Represents a single business intent/mutation |
| **Location** | `app/Console/Commands/<Module>/` | `domain/<Module>/Application/Commands/` |
| **Base class** | `Illuminate\Console\Command` | Plain PHP `final readonly class` — no framework base class |
| **Naming pattern** | Verb-first, operational: `ReconcilePayments`, `ReleaseExpiredReservations`, `ExpireRepairAuthorizations` | `[Verb][Entity]Command`: `InitiatePaymentCommand`, `CreateRepairJobCommand` |
| **Invoked by** | `routes/console.php` scheduler, `artisan` CLI | A Controller, a Console Command's `handle()`, a Queue Job, or another Handler |
| **Contains business logic?** | **No** — it resolves and calls an Application Command Handler | No — it is a data carrier only; the Handler contains orchestration, the Aggregate contains the rule |
| **Example file** | `app/Console/Commands/Payments/ReconcilePayments.php` | `domain/Payments/Application/Commands/InitiatePaymentCommand.php` |

```php
// app/Console/Commands/Payments/ReconcilePayments.php
// Laravel Console Command — operational entrypoint, delegates immediately
final class ReconcilePayments extends Command
{
    protected $signature = 'afprospos:payments:reconcile';

    public function __construct(
        private readonly ReconcileProviderPaymentsHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->handler->handle(new ReconcileProviderPaymentsCommand());

        return self::SUCCESS;
    }
}
```

```php
// domain/Payments/Application/Commands/InitiatePaymentCommand.php
// DDD Application Command — a pure, immutable data carrier
final readonly class InitiatePaymentCommand
{
    public function __construct(
        public string $payableType,
        public int $payableId,
        public PaymentMethod $method,
        public int $amountMinor,
        public ?string $idempotencyKey,
    ) {}
}
```

**Rule:** A Laravel Console Command's `handle()` method body MUST NOT exceed the work of (a) parsing CLI arguments/options, (b) constructing one Application Command or Query, (c) invoking exactly one Handler, and (d) formatting CLI output. If a Console Command contains a loop with business conditionals, a database query, or a call to a domain service directly, it is a violation of §4 (Domain vs. Infrastructure Boundaries) and must be refactored into an Application Handler.

## 2.3 Controllers Are Not Allowed To Contain Business Logic

A Controller method MUST do only the following, in this order:

1. Validate/authorize HTTP input (via a Form Request or explicit gate check).
2. Construct exactly one Command or Query DTO from validated input.
3. Invoke exactly one Application Handler (or Query object).
4. Translate the Handler's result into an Inertia response, redirect, or JSON payload.

A Controller method MUST NOT:

- Call `->save()`, `->create()`, `->update()`, or any other Eloquent mutation directly.
- Contain `if`/`match` branches that encode a business rule (e.g. "if bank transfer, mark pending_confirmation").
- Call a payment gateway, notification provider, or any vendor SDK directly.
- Perform calculations (pricing, commission, discount stacking, tax).
- Catch a `DomainException` and silently swallow it — it must be translated to an HTTP status per ADD §37, not hidden.

```text
DO — Controller responsibilities checklist
  [x] validate input (Form Request)
  [x] authorize (Policy/Gate or route middleware)
  [x] build one Command/Query DTO
  [x] call one Handler
  [x] map result → Inertia response

DON'T — Controller anti-patterns
  [ ] Eloquent calls
  [ ] business conditionals
  [ ] vendor SDK calls (Paystack, Tranzix, WhatsApp)
  [ ] calculation logic
  [ ] multiple Handler calls chained ad hoc (that orchestration belongs in a Handler or Saga, not a Controller)
```

## 2.4 Command vs. Query Separation

- **Commands** mutate state and MUST be handled inside a database transaction boundary (`Atomic::run(...)`, ADD §30) at the Handler level, not the Controller level.
- **Commands** SHOULD return only what the caller needs to redirect or acknowledge (e.g. an ID, a next-step URL) — never the full mutated aggregate serialized for display. If display data is needed, follow up with a Query.
- **Queries** MUST NOT mutate state, MUST NOT dispatch domain events, and MUST NOT go through the Aggregate/Repository `save()` path. They read via the Infrastructure query layer (Eloquent query builder is explicitly permitted here — ADD §39).
- A single Controller action MUST map to a single Command or a single Query, never both. A "store and then show updated data" flow issues the Command, redirects, and lets the next GET request issue the Query — it does not stitch both into the same handler call.

```php
// DO — Query object, Eloquent permitted here (read-side Infrastructure concern)
final class PaymentDisputeQueueQuery
{
    public function fetch(): Collection
    {
        return PaymentTransactionRecord::query()
            ->where('status', PaymentStatus::Disputed->value)
            ->latest('dispute_opened_at')
            ->get();
    }
}
```

## 2.5 The Exact Directory Mapping for the Flow

To remove any ambiguity about where each link in the chain physically lives:

```text
Route            → routes/admin.php | routes/customer.php | routes/webhooks.php | routes/api.php
Controller       → app/Http/Controllers/{Admin,Customer,Webhooks,Api}/
Command/Query DTO→ domain/<Module>/Application/{Commands,Queries}/
Handler          → domain/<Module>/Application/Handlers/
Domain Aggregate → domain/<Module>/Domain/Entities/  (+ ValueObjects, Policies, Services, Events)
Repository iface → domain/<Module>/Domain/Repositories/  (or Application/Contracts/ for provider-style contracts)
Repository impl  → domain/<Module>/Infrastructure/Persistence/Repositories/
Eloquent model   → domain/<Module>/Infrastructure/Persistence/Eloquent/
```

**Note on root namespace:** The ADD fixes the top-level source root as `domain/<Module>/...` — **not** `app/Domains/<Module>/...` and **not** `domain/Modules/<Module>/...`. There is one canonical root (`domain/`) directly containing one folder per bounded context (`Repair/`, `Sales/`, `Payments/`, etc.), and each of those contains exactly three subfolders: `Domain/`, `Application/`, `Infrastructure/`. Do not introduce a second `Modules/` wrapper layer — the ADD's Appendix and §5 file tree are authoritative, and mixing conventions mid-project is explicitly forbidden by the ADD ("Do not mix multiple conventions after implementation begins").

---

# 3. Strict Naming Conventions

## 3.1 PHP Classes & Interfaces

| Rule | Convention | Example |
|---|---|---|
| Class names | `PascalCase`, noun-first, no Hungarian prefixes | `RepairJob`, `PaymentTransaction`, `CheckoutPricingService` |
| Interfaces (Contracts) | `PascalCase`, **capability-based noun**, no `Interface` suffix | `PaymentGateway` — **not** `PaymentGatewayInterface` |
| Interface implementations | Prefixed by their technology/provider, not suffixed by "Impl" | `PaystackGateway`, `EloquentPaymentTransactionRepository` — **not** `PaymentGatewayImpl` |
| Abstract classes | Prefixed `Abstract` only when genuinely abstract and inherited across modules | `AbstractNotificationChannel` |
| Traits | Suffixed `Trait` | `HasIdempotencyKeyTrait` |
| Final by default | Every domain class, command, handler, and value object MUST be declared `final` unless explicitly designed for extension | `final class RepairJob { ... }` |

```php
// DO — capability-based interface, no "Interface" suffix
interface PaymentGateway
{
    public function name(): string;
    public function initialize(PaymentInitialization $payment): GatewayInitializationResult;
    public function verify(string $providerReference): GatewayVerificationResult;
    public function refund(GatewayRefundRequest $refund): GatewayRefundResult;
}

interface RepairJobRepository
{
    public function get(RepairJobId $id): RepairJob;
    public function save(RepairJob $repairJob): void;
}
```

```php
// DON'T
interface PaymentGatewayInterface { ... }      // ✗ redundant "Interface" suffix
interface IPaymentGateway { ... }              // ✗ Hungarian "I" prefix — not PHP/Laravel convention here
class PaymentGatewayImpl implements PaymentGateway { ... }  // ✗ "Impl" is meaningless; name it by what it IS
```

## 3.2 CQRS Elements

| Element | Pattern | Example |
|---|---|---|
| Command (mutation intent) | `[Verb][Entity]Command` | `InitiatePaymentCommand`, `CreateRepairJobCommand`, `ResolvePaymentDisputeCommand` |
| Command Handler | `[Verb][Entity]Handler` (matches its Command 1:1) | `InitiatePaymentHandler`, `CreateRepairJobHandler` |
| Query (read intent) | `[Noun]Query` — describes what is being asked for, not an action | `AvailableStockQuery`, `PaymentDisputeQueueQuery`, `RepairDashboardQuery` |
| Query Handler/Object | Same class as the Query is acceptable when trivial (`fetch()` method), or `[Noun]QueryHandler` for consistency with Commands when a separate handler is warranted | `PaymentDisputeQueueQuery::fetch()` |
| DTO (non-command data carrier) | `[Noun]Dto` or a self-explanatory value-holder name, `readonly` | `GatewayInitializationResult`, `PaymentInitialization` |

**Rule:** A Command and its Handler MUST share the same root name. `InitiatePaymentCommand` MUST be handled by `InitiatePaymentHandler`, never by a differently-named handler, and a Handler MUST NOT accept more than one Command type (no generic `PaymentHandler` that switches on command subtype).

```php
// DO
final readonly class InitiatePaymentCommand { /* ... */ }

final class InitiatePaymentHandler
{
    public function __construct(
        private readonly PaymentTransactionRepository $repository,
        private readonly PaymentGatewayResolver $gateways,
    ) {}

    public function handle(InitiatePaymentCommand $command): InitiatePaymentResult
    {
        // orchestration only — rules live in the PaymentTransaction aggregate
    }
}
```

```php
// DON'T
final class PaymentHandler
{
    public function handle(mixed $command): mixed        // ✗ generic handler, untyped, multi-purpose
    {
        if ($command instanceof InitiatePaymentCommand) { /* ... */ }
        if ($command instanceof ConfirmPaymentCommand) { /* ... */ }
    }
}
```

## 3.3 Events — Past-Tense Business Facts Only

Domain events describe **something that has already happened in the business**, never a UI action, an HTTP verb, or an imperative instruction.

| Rule | DO | DON'T |
|---|---|---|
| Tense | Past tense, unambiguous | Present/imperative tense |
| Subject | Business fact | Technical/HTTP fact |
| Examples | `PaymentConfirmed`, `RepairCompleted`, `CheckoutExpired`, `InventoryReservationReleased`, `CommissionEarned` | `UpdateRepairStatus`, `PostPaymentControllerFinished`, `ClickConfirmButton`, `ApiRequestSucceeded` |
| Location | `domain/<Module>/Domain/Events/` | — |
| Suffix | None required beyond the past-tense verb itself | Do not suffix with `Event` (`RepairCompletedEvent` is redundant — the folder and base contract already say it's an event) |

```php
// DO
final readonly class RepairCompleted
{
    public function __construct(
        public RepairJobId $repairJobId,
        public CarbonImmutable $completedAt,
    ) {}
}
```

```php
// DON'T
final readonly class UpdateRepairStatus { /* ... */ }      // ✗ this is a Command, not an Event, and misnamed either way
final readonly class RepairCompletedEvent { /* ... */ }    // ✗ redundant "Event" suffix
```

## 3.4 Database Tables & Eloquent Models

### Tables

Tables use **plural, module-prefixed, snake_case** names exactly as finalized in the DBDD. The module prefix is not optional and must not be abbreviated or aliased away, because the prefix is what preserves bounded-context clarity at the SQL level.

```text
repair_jobs, repair_diagnoses, repair_parts_reservations
sales_checkouts, sales_checkout_items, sales_sales
inventory_products, inventory_skus, inventory_items, inventory_stock_levels
payments_transactions
commission_ledger_entries
marketing_campaigns, marketing_rewards, marketing_store_credit_ledger_entries
notification_events, notification_delivery_attempts
audit_logs
sync_outbox_entries, sync_conflicts
```

**Rule:** Do not introduce convenience aliases like `payments`, `sales`, or `inventory` — the DBDD explicitly forbids this because it destroys module clarity at the schema level (ADD Appendix A).

### Eloquent Models — the `Record` Suffix Law

Every Eloquent model in AfProsPos is, by definition, an **Infrastructure persistence detail**, not a domain object. This is enforced by name and by location simultaneously:

| Rule | Requirement |
|---|---|
| Suffix | Every Eloquent model class MUST be suffixed `Record` |
| Location | `domain/<Module>/Infrastructure/Persistence/Eloquent/` — never in `app/Models/` |
| Relationship to the Domain Entity | The `*Record` and the plain-PHP Domain Entity are **two separate classes**; a Mapper converts between them |
| Cross-context Eloquent relationships | Forbidden (§4.5) |

```php
// domain/Payments/Infrastructure/Persistence/Eloquent/PaymentTransactionRecord.php
final class PaymentTransactionRecord extends Model
{
    protected $table = 'payments_transactions';

    protected $casts = [
        'amount_minor' => 'integer',
        'dispute_opened_at' => 'datetime',
    ];
}
```

```php
// domain/Payments/Domain/Entities/PaymentTransaction.php
// Pure domain entity — business behavior only, no Eloquent, no framework
final class PaymentTransaction
{
    // state transitions, invariants — nothing persistence-related
}
```

```text
DO:
    domain/Payments/Infrastructure/Persistence/Eloquent/PaymentTransactionRecord.php
    domain/Repair/Infrastructure/Persistence/Eloquent/RepairJobRecord.php

DON'T:
    app/Models/PaymentTransaction.php          // ✗ wrong location, wrong name (collides with the domain entity)
    domain/Payments/Domain/Entities/PaymentTransactionRecord.php   // ✗ Record in the Domain layer — forbidden
```

## 3.5 Variables & Methods

| Rule | Convention | Example |
|---|---|---|
| Variables/properties | strict `camelCase` | `$amountMinor`, `$providerReference`, `$activeShopId` |
| Methods | strict `camelCase`, verb-first | `reserveParts()`, `confirmPayment()`, `calculateCommission()` |
| Boolean methods/properties | MUST be prefixed `is`, `has`, or `can` | `isPaid()`, `hasOutstandingBalance()`, `canReserve()` |
| Money variables | MUST make the unit explicit — never a bare `$amount` | `$amountMinor` (BIGINT minor units per DBDD §2.3), never `$amount` alone |
| Constants | `SCREAMING_SNAKE_CASE` | `MAX_RESERVATION_HOLD_MINUTES` |
| Route names | dot-notation, audience-scoped, resource-first | `admin.payments.disputes.resolve`, `customer.payments.store` |

```php
// DO
public function isPaid(): bool { /* ... */ }
public function hasOutstandingBalance(): bool { /* ... */ }
public function canReserve(int $requestedQuantity): bool { /* ... */ }
private readonly int $amountMinor;

// DON'T
public function paid(): bool { /* ... */ }              // ✗ missing is/has/can prefix — ambiguous as a boolean
public function getOutstandingBalanceFlag(): bool { /* ... */ }  // ✗ not idiomatic, not camelCase-boolean
private readonly int $amount;                             // ✗ unit is ambiguous — naira? kobo? cents?
```

---

# 4. Domain vs. Infrastructure Boundaries

## 4.1 The Purity Law

**`domain/<Module>/Domain/` MUST NOT contain, import, or reference:**

- Eloquent (`Illuminate\Database\Eloquent\*`, any `*Record` class)
- HTTP (`Illuminate\Http\*`, `Request`, `Response`)
- Any vendor SDK (Paystack, Tranzix, Resend, Twilio, WhatsApp Business API clients)
- Queue facades (`Illuminate\Support\Facades\Queue`)
- Any other module's Eloquent model, Domain Entity, or Infrastructure class

This is not a style preference — it is what makes the domain layer unit-testable without a database, swappable across payment providers without touching Sales or Repair, and safe from an AI accidentally wiring a controller-layer concern three layers too deep.

## 4.2 Allowed Dependency Direction

```text
Presentation
    ↓
Application
    ↓
Domain
    ↓
Infrastructure adapters
```

A Domain package may depend only on:
1. itself;
2. Shared Kernel abstractions/value objects (`domain/Shared/Domain/`);
3. explicit Contracts owned by another module, when cross-module integration is genuinely required.

A module MUST NOT import another module's Eloquent model to perform business logic — not even for a "quick read."

```php
// FORBIDDEN — direct cross-module coupling
use App\Models\InventoryStockLevel;                          // ✗ forbidden in Sales domain
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventorySkuRecord; // ✗ forbidden in Repair domain
use Domain\Payments\Infrastructure\Gateways\Paystack\PaystackClient;         // ✗ forbidden in Sales domain
```

```php
// ALLOWED — capability contract, not persistence implementation
use Domain\Inventory\Application\Contracts\InventoryReservationService;
use Domain\Payments\Application\Contracts\PaymentGateway;
use Domain\Payments\Application\Contracts\PaymentQuery;
```

The calling module sees a stable **business capability**, never another module's persistence detail.

## 4.3 Cross-Module Data: IDs, Never Foreign Keys

Per the DBDD, cross-module references are **application-layer IDs**, not database foreign keys, with the sole exception of the approved Shared Kernel (`shops.id`, `customers.id`, `staff.id`).

```text
Sales → Inventory
    sends:  sku_id / inventory_item_id
    calls:  InventoryReservationService (Contract)
    never:  DB FK to an inventory_* table

Sales → Payments
    sends:  payable_type + payable_id
    calls:  a Payments Application Contract
    never:  direct access to PaymentTransactionRecord

Commission → Sales/Repair
    stores: source_type + source_id
    never:  a foreign key to sales_* or repair_* tables
```

```php
// FORBIDDEN — cross-context Eloquent relationship
final class PaymentTransactionRecord extends Model
{
    public function sale()
    {
        return $this->belongsTo(SaleRecord::class);   // ✗ SaleRecord belongs to Sales, this class belongs to Payments
    }
}
```

## 4.4 Infrastructure Adapters Implement Domain-Defined Contracts

The Contract (interface) is always **owned and defined by the Domain/Application layer that needs the capability** — never by the Infrastructure adapter that happens to implement it. This is the Dependency Inversion half of Hexagonal Architecture: Infrastructure depends on Domain, never the reverse.

```php
// domain/Payments/Application/Contracts/PaymentGateway.php  (owned by Payments Application layer)
interface PaymentGateway
{
    public function name(): string;
    public function initialize(PaymentInitialization $payment): GatewayInitializationResult;
    public function verify(string $providerReference): GatewayVerificationResult;
    public function refund(GatewayRefundRequest $refund): GatewayRefundResult;
}
```

```php
// domain/Payments/Infrastructure/Gateways/Paystack/PaystackGateway.php  (implements it, knows about the vendor)
final class PaystackGateway implements PaymentGateway
{
    public function __construct(private readonly PaystackHttpClient $client) {}

    public function initialize(PaymentInitialization $payment): GatewayInitializationResult
    {
        // Paystack-specific HTTP call lives HERE, never in Domain or Application
    }
}
```

Repositories follow the identical shape — interface in `Domain/Repositories/` (or `Application/Contracts/`), Eloquent-backed implementation in `Infrastructure/Persistence/Repositories/`, named `Eloquent[Entity]Repository`:

```php
// domain/Payments/Domain/Repositories/PaymentTransactionRepository.php
interface PaymentTransactionRepository
{
    public function get(PaymentTransactionId $id): PaymentTransaction;
    public function save(PaymentTransaction $payment): void;
    public function findByProviderReference(string $provider, string $reference): ?PaymentTransaction;
}

// domain/Payments/Infrastructure/Persistence/Repositories/EloquentPaymentTransactionRepository.php
final class EloquentPaymentTransactionRepository implements PaymentTransactionRepository
{
    public function get(PaymentTransactionId $id): PaymentTransaction
    {
        $row = PaymentTransactionRecord::query()->findOrFail($id->value());
        return PaymentTransactionMapper::toDomain($row);
    }

    public function save(PaymentTransaction $payment): void
    {
        PaymentTransactionMapper::toRecord($payment)->save();
    }
}
```

**The Domain does not know Eloquent exists.** This sentence, verbatim, is the test to apply to every Domain class during review.

## 4.5 Automated Enforcement — Architectural Tests Are Mandatory, Not Optional

Boundary rules that rely purely on code review discipline will erode under AI-assisted velocity. AfProsPos therefore requires a permanent `tests/Architecture/` suite (e.g. via `pestphp/pest-plugin-arch` or an equivalent static dependency-graph checker) asserting, at minimum:

```text
Domain namespaces cannot depend on Illuminate\Http\*
Domain namespaces cannot depend on any *Record Eloquent class
Domain namespaces cannot depend on any vendor SDK namespace
Payments/Domain cannot depend on Sales/Infrastructure/Persistence/Eloquent/*
Sales/Domain cannot depend on Domain/Payments/Infrastructure/Gateways/Paystack/*
Every Command class has exactly one matching Handler class
Every interface in Application/Contracts is implemented by at least one Infrastructure class
Audit-table Eloquent models expose no update()/delete() path
```

A pull request that fails this suite MUST NOT be merged regardless of feature completeness. This suite is the automated backstop for §1 and §4 — it is what prevents an AI's "quick fix" from quietly reintroducing a forbidden dependency six months into the project.

---

# 5. Frontend (React + Inertia.js) Constraints

## 5.1 Laravel Owns All Routing

`routes/web.php`, `routes/admin.php`, and `routes/customer.php` are the **only** router in this system. Inertia pages are rendered from server-resolved routes; React does not decide navigation.

**MUST NOT:**
```bash
npm install react-router-dom      # ✗ forbidden — do not add this dependency, ever
```

```jsx
// DON'T — client-side routing
import { BrowserRouter, Route, Routes } from 'react-router-dom';

<BrowserRouter>
  <Routes>
    <Route path="/repairs/:id" element={<RepairShow />} />   {/* ✗ Laravel already owns this URL */}
  </Routes>
</BrowserRouter>
```

```jsx
// DO — Inertia Link resolves a Laravel-named route, server decides everything
import { Link } from '@inertiajs/react';

<Link href={route('admin.repairs.show', repair.id)}>
  View repair
</Link>
```

Navigation-affecting logic (auth redirect, 403, 404, tenant/shop scoping) is resolved server-side in the Controller/middleware chain (ADD §26), never faked client-side with a React route guard.

## 5.2 The Pages / Components / Features Contract

| Folder | Contains | Rule |
|---|---|---|
| `resources/js/Pages/` | Inertia entry points — one file per Controller action that renders a page | MUST map 1:1 to a route/controller method. MUST NOT contain reusable UI logic — it composes Components and Features. |
| `resources/js/Components/` | Dumb, reusable, presentation-only UI primitives, Tailwind + design tokens only | MUST NOT know about a specific domain/module. MUST NOT call `axios`/`fetch`/`router.post` directly beyond what a generic form component needs. No business logic. |
| `resources/js/Features/` | Domain-specific, highly interactive components tied to a bounded context | MAY hold local interactive state (cart contents, IMEI scan buffer, reservation countdown). MUST still treat server data as authoritative (ADD §25.3) — a Feature component never claims a payment "succeeded" on its own; it reflects what the server told it. |

```text
resources/js/Pages/Admin/Repairs/Show.tsx
resources/js/Pages/Admin/Sales/Checkout.tsx
resources/js/Pages/Customer/Payments/Show.tsx

resources/js/Components/Admin/AdminButton.tsx
resources/js/Components/Admin/AdminTable.tsx
resources/js/Components/Feedback/ConfirmDialog.tsx
resources/js/Components/Feedback/Toast.tsx

resources/js/Features/Sales/POSCart.tsx
resources/js/Features/Inventory/SerializedUnitPicker.tsx
resources/js/Features/Payments/PaymentMethodSwitcher.tsx
resources/js/Features/Sync/OfflineSyncStatus.tsx
```

```text
DO — component placement decision test:
    "Would this component make sense on a completely unrelated page,
     with a different set of props, and no knowledge of Repair/Sales/Payments?"
        → YES → Components/
        → NO, it's inherently a POS-cart / IMEI-picker / payment-method concept → Features/<Module>/

DON'T:
    Put POSCart.tsx in Components/          // ✗ it's domain-specific, belongs in Features/Sales/
    Put AdminButton.tsx in Features/Sales/  // ✗ it's a generic primitive, belongs in Components/Admin/
```

**Frontend authority rule (ADD §25.3):** React state may *mirror* server state for responsiveness (e.g. an optimistic "processing…" spinner), but it is never the source of truth. A payment is not `confirmed` because the React state says so — it is `confirmed` because the server returned that status, full stop. No Feature component may render a final success state (e.g. "Payment Successful") purely from a client-side callback without the server round-trip confirming it.

## 5.3 Design Tokens — Zero Raw Hex Rule

Per the UI/UX Design System Document, colors flow through a four-layer token architecture (Primitive → Semantic → Theme → Component). Production React/Tailwind code MUST consume **Theme-layer Tailwind utilities**, never a raw hex value, and never a bare CSS variable inline where a Tailwind utility already exists for it.

```js
// tailwind.config.js — the only place primitive/semantic values are named
export default {
  theme: {
    extend: {
      colors: {
        admin: {
          bg: "var(--a-bg)",
          surface: "var(--a-surface)",
          border: "var(--a-border)",
          text: "var(--a-text)",
          blue: "var(--a-blue)",
          "blue-soft": "var(--a-blue-soft)",
          red: "var(--a-red)",
          green: "var(--a-green)",
        },
        customer: {
          blue: "var(--c-blue)",
          "blue-soft": "var(--c-blue-soft)",
          text: "var(--c-text)",
          red: "var(--c-red)",
          green: "var(--c-green)",
        },
      },
    },
  },
};
```

```jsx
// DO
<div className="bg-admin-surface border border-admin-border text-admin-text">
  <button className="bg-admin-blue text-white">Confirm dispute</button>
</div>

<p className="text-customer-blue">Track your repair</p>
```

```jsx
// DON'T
<div style={{ backgroundColor: '#FFFFFF', border: '1px solid #E4E7EC' }}>   {/* ✗ raw hex, inline style */}
<button className="bg-[#1E56E8]">Confirm dispute</button>                   {/* ✗ raw hex Tailwind arbitrary value */}
```

This rule applies uniformly to React components, Tailwind utility classes, CSS Modules, inline styles, SVG `fill`/`stroke` attributes, and pseudo-element styling. The **only** place a raw hex value is permitted is inside the central token source itself (`resources/css/tokens.css` / `tailwind.config.js`).

**Naming note:** the Tailwind theme keys are `admin` and `customer` (not the shorthand `a`/`c` used only in the raw CSS variable names `--a-blue`/`--c-blue`). The resulting utility classes are therefore `bg-admin-surface`, `text-customer-blue`, `border-admin-border-danger`, etc. — an AI generating className strings MUST use the `admin`/`customer` Tailwind prefix, not `a-`/`c-`, which are CSS-variable-only tokens never referenced directly from JSX.

## 5.4 Icons

Per the UI/UX Design System's Strict Anti-Emoji Rule: no emoji characters are used as UI iconography anywhere in the product (admin or customer surface). All icons come from the single official icon system defined in the Design System Document. An AI generating a status badge, empty state, or notification MUST NOT substitute an emoji for a missing icon component.

---

# 6. Testing Standards

## 6.1 Test Categories, Naming, and Location

| Category | Tests | Location | Naming pattern | May use Eloquent/DB? |
|---|---|---|---|---|
| **Unit** | Domain logic in total isolation (aggregates, value objects, policies, calculators) | `tests/Unit/Domain/<Module>/` | `[Subject][Behavior]Test` | **No** — pure PHP objects only, no DB, no framework boot |
| **Feature** | Application use-cases through HTTP, persona-scoped | `tests/Feature/{Admin,Customer}/<Module>/` | `[PersonaCan/CannotAction]Test` | Yes — full Laravel boot, DB transactions |
| **Integration** | Cross-layer wiring (e.g. Command → Handler → real repository → real DB, without HTTP) | `tests/Integration/<Module>/` | `[Action][Entity]IntegrationTest` | Yes |
| **Contract** | External providers (Paystack, Tranzix, notification channels) against one shared contract test suite | `tests/Contract/<Provider>/` | `[Provider]GatewayContractTest` | No direct production DB; sandbox/mocked provider transport only |
| **Concurrency** | Real database race conditions | `tests/Feature/<Module>/Concurrency/` or a dedicated `tests/Concurrency/` | `[Scenario]ConcurrencyTest` | Yes — required |
| **Architecture** | Static dependency-boundary rules (§4.5) | `tests/Architecture/` | `[Boundary]ArchTest` | No |

## 6.2 Unit Tests — Domain Only

Unit tests exercise a Domain Entity, Value Object, Policy, or Domain Service with **zero framework boot, zero database, zero HTTP**. If a test needs `RefreshDatabase` or `Illuminate\Foundation\Testing`, it is not a Unit test — it belongs in Feature or Integration.

```text
RepairStatusTransitionTest        — domain/Repair/Domain/Entities/RepairJob.php state machine
PaymentStateTransitionTest        — domain/Payments/Domain/Entities/PaymentTransaction.php state machine
MoneyTest                         — domain/Shared/Domain/ValueObjects/Money.php arithmetic/rounding
CommissionCalculationTest         — domain/Commission/Domain/Services/CommissionCalculator.php
WarrantyEligibilityTest           — domain/Warranty/Domain/Policies/WarrantyEligibilityPolicy.php
DiscountStackingTest              — domain/Sales/Domain/Services/CheckoutPricingService.php
ReservationPolicyTest             — domain/Inventory/Domain/Policies/ReservationPolicy.php
```

## 6.3 Feature Tests — Persona-Scoped Use Cases

Feature test names read as a sentence describing **who** can or cannot do **what**, matching the RBAC/shop-scoping requirements in the BLD:

```text
CustomerCanInitiatePaymentTest
AdminCanResolvePaymentDisputeTest
CashierCanCreateCheckoutTest
TechnicianCannotReservePartsBeforeAuthorizationTest
StaffCannotAccessAnotherShopTest
```

**Rule:** Every "Cannot" test MUST assert both the HTTP-layer rejection (403/redirect) **and** that no state mutation occurred (e.g. the checkout was not created, the reservation was not made) — a Feature test that only checks the HTTP status without asserting the absence of a side effect is incomplete.

## 6.4 Concurrency Tests — Mandatory for Inventory & Payments

The BLD's atomic reservation and payment-state guarantees are only real if proven under actual database contention, not mocked locks. At minimum, AfProsPos MUST maintain concurrency tests for:

```text
TwoRequestsReservingLastSerializedUnitConcurrencyTest
TwoRequestsReservingLastNonSerializedQuantityConcurrencyTest
CheckoutExpiryRacingPaymentConfirmationConcurrencyTest
PaymentConfirmationRacingManualDisputeConcurrencyTest
```

These tests MUST spin up genuinely parallel requests/transactions (not sequential calls that merely simulate the order) against a real MySQL/InnoDB connection, per ADD §30 and DBDD §5.

## 6.5 Contract Tests — One Shared Suite, Every Gateway Passes It

Every implementation of `PaymentGateway` (`PaystackGateway`, `TranzixGateway`, `ManualBankTransferGateway`) MUST pass the identical contract test suite, parameterized by provider:

```text
can_initialize
returns_provider_reference
can_verify
maps_success
maps_failure
maps_ambiguous_provider_result
can_refund
is_idempotent_under_duplicate_callback
```

A new payment provider is not considered integration-complete until its `[Provider]GatewayContractTest` passes every one of these cases — a partial implementation (e.g. skipping `is_idempotent_under_duplicate_callback`) is a merge blocker, because webhook idempotency is a first-class invariant (ADD §14.1), not an edge case.

## 6.6 Architectural Tests

See §4.5 — these live in `tests/Architecture/` and are treated as a required CI gate, identical in enforcement weight to a failing Feature test.

---

# 7. Enforcement, CI Gates & Governance

1. **CI MUST run, on every pull request:** static analysis (PHPStan/Larastan at a strict level), the Architectural test suite (§4.5), the full Unit + Feature + Contract test suites, and a frontend lint pass that fails on any raw hex color literal or `react-router-dom` import.
2. **Code review MUST reject** any PR that:
   - introduces a duplicate DTO/Contract/Value Object/Component without documented discovery (§1);
   - places business logic in a Controller, Console Command, or React Feature component (§2, §5);
   - imports another module's Eloquent model or vendor SDK from a Domain namespace (§4);
   - names a Command, Handler, Event, or Eloquent model outside the patterns in §3;
   - adds `react-router-dom` or any client-side router (§5.1);
   - leaves an orphaned file, unused route, or dead migration behind a refactor (§1.4).
3. **AI-generated pull requests are held to the identical bar** as human-authored ones — "an AI wrote it" is never a justification for a boundary violation, a naming deviation, or a duplicated artifact. If an AI assistant cannot satisfy a rule in this document, it must say so and ask, rather than degrade the rule to complete the task.
4. **This document changes only through the same process as the ADD/BLD/DBDD** — a dated revision, not an ad hoc in-conversation override.

---

# Appendix A — AI Pre-Flight Checklist (run before writing any code)

Any AI assistant asked to add or change AfProsPos code MUST silently work through this checklist before emitting a diff:

```text
[ ] 1. What business capability am I actually implementing? Name it in one sentence.
[ ] 2. Have I searched domain/ for an existing Contract/DTO/Value Object/Entity that
       already represents this concept, under any name — not just the name I was given?
[ ] 3. Which bounded context (module) does this belong to? Is that the module I'm
       about to write into?
[ ] 4. Does this change touch Domain/? If yes: does it import Eloquent, HTTP, or a
       vendor SDK anywhere in the diff? If yes → STOP, that belongs in Infrastructure.
[ ] 5. Am I adding a Controller method? If yes: does it do anything beyond
       validate → build DTO → call one Handler → map response? If yes → move that
       logic into a Handler or the Aggregate.
[ ] 6. Am I adding a Command? Does a Handler with the matching name exist or am I
       creating exactly one? Am I about to create a second differently-named
       Command that means the same thing as an existing one?
[ ] 7. Am I adding an Event? Is its name a past-tense business fact, with no
       "Event"/"Controller"/"Api" in the name?
[ ] 8. Am I adding an Eloquent model? Is it suffixed `Record` and placed under
       Infrastructure/Persistence/Eloquent/, never app/Models/?
[ ] 9. Am I touching a React file? Does it import react-router-dom or any
       client-side router? Does it use a raw hex value or arbitrary Tailwind
       color instead of an admin-*/customer-* token?
[ ] 10. Am I refactoring/renaming? Have I updated every caller and deleted the
        old file in the same change, and confirmed a grep for the old name
        returns nothing?
[ ] 11. Have I added or updated the matching test at the correct tier
        (Unit/Feature/Contract/Concurrency/Architecture) using the naming
        pattern in §6?
[ ] 12. Have I stated, in my output, what I searched for in step 2 and what
        I found — even if the answer was "nothing, so I created a new file"?
```

If any box cannot honestly be checked, the AI MUST surface the conflict to the human reviewer instead of proceeding.

---

# Appendix B — Master Naming Cheat Sheet

| Concept | Pattern | Example | Location |
|---|---|---|---|
| Domain Entity | `PascalCase` noun | `RepairJob` | `domain/<M>/Domain/Entities/` |
| Value Object | `PascalCase` noun | `Money`, `RepairJobId` | `domain/<M>/Domain/ValueObjects/` |
| Domain Service | `[Noun]Service` / `[Noun]Calculator` | `CheckoutPricingService`, `RepairCostCalculator` | `domain/<M>/Domain/Services/` |
| Domain Policy | `[Noun]Policy` | `ReservationPolicy`, `DeviceReleasePolicy` | `domain/<M>/Domain/Policies/` |
| Domain Event | Past-tense fact, no suffix | `RepairCompleted`, `PaymentConfirmed` | `domain/<M>/Domain/Events/` |
| Domain Exception | `[Failure]` describing the rule broken | `InsufficientAvailableStock`, `PaymentStateTransitionNotAllowed` | `domain/<M>/Domain/Exceptions/` |
| Application Command | `[Verb][Entity]Command` | `InitiatePaymentCommand` | `domain/<M>/Application/Commands/` |
| Application Handler | `[Verb][Entity]Handler` | `InitiatePaymentHandler` | `domain/<M>/Application/Handlers/` |
| Application Query | `[Noun]Query` | `AvailableStockQuery` | `domain/<M>/Application/Queries/` |
| Application Contract | Capability noun, no `Interface` suffix | `PaymentGateway`, `InventoryReservationService` | `domain/<M>/Application/Contracts/` |
| Repository interface | `[Entity]Repository` | `PaymentTransactionRepository` | `domain/<M>/Domain/Repositories/` |
| Repository implementation | `Eloquent[Entity]Repository` | `EloquentPaymentTransactionRepository` | `domain/<M>/Infrastructure/Persistence/Repositories/` |
| Eloquent model | `[Entity]Record` | `PaymentTransactionRecord` | `domain/<M>/Infrastructure/Persistence/Eloquent/` |
| Gateway/provider adapter | `[Provider][Capability]` | `PaystackGateway`, `ResendMailer` | `domain/<M>/Infrastructure/Gateways|Channels/` |
| Laravel Console Command | `[Verb][Object]` (operational) | `ReconcilePayments` | `app/Console/Commands/<M>/` |
| HTTP Controller | `[Entity]Controller` | `PaymentController` | `app/Http/Controllers/{Admin,Customer,Webhooks,Api}/` |
| Form Request | `[Verb][Entity]Request` | `InitiatePaymentRequest` | `app/Http/Requests/{Admin,Customer,Webhooks}/` |
| DB table | plural, module-prefixed snake_case | `payments_transactions` | migrations |
| Boolean method | `is`/`has`/`can` + verb | `isPaid()`, `canReserve()` | anywhere |
| Money variable | unit-explicit | `$amountMinor` | anywhere |
| Inertia Page | `[Entity]/[Action].tsx` | `Pages/Admin/Payments/Disputes/Index.tsx` | `resources/js/Pages/` |
| Dumb Component | `[Scope][Widget].tsx` | `AdminButton.tsx`, `Toast.tsx` | `resources/js/Components/` |
| Feature component | `[DomainConcept].tsx` | `POSCart.tsx`, `SerializedUnitPicker.tsx` | `resources/js/Features/<M>/` |
| Tailwind color token | `bg-`/`text-`/`border-` + `admin`/`customer` + role | `bg-admin-surface`, `text-customer-blue` | `tailwind.config.js` |
| Unit test | `[Subject][Behavior]Test` | `MoneyTest` | `tests/Unit/Domain/<M>/` |
| Feature test | `[Persona][Can/Cannot][Action]Test` | `AdminCanResolvePaymentDisputeTest` | `tests/Feature/{Admin,Customer}/<M>/` |
| Contract test | `[Provider]GatewayContractTest` | `PaystackGatewayContractTest` | `tests/Contract/<Provider>/` |
| Concurrency test | `[Scenario]ConcurrencyTest` | `TwoRequestsReservingLastSerializedUnitConcurrencyTest` | `tests/Feature/<M>/Concurrency/` |

---

# Appendix C — Canonical Module Skeleton (copy this, not a blank folder)

When bootstrapping a brand-new bounded context, the starting skeleton is always the following — never an ad hoc structure invented per module:

```text
domain/<NewModule>/
├── Domain/
│   ├── Entities/
│   ├── ValueObjects/
│   ├── Services/
│   ├── Policies/
│   ├── Events/
│   ├── Exceptions/
│   └── Repositories/
├── Application/
│   ├── Commands/
│   ├── Queries/
│   ├── DTOs/
│   ├── Handlers/
│   └── Contracts/
└── Infrastructure/
    ├── Persistence/
    │   ├── Eloquent/
    │   └── Repositories/
    └── Laravel/
        └── <NewModule>ServiceProvider.php
```

An AI asked to "start the X module" MUST generate this skeleton first — empty folders with `.gitkeep` where nothing exists yet is acceptable; a flattened or partial structure is not.

---

*End of document. This CPNC is a living contract between the engineering team and every contributor — human or AI — writing code for AfProsPos. Deviations require a written exception in the pull request, not silent drift.*
