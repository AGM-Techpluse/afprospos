# AfProsPos Implementation Plan

**Status:** Proposed implementation plan  
**Date:** September 3, 2026  
**Basis:** BRD v1.0, BLD v1.1, ADD, DBDD, UI/UX Design System, and CPNC

## 1. Delivery Approach

Build AfProsPos as a Laravel modular monolith with a MySQL 8/InnoDB transactional core and an Inertia.js + React frontend. Deliver the consistency-critical paths before feature breadth:

1. identity, shop context, RBAC, and engineering guardrails;
2. inventory allocation and POS checkout;
3. verified payments and repair lifecycle;
4. after-sales and operational modules;
5. offline support, reporting depth, and production hardening.

Each phase produces a vertically usable slice: migrations, domain/application/infrastructure code, routes/controllers, Inertia pages, policy checks, and the required automated tests. Frontend work is incremental within each slice, after the shared design system is available; it is not a separate end-of-project activity.

## 2. Non-Negotiable Implementation Rules

| Area | Rule to enforce from the first commit |
|---|---|
| Code structure | Use `domain/<Module>/{Domain,Application,Infrastructure}`. Domain code is framework- and vendor-free. |
| Write flow | Route -> Controller -> Command DTO -> Handler -> Aggregate -> Repository/Gateway. One controller action invokes one command or query. |
| Read flow | Route -> Controller -> Query -> Infrastructure read model -> Inertia props. Controllers do not query Eloquent directly. |
| Module boundaries | Cross-module communication uses IDs and explicit contracts; never imports another module's `*Record` or creates cross-module foreign keys. |
| Persistence | Eloquent classes are `*Record` classes under Infrastructure; domain entities are plain PHP. |
| Money and history | Store money as `BIGINT` minor units. Financial, audit, commission, store-credit, and reporting ledgers are append-only. |
| Inventory | `available = on_hand - reserved`; reserve/consume/release inside MySQL transactions with row locks. Never persist `available` as an independent source of truth. |
| Payments | Only verified/authorized events can confirm a payment. Webhooks and sync requests are idempotent. Pending bank transfers are not paid. |
| UI | Laravel owns routes; React owns local interaction only. Use token-backed Tailwind utilities, Bootstrap Icons, and no raw component hex values or emoji. |
| Quality gates | PHPStan/Larastan, architecture tests, unit/feature/contract/concurrency tests, and frontend lint (including raw-hex and `react-router-dom` checks) run in CI. |

## 3. Phase Plan

### Phase 0 - Bootstrap and enforce the architecture

**Goal:** establish a deployable Laravel baseline that makes the documented architecture the easiest way to work.

Follow the detailed Windows setup and verification instructions in [Phase 0: Project Setup Guide](AfProsPos_Phase_0_Project_Setup_Guide.md) before starting this phase's architecture-alignment work.

- Create the Laravel, Inertia, React, TypeScript, Tailwind, MySQL, queue, scheduler, and test-tooling baseline.
- Configure PSR-4 autoloading for `domain/`, environment configuration, database defaults (`utf8mb4`, `utf8mb4_unicode_ci`), and local/CI MySQL 8 services.
- Create the canonical module skeleton for Shared, Identity, RBAC, Shop, Inventory, Sales, Payments, Repair, Collection, Warranty, Commission, Referral, Marketing, Notifications, Finance, Audit, and Sync.
- Add route files for admin, customer, webhooks, API, and console scheduling; add middleware for authenticated actor, active shop, request/correlation IDs, and authorization.
- Add reusable infrastructure primitives: `Money`, UUID conversion for offline-capable records, transaction runner, clock, idempotency store, outbox writer, audit writer, and exception-to-HTTP mapping.
- Establish CI and architectural tests that reject boundary violations, incorrect `*Record` placement/naming, raw hex values, and client-side routers.

**Exit criteria:** a clean application starts locally; CI executes the required gates; one trivial command and one trivial query demonstrate the mandatory flow without bypassing any layer.

### Phase 1 - Shared Kernel, authentication, shop context, and RBAC

**Goal:** make every later feature safely actor- and shop-scoped.

- Implement `shops`, `customers`, `staff`, and RBAC schema/migrations first, following DBDD migration order and foreign-key rules.
- Integrate browser authentication for staff and customers; invalidate staff sessions on deactivation and prepare optional admin 2FA.
- Build staff lifecycle, multi-role assignment, effective-permission union, shop grants, role/permission historical snapshots, and server-side policies.
- Implement active-shop selection/switching, owner cross-shop access, and mandatory shop-scope validation in every admin command/query.
- Add admin and customer shell routing with permission-aware navigation; expose only authorized modules.
- Audit role changes, deactivations, permission changes, and shop-context-sensitive administration.

**Exit criteria:** owner, accountant, technician, cashier, product, marketing, and customer personas receive only their intended server-enforced access; a deactivated staff member cannot retain a session or access a shop.

### Phase 2 - Shared UI design system and application shells

**Goal:** create reusable, accessible visual primitives before feature screens proliferate.

- Create `resources/css/tokens.css` with primitive, semantic, theme, and component token layers; map token aliases to Tailwind.
- Implement shared feedback and interaction components: focus handling, form field/error states, skeletons, blocking loader, confirmation dialog/sheet, alert banners, and theme-specific toasts.
- Implement Admin components and shell: sidebar, topbar, compact buttons/inputs/badges, desktop tables, and mobile row-card/accordion transformation.
- Implement Customer components and shell: drawer, taskbar, rounded controls, list items, hero card, and mobile-first navigation.
- Use Bootstrap Icons exclusively; provide semantic status badges with icon/dot plus text, never colour alone.
- Add visual/responsive/accessibility checks at the `640px` interaction breakpoint, keyboard traversal, focus traps, reduced motion, and screen-reader labels.

**Exit criteria:** both theme shells render from production components, not the HTML prototype; shared components handle loading, validation, confirmation, responsive navigation, and accessibility contracts.

### Phase 3 - Inventory foundation and reservation engine

**Goal:** establish the consistency boundary on which repair and sales depend.

- Implement `inventory_products`, `inventory_skus`, `inventory_items`, `inventory_stock_levels`, and `inventory_transfers` with unique SKU/IMEI constraints, categories, condition/specification fields, cost/markup/selling-price snapshots, and indexes.
- Implement product creation, CSV import, SKU generation, serialized-unit handling, stock-in/out, reasoned adjustments, stock search, low-stock thresholds, and inter-shop transfer lifecycle.
- Implement a single Inventory reservation contract used by both Repairs and Sales. Support quantity reservation for non-serialized stock and exact-unit reservation for IMEI-tracked stock.
- Enforce a stable lock order, `SELECT ... FOR UPDATE`, transaction retries for deadlocks, reservation expiry/release, consumption, and transfer exclusion for reserved units.
- Build product, stock, import, adjustment, transfer, and low-stock admin screens with shop scoping.
- Add scheduled release/low-stock/integrity commands and reconciliation reporting; commands delegate to application handlers.

**Exit criteria:** stock cannot become over-reserved; an IMEI cannot be reserved twice; the last item/quantity succeeds for only one genuinely concurrent request; available stock and low-stock alerts account for reservations.

### Phase 4 - Sales/POS checkout and sale completion

**Goal:** deliver a cashier workflow that safely holds inventory while payment is in progress.

- Implement `sales_checkouts`, checkout items, checkout adjustments, and completed sales with pricing, discount, warranty, customer/walk-in, shop, and staff attribution snapshots.
- Implement commands for cart changes, checkout creation, inventory reservation, discount application, checkout expiration, sale conversion, invoice generation, and receipt-print payloads.
- Integrate Sales only through the Inventory reservation contract; use cross-module IDs with no inventory foreign keys.
- Build cashier POS cart, serialized-unit picker, customer/walk-in selection, checkout, invoice, and receipt UI. Provide customer-visible checkout routes where the purchaser is registered.
- Add expiry handling that releases reservations and cannot convert an expired checkout to a paid sale automatically.

**Exit criteria:** a cashier can complete cash-authorized sales and create remote/POS checkout intents; checkout creation is atomic with its reservation; every completed sale has a traceable invoice/receipt and correct inventory effect.

### Phase 5 - Payments, provider gateways, and payment operations

**Goal:** separate payment truth from sales/repair state and make all asynchronous confirmation safe.

- Implement `payments_transactions`, `PaymentTransaction` aggregate, `PaymentGateway` contract/resolver, provider-reference/idempotency data, and states: `pending`, `payment_pending_confirmation`, `confirmed`, `disputed`, `refunded`, and `exception`.
- Implement cash, POS-terminal, manual bank-transfer, and in-app gateway adapters behind the common contract. Configure the active provider mapping rather than coupling Sales/Repair to a vendor.
- Implement commands for initiation, bank-transfer pending confirmation, provider webhook processing, provider verification, confirmation, dispute opening/resolution, rejection, refund, and late-payment exception handling.
- Verify webhook authenticity before interpreting payloads; lock the payment row, apply one allowed state transition, commit, then publish downstream effects through the outbox.
- Keep bank transfers in pending-confirmation until an authorized review/verified provider result; add finance/admin dispute queues and customer payment-status UX.
- Add shared provider contract tests and certify each chosen integration, including duplicate callback idempotency and ambiguous provider results.

**Exit criteria:** no browser success page can mark a payment confirmed; duplicate callbacks produce one business effect; disputed and late-expired-checkout payments require explicit authorized resolution.

### Phase 6 - Repair and collection lifecycle

**Goal:** deliver repair intake through return while respecting diagnosis, authorization, inventory, and payment constraints.

- Implement `repair_jobs`, `repair_diagnoses`, `repair_parts_reservations`, `collection_cases`, and collection events.
- Implement the structured diagnosis model (Working, Faulty, Not Tested, Unable to Test) and transitions for repairable, unrepairable, further assessment, awaiting down payment, in progress, awaiting parts, completed, failed, ready for collection, collected, and administrative resolution.
- Reserve parts only after repairability and required authorization/payment; reuse Inventory reservations and show job-blocking shortages separately from general low-stock alerts.
- Model repair payment dimensions independently from job and device disposition; prevent release with an outstanding balance except for an audited authorized override.
- Implement configurable authorization deadlines, collection deadlines, abandonment thresholds, storage fees, and unrepairable/failed-repair settlement queues.
- Build technician repair intake, diagnosis, parts, progress, payment, collection, customer tracking, and customer repair-payment screens.

**Exit criteria:** repairs cannot begin before required authorization; parts are never silently consumed at selection; the device cannot be released when payment is outstanding without an authorized, audited override.

### Phase 7 - Warranty, returns, and trade-in

**Goal:** add controlled after-sales processes using completed-sales history without cross-module persistence coupling.

- Implement warranty policies, claims, return requests, trade-in assessments, eligibility checks, exclusions, assessment, resolution, refund, and swap flows.
- Snapshot relevant sale, warranty, pricing, and payment facts at decision time; use Sales/Payments contracts and IDs rather than ORM relationships.
- Make refund/return windows and business policies configurable; route exceptions to authorized human decisions.
- Build admin queues and customer self-service claim/return views.

**Exit criteria:** a claim/return is traceable to its source sale, cannot bypass its policy, and any refund is processed through the Payments state model and audited.

### Phase 8 - Event-driven operational modules

**Goal:** complete the supporting business capabilities without weakening transactional boundaries.

- Make the transactional outbox durable and dispatch internal domain events asynchronously after commit.
- Implement immutable `audit_logs` and audit capture for security, inventory, payment, financial, and administrative events.
- Implement notifications/events/delivery attempts, customer preferences, WhatsApp/email/in-app channel adapters, retries/failover, and delivery observability. Notification failure must not reverse the originating transaction.
- Implement commission configuration, multi-staff attribution snapshots, earned/paid/reversal ledger entries, and reports.
- Implement referrals with qualification timing, fraud/self-referral guards, configurable bonuses, and customer/admin history.
- Implement campaigns, eligibility segments, discount/reward stacking policy, flyers, vouchers, and append-only store-credit ledger.
- Implement expenses and the financial reporting projection; keep operational records authoritative and reporting rebuildable/traceable.

**Exit criteria:** all important mutations produce append-only audit history and durable follow-up work; retries do not duplicate notifications, rewards, commissions, or financial entries.

### Phase 9 - Offline/PWA, reporting depth, and operational readiness

**Goal:** add offline convenience only where central consistency is not compromised, then prepare for production operations.

- Implement the PWA shell, client-generated UUIDs for approved offline-created entities, `sync_outbox_entries`, sync processing, idempotent replay, conflict preservation, and explicit conflict-resolution UI.
- Allow only safe offline operations (such as drafts/notes and documented local capture). Keep live inventory allocation, payment confirmation, and other centrally coordinated actions online-only.
- Expand dashboards, shop/consolidated reports, exports, saved filters, and read-model query performance for sales, repairs, stock, commissions, campaigns, expenses, and notifications.
- Add observability: request/correlation/actor/shop IDs, structured logs, queue and provider failures, inventory conflict/deadlock reporting, and alerting for backlog/latency.
- Prepare scheduled integrity checks, backup/binlog recovery, encrypted backup storage, restore drills, deployment health checks, and runbooks.

**Exit criteria:** replaying an offline request never creates a duplicate effect; conflicts retain both versions for resolution; production monitoring and recovery procedures are tested rather than merely documented.

### Phase 10 - System hardening and release certification

**Goal:** prove the platform against its business invariants, not just happy-path UI tests.

- Complete domain unit tests for state machines, money, pricing, warranty, discount stacking, commission, and reservation policies.
- Complete persona-scoped feature tests for every command/query and shop boundary.
- Run real MySQL/InnoDB concurrency tests for the last serialized unit, last non-serialized quantity, checkout expiry versus payment confirmation, and confirmation versus manual dispute.
- Run all provider contract tests, webhook replay tests, migration tests, architecture tests, accessibility checks, responsive visual review, and performance/load checks for POS/payment/inventory hot paths.
- Reconcile data after pilot migration, verify append-only privilege configuration, complete security review (PII/IMEI encryption and log redaction), and certify backup restoration.

**Exit criteria:** all CI gates pass; critical business scenarios have executable tests; pilot operations can be supported with documented monitoring, recovery, and incident procedures.

## 4. Detailed Phase Work Breakdown

The work packages below expand the phases into sequenced implementation tasks. A later package within a phase must not start until the contracts and tests it depends on have been agreed and committed.

### Phase 0 work breakdown - Bootstrap and enforce the architecture

1. **Create the deployable baseline.** Initialise Laravel, Inertia, React, TypeScript, Tailwind, Vite, queue, scheduler, storage, error pages, environment templates, and test tooling. Configure development, test, staging, and production settings without committing secrets.
2. **Register the architecture.** Add the `Domain\\` PSR-4 mapping, module service-provider convention, canonical module folder structure, and a small Shared Kernel for IDs, `Money`, clock, transaction, and domain-exception primitives.
3. **Establish presentation boundaries.** Configure `bootstrap/app.php` to load `routes/admin.php` and `routes/customer.php` via the `then` callback alongside `routes/web.php`. Admin and customer audiences are separated by URL prefix (`/admin/*` and `/customer/*`), not by subdomain. Create the respective controller/request namespaces for Admin, Customer, Webhooks, API, and console entry points. Add a consistent domain-exception-to-HTTP response mapper.
4. **Add platform middleware.** Implement request/correlation IDs, actor resolution, active-shop resolution, locale/time-zone handling, CSRF/session/security headers, and structured log context. Preserve correlation/causation IDs in queued jobs.
5. **Build enforcement first.** Configure formatting, static analysis, TypeScript checking, frontend lint, test database lifecycle, migrations in CI, and architectural tests. CI must fail for domain framework imports, cross-module ORM imports, invalid `*Record` placement, raw component hex values, and `react-router-dom`.
6. **Prove the wiring.** Implement one harmless command and one read query end-to-end to prove Route -> Controller -> DTO -> Handler -> Domain -> Infrastructure and the query equivalent.

### Phase 1 work breakdown - Shared Kernel, authentication, shop context, and RBAC

1. **Create the initial schema.** Implement `shops`, `customers`, `staff`, roles, permissions, staff-role assignments, and staff-shop assignments in DBDD order. Apply only permitted Shared Kernel/intra-module foreign keys, uniqueness constraints, and shop-query indexes.
2. **Implement authentication.** Deliver customer and staff login/logout, registration/recovery where in scope, password policy, session regeneration, activation checks, and session invalidation on staff deactivation. Keep optional 2FA behind an authentication adapter boundary.
3. **Implement the authorization domain.** Create role, permission, shop-grant, and effective-permission policy objects. Multiple roles grant a permission union; event/audit records snapshot the role/permission facts applicable at decision time.
4. **Make shop scope mandatory.** Implement active-shop selection, owner all-shop scope, route middleware, and handler validation. Reject client-supplied IDs that are not in the actor's authorised scope.
5. **Deliver staff administration.** Build the staff list/search/create/edit/role assignment/shop assignment/deactivate command and query paths, policies, Admin pages, and audit records.
6. **Prove persona isolation.** Test every role, cross-shop rejection, multi-role union, customer admin-route rejection, deactivated-session rejection, and history-preserving role changes.

### Phase 2 work breakdown - Shared UI design system and application shells

1. **Transcribe the token system.** Add the primitive, semantic, Admin, Customer, component, typography, spacing, radius, motion, z-index, status, and breakpoint tokens to central CSS. Map these to Tailwind aliases; prohibit arbitrary colours in React files.
2. **Build reusable field and action primitives.** Implement accessible buttons, icon buttons, inputs, selects, textareas, labels, validation messages, badges, empty states, pagination, and loading states. Theme and severity must be explicit component contracts, not copied class lists.
3. **Build the feedback system.** Implement confirmation dialog/bottom sheet, theme-specific toast, alert banner, skeleton, and blocking loader. Modal components must trap focus, restore it to the trigger, support safe Escape/backdrop dismissal, and obey reduced motion.
4. **Build navigation shells.** Implement Admin sidebar/topbar/collapsed drawer behavior and Customer desktop navigation/mobile drawer/bottom taskbar. Use server-provided permissions and active-shop data to construct navigation.
5. **Build responsive data patterns.** Implement desktop table, mobile accordion row card, status display, filter/sort controls, and two-column-to-one-column form transformations at 640px.
6. **Automate UI conformance.** Add checks for keyboard access, visible focus, labels, reduced motion, Bootstrap Icons, prohibited emoji, raw hex, client router imports, and visual review against the supplied prototype.

### Phase 3 work breakdown - Inventory foundation and reservation engine

1. **Implement catalogue and SKU data.** Create product/category/SKU migrations and domain objects covering shop code, brand, condition, category-specific specs, cost price, markup percentage, editable selling price, SKU generation, uniqueness, and CSV import validation/error results.
2. **Implement physical stock.** Create stock-level records for non-serialized quantities and inventory-item records for serialized units. Enforce one stock-level per shop/SKU and global IMEI/serialized-identifier uniqueness.
3. **Implement stock movements.** Support receiving, sale/repair consumption, manual adjustment with mandatory reason, and transfer initiation/receipt/cancellation. Record actor, shop, time, source type/ID, and resulting quantities for every movement.
4. **Implement Inventory contracts.** Expose reserve, consume, release, transfer, and availability-query contracts. Sales and Repair supply a source type/ID and never access Inventory records directly.
5. **Implement locking and expiry.** Apply the documented stable lock order and `SELECT ... FOR UPDATE`; lock exact units for serialized stock and shop/SKU levels for quantity stock. Add safe deadlock retry, exactly-once release/consume, and expired reservation handling.
6. **Deliver operational UI/jobs.** Build catalogue search/detail, bulk import results, adjustment, transfer, serialized-unit, and low-stock screens. Add low-stock detection, release-expired-reservations, and reconciliation console commands that delegate to handlers.
7. **Prove allocation safety.** Test SKU/IMEI uniqueness, required adjustment reason, movement correctness, low-stock calculation, consume/release behavior, and the two real-MySQL concurrent last-unit scenarios.

### Phase 4 work breakdown - Sales/POS checkout and sale completion

1. **Implement checkout state.** Create checkout, checkout-item, adjustment, and completed-sale schema/aggregates with customer-or-walk-in data, staff/shop attribution, expiry, pricing snapshots, and explicit states for editable, payment-pending, expired, converted, and cancelled.
2. **Centralise pricing.** Implement pricing/discount calculations in a Sales service/policy using minor units. Snapshot product price and eligibility inputs so historical invoices never change if catalogue prices or campaign rules later change.
3. **Orchestrate checkout creation.** In one transaction, validate actor/shop, create the checkout, reserve all inventory through the Inventory contract, store source IDs, and write an outbox event. Roll back all state if a reservation fails.
4. **Separate payment responsibilities.** Support cashier-recorded cash and terminal-reference capture at the boundary, while leaving external verification and in-app confirmation to Payments. Sales must not determine whether a provider payment is valid.
5. **Generate documents.** Build an invoice/receipt read model and print-friendly renderer with stable identifiers, price snapshots, and payment status. Store business facts rather than referring to mutable current catalogue data.
6. **Deliver cashier and customer UX.** Implement product/cart search, quantity and serialized-unit selection, customer lookup/walk-in entry, totals/adjustments, payment-method choice, failure states, confirmation, customer checkout access, and receipt handoff.
7. **Handle expiry correctly.** Schedule expiry to lock open checkouts, release each reservation only once, emit a checkout-expired event, and route a late payment confirmation into the Payments exception workflow.
8. **Prove sale integrity.** Test pricing snapshots, insufficient-stock rollback, walk-in/customer paths, conversion, invoice data, release-on-expiry, and checkout-expiry versus payment-confirmation concurrency.

### Phase 5 work breakdown - Payments, gateways, and payment operations

1. **Implement the payment domain.** Add payment method, payable reference, payment amount, provider reference, idempotency key, transaction aggregate, state-transition policy, and business exceptions. Only normalised internal states may drive domain behavior.
2. **Define gateway contracts.** Implement `PaymentGateway`, initialisation/verification/refund DTOs, and a resolver configured by business/shop payment-channel mapping. Start with manual bank transfer plus sandbox adapters for selected provider(s).
3. **Implement initiation.** Create an idempotent pending transaction, invoke the selected gateway where applicable, retain safe provider metadata, and return only the redirect/instructions required by the caller. Link payables by type/ID without a cross-module FK.
4. **Implement webhook processing.** Create isolated webhook routes and requests; verify signature before interpreting payload; lock the transaction; verify with the provider where required; apply one allowed transition; commit; then dispatch downstream outbox work.
5. **Implement bank-transfer operations.** Capture proof/reference, move to `payment_pending_confirmation`, provide authorised staff confirmation/rejection/dispute queues, and show the distinct pending state to the customer.
6. **Implement refunds and exceptions.** Handle duplicate or late callbacks, disputes, human decisions, refunds capped at confirmed amounts, provider ambiguity, and exception queues with mandatory audit records.
7. **Deliver payment UX and certification.** Build customer pay/instructions/history/status pages and Admin reconciliation/dispute pages. Run every provider through the common contract suite, invalid-signature and replay tests, and the required payment concurrency tests.

### Phase 6 work breakdown - Repair and collection lifecycle

1. **Implement repair and collection schema.** Create repair job, diagnosis, repair-part reservation, collection case, and collection event records, including device custody, labour/parts/down-payment snapshots, technician, and collection links.
2. **Implement diagnosis.** Deliver intake, device assessment, per-component Working/Faulty/Not Tested/Unable to Test outcomes, notes, repairable/unrepairable/further-assessment decisions, estimates, and customer-visible repair information.
3. **Implement authorisation.** Configure down-payment requirement and deadline, create a payment payable where needed, and prohibit work or parts reservation until repairability and required payment/approval conditions are satisfied.
4. **Implement parts and work transitions.** Use the Inventory contract after authorisation, move shortages to Awaiting Parts, raise job-specific shortage events, support reservation removal/release, consume parts at installation, and model progress/completion/failure/resolution.
5. **Implement settlement and collection.** Handle unrepairable and failed-repair financial resolution through authorised workflows. Model ready-for-return, notification, due, overdue, abandoned, storage fees, and administrative resolution in Collection.
6. **Enforce device release.** Prohibit collection where a balance is outstanding except through an authorised override that records actor, reason, and audit event.
7. **Deliver technician/customer UI and tests.** Build technician intake/diagnosis/parts/work/collection screens, customer tracker/payment screens, and resolution queues. Test all transition, parts-timing, payment-release, deadline, and storage-fee policies.

### Phase 7 work breakdown - Warranty, returns, and trade-in

1. **Implement policy configuration.** Create shop-scoped/effective-dated warranty and return-window settings, coverage/exclusions, and immutable snapshots of the policy used for each decision.
2. **Implement claims and eligibility.** Validate source sales via an explicit Sales query contract, match serial/IMEI where relevant, create claims, capture assessment notes/evidence, and enforce valid transitions.
3. **Implement returns/refunds.** Add return reasons/evidence, inspection/approval/rejection, stock disposition, and Payments refund handoff. No return workflow may mutate a confirmed payment directly.
4. **Implement trade-in.** Capture assessment, configurable offer, acceptance/decline, inventory intake, credit/swap linkage, and staff-authorisation decisions.
5. **Deliver queues and proof.** Build customer claim/return history and staff assessment/resolution views. Test invalid source sale, expired window, excluded damage, unauthorised approval, refund failure, and trade-in transitions.

### Phase 8 work breakdown - Event-driven operational modules

1. **Finish the transactional outbox.** Define event envelopes, outbox writer, dispatcher job, retries/dead-letter handling, idempotency keys, and correlation/causation propagation. Monitor unprocessed and failed events.
2. **Implement immutable audit.** Add append-only audit schema/writer and capture actor, shop, action, aggregate reference, safe/redacted change context, request/correlation IDs, and timestamp. Do not expose update/delete paths.
3. **Implement notifications.** Add notification event/attempt records, recipient routing, preferences, templates, email/WhatsApp/in-app adapter contracts, retry/failover, and status queries. A delivery failure never rolls back its business transaction.
4. **Implement commission.** Add configuration, commissionable-amount policy, staff/role/rate snapshots, earned/paid recognition, adjustments/reversals, and owner/accountant reporting. Every correction is a new ledger entry.
5. **Implement referral and marketing.** Add referral attribution/qualification/fraud checks, campaigns, segment source, eligibility, discount/reward stacking, voucher/flyer handling, and append-only store-credit records with idempotent reward handling.
6. **Implement Finance.** Add expense categories/entries, immutable financial-ledger projection entries, rebuild/reconciliation tools, and shop/consolidated reports traceable back to source IDs.
7. **Prove idempotency/history.** Test outbox replay, notification retries, audit/ledger immutability, commission reversal, referral fraud rejection, reward stacking, and financial projection traceability.

### Phase 9 work breakdown - Offline/PWA, reporting depth, and operations

1. **Implement safe PWA foundations.** Add a manifest, service worker, cache policy, offline page, update prompt, and approved local-storage protection. Do not cache sensitive content without an explicit security decision.
2. **Define permitted offline intents.** Document each allowed offline action, client UUID/idempotency key, version metadata, local queue payload, and server command mapping. Explicitly exclude live inventory allocation/payment confirmation.
3. **Implement sync.** Build authenticated batch upload, idempotency lookup, per-command transactions, dependency ordering, acknowledgements, retries/backoff, and sync-status reads.
4. **Preserve conflicts.** Store both local and server state in `sync_conflicts`, classify it, provide resolution workflows, and never silently discard one side.
5. **Scale read models.** Add pagination, filters, exports, date/shop ranges, explicit projections, indexes, and instrumentation for dashboard/report hot paths. Avoid `SELECT *` on high-throughput reads.
6. **Operationalise the product.** Configure the production reverse proxy (e.g., Nginx/Cloudflare) to serve the single Laravel monolith on the primary domain, with path-based routing for `/admin/*`, `/customer/*`, `/api/*`, and `/webhooks/*`. Add logs, metrics, alert thresholds, integrity checks, queue/provider monitoring, backup/binlog recovery, restore drills, deployment health checks, and runbooks.
7. **Prove offline and recovery behavior.** Test duplicate UUID handling, preserved conflicts, unauthorised sync rejection, report-scope permissions, and a simulated restore procedure.

### Phase 10 work breakdown - System hardening and release certification

1. **Close the executable test matrix.** Link BRD/BLD critical rules to unit, feature, contract, migration, architecture, and real-concurrency tests. Require all of them in CI.
2. **Perform security/privacy review.** Threat-model authentication, RBAC, shop scope, webhooks, PII/IMEI, stored files, logs, backups, secrets, offline data, and rate limits. Verify encryption and redaction controls.
3. **Test performance and resilience.** Load-test inventory, POS, webhooks, queues, and reports; inject provider, queue, and database failures; verify safe retry and degraded behavior.
4. **Rehearse release operations.** Execute production-like migration/forward-remediation rehearsals, reconcile import data, restore a backup, confirm worker/scheduler supervision, configure alerts, and finalise runbooks.
5. **Run the pilot.** Enable a bounded set of pilot shops and personas, collect evidence and feedback without bypassing audit controls, resolve defects, perform final UX/accessibility/rule review, and document formal acceptance.

## 5. Migration and Integration Order

Follow the DBDD dependency sequence: Shared Kernel and RBAC; Inventory; Repair and Collection; Sales; Warranty; Payments; Commission/Referral; Marketing; Notifications; Audit; Finance; Sync. Use one table per migration where practical, explicitly set InnoDB/utf8mb4/collation, and create foreign keys only within a module or to `shops`, `customers`, and `staff`.

The main runtime dependency chain is:

```text
Identity/RBAC/Shop
        -> Inventory reservations
        -> Sales checkout
        -> Payments confirmation
        -> Repair and Collection
        -> Warranty/Returns
        -> Audit, Notifications, Commission, Referral, Marketing, Finance
        -> Sync and advanced reporting
```

## 6. Traceability and Test Matrix

| Business area | Primary phases | Required proof |
|---|---:|---|
| RBAC and multi-shop | 1 | Permission/shop-scope feature tests, deactivation/session tests, audit records |
| Inventory and SKU/IMEI | 3 | Unit, migration, integration, and real concurrency tests |
| POS, invoices, receipts | 4 | Cashier feature tests and reservation/expiry tests |
| Payment methods, disputes, refunds | 5 | Gateway contracts, webhook replay, state-transition and concurrency tests |
| Repairs and collection | 6 | Lifecycle/domain tests, technician/customer feature tests, release-override audit tests |
| Warranty, returns, trade-in | 7 | Eligibility/policy tests and payment/refund integration tests |
| Commissions, referrals, campaigns, expenses | 8 | Ledger immutability, timing/stacking/fraud, and projection tests |
| Notifications and audit | 8 | Outbox, retry/idempotency, audit append-only tests |
| Offline/PWA | 9 | Idempotent replay and preserved-conflict tests |
| Accessibility and responsive UX | 2-10 | Keyboard, focus, reduced-motion, 640px table-to-card, and visual QA checks |

## 7. Decisions Required Before Provider Certification

The documents resolve the business rules but leave operational configuration to the business. Confirm these before activating the corresponding production behaviour:

1. The selected in-app and POS-terminal payment providers, webhook credentials, verification rules, and refund capabilities.
2. Shop-specific configuration defaults: checkout hold, repair authorization, collection/abandonment, storage fee, return/refund, notification retry, commission, and referral windows.
3. The initial notification providers and approved WhatsApp/email templates.
4. Data-retention, privacy, backup-retention, recovery-time, and recovery-point policies.
5. Pilot shops, initial import data quality/ownership, and receipt-printer integration details.

These are configuration and rollout decisions, not reasons to defer the underlying contracts, state machines, or auditability.

## 8. Definition of Done for Every Work Item

- The capability is assigned to one bounded context and reuses an existing equivalent artefact where one exists.
- Its commands/queries, routes, controller boundary, policy, migrations, UI states, observability, and tests are complete together.
- It respects money, shop scope, module-boundary, idempotency, and append-only rules where applicable.
- Its UI uses the shared design system, supports loading/empty/error states, keyboard access, and the documented mobile interaction model.
- The relevant unit, feature, contract, concurrency, architecture, and lint checks pass in CI.
- Any replaced code has no remaining callers or orphaned files.
