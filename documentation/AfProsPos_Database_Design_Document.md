# AfProsPos — Database Design Document

**Document:** Low-Level Database Design Document (DBDD)  
**System:** AfProsPos — Phone Sales, Repair & Business Management System  
**Architecture:** Domain-Driven Design (DDD) Modular Monolith  
**Application:** Laravel / PHP  
**Database:** MySQL 8.x, InnoDB  
**Primary DB role:** Transactional system of record  
**Source of truth:** AfProsPos Data Model v1.0, BRD v1.0, BLD v1.1  
**Status:** Development Blueprint  
**Date:** September 2, 2026

> This document translates the finalized AfProsPos data model and business rules into a low-level, migration-ready database specification. It does not replace the business logic or application architecture documents. It defines how their state, integrity, concurrency, historical-integrity, and module-boundary decisions are enforced at the database layer.

---

## 1. Database Design Objectives

The database shall provide:

1. **Strong transactional integrity** for sales, repairs, inventory allocation, payments, and financial records.
2. **Concurrency-safe inventory allocation** so the same serialized device or non-serialized stock quantity cannot be committed twice.
3. **Strict bounded-context boundaries** in a modular-monolith architecture.
4. **Historical integrity** for financial, audit, commission, and reporting records.
5. **Immutable ledger semantics** for append-only records.
6. **Offline synchronization support** using client-generated UUIDs and idempotent replay.
7. **Efficient shop-scoped and business-wide queries** without duplicating authoritative business data across modules.
8. **Application-level cross-module references** where database FKs would violate the DDD module boundary.

The database is not permitted to become a hidden integration layer between bounded contexts. A module may own a record and expose its identifier, but another module must not create a database-level FK into that module merely for convenience.

---

# 2. Global Database Architecture

## 2.1 Storage Engine

Every AfProsPos table shall use:

```sql
ENGINE=InnoDB
```

MySQL InnoDB is required because inventory reservation depends on transactional row locking. MyISAM is prohibited.

Primary reasons:

- ACID transactions.
- Row-level locking.
- Consistent reads.
- `SELECT ... FOR UPDATE`.
- Foreign-key support inside the Shared Kernel and within bounded contexts.
- Crash recovery and durability.

---

## 2.2 Character Set and Collation

Every table shall use:

```sql
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci
```

This is the database-wide convention from the finalized data model.

Migration rule:

```php
$table->charset = 'utf8mb4';
$table->collation = 'utf8mb4_unicode_ci';
```

The preferred implementation is to establish these defaults at the database connection/server level and explicitly preserve them for AfProsPos tables.

---

## 2.3 Money Representation

All monetary values are stored as **integer minor units**.

For the Nigerian operating currency:

```text
₦1.00 = 100 kobo
```

Examples:

| Business amount | Database |
|---|---:|
| ₦1.00 | `100` |
| ₦25,000.00 | `2,500,000` |
| ₦250,000.00 | `25,000,000` |

The schema therefore uses:

```sql
BIGINT
```

for money.

Do not use:

```text
FLOAT
DOUBLE
DECIMAL
```

for monetary amounts represented by the finalized model.

Percentages are different: `markup_percent` and commission rates remain `DECIMAL(5,2)` because they represent rates rather than monetary amounts.

---

# 3. Primary-Key Policy

## 3.1 Standard Server-Generated IDs

The default primary key is:

```sql
BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
```

This applies to normal online-created records.

Laravel:

```php
$table->id();
```

---

## 3.2 Offline-Creatable Entities

Any entity that can be created by the offline PWA must be identifiable before reaching the server.

The finalized data model specifies:

```sql
BINARY(16) PRIMARY KEY
```

containing a client-generated UUID.

This enables the synchronization API to identify replayed requests by primary key without generating duplicate rows.

Recommended application representation:

```text
PHP: Ramsey\Uuid\Uuid
Database: BINARY(16)
```

The database value is binary for index efficiency; conversion is performed at the application boundary.

---

# 4. Global Foreign-Key Policy

This is one of the most important architectural constraints.

## 4.1 Valid FK Classes

Foreign keys are allowed:

### A. Shared Kernel references

Examples:

```text
repair_jobs.shop_id -> shops.id
repair_jobs.customer_id -> customers.id
repair_jobs.technician_staff_id -> staff.id
```

### B. Intra-module relationships

Examples:

```text
repair_diagnoses.repair_job_id -> repair_jobs.id

sales_checkout_items.sales_checkout_id
    -> sales_checkouts.id

inventory_skus.product_id
    -> inventory_products.id
```

These are safe because the referenced entity belongs to the same bounded context or the explicitly shared kernel.

---

## 4.2 Forbidden Cross-Module FKs

Do not create:

```text
payments_transactions.payable_id -> sales_sales.id
commission_ledger_entries.source_id -> sales_sales.id
warranty_claims.originating_sale_id -> sales_sales.id
sales_checkout_items.inventory_item_id -> inventory_items.id
repair_parts_reservations.inventory_item_id -> inventory_items.id
```

The ID exists, but the relationship is intentionally application-owned.

Example:

```text
Sales
  sales_checkout_items.inventory_item_id = 90042

Inventory
  inventory_items.id = 90042
```

The database deliberately does not contain a FK between them.

---

## 4.3 Why This Rule Exists

The DDD modular-monolith design treats bounded contexts as independently owned modules even though they share one physical MySQL database.

Database FKs crossing module boundaries would create:

- hidden coupling;
- migration ordering requirements across modules;
- accidental ownership leakage;
- inability to evolve module schemas independently;
- database-enforced relationships the domain model does not actually own.

Therefore:

> Cross-module referential integrity is enforced by application services, domain policies, integration tests, and the no-hard-delete rule — not by MySQL FKs.

The finalized data model explicitly identifies this as an accepted tradeoff.

---

# 5. Transaction and Locking Strategy

## 5.1 Transaction Boundary Principle

A database transaction must surround one atomic business operation whose state must remain internally consistent.

Examples:

### Create checkout + reserve inventory

```text
BEGIN
  validate checkout
  lock inventory
  verify Available
  increment reservation
  create checkout
  create checkout item
COMMIT
```

### Confirm payment + consume reservation

```text
BEGIN
  lock payment
  verify payment state
  lock reservation
  convert reservation to consumption
  create sale
  update checkout
COMMIT
```

### Inter-shop transfer

```text
BEGIN
  lock inventory item / stock row
  verify transferable state
  move current location
  create transfer history
COMMIT
```

---

## 5.2 Required Row Locking

For quantity-based inventory:

```sql
SELECT *
FROM inventory_stock_levels
WHERE sku_id = ?
  AND shop_id = ?
FOR UPDATE;
```

This row is the serialization point for competing reservations.

The workflow is:

```text
SELECT ... FOR UPDATE
        ↓
read on_hand
read reserved
        ↓
available = on_hand - reserved
        ↓
if available < requested:
    ROLLBACK
else:
    reserved = reserved + requested
    version = version + 1
    COMMIT
```

The calculation is deliberately:

```text
Available = On-hand - Reserved
```

and **Available is not persisted as a database column**.

---

## 5.3 Serialized Inventory Locking

For IMEI-tracked inventory, lock the specific physical unit:

```sql
SELECT *
FROM inventory_items
WHERE id = ?
FOR UPDATE;
```

Then validate:

```text
status = available
current_shop_id = intended shop
reserved_by_id IS NULL
```

Only then may the record become:

```text
status = reserved
reserved_by_type = checkout|repair
reserved_by_id = source ID
```

---

## 5.4 Optimistic Version Column

The finalized schema also includes:

```sql
version INT DEFAULT 0
```

on reservation-sensitive inventory rows.

This provides a second guard against stale writes.

Pessimistic locking remains the primary consistency mechanism:

```text
SELECT ... FOR UPDATE
```

`version` is an additional optimistic-lock/concurrency signal and should be incremented on successful state mutation.

---

# 6. Isolation and Deadlock Policy

The normal application transaction isolation level should remain the MySQL/InnoDB default unless a specific operation has a documented reason to differ.

The application must assume deadlocks are possible under concurrency.

For retryable operations:

```text
BEGIN
  business operation
COMMIT
```

If MySQL reports a deadlock/serialization failure:

```text
ROLLBACK
retry bounded number of times
```

The retry count is an application configuration decision and is intentionally not fixed by the finalized business rules.

Transactions must be kept short. Do not perform:

- HTTP requests;
- payment-provider calls;
- WhatsApp requests;
- email requests;
- long-running filesystem work

while holding an inventory row lock.

---

# 7. Delete Policy

Soft deletes are not used for financially or operationally significant entities.

Do not add:

```php
$table->softDeletes();
```

to core operational tables.

Instead use explicit lifecycle fields such as:

```text
status = inactive
status = deactivated
status = cancelled
status = expired
status = anonymised
```

Historical records must remain addressable.

This is essential because cross-module IDs are not backed by cross-module FKs.

---

# 8. Immutability Model

The finalized data model defines the following append-only tables:

```text
commission_ledger_entries
marketing_store_credit_ledger_entries
audit_logs
reporting_financial_ledger_entries
```

They deliberately contain:

```text
created_at
```

but **no**:

```text
updated_at
```

This is a structural signal that these records are events/ledger entries rather than mutable entities.

Where a financial correction is required:

```text
BAD:
UPDATE commission_ledger_entries SET amount = ...

GOOD:
INSERT reversal/adjustment row
```

Likewise:

```text
BAD:
UPDATE audit_logs ...

GOOD:
INSERT new audit event
```

---

# 9. Shared Kernel Schema

## 9.1 `shops`

| Column | SQL Type | Null | Default | Constraint |
|---|---|---:|---|---|
| `id` | BIGINT UNSIGNED | NO | auto | PK |
| `name` | VARCHAR(255) | NO | — | |
| `sku_prefix_code` | VARCHAR(10) | NO | — | UNIQUE |
| `address` | VARCHAR | NO | — | source model leaves length unspecified |
| `contact_phone` | VARCHAR | NO | — | |
| `contact_email` | VARCHAR | NO | — | |
| `offline_policy` | JSON | NO | — | |
| `status` | ENUM('active','inactive') | NO | — | |
| `created_at` | TIMESTAMP | NO | current | |
| `updated_at` | TIMESTAMP | NO | current | |

**Indexes**

```text
PRIMARY KEY (id)
UNIQUE KEY shops_sku_prefix_code_unique (sku_prefix_code)
```

---

## 9.2 `customers`

| Column | SQL Type | Null | Default | Constraint |
|---|---|---:|---|---|
| `id` | BIGINT UNSIGNED | NO | auto | PK |
| `name` | VARCHAR | NO | — | |
| `phone` | VARCHAR | NO | — | UNIQUE |
| `email` | VARCHAR | NO | — | source model does not specify UNIQUE |
| `notification_preferences` | JSON | NO | — | |
| `marketing_opt_out` | BOOLEAN | NO | FALSE | |
| `status` | ENUM('active','anonymised') | NO | — | |
| `created_at` | TIMESTAMP | NO | current | |
| `updated_at` | TIMESTAMP | NO | current | |

The unique phone constraint is also used as one referral-fraud signal.

---

## 9.3 `staff`

| Column | SQL Type | Null | Default |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED | NO | auto |
| `name` | VARCHAR | NO | — |
| `phone` | VARCHAR | NO | — |
| `email` | VARCHAR | NO | — |
| `status` | ENUM('active','deactivated') | NO | — |
| `created_at` | TIMESTAMP | NO | current |
| `updated_at` | TIMESTAMP | NO | current |

---

# 10. RBAC Schema Ownership

RBAC is implemented using the standard `spatie/laravel-permission` schema:

```text
roles
permissions
model_has_roles
model_has_permissions
role_has_permissions
```

`staff` is the application model used by the package's polymorphic permission tables.

The database design does not duplicate role names inside business transactions. Historical role context is snapshotted where required, for example:

```text
commission_ledger_entries.role_snapshot
audit_logs.actor_role_snapshot
```

This prevents later RBAC changes from rewriting history.

---

# 11. Repair Context Schema

## 11.1 `repair_jobs`

| Column | SQL Type | Null | Default |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED | NO | auto |
| `shop_id` | BIGINT UNSIGNED | NO | — |
| `customer_id` | BIGINT UNSIGNED | NO | — |
| `device_make` | VARCHAR | NO | — |
| `device_model` | VARCHAR | NO | — |
| `technician_staff_id` | BIGINT UNSIGNED | YES | NULL |
| `labour_charge_minor` | BIGINT | NO | — |
| `down_payment_required_minor` | BIGINT | YES | NULL |
| `down_payment_deadline_at` | DATETIME | YES | NULL |
| `repair_status` | ENUM(...) | NO | — |
| `financial_status` | ENUM('unpaid','partially_paid','fully_paid') | NO | — |
| `estimated_collection_date` | DATE | YES | NULL |
| `unrepairable_settlement_state` | ENUM('n/a','pending_decision','refunded','retained') | YES | NULL |
| `created_at` | TIMESTAMP | NO | current |
| `updated_at` | TIMESTAMP | NO | current |

### `repair_status`

```text
received
diagnosing
awaiting_authorization
payment_overdue
expired_cancelled
awaiting_parts
in_progress
completed
unrepairable
failed_requires_resolution
```

### Shared Kernel FKs

```text
shop_id -> shops.id
customer_id -> customers.id
technician_staff_id -> staff.id
```

### Indexes

```sql
INDEX repair_jobs_shop_status_idx (shop_id, repair_status)
INDEX repair_jobs_customer_idx (customer_id)
```

---

## 11.2 `repair_diagnoses`

```text
id                       BIGINT UNSIGNED PK
repair_job_id            BIGINT UNSIGNED FK -> repair_jobs.id
component                VARCHAR
condition                ENUM(
                           working,
                           faulty,
                           not_tested,
                           unable_to_test
                         )
notes                    TEXT NULL
outcome                  ENUM(
                           repairable,
                           unrepairable,
                           requires_further_assessment
                         ) NULL
diagnosed_by_staff_id    BIGINT UNSIGNED FK -> staff.id
created_at               TIMESTAMP
```

Index:

```sql
INDEX repair_diagnoses_job_idx (repair_job_id)
```

The data model describes finalized diagnoses as immutable once finalized. The application must prevent mutation of a finalized diagnostic record.

---

## 11.3 `repair_parts_reservations`

```text
id                       BIGINT UNSIGNED PK
repair_job_id            BIGINT UNSIGNED FK -> repair_jobs.id
inventory_item_id        BIGINT UNSIGNED NULL   -- cross-module ID
sku_id                   BIGINT UNSIGNED         -- cross-module ID
quantity                 INT
status                   ENUM('reserved','installed','released')
created_at               TIMESTAMP
updated_at               TIMESTAMP
```

Important:

```text
inventory_item_id
sku_id
```

are **not FKs** because Inventory is another bounded context.

---

# 12. Collection Context Schema

## 12.1 `collection_cases`

```text
id                               BIGINT UNSIGNED PK
source_type                      ENUM('repair_job','warranty_claim')
source_id                        BIGINT UNSIGNED
shop_id                          BIGINT UNSIGNED FK -> shops.id
originating_shop_id              BIGINT UNSIGNED FK -> shops.id
context                          ENUM('ready_for_collection','ready_for_return')
status                           ENUM('pending','overdue','abandoned','resolved')
collection_deadline_at            DATETIME
abandonment_threshold_at          DATETIME NULL
storage_fee_policy_snapshot       JSON NULL
accrued_storage_fee_minor         BIGINT DEFAULT 0
shop_override_reason              TEXT NULL
created_at                        TIMESTAMP
updated_at                        TIMESTAMP
```

`source_id` is deliberately an ID-only reference.

`shop_id` is the physical holding/collection shop and is not required to equal `originating_shop_id`.

---

## 12.2 `collection_case_events`

```text
id                       BIGINT UNSIGNED PK
collection_case_id       BIGINT UNSIGNED FK
event_type               ENUM(
                           deadline_set,
                           notified,
                           extended,
                           fee_accrued,
                           marked_overdue,
                           marked_abandoned,
                           administrative_resolution
                         )
actor_staff_id           BIGINT UNSIGNED NULL
detail                   JSON
created_at               TIMESTAMP
```

---

# 13. Sales Context Schema

## 13.1 `sales_checkouts`

```text
id                       BIGINT UNSIGNED PK
shop_id                  BIGINT UNSIGNED FK -> shops.id
customer_id              BIGINT UNSIGNED NULL FK -> customers.id
cashier_staff_id         BIGINT UNSIGNED FK -> staff.id
status                   ENUM('open','paid','expired','cancelled')
reservation_expires_at   DATETIME
subtotal_minor           BIGINT
discount_minor           BIGINT
total_minor              BIGINT
payment_transaction_id   BIGINT UNSIGNED NULL   -- Payments ID only
created_at               TIMESTAMP
updated_at               TIMESTAMP
```

Primary operational index:

```sql
INDEX sales_checkouts_status_expiry_idx
    (status, reservation_expires_at)
```

This is the main index for the checkout-reservation expiry worker.

---

## 13.2 `sales_checkout_items`

```text
id                       BIGINT UNSIGNED PK
sales_checkout_id        BIGINT UNSIGNED FK
inventory_item_id        BIGINT UNSIGNED NULL   -- Inventory ID only
sku_id                   BIGINT UNSIGNED        -- Inventory ID only
quantity                 INT
unit_price_minor         BIGINT
warranty_policy_id       BIGINT UNSIGNED NULL   -- Warranty ID only
```

The selling price is snapshotted on the checkout item so later catalog price changes do not rewrite an existing transaction.

---

## 13.3 `sales_checkout_adjustments`

```text
id                       BIGINT UNSIGNED PK
sales_checkout_id        BIGINT UNSIGNED FK
type                     ENUM(
                           promotion,
                           referral_voucher,
                           trade_in_credit,
                           store_credit
                         )
source_id                BIGINT UNSIGNED
amount_minor             BIGINT
applied_order             TINYINT
```

The `applied_order` field supports deterministic stacking.

---

## 13.4 `sales_sales`

This is a finalized sale, separate from the mutable checkout.

```text
id                       BIGINT UNSIGNED PK
sales_checkout_id        BIGINT UNSIGNED FK -> sales_checkouts.id
shop_id                  BIGINT UNSIGNED FK -> shops.id
customer_id              BIGINT UNSIGNED NULL FK -> customers.id
cashier_staff_id         BIGINT UNSIGNED FK -> staff.id
total_minor              BIGINT
payment_transaction_id   BIGINT UNSIGNED     -- Payments ID only
invoice_number           VARCHAR(50) UNIQUE
created_at               TIMESTAMP
```

There is deliberately no `updated_at`.

Index:

```sql
INDEX sales_sales_shop_created_idx (shop_id, created_at)
```

---

# 14. Inventory Context Schema

## 14.1 `inventory_products`

```text
id            BIGINT UNSIGNED PK
brand         VARCHAR
model         VARCHAR
category      VARCHAR
created_at    TIMESTAMP
updated_at    TIMESTAMP
```

The centralized product catalog is distinct from shop stock.

---

## 14.2 `inventory_skus`

```text
id                          BIGINT UNSIGNED PK
product_id                  BIGINT UNSIGNED FK -> inventory_products.id
sku_code                    VARCHAR(50) UNIQUE
attributes                   JSON
is_serialized                BOOLEAN
cost_price_minor             BIGINT
markup_percent               DECIMAL(5,2)
selling_price_minor          BIGINT
selling_price_overridden     BOOLEAN DEFAULT FALSE
low_stock_threshold          INT NULL
created_at                   TIMESTAMP
updated_at                   TIMESTAMP
```

The SKU is business-wide unique according to the finalized model.

Format:

```text
[ShopCode]-[CategoryCode]-[UniqueSequence]
```

Example:

```text
LGS-PHN-000482
```

---

## 14.3 `inventory_items`

This is the serialized inventory table.

```text
id                 BIGINT UNSIGNED PK
sku_id             BIGINT UNSIGNED FK -> inventory_skus.id
imei               VARCHAR(20) UNIQUE
current_shop_id    BIGINT UNSIGNED FK -> shops.id
condition          ENUM(
                     new,
                     used_grade_a,
                     used_grade_b,
                     used_grade_c,
                     refurbished
                   )
status             ENUM(
                     available,
                     reserved,
                     sold,
                     transferring
                   )
reserved_by_type   ENUM('repair','checkout') NULL
reserved_by_id     BIGINT UNSIGNED NULL
version            INT DEFAULT 0
created_at         TIMESTAMP
updated_at         TIMESTAMP
```

### Critical constraints

```sql
UNIQUE KEY inventory_items_imei_unique (imei)
INDEX inventory_items_shop_status_idx (current_shop_id, status)
```

IMEI is globally unique across the business, not merely unique within a shop.

---

## 14.4 `inventory_stock_levels`

Non-serialized stock.

```text
id           BIGINT UNSIGNED PK
sku_id       BIGINT UNSIGNED FK -> inventory_skus.id
shop_id      BIGINT UNSIGNED FK -> shops.id
on_hand      INT UNSIGNED
reserved     INT UNSIGNED
version      INT DEFAULT 0
```

Constraint:

```sql
UNIQUE KEY inventory_stock_levels_sku_shop_unique
    (sku_id, shop_id)
```

Critical rule:

```text
Available = on_hand - reserved
```

There is no:

```text
available
```

column.

The `(sku_id, shop_id)` row is the row locked by reservation operations.

---

## 14.5 `inventory_transfers`

```text
id                       BIGINT UNSIGNED PK
sku_id                   BIGINT UNSIGNED FK -> inventory_skus.id
inventory_item_id        BIGINT UNSIGNED NULL FK -> inventory_items.id
quantity                 INT NULL
from_shop_id             BIGINT UNSIGNED FK -> shops.id
to_shop_id               BIGINT UNSIGNED FK -> shops.id
initiated_by_staff_id    BIGINT UNSIGNED FK -> staff.id
received_by_staff_id     BIGINT UNSIGNED NULL FK -> staff.id
status                   ENUM('in_transit','completed','cancelled')
created_at               TIMESTAMP
updated_at               TIMESTAMP
```

For serialized transfers, `inventory_item_id` is populated.

For bulk/non-serialized transfers, `quantity` is populated.

---

# 15. Warranty / Returns / Trade-In Context

## 15.1 `warranty_policies`

```text
id                         BIGINT UNSIGNED PK
name                       VARCHAR
coverage_duration_days     INT
coverage_start_point       ENUM('sale_date','collection_date')
covered_scope              JSON
exclusions                 JSON
available_remedies         JSON
coverage_extent            ENUM(
                             full,
                             percentage,
                             fixed_amount,
                             labour_only,
                             parts_only
                           )
created_at                 TIMESTAMP
updated_at                 TIMESTAMP
```

---

## 15.2 `warranty_claims`

```text
id                         BIGINT UNSIGNED PK
warranty_policy_id         BIGINT UNSIGNED FK -> warranty_policies.id
originating_sale_id        BIGINT UNSIGNED NULL     -- Sales ID only
originating_repair_job_id  BIGINT UNSIGNED NULL     -- Repair ID only
customer_id                BIGINT UNSIGNED FK -> customers.id
inventory_item_id          BIGINT UNSIGNED NULL     -- Inventory ID only
resolution_state           ENUM(
                             submitted,
                             under_assessment,
                             eligible,
                             not_eligible,
                             remedy_selected,
                             resolved
                           )
assessment_notes            TEXT NULL
selected_remedy            ENUM(
                             repair,
                             replace,
                             refund,
                             exchange
                           ) NULL
remedy_reference_id        BIGINT UNSIGNED NULL
created_at                 TIMESTAMP
updated_at                 TIMESTAMP
```

There is no cross-module FK on the originating/remedy references.

---

## 15.3 `return_requests`

```text
id                           BIGINT UNSIGNED PK
sale_id                      BIGINT UNSIGNED
customer_id                  BIGINT UNSIGNED FK -> customers.id
resolution_state             ENUM(
                               requested,
                               under_assessment,
                               approved,
                               denied,
                               resolved
                             )
return_window_expires_at     DATETIME
denial_reason                VARCHAR NULL
override_approved_by_staff_id BIGINT UNSIGNED NULL
created_at                   TIMESTAMP
updated_at                   TIMESTAMP
```

The return window is snapshotted; it is not recalculated later from a mutable policy.

---

## 15.4 `trade_in_assessments`

```text
id                       BIGINT UNSIGNED PK
customer_id              BIGINT UNSIGNED FK -> customers.id
related_checkout_id      BIGINT UNSIGNED NULL
device_description       JSON
assessed_value_minor     BIGINT
resolution_state         ENUM(
                           submitted,
                           assessed,
                           approved,
                           rejected,
                           applied
                         )
approved_by_staff_id     BIGINT UNSIGNED NULL
created_at               TIMESTAMP
updated_at               TIMESTAMP
```

`related_checkout_id` is a Sales-module ID reference without a cross-module FK.

---

# 16. Payments Context

## 16.1 `payments_transactions`

Payments is a standalone bounded context.

```text
id                          BIGINT UNSIGNED PK
payable_type                ENUM(
                              sales_checkout,
                              repair_job,
                              warranty_claim,
                              return_request
                            )
payable_id                  BIGINT UNSIGNED
method                      ENUM(
                              cash,
                              pos_terminal,
                              bank_transfer,
                              in_app
                            )
amount_minor                BIGINT
status                      ENUM(
                              pending,
                              payment_pending_confirmation,
                              confirmed,
                              disputed,
                              refunded,
                              exception
                            )
provider_reference           VARCHAR NULL
confirmed_by_staff_id       BIGINT UNSIGNED NULL
dispute_opened_at           DATETIME NULL
dispute_proof_reference     VARCHAR NULL
dispute_resolved_by_staff_id BIGINT UNSIGNED NULL
created_at                  TIMESTAMP
updated_at                  TIMESTAMP
```

Primary lookup:

```sql
INDEX payments_transactions_payable_idx
    (payable_type, payable_id)
```

This allows Sales/Repair/Warranty code to ask:

```text
Find payments belonging to my business object
```

without creating a foreign-key relationship.

---

## 16.2 Payment State Semantics

The database must distinguish:

```text
pending
payment_pending_confirmation
confirmed
disputed
refunded
exception
```

A payment provider callback must be idempotent.

The application must not change an already-confirmed payment to a different terminal state simply because a duplicate provider callback arrived.

Database uniqueness for provider references is deliberately not imposed globally by the source model because a provider-specific reference strategy can differ between gateways. Provider reconciliation rules remain application logic unless a future migration introduces a provider+reference invariant.

---

# 17. Commission Context

## 17.1 `commission_ledger_entries`

Append-only.

```text
id                           BIGINT UNSIGNED PK
staff_id                     BIGINT UNSIGNED FK -> staff.id
source_type                  ENUM('sale','repair')
source_id                    BIGINT UNSIGNED
commissionable_amount_minor  BIGINT
rate_snapshot_percent        DECIMAL(5,2)
role_snapshot                VARCHAR
entry_type                   ENUM('earned','reversal','adjustment')
reversal_of_entry_id         BIGINT UNSIGNED NULL FK -> commission_ledger_entries.id
status                       ENUM('earned','paid')
shop_id                      BIGINT UNSIGNED FK -> shops.id
created_at                   TIMESTAMP
```

No `updated_at`.

A reversal is represented by inserting a new record.

Historical rate and role values are snapshotted directly into the row.

Important index candidates:

```sql
INDEX commission_entries_staff_created_idx (staff_id, created_at)
INDEX commission_entries_shop_created_idx (shop_id, created_at)
INDEX commission_entries_source_idx (source_type, source_id)
```

---

# 18. Referral Context

## 18.1 `referrals`

```text
id                         BIGINT UNSIGNED PK
referrer_customer_id       BIGINT UNSIGNED FK -> customers.id
referred_customer_id       BIGINT UNSIGNED FK -> customers.id UNIQUE
referral_code              VARCHAR(20) UNIQUE
qualifying_transaction_type ENUM('sale','repair') NULL
qualifying_transaction_id  BIGINT UNSIGNED NULL
status                     ENUM(
                             pending,
                             qualified,
                             fraud_flagged,
                             fraud_rejected
                           )
reward_id                  BIGINT UNSIGNED NULL
created_at                 TIMESTAMP
updated_at                 TIMESTAMP
```

Fraud comparison against:

```text
customers.phone
payments_transactions.provider_reference
inventory_items.imei
```

is a query-time/application decision, not a dedicated fraud table.

---

# 19. Marketing Context

## 19.1 `marketing_campaigns`

```text
id                    BIGINT UNSIGNED PK
name                  VARCHAR
type                  ENUM('discount','referral_bonus','loyalty')
reward_type           ENUM('store_credit','voucher')
eligibility_rules     JSON
stacking_allowed      BOOLEAN DEFAULT FALSE
valid_from            DATE
valid_until           DATE
created_at            TIMESTAMP
updated_at            TIMESTAMP
```

Eligibility is evaluated dynamically against source data.

Do not create a duplicated customer-segment table merely to make marketing queries convenient unless a later scaling design explicitly introduces a materialized projection.

---

## 19.2 `marketing_rewards`

```text
id                    BIGINT UNSIGNED PK
campaign_id           BIGINT UNSIGNED FK -> marketing_campaigns.id
customer_id           BIGINT UNSIGNED FK -> customers.id
instrument_type       ENUM('store_credit','voucher')
instrument_id         BIGINT UNSIGNED
eligibility_snapshot  JSON
status                ENUM(
                       pending,
                       payable,
                       issued,
                       cancelled
                     )
created_at            TIMESTAMP
updated_at            TIMESTAMP
```

`instrument_id` is an ID-only reference to the specific reward instrument.

---

## 19.3 `marketing_store_credit_ledger_entries`

Append-only.

```text
id                              BIGINT UNSIGNED PK
customer_id                    BIGINT UNSIGNED FK -> customers.id
reward_id                      BIGINT UNSIGNED NULL FK -> marketing_rewards.id
entry_type                     ENUM(
                                  issuance,
                                  usage,
                                  adjustment,
                                  expiration,
                                  withdrawal
                                )
amount_minor                   BIGINT
withdrawal_approved_by_staff_id BIGINT UNSIGNED NULL
created_at                     TIMESTAMP
```

No `updated_at`.

---

## 19.4 `marketing_vouchers`

```text
id                   BIGINT UNSIGNED PK
customer_id          BIGINT UNSIGNED FK -> customers.id
reward_id            BIGINT UNSIGNED NULL FK -> marketing_rewards.id
code                 VARCHAR(30) UNIQUE
discount_type        ENUM('flat','percentage')
value                DECIMAL(10,2)
max_discount_minor   BIGINT NULL
usage_limit          INT DEFAULT 1
used_count           INT DEFAULT 0
valid_until          DATE
created_at           TIMESTAMP
updated_at           TIMESTAMP
```

---

# 20. Notification Context

## 20.1 `notification_events`

```text
id              BIGINT UNSIGNED PK
event_type      VARCHAR
source_module   VARCHAR
source_id       BIGINT UNSIGNED
recipient_type  ENUM('customer','staff')
recipient_id    BIGINT UNSIGNED
category        ENUM(
                  transactional,
                  operational,
                  security,
                  marketing
                )
status          ENUM(
                  queued,
                  delivered,
                  failed_exhausted
                )
created_at      TIMESTAMP
```

`source_id` and `recipient_id` are IDs resolved by application services according to module/recipient type.

---

## 20.2 `notification_delivery_attempts`

```text
id                    BIGINT UNSIGNED PK
notification_event_id BIGINT UNSIGNED FK
channel               ENUM('whatsapp','email','in_app','sms')
provider              VARCHAR
attempt_number        INT
status                ENUM(
                        sent,
                        delivered,
                        failed_transient,
                        failed_permanent
                      )
provider_response     TEXT NULL
created_at            TIMESTAMP
```

Recommended index:

```sql
INDEX notification_attempts_event_idx
    (notification_event_id, attempt_number)
```

The provider string allows provider abstraction at the database level, so an email delivery can identify `resend` or `smtp` without changing the schema.

---

# 21. Unified Audit Context

## 21.1 `audit_logs`

Append-only.

```text
id                    BIGINT UNSIGNED PK
module                VARCHAR
event_type            VARCHAR
actor_staff_id        BIGINT UNSIGNED NULL
actor_role_snapshot   VARCHAR NULL
subject_type          VARCHAR
subject_id            BIGINT UNSIGNED
before_state          JSON NULL
after_state           JSON NULL
context               JSON NULL
created_at            TIMESTAMP
```

No `updated_at`.

Required history index:

```sql
INDEX audit_logs_subject_history_idx
    (subject_type, subject_id, created_at)
```

This supports:

```text
SELECT *
FROM audit_logs
WHERE subject_type = ?
  AND subject_id = ?
ORDER BY created_at;
```

---

# 22. Reporting and Financial Projection

## 22.1 `reporting_financial_ledger_entries`

Append-only projection.

```text
id              BIGINT UNSIGNED PK
shop_id         BIGINT UNSIGNED FK -> shops.id
entry_category  ENUM(
                  revenue,
                  cogs,
                  commission,
                  expense,
                  refund,
                  repair_income
                )
source_module   VARCHAR
source_id       BIGINT UNSIGNED
amount_minor    BIGINT
occurred_at     DATETIME
created_at      TIMESTAMP
```

No `updated_at`.

The table is a reporting projection, not the authoritative source of the originating financial event.

The authoritative source remains in the owning module.

---

## 22.2 `expenses`

```text
id                   BIGINT UNSIGNED PK
shop_id              BIGINT UNSIGNED NULL FK -> shops.id
category             VARCHAR
amount_minor         BIGINT
allocation            JSON NULL
recorded_by_staff_id BIGINT UNSIGNED FK -> staff.id
created_at            TIMESTAMP
updated_at            TIMESTAMP
```

A `NULL` `shop_id` means business-wide expense.

The allocation JSON is used when the shared expense is allocated across multiple shops.

---

# 23. Offline Synchronization Schema

## 23.1 `sync_outbox_entries`

This table is one of the explicit UUID-backed persistence points.

```text
id                  BINARY(16) PK
entity_type         VARCHAR
entity_id           BINARY(16)
payload             JSON
status              ENUM(
                      pending,
                      syncing,
                      synced,
                      failed,
                      conflict
                    )
shop_id             BIGINT UNSIGNED FK -> shops.id
created_locally_at  DATETIME
synced_at           DATETIME NULL
```

Primary key:

```sql
PRIMARY KEY (id)
```

Because `id` originates from the client:

```text
retry request
   ↓
same UUID
   ↓
same row identity
   ↓
duplicate effect prevented
```

---

## 23.2 `sync_conflicts`

```text
id                       BIGINT UNSIGNED PK
sync_outbox_entry_id     BINARY(16) FK -> sync_outbox_entries.id
conflict_reason          VARCHAR
local_state              JSON
server_state             JSON
resolution_status        ENUM(
                           unresolved,
                           resolved_kept_local,
                           resolved_kept_server,
                           resolved_merged
                         )
resolved_by_staff_id     BIGINT UNSIGNED NULL
created_at               TIMESTAMP
updated_at               TIMESTAMP
```

Both local and server state must remain preserved.

---

# 24. Indexing Strategy

## 24.1 Principles

Indexes shall be based on real query patterns rather than adding an index to every column.

Every index should justify:

```text
What query uses it?
What ordering does it avoid?
What lock lookup does it accelerate?
```

---

## 24.2 Critical Indexes

### Inventory

```sql
UNIQUE inventory_items(imei)

INDEX inventory_items(current_shop_id, status)

UNIQUE inventory_stock_levels(sku_id, shop_id)
```

### Sales

```sql
INDEX sales_checkouts(status, reservation_expires_at)

INDEX sales_sales(shop_id, created_at)
```

### Repairs

```sql
INDEX repair_jobs(shop_id, repair_status)

INDEX repair_jobs(customer_id)

INDEX repair_diagnoses(repair_job_id)
```

### Payments

```sql
INDEX payments_transactions(payable_type, payable_id)
```

### Audit

```sql
INDEX audit_logs(subject_type, subject_id, created_at)
```

### Notifications

```sql
INDEX notification_delivery_attempts(
    notification_event_id,
    attempt_number
)
```

### Commission

Recommended:

```sql
INDEX commission_ledger_entries(staff_id, created_at)
INDEX commission_ledger_entries(shop_id, created_at)
INDEX commission_ledger_entries(source_type, source_id)
```

### Referral

```sql
UNIQUE referrals(referred_customer_id)
UNIQUE referrals(referral_code)
```

### Marketing

```sql
UNIQUE marketing_vouchers(code)
```

---

# 25. Reservation Performance Design

The reservation operation is the highest-concurrency database path in the system.

## 25.1 Non-Serialized Reservation

Target row:

```sql
inventory_stock_levels
WHERE sku_id = ?
AND shop_id = ?
```

Lock:

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

if available >= quantity:
    reserved += quantity
else:
    fail
```

The entire sequence occurs in one transaction.

---

## 25.2 Serialized Reservation

Target row:

```sql
inventory_items
WHERE id = ?
FOR UPDATE
```

The application then verifies:

```text
status == available
current_shop_id == requested shop
reserved_by_id IS NULL
```

and sets:

```text
status = reserved
reserved_by_type = checkout|repair
reserved_by_id = source ID
version = version + 1
```

---

# 26. Checkout Expiration

The checkout expiry worker uses:

```sql
SELECT id
FROM sales_checkouts
WHERE status = 'open'
  AND reservation_expires_at <= NOW()
ORDER BY reservation_expires_at
LIMIT ?;
```

The composite index:

```sql
(status, reservation_expires_at)
```

is specifically designed for this query.

For every expired checkout:

```text
BEGIN
  lock checkout
  verify still open
  release inventory reservation
  mark expired
COMMIT
```

The expiry worker must be idempotent.

---

# 27. Inventory Reconciliation Invariants

These invariants should be validated with automated consistency tests and periodic reconciliation commands.

## 27.1 Non-Serialized

Must always satisfy:

```text
0 <= reserved <= on_hand
```

and therefore:

```text
available >= 0
```

---

## 27.2 Serialized

For every serialized item:

```text
status = reserved
    => reserved_by_type IS NOT NULL
    AND reserved_by_id IS NOT NULL
```

and:

```text
status != reserved
    => reserved_by_id IS NULL
```

The application must maintain these invariants.

---

# 28. Historical Integrity

Configuration can change:

```text
commission rates
selling prices
return windows
storage fee rules
warranty policies
roles
permissions
```

Past records must not change because the current configuration changed.

Therefore snapshot fields exist where required:

```text
commission.rate_snapshot_percent
commission.role_snapshot
collection.storage_fee_policy_snapshot
sales_checkout_items.unit_price_minor
return_requests.return_window_expires_at
marketing_rewards.eligibility_snapshot
```

The database should not attempt to recompute those values from current configuration.

---

# 29. Application-Level Referential Integrity

Because cross-module FKs are forbidden, the application must provide explicit integrity checks.

Examples:

### Commission source

```text
source_type = sale
source_id must resolve to Sales.sales_sales.id
```

### Payment payable

```text
payable_type = repair_job
payable_id must resolve to Repair.repair_jobs.id
```

### Warranty source

```text
originating_sale_id must resolve to Sales
```

Recommended automated validation jobs:

```text
php artisan system:integrity-check cross-module
php artisan system:integrity-check inventory
php artisan system:integrity-check ledgers
```

These should report, never silently repair, orphaned records.

---

# 30. Migration Ordering Strategy

Migrations must respect dependencies inside the Shared Kernel and each bounded context.

Recommended order:

```text
001_shared_shops
002_shared_customers
003_shared_staff
004_rbac_permissions

010_inventory_products
011_inventory_skus
012_inventory_items
013_inventory_stock_levels
014_inventory_transfers

020_repairs
021_repair_diagnoses
022_repair_parts_reservations
023_collection_cases
024_collection_case_events

030_sales_checkouts
031_sales_checkout_items
032_sales_checkout_adjustments
033_sales_sales

040_warranty_policies
041_warranty_claims
042_return_requests
043_trade_in_assessments

050_payments_transactions

060_commission_ledger_entries
070_referrals

080_marketing_campaigns
081_marketing_rewards
082_marketing_store_credit_ledger_entries
083_marketing_vouchers

090_notification_events
091_notification_delivery_attempts

100_audit_logs

110_reporting_financial_ledger_entries
111_expenses

120_sync_outbox_entries
121_sync_conflicts
```

The exact filename timestamp prefixes may differ, but dependency ordering should remain.

---

# 31. Laravel Migration Conventions

Use one table per migration where practical.

Every migration should explicitly define:

```php
$table->engine = 'InnoDB';
$table->charset = 'utf8mb4';
$table->collation = 'utf8mb4_unicode_ci';
```

Foreign keys should use explicit names for critical structures where migrations may be reordered.

Do not use:

```php
$table->foreignId(...)->constrained();
```

for cross-module IDs.

For example:

```php
$table->unsignedBigInteger('inventory_item_id')->nullable();
```

not:

```php
$table->foreignId('inventory_item_id')
      ->constrained('inventory_items');
```

inside Sales or Repair.

---

# 32. Migration Example 1 — Serialized Inventory

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->unsignedBigInteger('sku_id');

            $table->string('imei', 20)->unique();

            // Shared Kernel FK: valid database relationship.
            $table->foreignId('current_shop_id')
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->enum('condition', [
                'new',
                'used_grade_a',
                'used_grade_b',
                'used_grade_c',
                'refurbished',
            ]);

            $table->enum('status', [
                'available',
                'reserved',
                'sold',
                'transferring',
            ]);

            $table->enum('reserved_by_type', [
                'repair',
                'checkout',
            ])->nullable();

            /*
             * Application-level cross-module ID.
             *
             * Deliberately NOT a foreign key because this column may point
             * into Repair or Sales depending on reserved_by_type.
             */
            $table->unsignedBigInteger('reserved_by_id')->nullable();

            // Secondary concurrency guard.
            $table->unsignedInteger('version')->default(0);

            $table->timestamps();

            $table->foreign('sku_id')
                ->references('id')
                ->on('inventory_skus')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->index(
                ['current_shop_id', 'status'],
                'inventory_items_shop_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
```

### Why this migration is correct

The key distinction is:

```php
$table->foreignId('current_shop_id')->constrained('shops');
```

is allowed because `shops` is Shared Kernel.

But:

```php
reserved_by_id
```

is not a FK because it can reference either:

```text
repair_jobs.id
sales_checkouts.id
```

depending on:

```text
reserved_by_type
```

---

# 33. Migration Example 2 — Non-Serialized Reservation Row

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_levels', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('sku_id')
                ->constrained('inventory_skus')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('shop_id')
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->unsignedInteger('on_hand');
            $table->unsignedInteger('reserved');

            $table->unsignedInteger('version')->default(0);

            $table->unique(
                ['sku_id', 'shop_id'],
                'inventory_stock_levels_sku_shop_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_levels');
    }
};
```

The database intentionally has no:

```text
available
```

column.

The application/query layer derives:

```sql
(on_hand - reserved)
```

while the row itself is locked with:

```sql
SELECT ...
FOR UPDATE
```

for allocation operations.

---

# 34. Migration Example 3 — Append-Only Commission Ledger

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_ledger_entries', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('staff_id')
                ->constrained('staff')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->enum('source_type', [
                'sale',
                'repair',
            ]);

            // Cross-module source ID — intentionally no FK.
            $table->unsignedBigInteger('source_id');

            $table->bigInteger('commissionable_amount_minor');

            $table->decimal('rate_snapshot_percent', 5, 2);

            $table->string('role_snapshot');

            $table->enum('entry_type', [
                'earned',
                'reversal',
                'adjustment',
            ]);

            $table->unsignedBigInteger('reversal_of_entry_id')
                ->nullable();

            $table->enum('status', [
                'earned',
                'paid',
            ]);

            $table->foreignId('shop_id')
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            /*
             * IMPORTANT:
             *
             * Do NOT call $table->timestamps().
             *
             * This table is append-only and deliberately has no
             * updated_at column.
             */
            $table->timestamp('created_at')
                ->useCurrent();

            $table->foreign('reversal_of_entry_id')
                ->references('id')
                ->on('commission_ledger_entries')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->index(
                ['staff_id', 'created_at'],
                'commission_entries_staff_created_idx'
            );

            $table->index(
                ['shop_id', 'created_at'],
                'commission_entries_shop_created_idx'
            );

            $table->index(
                ['source_type', 'source_id'],
                'commission_entries_source_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_ledger_entries');
    }
};
```

---

# 35. Structural Immutability and Database Privileges

Omitting `updated_at` is the required schema-level signal from the finalized model, but it is not by itself a complete SQL security barrier.

For production database privileges, the preferred operational model is:

```text
Application DB user:
    SELECT
    INSERT
    UPDATE
    DELETE (where allowed by application policy)

Ledger tables:
    SELECT
    INSERT
```

If operational database permissions permit it, application infrastructure may enforce:

```text
INSERT only
```

for:

```text
commission_ledger_entries
audit_logs
marketing_store_credit_ledger_entries
reporting_financial_ledger_entries
```

For environments where schema-level triggers/privileges are used, they should reject updates/deletes against append-only ledgers.

The source-of-truth architectural rule remains:

> Corrections are represented as new entries, never mutations of the historical ledger entry.

---

# 36. Payment Database Lifecycle

The Payments table is intentionally designed as a state machine.

Typical sequence:

```text
pending
   ↓
payment_pending_confirmation
   ↓
confirmed
```

Bank-transfer exception:

```text
pending
   ↓
payment_pending_confirmation
   ↓
disputed
   ↓
confirmed
```

Alternative:

```text
disputed
   ↓
exception
```

Refund path:

```text
confirmed
   ↓
refunded
```

A payment provider callback must:

1. locate the payment;
2. lock the payment row;
3. verify the provider reference;
4. evaluate the current status;
5. apply an allowed transition;
6. commit atomically;
7. emit downstream events after the transaction succeeds.

Do not allow a webhook request to directly mutate Sales or Repair tables outside their own application service.

---

# 37. Database Responsibility vs Application Responsibility

## Database owns

```text
primary keys
unique keys
allowed FK relationships
basic data types
nullability
default values
row-level concurrency
transaction atomicity
immutable-table structure
```

## Application owns

```text
business state-transition rules
cross-module references
payment-provider semantics
commission calculations
discount stacking
referral fraud detection
notification routing
offline conflict resolution
authorization
collection policies
warranty eligibility
```

This boundary is deliberate.

---

# 38. Query Design Standards

Repositories/query services should avoid generic:

```sql
SELECT *
```

for high-throughput paths.

Prefer projections:

```sql
SELECT id, sku_id, status, current_shop_id
FROM inventory_items
...
```

Use dedicated read queries for dashboards and reporting.

Avoid loading entire transaction graphs in POS requests.

Examples:

```text
CheckoutRepository
InventoryAvailabilityQuery
PaymentTransactionQuery
RepairCustomerHistoryQuery
CommissionReportQuery
AuditHistoryQuery
```

This aligns the database access layer with the DDD module boundaries.

---

# 39. Reporting Read Strategy

Transactional tables remain authoritative.

The consolidated financial reporting projection:

```text
reporting_financial_ledger_entries
```

exists to make cross-source financial queries predictable.

The projection records:

```text
source_module
source_id
occurred_at
amount_minor
entry_category
shop_id
```

Therefore:

```text
Dashboard/report
    ↓
Reporting query
    ↓
reporting_financial_ledger_entries
    ↓
source_module/source_id
    ↓
authoritative transaction
```

This preserves traceability while preventing reporting queries from becoming multi-module transactional joins.

---

# 40. Integrity Test Suite

The database implementation should include automated tests for at least the following invariants.

## Inventory

```text
reserved <= on_hand
available >= 0
same IMEI cannot appear twice
same stock SKU/shop cannot appear twice
reserved serialized item has a source
reserved item cannot be transferred
```

## Payments

```text
confirmed payment cannot be confirmed twice
refund cannot exceed confirmed amount
provider callback is idempotent
```

## Sales

```text
paid checkout cannot expire
expired checkout cannot become paid
```

## Commission

```text
ledger entries cannot be edited
reversal creates a new row
rate snapshot remains unchanged
```

## Audit

```text
audit history is append-only
```

## Sync

```text
same client UUID cannot create duplicate entity effects
conflict retains both local and server states
```

---

# 41. Reconciliation Jobs

Recommended scheduled commands:

```bash
php artisan system:integrity-check inventory
php artisan system:integrity-check payments
php artisan system:integrity-check ledgers
php artisan system:integrity-check cross-module
php artisan system:integrity-check sync
```

The result should be a report containing:

```text
check name
records scanned
violations found
violation identifiers
severity
recommended action
```

The command must not silently modify financial or audit history.

---

# 42. Operational Database Monitoring

Monitor at least:

```text
deadlocks
lock wait time
slow queries
buffer pool utilization
table/index growth
failed transactions
failed foreign-key operations
payment lookup latency
checkout expiry backlog
sync backlog
notification backlog
```

The most important hot paths are expected to be:

```text
inventory_stock_levels
inventory_items
sales_checkouts
payments_transactions
notification_delivery_attempts
sync_outbox_entries
```

---

# 43. Backup and Recovery Requirements

The database contains:

```text
financial transactions
customer data
IMEIs
audit history
inventory state
payment state
commission history
```

Therefore production backup must include:

1. scheduled full backups;
2. incremental/binlog recovery strategy;
3. tested restore procedure;
4. retention aligned with business/legal policy;
5. encrypted backup storage;
6. documented recovery objectives.

Backup design is operational infrastructure, but the database design must assume the database is a critical system of record.

---

# 44. Data Protection Considerations

Sensitive fields include:

```text
customer phone
customer email
customer history
IMEI
payment provider references
proof/reference identifiers
```

The database must not store credentials or gateway secrets in business tables.

Secrets belong in application configuration/secret management.

For example:

```text
PAYSTACK_SECRET_KEY
TRANCIX_SECRET
RESEND_API_KEY
```

must never appear in:

```text
payments_transactions
audit_logs
notification_events
```

---

# 45. Final Schema Inventory

The finalized data model contains the following principal tables.

## Shared Kernel

```text
shops
customers
staff
```

## Repair / Collection

```text
repair_jobs
repair_diagnoses
repair_parts_reservations
collection_cases
collection_case_events
```

## Sales

```text
sales_checkouts
sales_checkout_items
sales_checkout_adjustments
sales_sales
```

## Inventory

```text
inventory_products
inventory_skus
inventory_items
inventory_stock_levels
inventory_transfers
```

## Warranty / Trade-In

```text
warranty_policies
warranty_claims
return_requests
trade_in_assessments
```

## Payments

```text
payments_transactions
```

## Commission / Referral

```text
commission_ledger_entries
referrals
```

## Marketing

```text
marketing_campaigns
marketing_rewards
marketing_store_credit_ledger_entries
marketing_vouchers
```

## Notifications

```text
notification_events
notification_delivery_attempts
```

## Audit / Reporting

```text
audit_logs
reporting_financial_ledger_entries
expenses
```

## Offline Sync

```text
sync_outbox_entries
sync_conflicts
```

---

# 46. Final Database Rules — Non-Negotiable

The following rules should be treated as database architecture invariants:

```text
1. MySQL 8 + InnoDB only.

2. All monetary amounts use BIGINT minor units.

3. All tables use utf8mb4 / utf8mb4_unicode_ci.

4. Standard online entities use BIGINT UNSIGNED AUTO_INCREMENT.

5. Offline-creatable entities use client-generated BINARY(16) UUIDs.

6. Shared Kernel and intra-module relationships may use real FKs.

7. Cross-module relationships use IDs without cross-module FKs.

8. Inventory reservation checks and writes execute inside one transaction.

9. Reservation operations use SELECT ... FOR UPDATE.

10. Available = On-hand - Reserved.

11. Available is not stored as a column.

12. IMEI is globally unique.

13. Serialized inventory locks individual physical units.

14. Non-serialized inventory locks the (sku_id, shop_id) row.

15. Operationally significant records are not soft-deleted.

16. Historical financial facts are snapshot-based where required.

17. Append-only ledgers have no updated_at.

18. Ledger corrections are new reversal/adjustment rows.

19. Payment provider callbacks must be idempotent.

20. Offline sync must be idempotent by stable transaction identity.

21. Offline conflicts preserve both local and server state.

22. Reporting projections do not replace authoritative module records.

23. Cross-module referential integrity is an application responsibility.

24. Database migrations must preserve bounded-context ownership.

25. No external network call may occur while a critical inventory row lock is held.
```

---

# 47. Implementation Checklist

Before considering the database layer production-ready, verify:

```text
[ ] All migrations explicitly use InnoDB.
[ ] Shared Kernel FKs are present.
[ ] Cross-module FKs have not been introduced.
[ ] All money columns use BIGINT.
[ ] IMEI has a global unique index.
[ ] inventory_stock_levels has UNIQUE(sku_id, shop_id).
[ ] sales_checkouts has (status, reservation_expires_at).
[ ] payments_transactions has (payable_type, payable_id).
[ ] audit_logs is append-only.
[ ] commission_ledger_entries is append-only.
[ ] marketing_store_credit_ledger_entries is append-only.
[ ] reporting_financial_ledger_entries is append-only.
[ ] No append-only table has updated_at.
[ ] Offline UUID tables use BINARY(16).
[ ] No core operational table uses soft deletes.
[ ] Reservation tests demonstrate race-condition safety.
[ ] Payment webhook tests demonstrate idempotency.
[ ] Cross-module integrity tests exist.
[ ] Database restore has been tested.
[ ] Slow-query monitoring is enabled.
[ ] Deadlock monitoring is enabled.
```

---

# 48. Source Alignment and Scope Note

This DBDD is derived primarily from the finalized AfProsPos Data Model v1.0 and its companion BRD v1.0 / BLD v1.1.

Where the source model specifies an exact datatype, enum, constraint, or index, this document preserves it.

Where the source model intentionally leaves a `VARCHAR` length unspecified, this document does **not** silently reinterpret that field as a business rule. Laravel migrations must choose an explicit physical length consistent with the application's validation/domain constraints before implementation. The same applies to database operational policies such as deadlock retry count and backup retention periods: those are infrastructure configuration decisions, not changes to the finalized business schema.

The result should be treated as the authoritative database implementation blueprint until a formally versioned Data Model revision supersedes it.
