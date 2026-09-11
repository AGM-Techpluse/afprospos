# AfProsPos — Architecture Design Document (ADD)

**System:** AfProsPos — Phone Sales, Repair & Business Management System  
**Architecture style:** Modular Monolith, Domain-Driven Design (DDD), Hexagonal/Clean Architecture, Event-Driven Integration  
**Backend:** PHP / Laravel  
**Frontend:** Inertia.js + React + Tailwind CSS  
**Database:** MySQL 8 / InnoDB  
**Document status:** Development Blueprint  
**Prepared:** September 2, 2026

---

## 0. Executive Architecture Decision

AfProsPos should be implemented as a **DDD modular monolith**, not as a collection of Laravel feature folders and not as microservices.

The application remains one deployable Laravel system and one primary MySQL database, but its code is partitioned into strict bounded contexts. Each module owns its domain objects, application commands/queries, repositories, database mappings, policies, and events. Cross-module dependencies are expressed through **application contracts and IDs**, never through direct domain-model coupling.

The three source documents establish the architectural constraints that drive this design:

- The BRD defines the business scope: customer dashboard, RBAC admin dashboard, Repairs, Sales/POS, Payments, Inventory, Multi-Shop, Warranty/Returns, Commission, Referral, Marketing, Notifications, Expenses and reporting.
- The BLD establishes the operational rules: atomic inventory reservation, serialized vs. non-serialized allocation, separate transaction state dimensions, pending bank-transfer confirmation, human-controlled payment disputes, append-only ledgers/audit history, asynchronous notifications, controlled offline operation and consolidated reporting.
- The finalized data model explicitly prohibits cross-module database foreign keys except approved Shared Kernel references (`shops`, `customers`, `staff`) and requires application-layer integrity for cross-module IDs.

**Source references:** BRD v1.0, BLD v1.1, Data Model v1.0. fileciteturn1file8 fileciteturn1file0 fileciteturn0file2

---

# 1. Architecture Goals and Non-Goals

## 1.1 Goals

1. Keep business rules inside domain/application code, not HTTP controllers or React components.
2. Enforce module boundaries structurally so Repair, Sales, Payments, Inventory, Warranty, Marketing, Notifications, Reporting, etc. cannot become a single tightly coupled Laravel codebase.
3. Make payment providers replaceable without modifying Sales, Repair or customer-facing payment use cases.
4. Make inventory reservation safe under concurrent POS and repair traffic.
5. Treat payments as a separate bounded context and distinguish `pending`, `payment_pending_confirmation`, `confirmed`, `disputed`, `refunded`, and `exception` states.
6. Preserve immutable financial, audit, commission and reporting history.
7. Make asynchronous work resilient through jobs, outbox/event records, retries and idempotency.
8. Support controlled offline workflows without pretending that centrally shared inventory and externally verified payments are safe to confirm offline.
9. Give the frontend a clean Inertia contract while allowing highly interactive React components where needed.
10. Keep the design deployable as a single Laravel application while leaving a clean path to service extraction later.

## 1.2 Non-Goals

- No microservice network is required in phase one.
- No event broker is required merely to connect internal modules; Laravel events/jobs plus a transactional outbox are sufficient.
- No direct vendor SDK calls from domain services.
- No business decisions inside Eloquent model mutators, React state, or database triggers.
- No hard deletion of financially/operationally significant records.
- No assumption that an exact retry count, return window, notification window or commission policy is fixed in code; these are configuration/business-policy values.

---

# 2. System Architecture Overview

## 2.1 Logical Architecture

```text
                                   ┌───────────────────────────────┐
                                   │        Browser / PWA          │
                                   │                               │
                                   │  Inertia pages               │
                                   │  React interactive components │
                                   │  Tailwind UI                 │
                                   │  Offline local store         │
                                   └───────────────┬───────────────┘
                                                   │ HTTPS
                                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           Laravel Application                               │
│                                                                             │
│  Presentation / HTTP                                                       │
│  ├── Web routes / Inertia                                                   │
│  ├── Customer controllers                                                   │
│  ├── Admin controllers                                                      │
│  └── Webhook / sync endpoints                                               │
│                                                                             │
│  Application Layer                                                          │
│  ├── Commands / Queries                                                     │
│  ├── Use cases                                                              │
│  ├── authorization                                                          │
│  └── transaction orchestration                                              │
│                                                                             │
│  Domain Layer                                                               │
│  ├── Repair        ├── Sales          ├── Payments                         │
│  ├── Inventory     ├── Warranty       ├── Commission                       │
│  ├── Referral      ├── Marketing      ├── Notifications                    │
│  ├── Reporting     ├── Expenses       ├── RBAC / Identity                   │
│  ├── Collections   └── Offline/Sync                                        │
│                                                                             │
│  Infrastructure / Adapters                                                  │
│  ├── Eloquent repositories                                                  │
│  ├── Payment providers (Paystack / Tranzix / Manual Transfer)              │
│  ├── Notification providers                                                 │
│  ├── Queue / scheduler                                                      │
│  ├── object storage                                                         │
│  └── cache                                                                  │
└──────────────┬────────────────────────────────────────────┬─────────────────┘
               │                                            │
               ▼                                            ▼
     ┌─────────────────────┐                       ┌────────────────────────┐
     │     MySQL 8 /       │                       │ External Providers      │
     │       InnoDB        │                       │                        │
     │                     │                       │ Paystack                │
     │ operational tables  │                       │ Tranzix                 │
     │ append-only ledgers │                       │ Bank-transfer workflow  │
     │ audit / events      │                       │ WhatsApp / Email / SMS  │
     │ sync state          │                       └────────────────────────┘
     └─────────────────────┘

                         ┌────────────────────────────┐
                         │ Queue Workers / Scheduler  │
                         │                            │
                         │ Notifications              │
                         │ Expiry sweeps              │
                         │ Ledger projections         │
                         │ Sync processing            │
                         │ Reconciliation             │
                         └────────────────────────────┘
```

## 2.2 Architectural Style

The preferred style is:

**DDD Modular Monolith + Hexagonal Architecture + Event-Driven internal integration.**

The key distinction is that **deployment boundaries and domain boundaries are intentionally different**. There is one Laravel runtime, but the source tree behaves as though modules were independently deployable applications.

## 2.3 Layer Responsibilities

| Layer | Responsibility | Must not contain |
|---|---|---|
| Presentation | HTTP requests, route binding, authorization checks, Inertia page composition | Business rules, payment provider API calls |
| Application | Use cases, transaction orchestration, command/query handlers, DTOs | UI concerns, provider-specific logic |
| Domain | Entities, value objects, aggregates, policies, domain services, domain events | Laravel HTTP, Eloquent, SDKs, database queries |
| Infrastructure | Eloquent, HTTP clients, queues, provider adapters, storage | Business decisions |
| Shared Kernel | Identity of common business concepts used by all modules | Arbitrary cross-module convenience utilities |

---

# 3. Bounded Contexts / Modules

The codebase should use the following top-level bounded contexts.

| Module | Main responsibility | Authoritative data |
|---|---|---|
| `Identity` | Authentication, staff/customer identity integration | staff/customer identity state |
| `RBAC` | Roles, permissions, shop scoping, deactivation/history | role/permission state |
| `Shop` | Business shops and shop context | shops |
| `Repair` | Repair lifecycle, diagnosis, parts requirements, repair work | repair_jobs, repair_diagnoses |
| `Collection` | Return/collection/abandonment lifecycle | collection_cases/events |
| `Sales` | Checkout, pricing snapshot, completed sale | sales_checkouts, sales_sales |
| `Inventory` | Product/SKU catalog, stock, serialized units, reservations, transfers | inventory_* |
| `Payments` | Payment transactions, provider references, confirmation, dispute, refund/exception state | payments_transactions |
| `Warranty` | Warranty policies, claims, returns, trade-ins | warranty_*, return_requests, trade_in_assessments |
| `Commission` | Commission rules and append-only ledger | commission_ledger_entries |
| `Referral` | Referral attribution and qualification | referrals |
| `Marketing` | Campaigns, rewards, store credit, vouchers | marketing_* |
| `Notifications` | Notification events, delivery attempts and provider abstraction | notification_* |
| `Finance` | Expenses and financial projection/reporting | expenses, reporting_financial_ledger_entries |
| `Audit` | Unified immutable audit trail | audit_logs |
| `Sync` | Offline sync outbox and conflict handling | sync_outbox_entries, sync_conflicts |

The BLD explicitly describes these as cross-cutting business modules, with Notifications operating through events and a separate engine, and Reporting consuming module-owned records. fileciteturn3file0 fileciteturn3file4

---

# 4. Strict Module Boundary Rules

## 4.1 Allowed Dependency Direction

```text
Presentation
    ↓
Application
    ↓
Domain
    ↓
Infrastructure adapters
```

A domain package may depend only on:

- itself;
- Shared Kernel abstractions/value objects;
- explicit contracts owned by another module when the integration requires one.

A module may **not** import another module's Eloquent model merely to perform business logic.

### Forbidden examples

```php
use App\Models\InventoryStockLevel;            // forbidden in Sales domain
use App\Domains\Inventory\Models\InventorySku; // forbidden in Repair domain
use App\Domains\Payments\Infrastructure\PaystackClient; // forbidden in Sales domain
```

### Allowed examples

```php
use Modules\Inventory\Contracts\InventoryReservationService;
use Modules\Payments\Contracts\PaymentGateway;
use Modules\Payments\Contracts\PaymentQuery;
```

The calling module sees a stable business capability, not another module's persistence implementation.

## 4.2 Cross-Module Data Rule

The data model deliberately uses IDs rather than foreign keys across module boundaries. `shops.id`, `customers.id`, and `staff.id` are the approved Shared Kernel references. All other cross-module references are application-layer references and must be validated by contracts/services and tests. fileciteturn0file2

Therefore:

```text
Sales → Inventory
    sends: sku_id / inventory_item_id
    calls: InventoryReservationService
    never: DB FK to inventory table

Sales → Payments
    sends: payable_type + payable_id
    calls: payment application contract
    never: direct Payments model access

Commission → Sales/Repair
    stores: source_type + source_id
    never: foreign key to sales/repair tables
```

## 4.3 Boundary Enforcement in Code Review

Every pull request should fail review when it introduces:

- an import of another module's ORM model;
- an Eloquent relationship crossing a bounded context;
- an SDK/client class referenced from a domain namespace;
- direct writes to another module's tables;
- provider-specific status values inside a business aggregate.

A lightweight architectural test suite should verify namespace dependencies automatically.

---

# 5. Exact Laravel File & Directory Structure

The following structure is the recommended source-of-truth layout.

```text
app/
├── Console/
│   ├── Commands/
│   │   ├── Payments/
│   │   │   ├── ReconcilePayments.php
│   │   │   └── ExpireStalePaymentExceptions.php
│   │   ├── Inventory/
│   │   │   ├── ReleaseExpiredReservations.php
│   │   │   └── DetectLowStock.php
│   │   ├── Repairs/
│   │   │   ├── ExpireRepairAuthorizations.php
│   │   │   └── ProcessCollectionDeadlines.php
│   │   ├── Notifications/
│   │   │   └── RetryFailedNotifications.php
│   │   └── Sync/
│   │       └── ProcessPendingSync.php
│   └── Kernel.php
│
├── Http/
│   ├── Controllers/
│   │   ├── Customer/
│   │   │   ├── DashboardController.php
│   │   │   ├── RepairController.php
│   │   │   ├── PaymentController.php
│   │   │   └── NotificationController.php
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── RepairController.php
│   │   │   ├── SalesController.php
│   │   │   ├── InventoryController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── StaffController.php
│   │   │   └── ReportsController.php
│   │   ├── Webhooks/
│   │   │   ├── PaystackWebhookController.php
│   │   │   └── TranzixWebhookController.php
│   │   └── Api/
│   │       └── SyncController.php
│   ├── Middleware/
│   │   ├── EnsureStaffIsActive.php
│   │   ├── ResolveShopContext.php
│   │   ├── EnsurePermission.php
│   │   ├── VerifyWebhookSignature.php
│   │   └── EnsureIdempotencyKey.php
│   ├── Requests/
│   │   ├── Admin/
│   │   ├── Customer/
│   │   └── Webhooks/
│   └── Responses/
│       └── InertiaErrorResponse.php
│
├── Providers/
│   ├── AppServiceProvider.php
│   ├── DomainServiceProvider.php
│   ├── PaymentServiceProvider.php
│   ├── EventServiceProvider.php
│   └── RepositoryServiceProvider.php
│
├── Support/
│   ├── Clock/
│   │   ├── Clock.php
│   │   └── SystemClock.php
│   ├── Idempotency/
│   ├── Transactions/
│   │   └── Atomic.php
│   ├── Authorization/
│   └── Serialization/
│
└── ViewModels/
    ├── Admin/
    └── Customer/

bootstrap/

routes/
├── web.php                       
├── admin.php                    
├── customer.php                  
├── api.php                       
├── webhooks.php                  
├── channels.php                  
└── console.php                   

config/
├── afprospos.php
├── payments.php
├── notifications.php
├── inventory.php
├── offline.php
└── tenancy.php

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── css/
│   └── app.css
├── js/
│   ├── app.tsx
│   ├── bootstrap.ts
│   ├── Pages/
│   │   ├── Admin/
│   │   └── Customer/
│   ├── Components/
│   │   ├── UI/
│   │   ├── Forms/
│   │   ├── Tables/
│   │   └── Payment/
│   ├── Features/
│   │   ├── Repairs/
│   │   ├── Sales/
│   │   ├── Inventory/
│   │   └── Payments/
│   ├── hooks/
│   ├── stores/
│   ├── types/
│   └── lib/
└── views/
    └── app.blade.php

domain/
├── Shared/
│   ├── Domain/
│   │   ├── ValueObjects/
│   │   ├── Events/
│   │   ├── Exceptions/
│   │   └── Contracts/
│   ├── Application/
│   │   ├── DTOs/
│   │   └── Services/
│   └── Infrastructure/
│       └── Persistence/
│
├── Identity/
│   ├── Domain/
│   │   ├── Entities/
│   │   ├── ValueObjects/
│   │   ├── Events/
│   │   └── Policies/
│   ├── Application/
│   │   ├── Commands/
│   │   ├── Queries/
│   │   ├── DTOs/
│   │   └── Handlers/
│   └── Infrastructure/
│       ├── Persistence/
│       │   ├── Eloquent/
│       │   └── Repositories/
│       └── Laravel/
│
├── RBAC/
│   ├── Domain/{Entities,ValueObjects,Policies,Events}
│   ├── Application/{Commands,Queries,DTOs,Handlers}
│   └── Infrastructure/{Persistence,Authorization,Laravel}
│
├── Shop/
│   ├── Domain/{Entities,ValueObjects,Policies,Events}
│   ├── Application/{Commands,Queries,DTOs,Handlers}
│   └── Infrastructure/{Persistence,Laravel}
│
├── Repair/
│   ├── Domain/
│   │   ├── Entities/
│   │   │   ├── RepairJob.php
│   │   │   ├── RepairDiagnosis.php
│   │   │   └── RepairPartReservation.php
│   │   ├── ValueObjects/
│   │   │   ├── RepairJobId.php
│   │   │   ├── RepairStatus.php
│   │   │   ├── FinancialStatus.php
│   │   │   ├── LabourCharge.php
│   │   │   └── DownPayment.php
│   │   ├── Services/
│   │   │   ├── RepairCostCalculator.php
│   │   │   └── RepairAuthorizationPolicy.php
│   │   ├── Policies/
│   │   │   └── DeviceReleasePolicy.php
│   │   ├── Events/
│   │   │   ├── RepairCreated.php
│   │   │   ├── RepairDiagnosed.php
│   │   │   ├── RepairCompleted.php
│   │   │   ├── RepairFailed.php
│   │   │   └── RepairReadyForCollection.php
│   │   └── Repositories/
│   │       └── RepairJobRepository.php
│   ├── Application/
│   │   ├── Commands/
│   │   │   ├── CreateRepairJob.php
│   │   │   ├── CompleteDiagnosis.php
│   │   │   ├── ReserveRepairParts.php
│   │   │   ├── StartRepair.php
│   │   │   ├── CompleteRepair.php
│   │   │   ├── FailRepair.php
│   │   │   └── ReleaseRepairDevice.php
│   │   ├── Queries/
│   │   ├── DTOs/
│   │   └── Handlers/
│   └── Infrastructure/
│       ├── Persistence/
│       │   ├── Eloquent/RepairJobRecord.php
│       │   └── Repositories/EloquentRepairJobRepository.php
│       └── Laravel/RepairServiceProvider.php
│
├── Collection/
│   ├── Domain/{Entities,ValueObjects,Events,Policies,Repositories}
│   ├── Application/{Commands,Queries,DTOs,Handlers}
│   └── Infrastructure/{Persistence,Laravel}
│
├── Sales/
│   ├── Domain/
│   │   ├── Entities/
│   │   │   ├── SalesCheckout.php
│   │   │   ├── SalesCheckoutItem.php
│   │   │   └── Sale.php
│   │   ├── ValueObjects/
│   │   │   ├── CheckoutId.php
│   │   │   ├── SaleId.php
│   │   │   ├── Money.php
│   │   │   └── InvoiceNumber.php
│   │   ├── Services/
│   │   │   ├── CheckoutPricingService.php
│   │   │   └── CheckoutReservationPolicy.php
│   │   ├── Events/
│   │   │   ├── CheckoutCreated.php
│   │   │   ├── CheckoutExpired.php
│   │   │   └── SaleCompleted.php
│   │   └── Repositories/
│   ├── Application/{Commands,Queries,DTOs,Handlers}
│   └── Infrastructure/{Persistence,Laravel}
│
├── Inventory/
│   ├── Domain/
│   │   ├── Entities/
│   │   │   ├── Product.php
│   │   │   ├── Sku.php
│   │   │   ├── InventoryItem.php
│   │   │   ├── InventoryStockLevel.php
│   │   │   ├── InventoryReservation.php
│   │   │   └── InventoryTransfer.php
│   │   ├── ValueObjects/
│   │   ├── Policies/
│   │   │   ├── ReservationPolicy.php
│   │   │   └── TransferPolicy.php
│   │   ├── Services/
│   │   │   ├── ReservationService.php
│   │   │   ├── StockAvailability.php
│   │   │   └── SkuGenerator.php
│   │   ├── Events/
│   │   └── Repositories/
│   ├── Application/{Commands,Queries,DTOs,Handlers}
│   └── Infrastructure/{Persistence,Locking,Laravel}
│
├── Payments/
│   ├── Domain/
│   │   ├── Entities/
│   │   │   └── PaymentTransaction.php
│   │   ├── ValueObjects/
│   │   │   ├── PaymentTransactionId.php
│   │   │   ├── PaymentReference.php
│   │   │   ├── PaymentAmount.php
│   │   │   └── PayableReference.php
│   │   ├── Enums/
│   │   │   ├── PaymentMethod.php
│   │   │   └── PaymentStatus.php
│   │   ├── Events/
│   │   │   ├── PaymentInitiated.php
│   │   │   ├── PaymentPendingConfirmation.php
│   │   │   ├── PaymentConfirmed.php
│   │   │   ├── PaymentDisputed.php
│   │   │   ├── PaymentRejected.php
│   │   │   ├── PaymentRefunded.php
│   │   │   └── PaymentExceptionRaised.php
│   │   ├── Policies/
│   │   ├── Services/
│   │   └── Repositories/
│   ├── Application/
│   │   ├── Commands/
│   │   │   ├── InitiatePayment.php
│   │   │   ├── ConfirmPayment.php
│   │   │   ├── MarkBankTransferPending.php
│   │   │   ├── OpenPaymentDispute.php
│   │   │   ├── ResolvePaymentDispute.php
│   │   │   ├── RejectPayment.php
│   │   │   ├── ProcessProviderWebhook.php
│   │   │   └── RefundPayment.php
│   │   ├── Queries/
│   │   ├── DTOs/
│   │   ├── Handlers/
│   │   └── Contracts/
│   │       ├── PaymentGateway.php
│   │       ├── PaymentGatewayFactory.php
│   │       └── PaymentConfirmationReader.php
│   └── Infrastructure/
│       ├── Gateways/
│       │   ├── Paystack/
│       │   │   └── PaystackGateway.php
│       │   ├── Tranzix/
│       │   │   └── TranzixGateway.php
│       │   └── ManualBankTransfer/
│       │       └── ManualBankTransferGateway.php
│       ├── Persistence/
│       │   ├── Eloquent/PaymentTransactionRecord.php
│       │   └── Repositories/EloquentPaymentTransactionRepository.php
│       ├── Webhooks/
│       ├── Reconciliation/
│       └── Laravel/PaymentServiceProvider.php
│
├── Warranty/
│   ├── Domain/{Entities,ValueObjects,Policies,Events,Repositories}
│   ├── Application/{Commands,Queries,DTOs,Handlers}
│   └── Infrastructure/{Persistence,Laravel}
│
├── Commission/
│   ├── Domain/{Entities,ValueObjects,Policies,Events,Repositories}
│   ├── Application/{Commands,Queries,DTOs,Handlers}
│   └── Infrastructure/{Persistence,Laravel}
│
├── Referral/
│   ├── Domain/{Entities,ValueObjects,Policies,Events,Repositories}
│   ├── Application/{Commands,Queries,DTOs,Handlers}
│   └── Infrastructure/{Persistence,Laravel}
│
├── Marketing/
│   ├── Domain/{Entities,ValueObjects,Policies,Events,Repositories}
│   ├── Application/{Commands,Queries,DTOs,Handlers}
│   └── Infrastructure/{Persistence,Laravel}
│
├── Notifications/
│   ├── Domain/{Entities,ValueObjects,Events,Policies,Repositories}
│   ├── Application/
│   │   ├── Commands/{QueueNotification,RetryNotification,RecordDeliveryCallback}
│   │   ├── Handlers/
│   │   └── Services/NotificationEngine.php
│   └── Infrastructure/
│       ├── Channels/
│       │   ├── Email/
│       │   │   ├── EmailChannel.php
│       │   │   ├── Resend/
│       │   │   │   └── ResendMailer.php
│       │   │   └── Smtp/
│       │   │       └── SmtpMailer.php
│       │   ├── WhatsApp/
│       │   ├── Sms/
│       │   └── InApp/
│       ├── Persistence/
│       └── Laravel/
│
├── Finance/
│   ├── Domain/{Entities,ValueObjects,Policies,Events,Repositories}
│   ├── Application/{Commands,Queries,DTOs,Handlers}
│   └── Infrastructure/{Persistence,Projection,Laravel}
│
├── Audit/
│   ├── Domain/{Entities,ValueObjects,Events,Repositories}
│   ├── Application/{Commands,Handlers,Services}
│   └── Infrastructure/{Persistence,Laravel}
│
└── Sync/
    ├── Domain/{Entities,ValueObjects,Events,Policies,Repositories}
    ├── Application/{Commands,Queries,DTOs,Handlers}
    └── Infrastructure/{Persistence,Laravel}

tests/
├── Architecture/
├── Unit/
│   └── Domain/
├── Feature/
│   ├── Admin/
│   ├── Customer/
│   ├── Payments/
│   ├── Inventory/
│   └── Sync/
├── Integration/
│   ├── Payments/
│   ├── Inventory/
│   └── Notifications/
└── Contract/
    ├── Paystack/
    ├── Tranzix/
    └── Notifications/
```

**Namespace convention:** `Domain\<Module>\...` (or `Modules\<Module>\...` if the team prefers a `Modules/` root). Do not mix multiple conventions after implementation begins.

---

# 5A. Exact Frontend Page, Component & Feature File Structure

Section 5 fixes the backend source tree to file-level precision — every module lists its actual entity, command, and repository filenames. The frontend tree in §5 stops at `Pages/Admin/` and `Pages/Customer/`, which leaves an implementer (human or AI) to invent page names ad hoc. That gap is not cosmetic: the CPNC's Zero Dead Code & File Reuse Directive (§1) exists precisely to stop an implementer from inventing a file location that isn't already agreed, and a missing frontend tree makes every new page a fresh naming decision instead of a lookup. This section is the frontend's equivalent of §5 — authoritative for `resources/js/`, cross-referenced by the CPNC §5.2 (Pages/Components/Features contract) and by the UI/UX Design System Document §15 (Component Architecture) and §14C (Page-Level Layout Specifications).

Every page listed below is one Inertia entry point mapping 1:1 to one Controller action (CPNC §5.2) — it composes `Components/` and `Features/` and contains no business logic. A page appearing here does not imply it is in scope for an early phase; the Implementation Plan's per-phase "Key files" listings state which phase actually creates each file.

## 5A.1 `resources/js/Pages/Admin/`

```text
Pages/Admin/
├── Auth/
│   ├── Login.tsx
│   ├── ForgotPassword.tsx
│   ├── ResetPassword.tsx
│   └── TwoFactorChallenge.tsx
│
├── Dashboard/
│   └── Index.tsx
│
├── Shops/
│   ├── Index.tsx
│   ├── Create.tsx
│   ├── Edit.tsx
│   └── Show.tsx
│
├── Staff/
│   ├── Index.tsx
│   ├── Create.tsx
│   ├── Edit.tsx
│   ├── Show.tsx
│   └── Roles/
│       ├── Index.tsx
│       ├── Create.tsx
│       └── Edit.tsx
│
├── Inventory/
│   ├── Products/
│   │   ├── Index.tsx
│   │   ├── Create.tsx
│   │   ├── Edit.tsx
│   │   └── Show.tsx
│   ├── Import/
│   │   ├── Create.tsx
│   │   └── Results.tsx
│   ├── Stock/
│   │   ├── Index.tsx
│   │   ├── Adjust.tsx
│   │   └── LowStock.tsx
│   └── Transfers/
│       ├── Index.tsx
│       ├── Create.tsx
│       └── Show.tsx
│
├── Sales/
│   ├── Checkout.tsx
│   ├── Index.tsx
│   ├── Show.tsx
│   └── Receipt.tsx
│
├── Payments/
│   ├── Index.tsx
│   ├── Show.tsx
│   └── Disputes/
│       ├── Index.tsx
│       └── Show.tsx
│
├── Repairs/
│   ├── Index.tsx
│   ├── Create.tsx
│   ├── Show.tsx
│   ├── Diagnosis.tsx
│   ├── Parts.tsx
│   └── Collection.tsx
│
├── Collection/
│   ├── Index.tsx
│   └── Show.tsx
│
├── Warranty/
│   ├── Claims/
│   │   ├── Index.tsx
│   │   └── Show.tsx
│   ├── Returns/
│   │   ├── Index.tsx
│   │   └── Show.tsx
│   └── TradeIns/
│       ├── Index.tsx
│       └── Show.tsx
│
├── Commission/
│   ├── Index.tsx
│   └── Show.tsx
│
├── Referrals/
│   └── Index.tsx
│
├── Marketing/
│   ├── Campaigns/
│   │   ├── Index.tsx
│   │   ├── Create.tsx
│   │   └── Show.tsx
│   ├── Vouchers/
│   │   └── Index.tsx
│   └── StoreCredit/
│       └── Index.tsx
│
├── Notifications/
│   └── Index.tsx
│
├── Finance/
│   ├── Expenses/
│   │   ├── Index.tsx
│   │   └── Create.tsx
│   └── Reports/
│       ├── ProfitAndLoss.tsx
│       └── ShopComparison.tsx
│
├── Reports/
│   ├── Sales.tsx
│   ├── Repairs.tsx
│   ├── Inventory.tsx
│   └── Commission.tsx
│
├── Audit/
│   └── Index.tsx
│
└── Sync/
    └── Conflicts/
        ├── Index.tsx
        └── Show.tsx
```

## 5A.2 `resources/js/Pages/Customer/`

```text
Pages/Customer/
├── Auth/
│   ├── Login.tsx
│   ├── Register.tsx
│   ├── ForgotPassword.tsx
│   └── ResetPassword.tsx
│
├── Dashboard/
│   └── Index.tsx
│
├── Repairs/
│   ├── Index.tsx
│   └── Show.tsx
│
├── Orders/
│   ├── Index.tsx
│   └── Show.tsx
│
├── Payments/
│   ├── Show.tsx
│   ├── History.tsx
│   └── BankTransferInstructions.tsx
│
├── Warranty/
│   ├── Claims/
│   │   ├── Index.tsx
│   │   ├── Create.tsx
│   │   └── Show.tsx
│   └── Returns/
│       ├── Index.tsx
│       └── Create.tsx
│
├── Referrals/
│   └── Index.tsx
│
├── Notifications/
│   └── Index.tsx
│
├── Shops/
│   └── Index.tsx
│
└── Profile/
    ├── Show.tsx
    └── Edit.tsx
```

Each `Index.tsx` composes the theme's responsive data pattern (UI/UX §9); each `Show.tsx` composes the theme's detail-page pattern (UI/UX §14C); `Create.tsx`/`Edit.tsx` compose the theme's form anatomy (UI/UX §10.2/§10.3).

## 5A.3 `resources/js/Components/` (design-system primitives)

```text
Components/
├── Admin/
│   ├── AdminButton.tsx
│   ├── Forms/
│   │   ├── AdminInput.tsx
│   │   ├── AdminSelect.tsx
│   │   └── AdminTextarea.tsx
│   ├── AdminBadge.tsx
│   ├── AdminTable.tsx
│   ├── AdminRowCard.tsx
│   ├── AdminSidebar.tsx
│   ├── AdminTopbar.tsx
│   ├── AdminShell.tsx
│   ├── AdminAuthShell.tsx
│   ├── AdminPageHead.tsx
│   ├── AdminStatCard.tsx
│   ├── AdminEmptyState.tsx
│   ├── AdminPagination.tsx
│   └── AdminFilterBar.tsx
│
├── Customer/
│   ├── CustomerButton.tsx
│   ├── Forms/
│   │   └── CustomerInput.tsx
│   ├── CustomerListItem.tsx
│   ├── CustomerDrawer.tsx
│   ├── CustomerTaskbar.tsx
│   ├── CustomerHeroCard.tsx
│   ├── NotificationDropdown.tsx
│   ├── CustomerShell.tsx
│   ├── CustomerAuthShell.tsx
│   ├── CustomerEmptyState.tsx
│   └── CustomerFab.tsx
│
├── Feedback/
│   ├── ConfirmDialog.tsx
│   ├── Toast.tsx
│   ├── AlertBanner.tsx
│   ├── Skeleton.tsx
│   ├── BlockingLoader.tsx
│   └── ConnectivityIndicator.tsx
│
├── Icons/
│   └── Icon.tsx
│
├── Forms/
│   ├── FormField.tsx
│   ├── FormErrorMessage.tsx
│   └── FormSuccessMessage.tsx
│
├── Tables/
│   ├── ResponsiveDataTable.tsx
│   └── StatusStepper.tsx
│
└── Layout/
    ├── MasterDetailSplit.tsx
    ├── SlideOverPanel.tsx
    └── ActivityTimeline.tsx
```

This tree reconciles with UI/UX §15 — that section owns the *design-system rationale* for each primitive; this section owns the *file location*. Where the two ever disagree on a path, this ADD section is authoritative, consistent with §5's precedence over prose elsewhere in this document.

**`Admin/Forms/` and `Customer/Forms/` boundary rule:** field components — the ones sharing the label + error/success/disabled contract defined in UI/UX §10 (`AdminInput`, `AdminSelect`, `AdminTextarea`, `CustomerInput`, and any future field type: search boxes, dropdowns, date pickers) — live in `<Theme>/Forms/`. Everything else in a theme folder is flat. `AdminButton`/`CustomerButton` are deliberately **not** in `Forms/` — UI/UX gives buttons their own section (§11), separate from forms, because a button is used far more broadly than form submission (modals, table row actions, nav triggers, standalone CTAs). Do not create a new subfolder (e.g. a `Cards/`) for a single file — a subfolder is justified only once a second genuine sibling in that same non-field category exists.

## 5A.4 `resources/js/Features/` (domain-specific, one folder per module needing interactive UI)

```text
Features/
├── Sales/
│   ├── POSCart.tsx
│   ├── ProductSearch.tsx
│   ├── CheckoutTotals.tsx
│   └── CheckoutExpiryTimer.tsx
│
├── Inventory/
│   ├── SerializedUnitPicker.tsx
│   ├── StockLevelBadge.tsx
│   └── CsvImportPreview.tsx
│
├── Payments/
│   ├── PaymentMethodSwitcher.tsx
│   ├── PaymentStatusBadge.tsx
│   └── BankTransferProofUpload.tsx
│
├── Repairs/
│   ├── DiagnosisChecklist.tsx
│   ├── PartsReservationPicker.tsx
│   └── RepairStatusStepper.tsx
│
├── Warranty/
│   └── EligibilityChecklist.tsx
│
├── Marketing/
│   └── DiscountStackPreview.tsx
│
├── Sync/
│   ├── OfflineSyncStatus.tsx
│   └── ConflictResolutionPanel.tsx
│
└── Notifications/
    └── NotificationInbox.tsx
```

A component's placement test (Components/ vs. Features/<Module>/) is defined in CPNC §5.2 — do not re-derive it here; apply that test before adding a file to either tree.

## 5A.5 Supporting frontend folders

```text
resources/js/
├── hooks/
│   ├── useConfirm.ts
│   ├── useToast.ts
│   └── useOnlineStatus.ts
├── stores/
│   └── (local, per-Feature React state only — never a client-side source of business truth, §25.3)
├── types/
│   ├── admin.ts
│   ├── customer.ts
│   └── shared.ts
└── lib/
    ├── route.ts         (Ziggy/Inertia route() helper wiring)
    └── money.ts          (minor-unit formatting for display only — never a source of monetary truth)
```

**Rule:** A new page, component, or feature is added *to* this tree, not beside it. If a task requires a file that does not appear here, the correct response — per CPNC Appendix A question 2 — is to add it to this section in the same change set that creates the file, not to invent an undocumented location.

---

# 6. Shared Kernel

The Shared Kernel should be deliberately small.

## 6.1 Approved Shared Concepts

- `ShopId`
- `CustomerId`
- `StaffId`
- `Money` (minor units only)
- `Clock`
- `DomainEvent`
- `AggregateId`
- `CorrelationId`
- `IdempotencyKey`
- common result/error abstractions

## 6.2 Money

The data model mandates `BIGINT` minor units (kobo) and rejects `DECIMAL`/`FLOAT` for monetary values. Domain code therefore uses a `Money` value object backed by an integer.

```php
final readonly class Money
{
    public function __construct(public int $minor)
    {
        if ($minor < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
    }

    public function add(self $other): self
    {
        return new self($this->minor + $other->minor);
    }

    public function subtract(self $other): self
    {
        return new self($this->minor - $other->minor);
    }
}
```

This aligns with the finalized data model's money convention. fileciteturn0file2

---

# 7. Domain-Driven Design Model

## 7.1 Aggregates

Recommended aggregate roots:

| Module | Aggregate root |
|---|---|
| Repair | `RepairJob` |
| Collection | `CollectionCase` |
| Sales | `SalesCheckout`, `Sale` |
| Inventory | `InventoryItem`, `InventoryStockLevel`, `InventoryTransfer`, `Sku` |
| Payments | `PaymentTransaction` |
| Warranty | `WarrantyClaim`, `ReturnRequest`, `TradeInAssessment` |
| Commission | `CommissionLedger` conceptual aggregate around immutable entries |
| Marketing | `Campaign`, `Reward`, `StoreCreditAccount` conceptual ledger |
| Notifications | `NotificationEvent` |
| Audit | `AuditLog` append-only entry |
| Sync | `SyncOutboxEntry` |

## 7.2 Aggregate Rules

An aggregate is responsible for invariants that must be true at the point of change.

Examples:

- `RepairJob` controls valid repair-state transitions.
- `PaymentTransaction` controls valid payment-state transitions.
- `InventoryStockLevel` controls reserved quantity and prevents `reserved > on_hand`.
- `InventoryItem` controls its own serialized reservation state.
- `CommissionLedger` never mutates an earned entry to reverse it; it creates a new entry.

## 7.3 Domain Events

Domain events describe business facts, not HTTP actions.

Good:

```text
PaymentConfirmed
RepairCompleted
CheckoutExpired
InventoryReservationReleased
CommissionEarned
NotificationRequested
```

Avoid:

```text
PostPaymentControllerFinished
ClickConfirmButton
ApiRequestSucceeded
```

---

# 8. Application Layer and Use-Case Design

Every meaningful mutation is implemented as a command/use case.

Examples:

```text
CreateRepairJob
CompleteDiagnosis
AuthorizeRepair
ReserveParts
StartRepair
CompleteRepair
CreateCheckout
ReserveCheckoutInventory
InitiatePayment
ConfirmPayment
MarkBankTransferPending
OpenPaymentDispute
ResolvePaymentDispute
CreateSaleFromPaidCheckout
CreateWarrantyClaim
ApproveRefund
TransferInventory
RecordExpense
GrantReward
PublishNotificationEvent
SyncOfflineTransaction
ResolveSyncConflict
```

The controller should do only this:

1. validate HTTP input;
2. construct a command DTO;
3. invoke one application handler;
4. return Inertia data/redirect/error.

---

# 9. Repository Pattern

The repository abstraction belongs to the domain/application side; Eloquent implementations belong to Infrastructure.

```php
interface PaymentTransactionRepository
{
    public function get(PaymentTransactionId $id): PaymentTransaction;

    public function save(PaymentTransaction $payment): void;

    public function findByProviderReference(
        string $provider,
        string $reference
    ): ?PaymentTransaction;
}
```

Implementation:

```php
final class EloquentPaymentTransactionRepository
    implements PaymentTransactionRepository
{
    public function __construct(
        private readonly PaymentTransactionRecord $record
    ) {}

    public function get(PaymentTransactionId $id): PaymentTransaction
    {
        $row = $this->record->newQuery()->findOrFail($id->value());

        return PaymentTransactionMapper::toDomain($row);
    }

    public function save(PaymentTransaction $payment): void
    {
        PaymentTransactionMapper::toRecord($payment)->save();
    }
}
```

The domain does not know Eloquent exists.

---

# 10. Strategy Pattern for Payment Gateways

## 10.1 Core Contract

All gateway integrations implement one domain/application contract.

```php
interface PaymentGateway
{
    public function name(): string;

    public function initialize(PaymentInitialization $payment): GatewayInitializationResult;

    public function verify(string $providerReference): GatewayVerificationResult;

    public function refund(GatewayRefundRequest $refund): GatewayRefundResult;
}
```

Implementations:

```text
PaystackGateway
TranzixGateway
ManualBankTransferGateway
```

The manual bank-transfer adapter is intentionally an application gateway abstraction even though it does not call an external API. It allows the rest of the system to use the exact same initiation/confirmation interface while the confirmation path delegates to authorized staff.

## 10.2 Gateway Factory / Resolver

```php
final class PaymentGatewayResolver
{
    public function __construct(
        private readonly PaymentGateway $paystack,
        private readonly PaymentGateway $tranzix,
        private readonly PaymentGateway $manualBankTransfer,
    ) {}

    public function resolve(PaymentMethod $method): PaymentGateway
    {
        return match ($method) {
            PaymentMethod::InApp => $this->paystack,
            PaymentMethod::BankTransfer => $this->manualBankTransfer,
            PaymentMethod::PosTerminal => $this->tranzix,
            PaymentMethod::Cash => throw new UnsupportedPaymentGateway(
                'Cash is recorded locally and does not use an external gateway.'
            ),
        };
    }
}
```

**Important:** the mapping above is an architectural example, not a claim that Paystack must be used for every in-app payment or Tranzix for every terminal flow. Provider-method mapping belongs in configuration because the BRD says the final vendor selection affects technical design.

---

# 11. Payment Domain Architecture

The Payments module is a separate bounded context in the data model. `payments_transactions` references a payable by `payable_type + payable_id` rather than a cross-module foreign key. fileciteturn0file2

## 11.1 Payment Transaction Model

```text
PaymentTransaction
├── id
├── payable_type
├── payable_id
├── method
├── amount_minor
├── status
├── provider_reference
├── confirmed_by_staff_id
├── dispute_opened_at
├── dispute_proof_reference
├── dispute_resolved_by_staff_id
└── audit timestamps
```

Supported status values from the finalized data model:

```text
pending
payment_pending_confirmation
confirmed
disputed
refunded
exception
```

## 11.2 State Machine

```text
pending
  │
  ├── provider success ───────────────► confirmed
  │
  ├── bank transfer initiated ───────► payment_pending_confirmation
  │                                        │
  │                                        ├── staff confirms ─────► confirmed
  │                                        │
  │                                        └── staff rejects ──────► exception
  │
  └── customer disputes ──────────────► disputed
                                           │
                                           ├── verified ──────────► confirmed
                                           └── rejected ──────────► exception

confirmed ── refund approved/executed ──► refunded
```

The BLD explicitly requires a separate pending-confirmation status for bank transfers and requires human verification for disputes. fileciteturn4file0

## 11.3 Payment State Must Not Be the Sales/Repair State

Payment state is not a replacement for sales or repair status.

For example:

```text
Sale checkout state = open
Payment state       = payment_pending_confirmation
Inventory state     = reserved
```

Later:

```text
Sale checkout state = paid
Payment state       = confirmed
Inventory state     = consumed / stock-out
```

This preserves the BLD's multi-dimensional transaction model. fileciteturn2file18

---

# 12. Bank Transfer Pending Confirmation Architecture

## 12.1 Why a Separate State Exists

The BLD deliberately avoids two dangerous shortcuts:

```text
bank transfer received claim → Paid
```

and

```text
bank transfer received claim → Unpaid + reservation expires normally
```

Instead:

```text
Bank transfer initiated
        ↓
payment_pending_confirmation
        ↓
reservation hold remains active
        ↓
authorized verification
    ┌───┴────────┐
    ↓            ↓
confirmed      rejected
```

This follows PAY-BR-01 and PAY-BR-02. fileciteturn3file1

## 12.2 Reservation Hold Rule

The Payments module does not directly manipulate Inventory tables.

Instead it emits:

```php
BankTransferPendingConfirmation
```

A Sales/Repair application handler subscribes to that domain event and asks the Inventory/transaction context to maintain the reservation hold.

Conceptually:

```text
Payments
  │
  └── BankTransferPendingConfirmation
             │
             ▼
       Sales/Repair listener
             │
             ▼
     Inventory Reservation Policy
             │
             └── HOLD
```

The reservation is released only when the payment is explicitly rejected/cancelled or the business's configured exception process releases it.

---

# 13. Payment Dispute Architecture

## 13.1 Dispute Lifecycle

```text
Customer claims payment made
        ↓
OpenPaymentDispute
        ↓
payment.status = disputed
        ↓
proof/reference attached
        ↓
Notification to authorized finance staff
        ↓
Administrative review
      ┌─┴────────────────┐
      ↓                  ↓
   verified            rejected
      ↓                  ↓
 confirmed            exception
```

The BLD requires proof/reference plus authorized human resolution; the system must not auto-confirm or auto-cancel a disputed bank transfer. fileciteturn4file0

## 13.2 Required Application Commands

```text
OpenPaymentDispute
AttachDisputeProof
ResolvePaymentDispute
RejectPayment
ConfirmPayment
```

## 13.3 Authorization

`ResolvePaymentDispute` must require an explicit permission such as:

```text
payments.dispute.review
payments.dispute.resolve
```

The permission must be evaluated server-side, consistent with RBAC-BR-05. fileciteturn2file2

## 13.4 Audit Requirements

Every dispute transition generates an Audit event with:

```json
{
  "module": "payments",
  "event_type": "PAYMENT_DISPUTE_RESOLVED",
  "actor_staff_id": 42,
  "subject_type": "payments_transaction",
  "subject_id": 781,
  "before_state": {"status": "disputed"},
  "after_state": {"status": "confirmed"},
  "context": {
    "resolution_reason": "Transfer verified against bank statement"
  }
}
```

The Audit module remains append-only; corrections are new events, never edits. fileciteturn1file4

---

# 14. Webhook Architecture

External webhooks must never write a sale directly.

```text
Provider webhook
      ↓
WebhookController
      ↓
Signature verification
      ↓
Idempotency check
      ↓
ProcessProviderWebhook command
      ↓
PaymentGateway adapter parses provider payload
      ↓
PaymentTransaction application handler
      ↓
transaction commit
      ↓
domain event PaymentConfirmed / PaymentExceptionRaised
      ↓
module listeners
```

## 14.1 Webhook Idempotency

Every webhook event should carry a provider event identifier or a deterministic fallback fingerprint.

Store an idempotency record such as:

```text
provider = paystack
provider_event_id = evt_...
handled_at = ...
```

A repeated webhook must return the previous result without duplicating:

- payment confirmation;
- sale creation;
- stock-out;
- commission earning;
- notification.

## 14.2 Never Trust a Client-Side “Success” Screen

The customer browser is not authoritative for payment confirmation.

Only one of the following should set `confirmed`:

- a verified provider webhook / API verification;
- authorized manual bank-transfer confirmation;
- an explicitly supported terminal settlement workflow.

---

# 15. Sales + Inventory Transaction Architecture

## 15.1 Checkout Creation

```text
Create Checkout
      ↓
validate price/warranty/discounts
      ↓
BEGIN TRANSACTION
      ↓
lock serialized inventory items / stock level rows
      ↓
check Available
      ↓
create reservation
      ↓
create checkout
      ↓
COMMIT
```

The BLD requires the availability check and reservation to be atomic and explicitly references transactional concurrency. The finalized data model specifies MySQL 8 / InnoDB and `SELECT ... FOR UPDATE` for the reservation model. fileciteturn1file9 fileciteturn0file2

## 15.2 Serialized Inventory

For IMEI-tracked phones:

```sql
SELECT *
FROM inventory_items
WHERE id = ?
FOR UPDATE;
```

Then verify:

```text
status = available
current_shop_id = requested shop
reserved_by_id IS NULL
```

Then atomically set:

```text
status = reserved
reserved_by_type = checkout
reserved_by_id = checkout_id
version = version + 1
```

## 15.3 Non-Serialized Inventory

```sql
SELECT *
FROM inventory_stock_levels
WHERE sku_id = ?
  AND shop_id = ?
FOR UPDATE;
```

Then:

```text
available = on_hand - reserved
available >= requested_quantity
```

Update only after the row is locked.

## 15.4 Never Store `Available`

The finalized model explicitly defines `Available` as `on_hand - reserved` and says it is computed at query time, not stored. fileciteturn0file2

---

# 16. Sale Completion Flow

A successful payment should not independently “sell” inventory. The flow must be coordinated by an application service.

```text
PaymentConfirmed
      ↓
SalesPaymentConfirmationHandler
      ↓
BEGIN TRANSACTION
      ↓
lock checkout
      ↓
verify checkout still owns reservation
      ↓
consume reservation
      ↓
write stock-out / sale
      ↓
create immutable sales_sales record
      ↓
commit
      ↓
emit SaleCompleted
```

If the reservation has already expired, `SALE/PAY-BR-08` requires an exception path rather than silently completing the sale. fileciteturn3file1

---

# 17. Repair Architecture

## 17.1 Repair Aggregate Lifecycle

```text
Received
  ↓
Diagnosing
  ↓
Diagnosis Complete
  ├── Unrepairable → Ready for Return → Collection
  └── Repairable
        ↓
   Awaiting Authorization
        ↓
   payment confirmed if required
        ↓
   Reserve parts
        ↓
   In Progress
        ├── Completed
        │      ├── fully paid → Ready for Collection
        │      └── balance due → Ready for Collection (blocked)
        └── Failed / Requires Resolution
```

These transitions come directly from the BLD's revised repair lifecycle. fileciteturn4file13

## 17.2 Diagnosis Is a Domain Object

Do not store diagnosis as a single boolean such as:

```php
$repair->is_repairable
```

Use a collection of diagnostic observations:

```text
screen → faulty
battery → not_tested
face_id → unable_to_test
camera → working
```

and a final outcome:

```text
repairable
unrepairable
requires_further_assessment
```

## 17.3 Parts Reservation Timing

Candidate parts may be selected during diagnosis UI, but the actual reservation command should only execute once the repair is repairable and authorization/payment rules are satisfied. This is explicitly required by REP-BR-06. fileciteturn1file15

---

# 18. Warranty / Returns / Trade-In Architecture

Warranty is a separate bounded context and should never become a “free RepairJob” shortcut.

```text
WarrantyClaim
    ↓ eligibility assessment
    ↓ remedy decision
 ┌──┼───────────────┬───────────┐
Repair Replace     Refund      Exchange
  ↓       ↓          ↓            ↓
Repair   Inventory  Payments    Sales/Trade-In
```

The BLD explicitly requires eligibility and assessment before remedy and a dedicated warranty/resolution layer that hands off to existing capabilities. fileciteturn2file7

`ResolutionLifecycle` may be implemented as a reusable domain service, but each table retains its own resolution-state column as specified in the data model.

---

# 19. Commission Architecture

Commission is append-only and payment-based by default.

```text
PaymentConfirmed
      ↓
CommissionRecognitionPolicy
      ↓
commissionable amount
      ↓
rate snapshot
      ↓
append commission_ledger_entries row
```

Refunds or corrections produce a new reversal/adjustment entry; the original entry is never edited. Partial refunds create proportional commission reversals. The BLD and schema both establish this immutability. fileciteturn3file5 fileciteturn4file3

Important architectural rule:

```text
Do not calculate commission from today's rate configuration.
Use the rate snapshot stored at earning time.
```

---

# 20. Marketing / Referral Architecture

Marketing owns campaign and reward instruments. Referral determines referral relationship and qualification, but reward issuance is delegated to Marketing.

```text
ReferralQualified
       ↓
Marketing Reward Engine
       ↓
Reward
   ┌───┴──────────────┐
   ↓                  ↓
Store Credit        Voucher
```

Campaign eligibility is evaluated against owning modules' source data at benefit time, then frozen as an eligibility snapshot when the benefit is granted. fileciteturn2file2

Discount stacking must use a deterministic application order and every adjustment must be traceable to its source.

---

# 21. Notification Architecture

## 21.1 Event-Driven Model

A module emits a business event; it does not call WhatsApp/email directly.

```text
RepairCompleted
      ↓
Notification Requested
      ↓
notification_events row
      ↓
queue job
      ↓
Notification Engine
      ↓
preferences + category policy
      ↓
channel provider
      ↓
notification_delivery_attempts
```

The BLD explicitly requires asynchronous delivery, provider abstraction, retry/failover, delivery attempt logging and duplicate prevention. fileciteturn1file1

## 21.2 Notification Categories

```text
transactional
operational
security
marketing
```

Marketing opt-out is independent. Transactional notifications remain in the dashboard even when an external channel is disabled. fileciteturn3file9

## 21.3 Retry and Failover

```text
Attempt 1
  ↓ transient failure
Retry with backoff
  ↓ transient failure
Try next configured provider
  ↓
Delivered
```

Permanent failures must not retry forever.

A notification failure must never roll back the originating repair, sale or payment transaction.

---

# 21A. Email Provider Architecture (Resend + SMTP)

The ADD should explicitly record **Resend** as a supported email provider alongside **SMTP**. This belongs in infrastructure because the BLD requires provider abstraction, configurable multi-channel delivery, delivery-attempt logging, retries/failover, and provider replacement without changing business modules. fileciteturn5file0

```text
Business Event
      ↓
Notification Engine
      ↓
Email Channel
      ↓
EmailProvider
      ├── ResendMailer
      └── SmtpMailer
```

Recommended contract:

```php
interface EmailProvider
{
    public function send(EmailMessage $message): ProviderSendResult;
}
```

Provider selection is configuration-driven, for example:

```text
MAIL_MAILER=resend        # or smtp
RESEND_API_KEY=...
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
```

The exact Resend Laravel mail-driver wiring depends on the package/adapter selected during implementation, but the architectural rule is fixed: business modules know only that an email channel exists. They do not import Resend or SMTP implementations. Notification delivery attempts retain the selected provider so retries, failover, and diagnostics remain observable.

Do not create provider-specific business events such as `RepairCompletedEmailViaResend`; keep the event provider-neutral (`RepairCompleted`) and let the Notification Engine select the configured email provider.

---

# 22. Unified Audit Architecture

## 22.1 Audit Event Capture

Critical operations should emit an audit event as part of the same logical transaction whenever practical.

Example:

```text
ResolvePaymentDispute
   ├── mutate payment aggregate
   ├── write audit_logs row
   └── commit
```

If the operation fails, neither the business state nor the critical audit record should claim success.

## 22.2 Audit Record Contents

The finalized schema includes:

```text
module
event_type
actor_staff_id
actor_role_snapshot
subject_type
subject_id
before_state
after_state
context
created_at
```

Role history is snapshotted; audit events do not perform a live RBAC lookup later. fileciteturn0file2

---

# 23. Reporting / Financial Projection Architecture

Reporting is a **projection**, not the source of truth.

```text
Sales -----------┐
Repairs ---------┤
Inventory -------┤
Payments --------┤
Commission ------┤
Expenses --------┤
Refunds ---------┘
        ↓
Financial projection
        ↓
reporting_financial_ledger_entries
        ↓
Reports / P&L / Shop dashboards
```

The BLD requires a consolidated reporting layer while preserving each source module as authoritative. fileciteturn3file4

The data model states that `reporting_financial_ledger_entries` is an append-only projection populated by synchronous Lane A listeners and must not be directly written by command handlers. fileciteturn0file2

---

# 24. Offline / PWA Architecture

Offline operation is intentionally controlled.

## 24.1 Safe Offline Classes

Examples approved by business policy may include:

- cash sales;
- repair diagnosis notes;
- eligible repair-workflow updates.

## 24.2 Unsafe Offline Classes

Do not confirm offline:

- shared inventory reservation;
- bank-transfer verification;
- refunds;
- inter-shop transfers;
- other centrally coordinated financially risky operations.

These restrictions are established by OFF-BR-01 through OFF-BR-13. fileciteturn3file7

## 24.3 Client Sync

```text
Local transaction
      ↓
local commit
      ↓
sync_outbox_entries = pending
      ↓ reconnect
syncing
      ↓
server idempotency check
      ↓
apply command
      ├── synced
      └── conflict
```

Use stable client-generated UUIDs for offline-created entities, as specified in the data model, so replay detection can be based on the primary key. fileciteturn0file2

---

# 25. Inertia + React Frontend Architecture

## 25.1 Page Responsibility

Inertia pages should map to application use cases and authorization scopes, not database tables.

Example:

```text
resources/js/Pages/Admin/Repairs/Show.tsx
resources/js/Pages/Admin/Sales/Checkout.tsx
resources/js/Pages/Customer/Repairs/Show.tsx
resources/js/Pages/Customer/Payments/Show.tsx
```

## 25.2 React Usage Boundary

Prefer standard Inertia forms/pages for:

- CRUD screens;
- tables;
- filters;
- simple workflows.

Use interactive React components for:

- POS cart and checkout composition;
- IMEI/unit selection;
- reservation timers;
- dynamic promotion stacking preview;
- repair part selection;
- payment method switching;
- offline synchronization status;
- rich dashboard widgets.

## 25.3 Frontend Domain Rule

The frontend may mirror state for UX but is never the authority.

For example:

```text
React says payment = "success"
                 ↓
           NOT authoritative
                 ↓
server/provider verification
                 ↓
payment.status = confirmed
```

---

# 26. Route Design

Routes are an explicit architectural layer. `routes/web.php` remains the browser/Inertia entry point, while detailed routes are split by audience/integration boundary. These files declare transport entry points; business rules remain in application/domain layers.

## 26.1 Route File Responsibilities

```text
routes/web.php
    └── loads/aggregates admin.php + customer.php

routes/admin.php
    ├── dashboard
    ├── repairs
    ├── sales / POS
    ├── inventory
    ├── payments / disputes
    ├── staff / RBAC
    ├── shops
    └── reports

routes/customer.php
    ├── dashboard
    ├── repairs
    ├── purchases/history
    ├── payments
    ├── notifications
    └── profile

routes/api.php
    └── stateless API + offline sync

routes/webhooks.php
    ├── POST /webhooks/paystack
    └── POST /webhooks/tranzix

routes/channels.php
    └── broadcast/private-channel authorization

routes/console.php
    └── console/scheduler route registrations
```

## 26.2 Middleware Composition

```text
Admin request
  → web
  → auth
  → EnsureStaffIsActive
  → ResolveShopContext
  → EnsurePermission
  → Controller
  → Application Handler

Payment webhook
  → webhook route
  → VerifyWebhookSignature
  → provider-event deduplication / idempotency
  → Webhook Controller
  → Payments Application Handler
```

Webhook endpoints do not use normal user/session authentication. They authenticate the provider request and then enter the Payments application boundary.

## 26.3 Route Boundary Rule

A route/controller boundary may authenticate, authorize, validate input, create a command/query, and map the result to an HTTP/Inertia response. It must not contain inventory reservation logic, payment confirmation logic, commission calculations, or direct Paystack/Tranzix calls.

Keep route names stable and use them from Inertia instead of hard-coded URLs, e.g. `admin.sales.checkouts.store`, `admin.payments.disputes.resolve`, `customer.payments.store`, `payments.webhooks.paystack`, and `payments.webhooks.tranzix`.

Keep route groups organized by audience and bounded context.

```php
// routes/web.php

// 1. Admin Operations Subdomain
Route::domain('dashboard.' . config('app.url'))->group(function () {
    Route::middleware(['auth:staff', 'verified'])
        ->prefix('admin')
        ->group(function () {
            Route::middleware('permission:repairs.view')
                ->get('/repairs/{repair}', [RepairController::class, 'show'])
                ->name('admin.repairs.show');

            Route::middleware('permission:sales.create')
            ->post('/sales/checkouts', [SalesController::class, 'store'])
            ->name('admin.sales.checkouts.store');

            Route::middleware('permission:payments.dispute.resolve')
            ->post('/payments/{payment}/dispute/resolve', [PaymentController::class, 'resolveDispute'])
            ->name('admin.payments.disputes.resolve');    
            // ... require __DIR__.'/admin.php';
        });
});

// 2. Customer Portal Subdomain
Route::domain('app.' . config('app.url'))->group(function () {
    Route::middleware('auth:customer')
        ->prefix('customer')
        ->group(function () {
            Route::get('/repairs/{repair}', [\App\Http\Controllers\Customer\RepairController::class, 'show'])
                ->name('customer.repairs.show');

            Route::post('/payments', [\App\Http\Controllers\Customer\PaymentController::class, 'store'])
            ->name('customer.payments.store');
            // ... require __DIR__.'/customer.php';
        });
});

// Webhooks (Domain Agnostic)
    Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])
    ->middleware('verify.webhook:paystack');

    Route::post('/webhooks/tranzix', [TranzixWebhookController::class, 'handle'])
    ->middleware('verify.webhook:tranzix');

    Route::post('/api/sync', [SyncController::class, 'store'])
    ->middleware(['auth', 'ensure.idempotency']);
```

### Code design principle

Routes should reference controllers; controllers reference application handlers; handlers reference domain contracts. Routes must not call repositories or gateways directly.

---

# 27. Core Controller Example

A controller should be intentionally thin.

```php
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

No provider logic belongs here.

---

# 28. Payment Strategy Example

```php
interface PaymentGateway
{
    public function name(): string;

    public function initialize(
        PaymentInitialization $payment
    ): GatewayInitializationResult;

    public function verify(
        string $providerReference
    ): GatewayVerificationResult;

    public function refund(
        GatewayRefundRequest $request
    ): GatewayRefundResult;
}

final class PaystackGateway implements PaymentGateway
{
    public function __construct(
        private readonly PaystackClient $client,
    ) {}

    public function name(): string
    {
        return 'paystack';
    }

    public function initialize(
        PaymentInitialization $payment
    ): GatewayInitializationResult {
        $response = $this->client->initializeTransaction([
            'reference' => $payment->idempotencyReference,
            'amount' => $payment->amount->minor,
            'email' => $payment->customerEmail,
        ]);

        return GatewayInitializationResult::redirect(
            authorizationUrl: $response->authorizationUrl,
            providerReference: $response->reference,
        );
    }

    public function verify(string $providerReference): GatewayVerificationResult
    {
        $response = $this->client->verifyTransaction($providerReference);

        return GatewayVerificationResult::fromProviderResponse($response);
    }

    public function refund(GatewayRefundRequest $request): GatewayRefundResult
    {
        return $this->client->refund($request->providerReference, $request->amount->minor);
    }
}
```

**Provider-specific response mapping ends at the Infrastructure boundary.** The domain sees only AfProsPos payment concepts.

---

# 29. Payment Confirmation Service

```php
final class ConfirmPaymentHandler
{
    public function __construct(
        private readonly PaymentTransactionRepository $payments,
        private readonly PaymentGatewayResolver $gateways,
        private readonly DomainEventBus $events,
        private readonly TransactionManager $tx,
    ) {}

    public function handle(ConfirmPaymentCommand $command): void
    {
        $this->tx->run(function () use ($command) {
            $payment = $this->payments->get($command->paymentId);

            if ($payment->isConfirmed()) {
                return; // idempotent replay
            }

            $gateway = $this->gateways->resolve($payment->method());
            $verification = $gateway->verify($command->providerReference);

            if (!$verification->isSuccessfulFor($payment->amount())) {
                throw new PaymentVerificationFailed();
            }

            $payment->confirm(
                providerReference: $command->providerReference,
                confirmedBy: $command->confirmedByStaffId,
            );

            $this->payments->save($payment);

            foreach ($payment->releaseEvents() as $event) {
                $this->events->record($event);
            }
        });
    }
}
```

For manual bank transfer confirmation, the same application handler can be used with an authorized actor and a specialized verification policy.

---

# 30. Transaction and Locking Rules

Use a dedicated transaction wrapper to make transaction boundaries visible in application code.

```php
final class Atomic
{
    public function __construct(private readonly Connection $db) {}

    public function run(Closure $operation): mixed
    {
        return $this->db->transaction(
            $operation,
            attempts: 3
        );
    }
}
```

For inventory reservations, the handler must explicitly lock the authoritative stock row/unit.

## 30.1 Lock Order

To minimize deadlocks, define and document a consistent order. Example:

```text
1. checkout / repair row
2. serialized inventory item OR inventory_stock_levels rows sorted by id
3. reservation rows
4. sale/payment projection rows
```

Never dynamically lock inventory rows in arbitrary UI order.

## 30.2 Deadlock Handling

Retry the whole transaction only for known transient database deadlocks/serialization failures. Never blindly retry business exceptions such as insufficient stock.

---

# 31. Idempotency Architecture

Idempotency is required in three major places:

1. payment provider webhooks;
2. customer payment initiation retries;
3. offline synchronization.

Recommended request contract:

```http
Idempotency-Key: 8b0f3c6d-...
```

Server semantics:

```text
new key
  → execute once
  → persist response/result
  → return result

same key + same payload
  → return previous result

same key + different payload
  → reject as idempotency conflict
```

The BLD defines idempotency as a core property for sync/reprocessing, and the offline section requires stable identifiers so retries do not produce duplicate business effects. fileciteturn3file11

---

# 32. Scheduler / Background Jobs

Recommended scheduled responsibilities:

```text
Every minute:
  - payment exception housekeeping
  - checkout expiry sweep
  - repair authorization expiry sweep
  - collection deadline checks
  - notification reminders

Every few minutes:
  - notification retry queue
  - sync outbox processing
  - provider reconciliation

Hourly/daily:
  - low-stock evaluation
  - financial projection consistency checks
  - orphaned cross-module reference checks
  - failed job / conflict review summaries
```

A checkout expiry job must still acquire the same inventory locks used by the reservation command. It must never simply decrement reservation counters based on stale assumptions.

---

# 33. Database Mapping Rules

## 33.1 Eloquent Is an Infrastructure Detail

Eloquent models should be named `*Record` or placed inside `Infrastructure/Persistence/Eloquent` to signal that they are persistence objects.

Example:

```php
final class PaymentTransactionRecord extends Model
{
    protected $table = 'payments_transactions';

    protected $casts = [
        'amount_minor' => 'integer',
        'dispute_opened_at' => 'datetime',
    ];
}
```

The domain entity is separate:

```php
final class PaymentTransaction
{
    // business behavior only
}
```

## 33.2 No Cross-Context Eloquent Relationships

Avoid:

```php
public function sale()
{
    return $this->belongsTo(SaleRecord::class);
}
```

when `PaymentTransactionRecord` is in Payments and `SaleRecord` is in Sales.

Store the ID/type and resolve the target through the Sales application boundary when needed.

---

# 34. Authorization / RBAC Architecture

The BLD requires multiple simultaneous roles, effective permissions as the union of roles, server-side authorization, historical preservation, and deactivation rather than deletion. fileciteturn2file2

Recommended authorization stack:

```text
HTTP middleware
      ↓
Policy / Permission check
      ↓
Application authorization guard
      ↓
Domain policy
```

This gives two levels of defense:

1. route/UI access restriction;
2. use-case-level enforcement.

A user should not be able to bypass authorization by calling an application endpoint directly.

Shop scope should be resolved as part of the authenticated actor context:

```php
final readonly class ActorContext
{
    public function __construct(
        public StaffId $staffId,
        public array $effectivePermissions,
        public array $shopIds,
        public ?int $activeShopId,
    ) {}
}
```

---

# 35. Multi-Shop Context

The shop context should be explicit in admin requests.

```text
Authenticated staff
      ↓
ResolveShopContext
      ↓
ActorContext(activeShopId = X)
      ↓
Application command
      ↓
Domain policy checks shop scope
```

The Shop Owner may operate across all shops. Other staff are limited by assigned shop permissions.

Never trust a client-provided `shop_id` alone; validate it against effective permissions.

The business requirement supports shop-level operations plus business-wide reporting. fileciteturn1file8

---

# 36. API / DTO Boundaries

Use explicit DTOs between Presentation and Application:

```php
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

Avoid passing:

```php
Request $request
```

into the domain/application layer.

---

# 37. Exception Taxonomy

Use business exceptions that map cleanly to UX messages.

```text
DomainException
├── InsufficientAvailableStock
├── InvalidRepairTransition
├── PaymentStateTransitionNotAllowed
├── PaymentVerificationFailed
├── PaymentDisputeNotResolvable
├── ExpiredCheckoutCannotBePaidNormally
├── UnauthorizedDeviceRelease
└── ShopScopeViolation
```

Presentation maps these to:

- 422 validation/business error;
- 403 authorization error;
- 404 resource not found;
- 409 conflict/concurrency/state conflict.

Never expose provider API errors directly to the user.

---

# 38. Event Bus / Outbox Design

For high-value business events, use a transactional outbox pattern.

```text
BEGIN TRANSACTION
  mutate domain state
  write outbox event
COMMIT
      ↓
worker publishes/processes event
      ↓
listeners
```

This prevents the failure mode:

```text
DB commit succeeds
but event publish fails
```

The outbox can be implemented inside the same MySQL database in phase one. The architecture does not require Kafka/RabbitMQ to start.

For notifications and reporting, the outbox/event records should carry:

```text
event_id
aggregate_type
aggregate_id
event_type
payload
occurred_at
correlation_id
causation_id
processed_at
attempts
```

---

# 39. Read Models / Query Architecture

Queries should not load an aggregate when a read model is sufficient.

Examples:

```text
RepairListQuery
RepairDashboardQuery
OpenCheckoutQuery
AvailableInventoryQuery
PaymentDisputeQueueQuery
CommissionReportQuery
FinancialPAndLQuery
NotificationInboxQuery
```

Laravel query builders are acceptable inside the Infrastructure query layer.

Example:

```php
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

The query object is allowed to use Eloquent because it is explicitly a read-side Infrastructure concern.

---

# 40. Audit and Ledger Immutability

The following tables must not expose normal update/delete paths:

```text
commission_ledger_entries
marketing_store_credit_ledger_entries
reporting_financial_ledger_entries
audit_logs
```

The data model structurally omits `updated_at` from these append-only tables. fileciteturn0file2

Repository interfaces should reinforce this:

```php
interface AuditLogWriter
{
    public function append(AuditEntry $entry): void;
}
```

Do not provide:

```php
updateAuditLog(...)
deleteAuditLog(...)
```

---

# 41. Security Architecture

## 41.1 Authentication

- Laravel's authenticated session stack for browser users.
- Optional 2FA for admin accounts as required by the BRD security NFR.
- Session invalidation when staff are deactivated.
- Strong password policy and standard CSRF protections.

## 41.2 Sensitive Data

The BRD requires protection of payment references, IMEI and customer PII. fileciteturn3file6

Recommended:

- application-level encryption for especially sensitive stored fields;
- encrypted transport only (HTTPS);
- secrets exclusively in environment/secret manager configuration;
- no provider secret in frontend JavaScript;
- audit every sensitive administrative operation.

## 41.3 Webhook Security

Each provider webhook endpoint must verify provider authenticity before parsing the business event.

---

# 42. Observability

Every request and background job should carry:

```text
request_id
correlation_id
actor_id
shop_id
module
use_case
```

Log events at boundaries:

```text
HTTP request start
application command start/end
payment provider call
webhook received
queue job failed
inventory conflict
sync conflict
notification provider failure
```

Avoid logging:

- secrets;
- card data;
- full sensitive customer payloads;
- authentication tokens.

---

# 43. Testing Architecture

## 43.1 Unit Tests

Domain-only tests:

```text
RepairStatusTransitionTest
PaymentStateTransitionTest
MoneyTest
CommissionCalculationTest
WarrantyEligibilityTest
DiscountStackingTest
ReservationPolicyTest
```

## 43.2 Feature Tests

HTTP/use-case tests:

```text
CustomerCanInitiatePaymentTest
AdminCanResolvePaymentDisputeTest
CashierCanCreateCheckoutTest
TechnicianCannotReservePartsBeforeAuthorizationTest
StaffCannotAccessAnotherShopTest
```

## 43.3 Concurrency Tests

Must include actual database concurrency tests for:

- two requests reserving the last serialized unit;
- two requests reserving the last non-serialized quantity;
- checkout expiry racing payment confirmation;
- payment confirmation racing manual dispute creation.

## 43.4 Provider Contract Tests

Each gateway must pass the same contract suite:

```text
can initialize
returns provider reference
can verify
maps success
maps failure
maps ambiguous provider result
can refund
is idempotent under duplicate callback
```

## 43.5 Architectural Tests

Examples:

```text
Domain namespaces cannot import Illuminate\Http
Domain namespaces cannot import Eloquent models
Payments cannot import Sales Eloquent models
Sales cannot import Paystack client
Audit cannot be mutated after append
```

---

# 44. End-to-End Payment Scenarios

## Scenario A — Paystack / in-app payment

```text
Customer
  ↓
POST /customer/payments
  ↓
InitiatePayment
  ↓
PaymentTransaction = pending
  ↓
PaystackGateway.initialize()
  ↓
Customer completes payment
  ↓
Provider callback/webhook
  ↓
ProcessProviderWebhook
  ↓
verify signature + idempotency
  ↓
ConfirmPayment
  ↓
PaymentConfirmed
  ↓
Sales/Repair handler
  ↓
transaction completes business effect
```

## Scenario B — Bank transfer

```text
Customer selects bank transfer
  ↓
PaymentTransaction = payment_pending_confirmation
  ↓
Checkout/repair reservation held
  ↓
Customer submits transfer reference/proof
  ↓
Admin verification queue
  ↓
ConfirmPayment OR RejectPayment
```

## Scenario C — Disputed transfer

```text
Customer claims payment
  ↓
OpenPaymentDispute
  ↓
PaymentTransaction = disputed
  ↓
Proof retained
  ↓
Finance/admin review
  ↓
confirmed OR exception
```

## Scenario D — Late payment after checkout expiry

```text
Checkout = expired
Reservation = released
Late provider confirmation arrives
          ↓
SALE/PAY-BR-08
          ↓
Payment = exception / manual resolution
          ↓
Do NOT recreate sale automatically
```

The BLD explicitly requires this exception route. fileciteturn3file1

---

# 45. Frontend Payment UX State Model

The frontend should surface backend state directly rather than inventing a simpler boolean.

```ts
type PaymentStatus =
  | 'pending'
  | 'payment_pending_confirmation'
  | 'confirmed'
  | 'disputed'
  | 'refunded'
  | 'exception';
```

Customer UI examples:

```text
pending                         → “Waiting for payment”
payment_pending_confirmation   → “Transfer received — awaiting confirmation”
confirmed                      → “Payment confirmed”
disputed                       → “Payment under review”
exception                      → “Payment requires assistance”
```

Do not display “Paid” for `payment_pending_confirmation`.

---

# 46. Recommended Configuration Structure

Keep business-variable behavior in configuration tables/services rather than source-code constants.

Example `config/afprospos.php`:

```php
return [
    'payments' => [
        'checkout_validity_minutes' => env('CHECKOUT_VALIDITY_MINUTES', 15),
        'provider_mapping' => [
            'in_app' => env('PAYMENT_IN_APP_PROVIDER', 'paystack'),
            'pos_terminal' => env('PAYMENT_POS_PROVIDER', 'tranzix'),
            'bank_transfer' => 'manual',
        ],
    ],

    'offline' => [
        'enabled' => true,
    ],
];
```

Business-configurable periods such as return windows, collection deadlines and notification reminder schedules should ultimately be persisted at business/shop configuration level when they are expected to change without deployment.

---

# 47. Data Model → Code Mapping

The finalized data model maps cleanly to modules:

```text
Shared Kernel
  shops
  customers
  staff

Repair
  repair_jobs
  repair_diagnoses
  repair_parts_reservations

Collection
  collection_cases
  collection_case_events

Sales
  sales_checkouts
  sales_checkout_items
  sales_checkout_adjustments
  sales_sales

Inventory
  inventory_products
  inventory_skus
  inventory_items
  inventory_stock_levels
  inventory_transfers

Warranty
  warranty_policies
  warranty_claims
  return_requests
  trade_in_assessments

Payments
  payments_transactions

Commission
  commission_ledger_entries

Referral
  referrals

Marketing
  marketing_campaigns
  marketing_rewards
  marketing_store_credit_ledger_entries
  marketing_vouchers

Notifications
  notification_events
  notification_delivery_attempts

Audit
  audit_logs

Finance / Reporting
  expenses
  reporting_financial_ledger_entries

Sync
  sync_outbox_entries
  sync_conflicts
```

These table names and boundaries are taken from the finalized Data Model. fileciteturn0file2

---

# 48. Recommended Laravel Service Providers

Each bounded context should register its own bindings:

```php
final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PaymentTransactionRepository::class,
            EloquentPaymentTransactionRepository::class,
        );

        $this->app->bind(
            PaymentGatewayResolver::class,
            fn ($app) => new PaymentGatewayResolver(
                paystack: $app->make(PaystackGateway::class),
                tranzix: $app->make(TranzixGateway::class),
                manualBankTransfer: $app->make(ManualBankTransferGateway::class),
            )
        );
    }
}
```

Do the same for Inventory, Repair, Sales, Notifications, etc.

---

# 49. Deployment Architecture

The application uses path-based routing on a single domain. Admin and customer audiences are separated by URL prefix (`/admin/*` and `/customer/*`), loaded via `bootstrap/app.php`'s `then` callback. There is no subdomain split or separate static marketing site — all routes are served by the same Laravel monolith.

A practical first production deployment is:
```text

                                         DNS / CDN Edge (e.g., Cloudflare)
                                                       |
                                              afprospos.com
                                         -------------------------
                                          Laravel Monolith Server
                                          (Nginx + PHP-FPM)
                                                       |
                                         +-------------+-------------+
                                         |                           |
                                    /admin/*                   /customer/*
                               (Admin Inertia PWA)        (Customer Inertia PWA)
                                         |                           |
                                         +---------------------------+
                                                       |
                                               /webhooks/*
                                               /api/*
```
Add Redis when needed for:

- queue transport;
- cache;
- rate limiting;
- locks where appropriate.

Do not use Redis as the sole source of reservation truth. Inventory truth belongs in MySQL transaction boundaries.

---

# 49A. Database Connection Management

PHP-FPM does not keep a long-running application process the way Node.js or Java does — each request is served by a worker that boots Laravel fresh, including its MySQL connection, and tears down at the end of the request. There is no in-process memory for an application-level connection pool to live in between requests. This is not a defect to work around inside the application; it is a deployment-layer concern, and it is decided here rather than left to whoever configures the next environment.

```text
Phase 1–8 (current):

PHP-FPM worker ──connect──> MySQL ──close──> (next request repeats)


Trigger condition met (see below):

PHP-FPM worker ──connect──> ProxySQL ──pooled, reused──> MySQL
PHP-FPM worker ──connect──> ProxySQL ──pooled, reused──> MySQL
PHP-FPM worker ──connect──> ProxySQL ──pooled, reused──> MySQL
```

**PDO persistent connections (`PDO::ATTR_PERSISTENT`) are explicitly rejected**, not merely deferred. A persistent connection is reused across requests by the PHP-FPM worker itself, which means a connection can carry an uncommitted transaction, a stale advisory/row lock, or leftover session state from a previous request into the next one. §30 (Transaction and Locking Rules) and the reservation model (BLD §2.5) depend on every request beginning from a clean connection state; that guarantee is worth more than the connection-setup cost it would save.

**ProxySQL is the designated pooling layer, introduced on a specific trigger, not a vague "at scale."** Add it when either is observed in production monitoring (§42 Observability):

```text
[ ] MySQL Threads_connected regularly exceeds 70% of max_connections
[ ] Connection setup (not query execution) is a measurable share of request latency
```

Until then, standard per-request connections via PHP-FPM are correct and sufficient — introducing a pooling layer before there is a measured need adds an operational dependency with nothing to show for it.

ProxySQL is wire-protocol compatible with MySQL, so activating it is a configuration change, not an application change: point `DB_HOST`/`DB_PORT` at ProxySQL instead of MySQL directly.

```text
# Before
DB_HOST=mysql
DB_PORT=3306

# After ProxySQL is introduced
DB_HOST=proxysql
DB_PORT=6033
```

No business module, Repository, or Eloquent Record changes when this happens — the entire pooling layer sits below `config/database.php`.

**Laravel Octane** (Swoole/RoadRunner) is a related but materially larger decision, deliberately out of scope here: it keeps the whole application resident across requests, not just the DB connection, which means every container singleton and per-request binding — including `ActorContext` (§34/§35) — needs an explicit audit for cross-request state leakage before adoption. Do not adopt Octane as a side effect of a connection-pooling conversation; it earns its own architecture review pass if and when it comes up.

Do not enable `PDO::ATTR_PERSISTENT` as a quick fix if connection overhead becomes visible before the ProxySQL trigger is met — introduce ProxySQL early instead, even below the stated thresholds. The failure mode of a leaked lock is a data-integrity incident; the failure mode of "introduced pooling a bit early" is nothing.

# 50. Operational Resilience

## 50.1 Payment Provider Failure

If Paystack/Tranzix is unavailable:

- no domain state should falsely move to `confirmed`;
- customer sees an appropriate `pending`/retry message;
- provider failure is logged;
- retry policy applies where safe.

## 50.2 Notification Provider Failure

Business transaction remains successful. Notification is retried/failover processed asynchronously. This behavior is explicitly required in the BLD. fileciteturn1file4

## 50.3 Database Failure

Critical state transitions must be transactionally atomic. Do not acknowledge a successful business mutation until the transaction commits.

---

# 51. Architectural Invariants

These invariants should be treated as non-negotiable implementation rules.

### Inventory

```text
Available = On-hand - Reserved
Reserved <= On-hand
```

### Serialized inventory

```text
One physical unit
    → at most one active reservation
```

### Payment

```text
Only verified/authorized confirmation
    → payment.status = confirmed
```

### Bank transfer

```text
Pending confirmation
    ≠ confirmed
    ≠ unpaid
```

### Audit

```text
Audit entries are append-only.
```

### Commission

```text
Original earned entry is immutable.
Reversal = new entry.
```

### Reporting

```text
Reporting is a projection.
Operational modules remain authoritative.
```

### Notifications

```text
Notification failure cannot reverse a business transaction.
```

### RBAC

```text
Authorization is enforced server-side.
```

### Offline

```text
Offline request ≠ permission to bypass central consistency rules.
```

These invariants are directly traceable to the BLD and Data Model. fileciteturn1file9 fileciteturn3file0 fileciteturn3file7

---

# 52. Implementation Sequence

Recommended engineering order:

1. Laravel foundation, authentication, actor/shop context and Shared Kernel.
2. RBAC and authorization infrastructure.
3. Inventory + locking/reservation engine.
4. Sales checkout and sale conversion.
5. Payments bounded context + gateway strategy + webhook/idempotency.
6. Repair lifecycle + inventory part reservation.
7. Collection lifecycle.
8. Warranty/Returns/Trade-In.
9. Notifications/event/outbox infrastructure.
10. Commission and referral/marketing event handlers.
11. Finance/reporting projections.
12. Offline sync/conflict subsystem.
13. Advanced dashboards and reporting UX.
14. Hardening, concurrency testing and provider certification.

The order intentionally builds the consistency-critical primitives before feature breadth.

---

# 53. Architecture Review Checklist

Before any module is considered production-ready, verify:

```text
[ ] Domain logic contains no HTTP/Eloquent/provider dependencies
[ ] Application commands are explicit and testable
[ ] Repositories are interfaces in domain/application and implementations in infrastructure
[ ] Cross-module references use IDs/contracts, not cross-module ORM relationships
[ ] Payment providers are hidden behind PaymentGateway
[ ] Payment webhook handling is idempotent
[ ] Bank transfer pending confirmation is distinct from confirmed/unpaid
[ ] Disputes require human resolution
[ ] Inventory reservations use DB locking
[ ] Available is calculated, never stored
[ ] Serialized items cannot be double-reserved
[ ] Expired checkout payments enter exception handling
[ ] Audit records are append-only
[ ] Commission reversals are new ledger entries
[ ] Notification delivery is asynchronous
[ ] Notification failure cannot reverse business state
[ ] Offline sync is idempotent and conflict-preserving
[ ] RBAC is enforced server-side
[ ] Shop scope is validated server-side
[ ] Financial projections remain traceable to source transactions
[ ] Sensitive data is protected and excluded from logs
[ ] PDO persistent connections are not enabled anywhere in config/database.php
[ ] Concurrency, webhook replay, payment dispute and late-payment tests exist
```

---

# 54. Source Traceability Matrix

| Architecture decision | Source |
|---|---|
| DDD modular separation | BLD module prefixes + Data Model module boundaries |
| Shared Kernel `shops/customers/staff` | Data Model §0 |
| No cross-module FKs except Shared Kernel | Data Model conventions / §14 |
| MySQL 8 InnoDB + row locking | Data Model introduction |
| Money as BIGINT minor units | Data Model conventions |
| Serialized vs non-serialized inventory | BLD §2.3 |
| On-hand / Reserved / Available | BLD §2.1–2.5 |
| Atomic reservation | BLD §2.5; Sales/Inventory rules |
| Separate Payment state | BLD §2.4, §4.5; Data Model Payments |
| Bank-transfer pending confirmation | BLD PAY-BR-01 |
| Reservation hold during pending confirmation | BLD PAY-BR-02 |
| Human payment dispute resolution | BLD PAY-BR-03 |
| Late payment exception | BLD SALE/PAY-BR-08 |
| Append-only audit | BLD AUD-BR-01–06; Data Model Audit |
| Async notifications | BLD NOTIF-BR-01–13 |
| Notification provider abstraction | BLD NOTIF-BR-04 |
| Consolidated reporting projection | BLD FIN-BR-01–08; Data Model Reporting |
| Controlled offline-first | BLD OFF-BR-01–13 |
| Idempotent synchronization | BLD OFF-BR-05 |
| Multi-role/effective permission union | BLD RBAC-BR-01–09 |
| Historical role snapshots | BLD RBAC-BR-07–09 |
| Rate/price historical integrity | BLD FIN-BR-08 / Commission rules |
| Per-request MySQL connections; PDO persistent connections rejected; ProxySQL as the designated scaling lever | Architecture decision (§49A) — informed by §30 Transaction and Locking Rules and NFR Performance/Scalability; no direct BRD/BLD requirement |

---

# 55. Final Architecture Position

AfProsPos should be treated as a **business-critical modular transaction system**, not as a CRUD dashboard.

Its highest-risk technical areas are not page rendering or ordinary form handling. They are:

1. concurrent inventory allocation;
2. payment truth and asynchronous provider callbacks;
3. bank-transfer uncertainty and dispute resolution;
4. immutable financial/audit history;
5. module boundaries and cross-module referential integrity;
6. offline replay and conflict detection.

The architecture above isolates those concerns behind explicit aggregates, repositories, application commands, domain events, provider strategies, transaction boundaries and durable audit/projection records. That gives the development team a blueprint that can remain a single Laravel deployment while maintaining enough modular discipline to extract individual contexts later if the business actually needs that scale.

---

## Appendix A — Minimal Naming Standards

### PHP

```text
Classes: PascalCase
Methods: camelCase
Interfaces: BusinessCapability / noun form
Commands: VerbNoun
Queries: NounQuery
Handlers: CommandHandler / QueryHandler
Events: Past-tense business fact
```

Examples:

```text
InitiatePayment
ConfirmPayment
ResolvePaymentDispute
PaymentConfirmed
PaymentDisputed
AvailableStockQuery
```

### Database

Use the finalized table names exactly; do not introduce aliases such as `payments`, `sales`, `inventory` that destroy module clarity.

### React

```text
Pages/Admin/Payments/Disputes/Index.tsx
Pages/Customer/Payments/Show.tsx
Features/Payments/PaymentStatusBadge.tsx
Features/Sales/POSCart.tsx
Features/Inventory/SerializedUnitPicker.tsx
```

---

## Appendix B — Source Documents Used

1. **AfProsPos Business Requirements Document v1.0** — August 28, 2026.
2. **AfProsPos Business Logic Document v1.1** — September 1, 2026.
3. **AfProsPos Data Model Document v1.0** — September 2, 2026.

The BRD establishes the system's business scope and role model; the BLD resolves cross-module behavior and edge cases; the Data Model establishes storage boundaries, MySQL/InnoDB constraints, append-only structures, and cross-module referential integrity. fileciteturn1file8 fileciteturn1file0 fileciteturn0file2

