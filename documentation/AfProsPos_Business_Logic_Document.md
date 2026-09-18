**BUSINESS LOGIC DOCUMENT**

**AfProsPos**

*Repair, Inventory, Sales, Payment & Warranty Rule Set*

Document Version: 1.1

Date: September 1, 2026

Status: Draft --- All Cross-Module Questions Resolved

*Companion to: AfProsPos Business Requirements Document v1.0*

**Document Control**

**Revision History**

  --------------------------------------------------------------------------
  **Version**   **Date**      **Description**
  ------------- ------------- ----------------------------------------------
  1.0           Aug 31, 2026  Initial BLD covering Repair↔Inventory,
                              Sales/POS↔Inventory↔Payments, and
                              Warranty/Returns/Trade-In. Remaining module
                              questions listed as open items (Section 8).

  1.1           Sep 1, 2026   Resolves all remaining Section 8 items:
                              Commission, Referral & Rewards, Marketing
                              Campaigns, Inventory/Multi-Shop/SKU, RBAC, a
                              new unified Audit Trail module, Notifications,
                              Consolidated Financial Reporting,
                              Offline/Connectivity, and Data Protection &
                              Privacy. Adds Bank-Transfer Payment Disputes
                              (Sales/POS) and a Return/Refund Window policy
                              (Warranty/Returns). No open cross-module
                              questions remain from the original analysis.
  --------------------------------------------------------------------------

**Purpose vs. the BRD**

The Business Requirements Document (BRD) states what the system must do.
This Business Logic Document (BLD) states how the system decides what
happens in edge cases, races, exceptions, and multi-module interactions
that the BRD\'s feature list does not fully specify --- the operational
rules, state models, and calculations that keep repairs, inventory,
sales, payments, commission, referral, notifications, reporting, and
warranty handling consistent across every module.

**Table of Contents**

**1. Introduction 5**

> 1.1 Purpose & Relationship to the BRD 5
>
> 1.2 What a Business Logic Document Captures 5
>
> 1.3 Document Conventions 5
>
> 1.4 Scope & Status of This Version 6

**2. Core Business Logic Concepts (Cross-Cutting) 7**

> 2.1 Inventory Reservation Model 7
>
> 2.2 Reservation Sources & Reuse Across Modules 7
>
> 2.3 Serialized vs. Non-Serialized Allocation 7
>
> 2.4 Four-Dimension Transaction State Model 8
>
> 2.5 Concurrency & Transactional Integrity 8

**3. Repair Management --- Business Logic 9**

> 3.1 Revised Repair Lifecycle 9
>
> 3.2 Diagnosis as a Structured Business Object 9
>
> 3.3 Parts Reservation & Conflict Handling 10
>
> 3.4 Parts Shortage vs. Low-Stock Alerts 10
>
> 3.5 Diagnosis Outcome & Unrepairable Handling 11
>
> 3.6 Repair Authorization & Payment-Before-Work 12
>
> 3.7 Completed Repair With Outstanding Balance 12
>
> 3.8 Collection & Abandonment Management 13
>
> 3.9 Repair State Summary 15

**4. Sales / POS --- Business Logic 16**

> 4.1 Checkout Reservation Model 16
>
> 4.2 Concurrency at Checkout 16
>
> 4.3 Payment Confirmation, Expiry & Release 17
>
> 4.4 Relationship to Repair Down Payments 17
>
> 4.5 Bank Transfer Confirmation & Payment Disputes 17

**5. Warranty, Returns & After-Sales Management 19**

> 5.1 Why a Dedicated Module Is Needed 19
>
> 5.2 Key Distinctions 19
>
> 5.3 Warranty Policy Model 19
>
> 5.4 Warranty Claim Lifecycle 20
>
> 5.5 Eligibility, Assessment & Exclusions 21
>
> 5.6 Resolution Options & Financial Control 21
>
> 5.7 Trade-In / Swap Flow 22
>
> 5.8 Regulatory Consideration (Nigeria) 22
>
> 5.9 Return / Refund Window 22

**6. Commission Management --- Business Logic 23**

> 6.1 Commissionable Amount 23
>
> 6.2 Payment-Based Recognition: Earned vs. Paid 23
>
> 6.3 Reversal, Ledger & Partial Refunds 23
>
> 6.4 Multi-Staff Attribution & Assignment History 24

**7. Referral Program --- Business Logic 26**

> 7.1 Qualifying Transaction & Bonus Timing 26
>
> 7.2 Reward Generation 26
>
> 7.3 Refund Window & Bonus Timing 26
>
> 7.4 Fraud & Self-Referral Safeguards 27

**8. Marketing Campaigns & Rewards --- Business Logic 28**

> 8.1 Reward Types: Store Credit & Vouchers 28
>
> 8.2 Discount & Reward Stacking 28
>
> 8.3 Customer Segment Source of Truth 29

**9. Inventory, Multi-Shop & SKU --- Business Logic 30**

> 9.1 Product, SKU & Inventory Item Model 30
>
> 9.2 Inter-Shop Transfers 30
>
> 9.3 Available Stock for Allocation 31

**10. RBAC --- Business Logic 32**

> 10.1 Multiple Roles & Effective Permissions 32
>
> 10.2 Historical Preservation & Deactivation 32

**11. Unified Audit Trail --- Business Logic 33**

**12. Notifications --- Business Logic 34**

> 12.1 Event-Driven Delivery Architecture 34
>
> 12.2 Retry, Failover & Delivery Status 34
>
> 12.3 Notification Categories & Preferences 35

**13. Expense Tracking & Consolidated Financial Reporting 36**

**14. Offline & Connectivity --- Business Logic 37**

**15. Data Protection & Privacy --- Business Logic 39**

**16. Consolidated Business Rule Register 40**

**17. Recommended Amendments to the BRD 48**

**18. Resolution Status Summary 49**

**19. Glossary 50**

**1. Introduction**

**1.1 Purpose & Relationship to the BRD**

This document extends the AfProsPos Business Requirements Document (BRD
v1.0) by resolving the operational business logic behind the features it
lists. Where the BRD states that a feature exists (e.g., "REP-02: a
technician shall be able to select parts for a repair"), this BLD states
the rule governing what happens when that feature is used under real
conditions --- concurrent access, partial payment, cancellation, expiry,
and disagreement between modules over the same fact.

**1.2 What a Business Logic Document Captures**

This document captures four kinds of content that a feature list cannot:

-   State models --- the distinct states a business object (a repair, a
    checkout, a warranty claim, a referral reward) can be in, and what
    causes it to move between them.

-   Business rules --- specific, testable statements of system behavior,
    each with a unique ID for traceability into design, development, and
    QA.

-   Calculations --- how derived values (available stock, outstanding
    balance, warranty coverage, trade-in credit, commissionable amount,
    referral bonus) are computed from underlying data.

-   Boundaries of automation --- which decisions the system may make
    automatically, and which must be routed to an authorized human (an
    accountant, a shop owner, a senior technician).

**1.3 Document Conventions**

Every rule in this document carries a unique ID composed of a module
prefix and a sequence number, so it can be referenced directly from
design specs, test cases, and code comments.

  -----------------------------------------------------------------------
  **Prefix**         **Module**
  ------------------ ----------------------------------------------------
  INV/REP-BR         Repair ↔ Inventory (parts reservation)

  REP/INV-BR, INV-BR Repair ↔ Inventory (parts shortage, low-stock,
                     available stock, SKU/catalog/transfers)

  REP-BR             Repair Management (diagnosis, authorization,
                     collection, abandonment)

  SALE/INV-BR        Sales/POS ↔ Inventory (checkout reservation)

  SALE/PAY-BR,       Sales/POS ↔ Payments (incl. bank-transfer
  PAY-BR             confirmation & disputes)

  WAR-BR             Warranty, Returns & After-Sales Management

  TRADE-BR           Trade-In / Swap

  COMM-BR            Commission Management

  REF-BR             Referral Program

  MKT-BR             Marketing Campaigns & Rewards (store credit,
                     vouchers, stacking, segments)

  RBAC-BR            Role-Based Access Control

  AUD-BR             Unified Audit Trail

  NOTIF-BR           Notifications

  FIN-BR             Expense Tracking & Consolidated Financial Reporting

  OFF-BR             Offline & Connectivity

  DATA-BR            Data Protection & Privacy
  -----------------------------------------------------------------------

Flow diagrams are shown in monospace text blocks using arrows (→) to
indicate state transitions; branch points are shown as indented
alternatives beneath the deciding step.

**1.4 Scope & Status of This Version**

Version 1.0 resolved Repair ↔ Inventory, Sales/POS ↔ Inventory ↔
Payments, and Warranty/Returns/Trade-In, and listed every other
module-crossing question raised during analysis as outstanding. This
version (1.1) resolves all of those remaining items: Commission,
Referral & Rewards, Marketing Campaigns, Inventory/Multi-Shop/SKU, RBAC,
a new unified Audit Trail, Notifications, Consolidated Financial
Reporting, Offline/Connectivity, and Data Protection & Privacy --- plus
two items surfaced along the way that hadn\'t been assigned a home:
bank-transfer payment disputes (Sales/POS, Section 4.5) and a general
return/refund window policy (Warranty/Returns, Section 5.9).

No cross-module question from the original analysis remains open as of
this version. Section 18 summarizes what was resolved and where; new
questions that surface once the business begins configuring the system
(exact retry counts, specific window durations, and similar defaults)
are configuration decisions within the rules below, not business-logic
gaps.

**2. Core Business Logic Concepts (Cross-Cutting)**

The rules in Sections 3--5 all build on five shared concepts.
Establishing them once here avoids re-deriving the same logic separately
for repairs, sales, and warranty handling.

**2.1 Inventory Reservation Model**

A single quantity field per product is not sufficient once two modules
(repairs and sales) can both draw on the same stock. Every inventory
item therefore carries three related quantities:

+-----------------------------------------------------------------------+
| **On-hand** --- physically present in the shop.                       |
|                                                                       |
| **Reserved** --- physically present but committed to an open repair   |
| job or checkout.                                                      |
|                                                                       |
| **Available** --- On-hand minus Reserved; the quantity that can be    |
| newly committed.                                                      |
+-----------------------------------------------------------------------+

Reservation is not a stock-out. Physical stock (On-hand) only decreases
when a part is actually installed on a repair, or a sold item is
actually paid for. Example, for a single screen in stock:

  -------------------------------------------------------------------------------
  **Event**                          **On-hand**   **Reserved**   **Available**
  ---------------------------------- ------------- -------------- ---------------
  Before any repair                  1             0              1

  Technician selects the screen for  1             1              0
  a repair                                                        

  Repair completed (part installed / 0             0              0
  consumed)                                                       
  -------------------------------------------------------------------------------

**2.2 Reservation Sources & Reuse Across Modules**

Reservation is a general inventory mechanism with a source/reason
attached to each reservation record, not a repair-only or sales-only
feature:

+-----------------------------------------------------------------------+
| Available \--reserve\--\> Reserved \[source: REPAIR \| CHECKOUT\]     |
| \--consume\--\> Sold/Installed                                        |
|                                                                       |
| Reserved \--release (cancel/expire)\--\> Available                    |
+-----------------------------------------------------------------------+

This means Sections 3 and 4 apply the same underlying reservation logic
to two different triggers (a technician selecting a part; a cashier or
customer creating a checkout) rather than maintaining two separate
inventory engines.

**2.3 Serialized vs. Non-Serialized Allocation**

Two different allocation strategies are required depending on whether a
product is individually tracked:

-   Serialized inventory (e.g., an IMEI-tracked phone): the system
    identifies which specific physical unit is available and reserves
    that exact unit against the repair or checkout. Two open
    transactions can never point at the same physical unit.

-   Non-serialized inventory (e.g., a screen protector, a charging
    cable): the system reserves a quantity against the total available
    count; no specific unit identity is tracked.

**2.4 Four-Dimension Transaction State Model**

Rather than one long linear status list, a repair (and, with adaptation,
a sale) is modeled along four independent dimensions that combine to
describe its real situation at any moment:

  -----------------------------------------------------------------------
  **Dimension**       **Example Values**
  ------------------- ---------------------------------------------------
  Repair / Order      Diagnosing → Repairable → In Progress → Completed /
  State               Failed

  Payment State       Unpaid → Partially Paid → Fully Paid → Overdue

  Inventory State     Unreserved → Reserved → Consumed → Released

  Device Disposition  With Business → Ready for Return/Collection →
                      Collected
  -----------------------------------------------------------------------

Business policies (Sections 3.6--3.8) determine which combinations of
these four values are permitted to transition into which others ---
e.g., Device Disposition cannot become Collected while Payment State is
Overdue, unless an authorized override is recorded.

**2.5 Concurrency & Transactional Integrity**

Any operation that checks availability and then reserves stock must be
atomic: the check and the reservation happen inside one transaction so
that two staff members acting at the same instant cannot both succeed
against the last available unit. Where a race is detected, the second
request is rejected with a clear reason rather than silently
over-committing stock.

**3. Repair Management --- Business Logic**

**3.1 Revised Repair Lifecycle**

The BRD\'s original lifecycle (Received → Diagnosing → Awaiting Down
Payment → In Progress → Awaiting Parts → Completed → Ready for
Collection → Collected) assumed the device would always turn out to be
repairable and would always be collected promptly. Diagnosis and
collection are now separated out as their own decision points:

+-----------------------------------------------------------------------+
| Received -\> Diagnosing -\> Diagnosis Complete                        |
|                                                                       |
| \|\-- Unrepairable \-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--\> Ready for    |
| Return \-\--\> Collection Lifecycle (3.8)                             |
|                                                                       |
| \`\-- Repairable                                                      |
|                                                                       |
| -\> Awaiting Down Payment (if required)                               |
|                                                                       |
| \|\-- Payment received \--\> Parts Reserved -\> In Progress -\>       |
| Completed / Failed                                                    |
|                                                                       |
| \`\-- Payment overdue \--\> Repair Expired/Cancelled -\> Ready for    |
| Return -\> Collection Lifecycle                                       |
+-----------------------------------------------------------------------+

**3.2 Diagnosis as a Structured Business Object**

Diagnosis is recorded, not merely concluded. For each relevant
component/function, the technician records one of: Working, Faulty, Not
Tested, or Unable to Test --- the last two being legitimate outcomes for
dead, severely damaged, water-damaged, or locked devices, rather than
being silently assumed "working." Diagnosis then resolves to one of
three outcomes: Repairable, Unrepairable, or Requires Further
Assessment.

**3.3 Parts Reservation & Conflict Handling**

Selecting a part for a repair (BRD ref. REP-02) reserves it; it does not
deduct it from physical stock. This prevents two technicians from both
believing a single remaining part is available to them.

-   **INV/REP-BR-01 --- Part Reservation.** When a part is selected for
    an active repair job, the system shall reserve the required quantity
    against that repair job and reduce the part\'s Available quantity
    without reducing its On-hand quantity.

-   **INV/REP-BR-02 --- Reservation Conflict.** The system shall prevent
    a repair job from reserving a quantity greater than the part\'s
    current Available quantity.

-   **INV/REP-BR-03 --- Reservation Release.** When a reserved part is
    removed from a repair job, or the repair job is cancelled, the
    reservation shall be released and the quantity shall return to
    Available.

-   **INV/REP-BR-04 --- Part Consumption.** When a reserved part is
    actually installed/consumed during a repair, the system shall
    convert the reservation into a stock-out transaction, reducing
    On-hand.

**3.4 Parts Shortage vs. Low-Stock Alerts**

A part being unavailable for one specific repair job is a different
business event from a product\'s overall stock falling below its
configured threshold; the two triggers are kept distinct so an urgent,
job-blocking shortage is never lost inside a routine restocking report.

-   **REP/INV-BR-05 --- Parts Availability.** If a required repair part
    cannot be reserved because insufficient Available quantity exists,
    the repair shall enter or remain in Awaiting Parts status.

-   **REP/INV-BR-06 --- Parts Shortage Notification.** When a repair
    enters Awaiting Parts due to unavailable inventory, the system shall
    notify the staff responsible for inventory/repair operations,
    separately from any general low-stock alert.

-   **INV-BR-07 --- Available Stock for Alerts.** Low-stock evaluation
    (BRD ref. INV-14) shall be calculated against Available quantity
    (On-hand less Reserved), not On-hand alone, so committed stock is
    never reported as free stock.

**3.5 Diagnosis Outcome & Unrepairable Handling**

The technical judgment (repairable or not) and the financial decision
(refund, retain, or partially retain) are handled by different roles. A
technician determines repairability; an authorized accountant/admin
determines the resulting financial settlement. The exact refund policy
is deliberately left to the business to configure --- Nigerian
phone-repair businesses are not uniform on this point, and neither
Nigerian consumer-protection guidance nor AfProsPos should have that
decision hard-coded into the system.

-   **REP-BR-01 --- Diagnosis Before Repair.** A repair job shall
    undergo diagnosis before parts are reserved or repair work begins,
    unless the business explicitly permits a direct-repair workflow.

-   **REP-BR-02 --- Diagnostic Assessment.** The technician shall record
    the condition of relevant device components/functions as Working,
    Faulty, Not Tested, or Unable to Test, with notes where necessary.

-   **REP-BR-03 --- Inaccessible Device.** Where a device cannot be
    fully tested, the technician shall record the reason and mark the
    affected components as Unable to Test rather than assuming they are
    functional.

-   **REP-BR-04 --- Diagnosis Outcome.** Upon completing diagnosis, the
    technician shall classify the repair as Repairable, Unrepairable, or
    Requiring Further Assessment.

-   **REP-BR-05 --- Unrepairable Device.** When a device is classified
    Unrepairable before repair work begins, the system shall prevent
    progression to repair execution and shall not reserve repair parts.

-   **REP-BR-06 --- Parts Reservation Timing.** Parts shall only be
    reserved once the repair has been determined Repairable and any
    applicable authorization/payment requirement has been satisfied ---
    not merely because a technician selected a candidate part during
    diagnosis.

-   **REP-BR-07 --- Unrepairable Financial Settlement.** When a repair
    is classified Unrepairable after payment has been received, the
    system shall place the transaction into a financial settlement state
    and calculate amount paid and applicable charges; the refund or
    retention amount shall follow the business\'s configured refund
    policy or an authorized administrative decision --- never a
    technician decision.

-   **REP-BR-08 --- Failed Repair Resolution.** If a repair fails after
    work has commenced, the system shall record the outcome as Failed /
    Requires Resolution and shall not automatically determine a refund,
    additional charge, or further repair action, unless a corresponding
    business policy has been configured. A failed repair is not
    automatically the same as an unrepairable device --- the business
    may choose to retry, escalate, refund, or return the device.

**3.6 Repair Authorization & Payment-Before-Work**

A device can sit with the business, diagnosed as repairable, without the
customer ever authorizing the work by paying the required down payment.
This is treated as its own branch rather than being folded into either
the repair-in-progress flow or the post-completion collection flow.

-   **REP-BR-19 --- Repair Payment Deadline.** Where a down payment is
    required, the system shall support a configurable period within
    which the customer must pay before the repair authorization expires.

-   **REP-BR-20 --- Payment Expiry.** If the required down payment is
    not received within the configured period, the repair shall be
    marked Payment Overdue and subsequently Repair Expired/Cancelled per
    the configured policy.

-   **REP-BR-21 --- Unpaid Repair Return.** When a repair expires before
    work begins, the system shall prevent further repair execution and
    make the device available for return/collection by the customer via
    the Collection Lifecycle (3.8).

**3.7 Completed Repair With Outstanding Balance**

Completing repair work and being entitled to release the device are
treated as two separate facts. A completed repair does not automatically
authorize release if money is still owed.

  ------------------------------------------------------------------------
  **Repair        **Financial         **Can Release Device?**
  Status**        Status**            
  --------------- ------------------- ------------------------------------
  Completed       Fully paid          Yes

  Completed       Balance outstanding No, by default

  Unrepairable    Payment received    Depends on settlement policy (3.5)

  Unrepairable    No payment          Yes --- return

  Failed          Balance outstanding Requires business resolution
  ------------------------------------------------------------------------

-   **REP-BR-22 --- Outstanding Balance.** When a repair is completed
    with an outstanding balance, the system shall record the remaining
    amount as payable and shall not automatically mark the device as
    eligible for release.

-   **REP-BR-23 --- Collection Payment Requirement.** By default, a
    completed repair with an outstanding balance shall require payment
    of that balance before the device is released.

-   **REP-BR-24 --- Authorized Collection Override.** An appropriately
    authorized staff member may override the payment requirement and
    release the device, subject to recording the authorization, the
    staff member, and the reason --- real businesses need this
    exception, and it must be auditable.

**3.8 Collection & Abandonment Management**

Collection is modeled as its own lifecycle, reusable by any device that
needs to leave the business\'s possession --- a successful repair, an
unrepairable device, or a repair that expired unpaid --- rather than
only applying to completed repairs.

+-----------------------------------------------------------------------+
| Ready for Collection/Return -\> Customer notified -\> Collection Due  |
|                                                                       |
| -\> (deadline passes) -\> Overdue for Collection                      |
|                                                                       |
| -\> (abandonment threshold passes) -\> Abandoned/Uncollected -\>      |
| Administrative Resolution                                             |
|                                                                       |
| \[continue holding \| contact customer \| waive fee \| extend         |
| deadline \| mark abandoned \| other\]                                 |
+-----------------------------------------------------------------------+

Two distinct, independently configurable policies apply here: an
abandonment timeline (when does the business stop treating this as an
ordinary pending collection) and an optional storage fee (does the
business charge for holding the device past a grace period). They are
not assumed to be linked.

-   **REP-BR-09 --- Collection Deadline.** When a repair is marked Ready
    for Collection, the system shall assign a collection deadline per
    the business\'s configured collection policy.

-   **REP-BR-10 --- Overdue Status.** If the customer has not collected
    the device by the deadline, the repair shall be marked Overdue for
    Collection and shall remain available for collection unless an
    administrative action changes its status.

-   **REP-BR-11 --- Abandonment Threshold.** The system shall support a
    configurable abandonment period after which an uncollected repair is
    flagged Abandoned/Uncollected and requires administrative
    resolution.

-   **REP-BR-12 --- Storage Fee Configuration.** Authorized
    administrators may enable or disable storage fees for uncollected
    devices and configure the grace period, fee amount, charging
    interval, and an optional maximum fee.

-   **REP-BR-13 --- Storage Fee Accrual.** Where storage fees are
    enabled, the system shall automatically calculate and display
    accrued storage charges per the configured policy.

-   **REP-BR-14 --- Storage Fee Visibility.** Collection deadlines,
    storage-fee rules, accrued fees, and abandonment dates shall be
    visible to authorized staff and, where appropriate, the affected
    customer, before charges arrive as a surprise.

-   **REP-BR-15 --- Collection Notifications.** The system shall notify
    customers when a repair becomes Ready for Collection and shall
    support configurable reminder notifications before and after the
    deadline.

-   **REP-BR-16 --- Abandonment Notification.** The system shall notify
    the customer before an uncollected device reaches the configured
    abandonment threshold.

-   **REP-BR-17 --- Administrative Resolution.** Reaching the
    abandonment threshold shall not automatically authorize disposal,
    sale, transfer, or other permanent disposition of the device. The
    device shall instead be flagged for an authorized administrative
    decision per the business\'s policy.

-   **REP-BR-18 --- Collection History.** The system shall maintain a
    chronological record of collection deadlines, notifications,
    extensions, storage-fee accruals, and administrative actions for
    every uncollected device, feeding the same audit trail required
    elsewhere in the BRD\'s Non-Functional Requirements.

-   **REP-BR-25 --- Collection Lifecycle Reuse.** Devices requiring
    customer return or collection shall enter the collection-management
    lifecycle regardless of whether the repair was completed,
    unsuccessful, or cancelled before repair execution.

-   **REP-BR-26 --- Collection Context.** The system shall distinguish a
    device Ready for Collection following a successful repair from a
    device Ready for Return following an uncompleted, cancelled, or
    unrepairable repair, so the customer is never told a repair is
    "ready" when no repair occurred.

**3.9 Repair State Summary**

The table below shows representative combinations of the four dimensions
introduced in Section 2.4, applied to the repair scenarios resolved in
this section.

  -----------------------------------------------------------------------------
  **Scenario**            **Repair State**    **Payment      **Device
                                              State**        Disposition**
  ----------------------- ------------------- -------------- ------------------
  Diagnosed, awaiting     Repairable          Unpaid         With Business
  customer authorization                                     

  Down payment not        Expired/Cancelled   Overdue        Ready for Return
  received in time                                           

  Diagnosed as not        Unrepairable        Depends on     Ready for Return
  fixable                                     prior payment  

  Repair finished, fully  Completed           Fully Paid     Ready for
  paid                                                       Collection

  Repair finished,        Completed           Partially Paid Ready for
  balance owed                                               Collection
                                                             (blocked)

  Repair attempted, did   Failed/Requires     Varies         With Business
  not resolve fault       Resolution                         (pending decision)

  Customer collected the  Completed           Fully Paid (or Collected
  device                                      authorized     
                                              override)      
  -----------------------------------------------------------------------------

**4. Sales / POS --- Business Logic**

**4.1 Checkout Reservation Model**

Generating a checkout (BRD ref. SALE-03) reserves inventory; it does not
sell it. The same On-hand / Reserved / Available mechanism from Section
2.1 applies, with the checkout itself as the reservation\'s source.

+-----------------------------------------------------------------------+
| On-hand=5 Reserved=0 Available=5                                      |
|                                                                       |
| -\> customer/cashier creates checkout for 1 unit                      |
|                                                                       |
| On-hand=5 Reserved=1 Available=4 (nothing physically left the shop    |
| yet)                                                                  |
|                                                                       |
| -\> payment confirmed                                                 |
|                                                                       |
| On-hand=4 Reserved=0 Available=4 (stock-out now recorded)             |
+-----------------------------------------------------------------------+

**4.2 Concurrency at Checkout**

Multiple open checkouts are normal and expected. What is never permitted
is two active checkouts both holding a claim on the same physical unit.

  ---------------------------------------------------------------------------
  **Checkout**   **Item**         **Unit**         **Valid?**
  -------------- ---------------- ---------------- --------------------------
  #101           iPhone 13        IMEI-001         Yes

  #102           iPhone 13        IMEI-002         Yes

  #103           iPhone 13        IMEI-001         No --- already held by
                                                   #101
  ---------------------------------------------------------------------------

**4.3 Payment Confirmation, Expiry & Release**

-   **SALE/INV-BR-01 --- Checkout Reservation.** When a checkout is
    created, the system shall reserve the required inventory for the
    configured checkout/payment validity period. Reserved inventory
    remains part of On-hand stock but is not Available for other
    transactions.

-   **SALE/INV-BR-02 --- Available Quantity.** Inventory available for
    allocation shall be calculated as On-hand less quantities reserved
    by active transactions.

-   **SALE/INV-BR-03 --- Unit-Level Reservation.** For individually
    tracked devices, including IMEI-tracked phones, each reserved
    physical unit shall be uniquely associated with the checkout that
    reserved it.

-   **SALE/INV-BR-04 --- Reservation Concurrency.** The system shall
    prevent the same inventory unit from being simultaneously reserved
    by multiple active checkouts, using transactional concurrency
    control so competing requests cannot allocate inventory beyond its
    Available quantity.

-   **SALE/INV-BR-05 --- Payment-to-Sale Conversion.** Upon valid
    payment confirmation, the system shall convert the checkout\'s
    active reservation into a completed stock-out/sale transaction.

-   **SALE/INV-BR-06 --- Checkout Expiration.** If payment is not
    confirmed before the checkout\'s configured validity period expires,
    the system shall expire the checkout and release its reservation
    back to Available stock, while retaining the expired checkout\'s
    record (unit, reservation time, expiry time, reason) for audit.

-   **SALE/INV-BR-07 --- Reservation Traceability.** The system shall
    maintain a traceable relationship between a checkout, its reserved
    unit(s), and any subsequent payment and sale transaction.

-   **SALE/PAY-BR-08 --- Expired Reservation Payment.** A payment
    confirmation received after its reservation has already expired
    shall not automatically complete the sale against released
    inventory; it shall instead be routed to a configured
    payment-exception process rather than silently succeeding or
    silently failing.

**4.4 Relationship to Repair Down Payments**

The payment-validity concept introduced here for POS checkouts
(SALE/INV-BR-01) is conceptually the same mechanism used for repair
down-payment deadlines (REP-BR-19). The two are not assumed to share one
timer --- the business may reasonably want a short checkout window
(minutes) alongside a longer repair-authorization window (days) --- so
each is independently configurable.

**4.5 Bank Transfer Confirmation & Payment Disputes**

A bank transfer cannot be verified in real time the way a POS terminal
or in-app payment can. Leaving a transaction simply "Unpaid" while a
transfer is in flight risks its checkout reservation expiring
(SALE/INV-BR-06) and the item being resold to someone else while the
original customer\'s payment is genuinely in transit; marking it "Paid"
before anyone has verified it risks releasing goods or starting repair
work against money that never arrived. A distinct in-between status,
with the reservation held rather than ticking down, resolves both risks.

-   **PAY-BR-01 --- Bank Transfer Pending Status.** A transaction
    awaiting bank-transfer confirmation shall be marked Payment Pending
    Confirmation --- distinct from both Paid and Unpaid --- and shall be
    visible as such to both staff and the customer.

-   **PAY-BR-02 --- Reservation Hold During Confirmation.** A checkout
    or repair-payment reservation with a bank-transfer claim pending
    confirmation shall not expire and release its inventory reservation
    (SALE/INV-BR-06) until the claim is confirmed or explicitly rejected
    by an authorized staff member.

-   **PAY-BR-03 --- Payment Dispute Handling.** Where a customer asserts
    payment was made but no admin has confirmed it, the system shall
    route the transaction to a Payment Dispute state requiring the
    customer to submit proof of payment and an authorized staff member
    to verify and resolve it, rather than auto-confirming or
    auto-cancelling the transaction.

**5. Warranty, Returns & After-Sales Management**

This is a new module, not present in the BRD\'s original section list.
Section 4.3 (SALE-02) mentions setting a warranty at the point of sale,
but a warranty flag alone does not describe what happens when it is
later invoked --- that gap is resolved here.

**5.1 Why a Dedicated Module Is Needed**

Treating "warranty repair" as simply a free repair job loses information
the business needs: why it was free, whether the claim was actually
eligible, and who authorized the cost. A dedicated Warranty & Resolution
layer evaluates eligibility first, then hands off to the repair,
inventory, or payment flows that already exist --- it does not duplicate
them.

**5.2 Key Distinctions**

  -----------------------------------------------------------------------
  **Concept**         **Definition**
  ------------------- ---------------------------------------------------
  Return              Customer wants to give back a recently purchased
                      product under the business\'s return policy.

  Warranty Claim      Customer reports a fault they believe is covered by
                      a product\'s warranty.

  Exchange / Trade-In Customer gives an existing device toward the price
                      of another (see 5.7).

  Repair Warranty     A previously completed repair is itself covered for
                      a defined period/condition.
  -----------------------------------------------------------------------

**5.3 Warranty Policy Model**

A warranty is a configured policy, not a yes/no flag on a sale. Each
policy defines:

-   Coverage duration and start point (e.g., 12 months from date of
    sale)

-   What is covered (e.g., manufacturing defects, internal component
    failure)

-   Exclusions (e.g., screen breakage, liquid damage, unauthorized
    repair, misuse)

-   Available remedies (repair, replacement, refund, exchange, store
    credit, or other business-defined remedy)

-   Coverage extent (full, a percentage, a fixed amount, labour-only,
    parts-only, or specific component)

**5.4 Warranty Claim Lifecycle**

+-----------------------------------------------------------------------+
| Warranty Claim Requested -\> Locate Warranty -\> Check Warranty       |
| Period -\> Device Assessment                                          |
|                                                                       |
| \|\-- Not Eligible \--\> Reject / Offer Paid Repair                   |
|                                                                       |
| \`\-- Eligible \--\> Determine Permitted Remedies                     |
|                                                                       |
| -\> Repair (hands off to existing Repair flow, Section 3, cost        |
| attributed to warranty)                                               |
|                                                                       |
| -\> Replace (reserve replacement unit -\> approval -\> exchange;      |
| defective unit -\> inventory)                                         |
|                                                                       |
| -\> Refund (refund request -\> authorization -\> payment processing)  |
|                                                                       |
| -\> Exchange (handled as a Trade-In-style transaction, Section 5.7)   |
+-----------------------------------------------------------------------+

A warranty is always associated with its originating sale or repair, its
product/physical unit (IMEI where applicable), and the customer, so
staff can retrieve the full history --- original purchase, prior claims,
prior repairs, prior exclusions --- from a single lookup.

**5.5 Eligibility, Assessment & Exclusions**

-   **WAR-BR-01 --- Warranty Policy.** The system shall support
    configurable warranty policies defining coverage duration, coverage
    scope, exclusions, eligibility conditions, and available resolution
    options.

-   **WAR-BR-02 --- Warranty Association.** A warranty shall be
    associated with the applicable product/device or completed repair,
    retaining a traceable link to the originating sale, repair,
    customer, and physical device where applicable.

-   **WAR-BR-03 --- Warranty Eligibility.** Before a warranty remedy is
    approved, the system shall verify that the warranty is active and
    that the reported issue satisfies the policy\'s eligibility
    conditions.

-   **WAR-BR-04 --- Warranty Assessment.** A warranty claim shall
    require assessment of the reported fault before a remedy is
    approved, unless the applicable policy explicitly permits automatic
    resolution.

-   **WAR-BR-05 --- Warranty Exclusions.** The system shall evaluate
    applicable exclusions --- e.g., physical damage, liquid damage,
    misuse, unauthorized modification or repair --- as configured on the
    policy, before approving a remedy.

**5.6 Resolution Options & Financial Control**

A warranty permitting a refund or replacement does not mean a technician
can trigger it directly. Approval and execution are separated so
financial exposure stays with the roles the BRD already assigns that
responsibility to (Accountant, Shop Owner).

-   **WAR-BR-06 --- Warranty Remedies.** A warranty policy shall define
    one or more permitted resolution options: repair, replacement,
    refund, exchange, store credit, or other business-defined remedy.

-   **WAR-BR-07 --- Warranty Repair.** Where warranty repair is
    approved, the system shall create or associate a repair job with the
    warranty claim and apply the warranty\'s coverage to that repair\'s
    financial responsibility, rather than recording it as an ordinary
    zero-cost repair.

-   **WAR-BR-08 --- Coverage Calculation.** The system shall support
    warranty coverage rules such as full coverage, percentage coverage,
    fixed-amount coverage, labour-only coverage, parts-only coverage, or
    other policy-defined limits, and shall calculate the
    customer-payable remainder accordingly.

-   **WAR-BR-09 --- Warranty Refund Request.** Where refund is an
    available remedy, the system shall create a refund request for
    authorization rather than automatically executing the refund.

-   **WAR-BR-10 --- Warranty Replacement.** Where replacement is
    approved, the system shall associate the replacement device with the
    warranty claim and record the original device as
    returned-under-warranty inventory (e.g., Pending Assessment), not
    simply remove it from records.

-   **WAR-BR-11 --- Repair Warranty.** The system shall support warranty
    coverage attached to a completed repair job itself, including
    configurable duration, covered work/parts, exclusions, and eligible
    remedies, distinct from a product warranty.

**5.7 Trade-In / Swap Flow**

A customer\'s existing device offered toward a new purchase is modeled
as an inventory acquisition plus a transaction credit --- not as a
discount --- so inventory and financial reports reconcile correctly.

+-----------------------------------------------------------------------+
| Customer selects desired phone                                        |
|                                                                       |
| -\> Trade-in device assessed (IMEI, condition, functional tests,      |
| cosmetic grade)                                                       |
|                                                                       |
| -\> Trade-in value determined (assessment) -\> Approval (separate     |
| role)                                                                 |
|                                                                       |
| -\> Trade-in value becomes transaction credit                         |
|                                                                       |
| -\> Balance payable = New phone price - Trade-in credit               |
|                                                                       |
| -\> Customer pays balance -\> New phone: stock-out \| Old phone:      |
| stock-in                                                              |
+-----------------------------------------------------------------------+

Example: a ₦1,000,000 phone traded against a ₦350,000 assessed device
leaves a ₦650,000 balance payable; the outgoing new phone and the
incoming trade-in are both recorded as separate stock movements, not
netted away.

-   **TRADE-BR-01 --- Trade-in Assessment.** Staff shall record and
    assess a customer\'s trade-in device, including device identity,
    IMEI where applicable, condition, functional assessment, and
    assessed trade-in value.

-   **TRADE-BR-02 --- Trade-in Credit.** An approved trade-in value
    shall be recorded as a credit against the customer\'s purchase
    transaction.

-   **TRADE-BR-03 --- Balance Calculation.** The system shall calculate
    the customer\'s remaining payable amount as the purchase amount less
    the approved trade-in credit and any other applicable adjustments.

-   **TRADE-BR-04 --- Trade-in Inventory.** On completion, the accepted
    trade-in device shall be recorded as inventory acquired by the
    business, and the sold device shall be recorded as a corresponding
    inventory outflow.

-   **TRADE-BR-05 --- Trade-in Approval.** Assessed trade-in values
    shall require authorization per configured staff permissions,
    separate from the staff member who performed the assessment, to
    prevent a single employee from both setting and approving an
    inflated value.

**5.8 Regulatory Consideration (Nigeria)**

Nigeria\'s Federal Competition and Consumer Protection Act (FCCPA)
recognizes consumer remedies for defective goods and services ---
including repair, replacement, and, in applicable circumstances, refund
--- and the FCCPC has taken public positions against blanket "no-refund"
policies in some circumstances. Rather than hard-coding one commercial
policy, AfProsPos should let each business configure its warranty,
return, and refund policy within applicable consumer-protection
requirements. This section is informational context for the business,
not legal advice; the business should confirm compliance with a
qualified adviser before finalizing its policy configuration.

**5.9 Return / Refund Window**

Section 5.2\'s "Return" concept implies a time limit but the original
version of this document never defined one. A configured window matters
beyond returns themselves --- the Referral module (Section 7.3) depends
on knowing when a purchase is safely final before paying out a bonus on
it.

-   **WAR-BR-12 --- Return Window.** The system shall support a
    configurable return/refund window --- a period after purchase or
    repair collection --- within which a customer may request a return
    or refund. Requests made after this window shall be denied by
    default, subject to authorized administrative override.

**6. Commission Management --- Business Logic**

Commission touches four other modules --- Sales, Repairs, Payments, and
Marketing --- and gets it wrong in different ways depending on which one
is driving the transaction. The rules below establish one consistent
basis (a defined "commissionable amount"), one consistent timing rule
(earned as payment lands, not at invoice creation), and one consistent
response to money moving backwards (an append-only adjustment ledger,
never a silent rewrite).

**6.1 Commissionable Amount**

The customer\'s price and the staff member\'s commission are two
separate calculations --- discounting a sale never changes what the
commission is based on by accident. A promotional discount reduces the
amount the business actually earned, so it reduces the commission base
by default; a trade-in or store-credit reduction is different (the
business received another asset or had already recognized that
liability), so those are not silently treated the same way.

  -----------------------------------------------------------------------
  **Component**                      **Example**
  ---------------------------------- ------------------------------------
  Selling price                      ₦1,000,000

  Promotional discount               −₦100,000

  Commissionable amount (default)    ₦900,000

  Commission @ 5%                    ₦45,000
  -----------------------------------------------------------------------

-   **COMM-BR-01 --- Commissionable Amount.** The system shall calculate
    sales commission using the transaction\'s configured commissionable
    amount. By default, promotional discounts shall reduce the
    commissionable amount, such that commission is calculated on the net
    selling amount after applicable discounts. The commission basis
    shall be configurable by an authorized administrator, and treatment
    of trade-in credit, store credit, and other adjustments shall be
    determined explicitly by the configured commission policy rather
    than automatically following the discount rule.

**6.2 Payment-Based Recognition: Earned vs. Paid**

An invoice is an amount owed, not revenue collected. Commission is
therefore earned progressively as each payment against a transaction is
confirmed --- a ₦300,000 down payment on a ₦1,000,000 sale earns
commission on ₦300,000, with the remainder earned only if and when the
balance is actually paid. Commission Earned is also tracked separately
from Commission Paid, since a business may calculate commission
immediately but settle it to staff weekly or monthly.

-   **COMM-BR-02 --- Payment-Based Commission Recognition.** Where a
    sale is paid through multiple payments, commission shall be
    progressively earned based on confirmed payments received against
    the transaction. Commission shall not be earned on unpaid balances
    unless the configured commission policy explicitly permits
    invoice-based recognition.

**6.3 Reversal, Ledger & Partial Refunds**

Commission follows the economic state of the underlying transaction: a
full refund reverses the related commission, a partial refund reverses
only the corresponding share, and a checkout that simply expired unpaid
never generated commission to reverse in the first place. None of this
happens by silently editing the original commission record --- every
change is a new, linked, append-only ledger entry, so a historical
report always shows both what was originally earned and what was later
adjusted.

+-----------------------------------------------------------------------+
| Sale commission: +₦45,000 \[Earned\]                                  |
|                                                                       |
| -\> Customer refunded ₦200,000 of a ₦900,000 commissionable sale      |
|                                                                       |
| Reversal: -₦10,000 \[Reversed\] (proportional to the refunded share)  |
|                                                                       |
| Net commission: ₦35,000                                               |
+-----------------------------------------------------------------------+

-   **COMM-BR-03 --- Commission Reversal.** When a completed sale,
    repair, or other commission-generating transaction is subsequently
    reversed or refunded, the system shall automatically determine and
    record the corresponding commission adjustment based on the portion
    of the transaction affected.

-   **COMM-BR-04 --- Commission Ledger.** Commission reversals shall be
    recorded as separate adjustment entries linked to the original
    commission record and underlying transaction. Original commission
    records shall not be deleted or overwritten.

-   **COMM-BR-05 --- Partial Reversal.** Where only part of a
    commission-generating transaction is refunded or reversed, the
    system shall calculate and record only the corresponding commission
    adjustment rather than automatically reversing the entire
    commission.

-   **COMM-BR-06 --- Paid Commission Clawback.** Where a commission has
    already been paid when a subsequent reversal occurs, the resulting
    negative commission balance shall be recorded as an amount subject
    to the business\'s configured settlement or recovery policy ---
    e.g., offset against a future payout --- rather than the system
    attempting to reverse a payment that has already left the business.

-   **COMM-BR-07 --- Authorized Adjustment.** The system shall allow
    authorized staff to review and approve applicable commission
    adjustments and shall record the reason, user, date, and affected
    transaction.

**6.4 Multi-Staff Attribution & Assignment History**

A repair reassigned from one technician to another is not automatically
a 50/50 split --- the technician who diagnosed a phone and the one who
actually repaired it may have contributed very differently. The assigned
technician remains the default sole recipient; the system additionally
preserves the full assignment history so a manual or configured
allocation across multiple contributors is possible without
reconstructing who-did-what from memory.

-   **COMM-BR-08 --- Multi-Staff Commission Attribution.** The system
    shall support commission attribution to one or more staff members
    involved in a commission-generating repair or transaction.

-   **COMM-BR-09 --- Commission Allocation.** Where multiple staff
    members are eligible for commission on a repair, the system shall
    support allocation of the commission among eligible staff according
    to the configured commission policy or an authorized manual
    allocation.

-   **COMM-BR-10 --- Assignment History.** The system shall maintain the
    history of staff assignments and relevant work performed during a
    repair, including reassignment events.

-   **COMM-BR-11 --- Commission Preservation.** Changes to staff
    assignment shall not automatically modify previously earned
    commission records. Any resulting commission reallocation or
    adjustment shall be recorded as a separate auditable transaction.

-   **COMM-BR-12 --- Commission Snapshot.** Each earned commission
    record shall retain the commission rate, commissionable amount,
    applicable commission rule or policy version, and resulting
    commission amount used when the commission was calculated, so a
    later rate change never silently alters a historical record.

-   **COMM-BR-13 --- Policy Changes.** Changes to commission rates or
    commission policies shall apply according to their effective date
    and shall not automatically recalculate previously earned
    commissions.

-   **COMM-BR-14 --- Discount Impact.** Commission shall be calculated
    using the transaction\'s configured commissionable amount after
    applicable promotional discounts, per COMM-BR-01; treatment of other
    credits or adjustments (trade-in, store credit, referral vouchers)
    shall be determined by the configured commission policy rather than
    automatically following the discount rule.

**7. Referral Program --- Business Logic**

**7.1 Qualifying Transaction & Bonus Timing**

"First purchase" is defined precisely rather than left ambiguous: it is
the referred customer\'s first completed, qualifying transaction ---
sale or paid repair, per business configuration --- not the first
invoice or checkout created. An expired checkout, a cancelled repair, or
a free warranty repair does not qualify, and a referral relationship
itself is tracked separately from whether a bonus has actually been
earned from it, so a referral can exist for months before it produces
any reward.

+-----------------------------------------------------------------------+
| Customer A refers Customer B -\> Referral relationship recorded (no   |
| bonus yet)                                                            |
|                                                                       |
| Customer B completes a qualifying transaction (payment confirmed)     |
|                                                                       |
| -\> Referral condition satisfied -\> Bonus becomes Earned             |
+-----------------------------------------------------------------------+

-   **REF-BR-01 --- Qualifying Transaction.** The system shall determine
    a referred customer\'s eligibility for a referral bonus based on
    completion of the first transaction that satisfies the configured
    referral policy.

-   **REF-BR-02 --- Transaction Types.** The system shall support
    configuration of the transaction types that qualify as a referred
    customer\'s first qualifying transaction, including product sales,
    paid repairs, or both --- defaulting to both, since each represents
    real business revenue.

-   **REF-BR-03 --- Completed Transaction.** Unpaid, cancelled, expired,
    or otherwise incomplete transactions shall not qualify as a
    completed referral transaction unless explicitly permitted by the
    configured referral policy.

-   **REF-BR-04 --- Referral Bonus.** Once a referred customer completes
    a qualifying transaction, the system shall calculate and record the
    applicable referral bonus for the associated referrer.

-   **REF-BR-05 --- Referral Reversal.** If a qualifying transaction is
    subsequently refunded or reversed, the system shall identify the
    associated referral bonus and record the applicable bonus adjustment
    according to the configured referral policy --- refined for
    AfProsPos by the window-based rule in Section 7.3 (REF-BR-14).

-   **REF-BR-06 --- Referral Attribution.** Each qualifying referred
    customer shall have an auditable association with the referrer
    responsible for the referral, and the system shall prevent multiple
    referral bonuses from being generated for the same qualifying
    referral unless explicitly permitted by business policy.

**7.2 Reward Generation**

Rather than hard-wiring the Referral module to one payout mechanism, a
referral simply generates a Reward --- the same reusable concept the
Marketing module uses for any campaign benefit (Section 8.1). This means
a referral can pay out as store credit, a discount voucher, or (via
store credit made eligible for withdrawal) an eventual cash payment,
without the Referral module needing to know how any of those actually
work.

-   **REF-BR-07 --- Reward Generation.** When a referral satisfies the
    configured qualifying conditions, the system shall generate the
    reward defined by the applicable referral campaign, using the reward
    mechanisms defined in Section 8.1.

**7.3 Refund Window & Bonus Timing**

Rather than paying a bonus immediately and then trying to claw it back
if the underlying purchase is refunded, the bonus is held in a Pending
state until the business\'s configured return/refund window (WAR-BR-12)
has passed. This sidesteps the recovery problem in the common case
entirely: if no refund happens during the window, the bonus simply
becomes payable; if a refund happens during the window, the bonus is
cancelled and was never paid out; if a customer tries to refund after
the window has already closed, the refund itself is denied by default
and the referrer keeps their bonus. REF-BR-05\'s reversal mechanism
remains as a safety net for the one remaining edge case --- an
authorized override that permits a late refund after a bonus has already
been paid.

+-----------------------------------------------------------------------+
| Qualifying transaction paid -\> Bonus: Pending                        |
|                                                                       |
| \|\-- Return window elapses, no refund \--\> Bonus: Payable           |
|                                                                       |
| \|\-- Refund occurs WITHIN the window \--\> Bonus: Cancelled (never   |
| paid)                                                                 |
|                                                                       |
| \`\-- Refund requested AFTER the window \--\> Refund Denied           |
| (WAR-BR-12) -\> Bonus stays Payable                                   |
|                                                                       |
| \[Admin override permits a late refund despite denial, bonus already  |
| paid\]                                                                |
|                                                                       |
| -\> Recovery of the already-paid bonus follows REF-BR-05              |
+-----------------------------------------------------------------------+

-   **REF-BR-14 --- Refund Window & Bonus Timing.** Where the business
    has configured a return/refund window (WAR-BR-12), a referral bonus
    for a qualifying transaction shall be held in a Pending state until
    that window elapses. If the qualifying transaction is refunded
    within the window, the pending bonus shall be cancelled and not
    paid. If a refund is requested after the window has closed, the
    refund shall be denied by default per WAR-BR-12 and the referrer
    shall keep the bonus. Where an authorized override permits a refund
    after the window despite denial, and the bonus has already been
    paid, recovery of that bonus shall follow REF-BR-05.

**7.4 Fraud & Self-Referral Safeguards**

The goal is blocking the obvious abuse case automatically while routing
ambiguous ones to a human --- aggressive automatic fraud detection risks
blocking legitimate cases (two family members sharing a household phone
number, for instance), so the system flags rather than auto-rejects
anything short of a customer referring themselves.

-   **REF-BR-08 --- Self-Referral Prevention.** The system shall prevent
    a customer from referring themselves and earning a referral reward
    from their own qualifying transaction.

-   **REF-BR-09 --- Referral Integrity.** The system shall maintain an
    auditable association between a referred customer and their referrer
    and shall restrict modification of an established referral
    relationship to authorized staff.

-   **REF-BR-10 --- Campaign Limits.** The system shall support
    configurable limits on referral activity and rewards, including
    referral count, reward frequency, reward value, and other
    campaign-defined restrictions.

-   **REF-BR-11 --- Fraud Detection.** The system shall identify
    configured indicators of potentially fraudulent or abusive referral
    activity (e.g., referrer and referred customer sharing a phone
    number, payment reference, or device IMEI) and flag affected
    referrals for authorized staff review rather than auto-approving or
    auto-blocking them.

-   **REF-BR-12 --- Fraud Review.** Authorized staff shall be able to
    approve or reject flagged referral activity, with the system
    recording the decision, reason, user, and timestamp.

-   **REF-BR-13 --- Reward Integrity.** Referral rewards shall remain
    traceably associated with the referral and qualifying transaction
    throughout their lifecycle, including issuance, use, adjustment,
    reversal, or withdrawal.

**8. Marketing Campaigns & Rewards --- Business Logic**

Rather than building Referral, Loyalty, and Promotional discounting as
three separate features, they share one underlying engine: a Campaign
defines eligibility, a trigger, and a Reward; the reward is generated
and consumed through shared, reusable mechanisms. This section defines
those shared reward mechanisms and the rules governing how multiple
discounts and rewards interact on the same transaction.

**8.1 Reward Types: Store Credit & Vouchers**

Cash is deliberately not an ordinary referral/campaign reward type.
Instead, a reward is issued as Store Credit or a Voucher --- two
instruments that behave differently --- and Store Credit can optionally
be made eligible for cash withdrawal under conditions the business
controls (a minimum holding period, verification, admin approval), which
gives the business far more control than paying out cash the moment a
reward is earned.

  -----------------------------------------------------------------------
  **Instrument**   **Behavior**
  ---------------- ------------------------------------------------------
  Store Credit     A traceable customer-value ledger (issuance, usage,
                   adjustment, expiration). Represents value the business
                   owes the customer within the system.

  Discount Voucher A conditional discount entitlement (e.g., 10% off, max
                   ₦20,000, valid 30 days, one use). Does not represent
                   money owed --- it reduces a future transaction\'s
                   price.

  Credit           Optional conversion of eligible Store Credit into a
  Withdrawal       cash payout, subject to configured eligibility
                   conditions and authorization --- not a default
                   behavior of Store Credit.
  -----------------------------------------------------------------------

-   **MKT-BR-01 --- Configurable Reward Types.** The system shall allow
    authorized administrators to configure the reward type offered by a
    campaign, including store credit, discount vouchers, and other
    supported reward mechanisms.

-   **MKT-BR-02 --- Reward Conditions.** Campaign rewards shall support
    configurable conditions including eligibility, value, validity
    period, usage restrictions, minimum transaction amount, maximum
    benefit, stacking rules, and other applicable business conditions.

-   **MKT-BR-03 --- Store Credit.** The system shall maintain store
    credit as a traceable customer-value ledger that records credit
    issuance, usage, adjustments, expiration, and other applicable
    movements.

-   **MKT-BR-04 --- Credit Withdrawal.** The system may support
    conversion of eligible store credit into a cash withdrawal where
    enabled by the applicable campaign or business policy. Withdrawal
    eligibility and conditions shall be configurable and subject to
    authorization.

-   **MKT-BR-05 --- Voucher.** The system shall support configurable
    discount vouchers that may be issued as campaign rewards and applied
    to eligible transactions according to their configured conditions.

**8.2 Discount & Reward Stacking**

Multiple things can reduce what a customer pays on one transaction --- a
promotion, a referral voucher, trade-in credit, store credit --- and
stacking them unpredictably risks destroying margin. The safe default is
non-stackable unless a campaign explicitly permits it, applied in a
fixed, deterministic order, with every component of the final price
traceable back to exactly why it was deducted.

  -----------------------------------------------------------------------
  **Component**                      **Amount**
  ---------------------------------- ------------------------------------
  Base price                         ₦1,000,000

  Promotion (−10%)                   −₦100,000

  Referral voucher                   −₦20,000

  Trade-in credit                    −₦200,000

  Store credit used                  −₦50,000

  Customer payable                   ₦630,000
  -----------------------------------------------------------------------

-   **MKT-BR-06 --- Discount Stacking.** The system shall support
    configurable stacking rules for promotional campaigns and rewards.
    Campaigns shall be configured as stackable or non-stackable
    according to authorized business policy, with non-stacking as the
    default where no explicit stacking rule is defined.

-   **MKT-BR-07 --- Application Order.** The system shall apply multiple
    eligible discounts and credits according to a deterministic
    configured order (promotion → referral/voucher → trade-in credit →
    store credit) and shall prevent conflicting or prohibited
    combinations.

-   **MKT-BR-08 --- Adjustment Traceability.** The system shall record
    each discount, voucher, credit, trade-in adjustment, and other price
    adjustment separately within the transaction so that the final
    payable amount can be traced to its individual components.

-   **MKT-BR-09 --- Campaign Override.** Where authorized staff override
    a campaign\'s stacking or discount rules, the system shall record
    the override, user, reason, affected campaign, and resulting
    adjustment.

**8.3 Customer Segment Source of Truth**

A segment like "first-time customer" is defined by Marketing but never
duplicated as stored data Marketing owns --- it is evaluated live
against the module that actually owns the underlying fact (Sales/Repairs
for purchase history, Referral for referral relationships), because a
stored is_first_time_customer flag inevitably goes stale the moment it
isn\'t updated everywhere it needs to be. The one exception is when a
campaign benefit is actually granted: at that moment, the system
snapshots why the customer qualified, so a later change in their history
never rewrites the historical record of what already happened.

-   **MKT-BR-10 --- Segment Definition.** The Marketing & Campaigns
    module shall define customer segments using configurable eligibility
    criteria and shall not duplicate authoritative customer,
    transaction, payment, repair, or referral data solely for the
    purpose of determining segment membership.

-   **MKT-BR-11 --- Source of Truth.** Customer segment eligibility
    shall be evaluated using authoritative data from the module
    responsible for the relevant business information (e.g.,
    Sales/Repairs for purchase history, Referral for referral
    relationships).

-   **MKT-BR-12 --- Dynamic Eligibility.** Where practical, campaign
    eligibility shall be evaluated from current qualifying business data
    at the time a campaign benefit is requested or applied, rather than
    from a separately stored segment flag.

-   **MKT-BR-13 --- Eligibility Snapshot.** When a campaign benefit is
    granted, the system shall record the campaign, customer, eligibility
    result, applicable conditions, and relevant transaction information
    required to explain why the customer qualified.

-   **MKT-BR-14 --- Cross-Module Data.** Marketing campaigns may consume
    information from Customer Management, Sales/POS, Repair Management,
    Payments, Referral, Inventory, and other authorized modules for
    eligibility evaluation while preserving each module\'s ownership of
    its underlying business data.

**9. Inventory, Multi-Shop & SKU --- Business Logic**

**9.1 Product, SKU & Inventory Item Model**

Three concepts that were previously blurred together are now distinct: a
Product (the conceptual model --- "Apple iPhone 15"), a SKU (a specific
sellable configuration --- "iPhone 15 / 128GB / Black", defined once at
the business level), and an Inventory Item (a physical unit --- one
IMEI, or a quantity for non-serialized stock). This is what makes
SKU-04\'s per-shop-vs-system-wide question resolvable: the SKU belongs
to the business catalog, not to any one shop, so a phone transferred
between shops never needs a second SKU.

+-----------------------------------------------------------------------+
| Product: iPhone 15                                                    |
|                                                                       |
| -\> SKU: IPH15-128-BLK (defined once, business-wide)                  |
|                                                                       |
| -\> Inventory Item: IMEI 001 \[current shop: A\]                      |
|                                                                       |
| -\> Inventory Item: IMEI 002 \[current shop: A\]                      |
|                                                                       |
| -\> Inventory Item: IMEI 003 \[current shop: B\]                      |
|                                                                       |
| Non-serialized SKU (e.g. USB-C-20W): Quantity=50 per shop, no         |
| per-unit records needed                                               |
+-----------------------------------------------------------------------+

-   **INV-BR-08 --- Business-Wide SKU.** SKUs shall be unique across the
    business and shall identify the same product configuration
    regardless of the shop in which its inventory is held.

-   **INV-BR-09 --- Central Product Catalog.** The system shall maintain
    a centralized business-level product catalog containing product and
    SKU definitions, while shop-level records shall maintain inventory
    quantities, physical inventory units, availability, and applicable
    shop-specific commercial information (e.g., a shop-specific price
    override) --- this is also what makes cross-shop search (BRD ref.
    CUST-04) possible: a customer searches one catalog, not each shop\'s
    catalog separately.

-   **INV-BR-10 --- Inventory Location.** Each inventory item shall
    maintain its current shop/location, updated by inventory-transfer
    transactions, while a separate inventory-movement record maintains
    the historical record of transfers between shops --- the
    current-shop field alone is state, not history.

**9.2 Inter-Shop Transfers**

A transfer moves inventory belonging to a SKU between shops; it never
creates a second SKU. For an IMEI-tracked phone this is enforced by a
global uniqueness constraint on IMEI (not merely unique-within-shop),
and the whole operation --- verify the item is transferable, verify it
isn\'t reserved, change its current shop, write the movement record ---
happens as a single atomic transaction so a failure partway through can
never leave a transfer record disagreeing with where the system thinks
the item actually is.

-   **INV-BR-11 --- SKU Transfer.** An inter-shop transfer shall move
    existing inventory associated with a SKU from one shop to another
    without creating a new SKU or changing the inventory item\'s product
    identity.

-   **INV-BR-12 --- IMEI Uniqueness.** IMEI values shall be globally
    unique within the business and shall remain associated with the same
    inventory item throughout transfers between shops.

-   **INV-BR-13 --- Transfer History.** Every completed inventory
    transfer shall generate an auditable movement record containing the
    inventory item or quantity, source shop, destination shop,
    initiating staff member, authorization where applicable, timestamp,
    and transfer status.

-   **INV-BR-14 --- Transfer Restrictions.** The system shall prevent or
    appropriately restrict the transfer of inventory that is actively
    reserved (per Section 2.1), allocated to an incomplete transaction,
    or otherwise unavailable for transfer according to configured
    business rules.

**9.3 Available Stock for Allocation**

This restates and generalizes the Available-stock principle from Section
3.4 (INV-BR-07): any operation that allocates inventory --- not just
low-stock alerts, but repairs, sales, and transfers alike --- must check
Available (On-hand less Reserved), never On-hand alone, or the system
will happily promise the same physical unit to two different
transactions.

-   **INV-BR-15 --- Available Stock.** Available inventory shall account
    for active reservations and shall be used when determining whether
    inventory can be allocated to new sales, repairs, transfers, or
    other inventory-consuming transactions.

**10. RBAC --- Business Logic**

**10.1 Multiple Roles & Effective Permissions**

Permissions are additive across a staff member\'s assigned roles, not a
contest where one role "wins" --- a role simply not granting a
capability isn\'t the same as that role denying it, so holding both
Cashier and Sales Manager gives a staff member the union of what each
role allows. No permission granted by any assigned role means no access;
enforcement lives on the server, never only in what the interface
happens to show or hide.

-   **RBAC-BR-01 --- Multiple Roles.** The system shall allow a staff
    member to be assigned multiple roles simultaneously.

-   **RBAC-BR-02 --- Effective Permissions.** A staff member\'s
    effective permissions shall be derived from all roles assigned to
    the staff member. Permissions granted by any assigned role shall be
    available to the staff member unless an explicit restriction is
    defined by the applicable authorization policy.

-   **RBAC-BR-03 --- Default Authorization.** A staff member shall not
    be permitted to perform an action unless the required permission has
    been granted through an assigned role or other explicitly authorized
    mechanism.

-   **RBAC-BR-04 --- Role Management.** Authorized administrators shall
    be able to create, modify, assign, and deactivate roles and their
    associated permissions according to their own administrative
    permissions.

-   **RBAC-BR-05 --- Permission Enforcement.** Authorization checks
    shall be enforced at the server/business-logic level for protected
    operations. User-interface restrictions shall not constitute the
    sole mechanism for enforcing permissions.

-   **RBAC-BR-06 --- Permission Extensibility.** The authorization model
    shall support the addition of new roles and permissions without
    requiring structural changes to existing business transactions or
    modules.

**10.2 Historical Preservation & Deactivation**

Roles, rates, and permissions change over time; the historical record of
what applied when a past action was taken must not change with them. A
sale commissioned at a 5% rate stays a 5% commission in the record even
after the rate is later changed to 3%, and deactivating a staff member
stops their future access without deleting or altering anything they did
while active.

-   **RBAC-BR-07 --- Historical Role Preservation.** Changes to a staff
    member\'s role assignments or permissions shall not alter the
    historical authorization context or records of previously performed
    transactions and activities.

-   **RBAC-BR-08 --- Staff Deactivation.** Deactivating a staff account
    shall prevent future access or authorized operations while
    preserving the staff member\'s historical transactions, activities,
    commission records, and audit records.

-   **RBAC-BR-09 --- Assignment History.** The system shall maintain
    sufficient history of staff role assignments and changes to support
    identification of the authorization context applicable when
    historical actions were performed.

**11. Unified Audit Trail --- Business Logic**

RBAC-06 (administrative actions) and the BRD\'s Non-Functional
Requirements (financial/stock audit log) were never actually two
separate logging systems that needed reconciling --- they are two
categories of event inside one audit infrastructure. A single unified
trail, with typed events (RBAC, Sales, Inventory, Repairs, Finance,
\...), makes questions like "who changed this employee\'s permissions
right before they approved this refund" answerable from one place
instead of two.

+-----------------------------------------------------------------------+
| AUDIT LOG                                                             |
|                                                                       |
| \|\-- Authentication (login, logout)                                  |
|                                                                       |
| \|\-- RBAC (role created, permission changed, staff deactivated)      |
|                                                                       |
| \|\-- Sales (sale created, cancelled, refund approved)                |
|                                                                       |
| \|\-- Inventory (stock adjusted, item transferred, reservation        |
| released)                                                             |
|                                                                       |
| \|\-- Repairs (status changed, technician reassigned, warranty        |
| approved)                                                             |
|                                                                       |
| \`\-- Finance (payment confirmed, expense created, commission         |
| adjusted)                                                             |
+-----------------------------------------------------------------------+

-   **AUD-BR-01 --- Unified Audit Trail.** The system shall maintain a
    unified audit trail for administrative, financial, inventory,
    operational, security, and other configured auditable activities.

-   **AUD-BR-02 --- Event Classification.** Audit events shall be
    categorized by the type of activity and associated business module
    or entity, including but not limited to RBAC, Sales, Inventory,
    Repairs, Payments, Commission, Expenses, and Marketing.

-   **AUD-BR-03 --- Audit Information.** Auditable events shall record
    sufficient information to identify the actor, action performed,
    affected entity or transaction, timestamp, and relevant contextual
    information required to reconstruct the event.

-   **AUD-BR-04 --- State Changes.** Where applicable, audit events
    shall retain relevant before-and-after values for significant
    administrative, financial, inventory, configuration, and permission
    changes.

-   **AUD-BR-05 --- Audit Immutability.** Audit records shall not be
    modified or deleted through normal application operations.
    Corrections to previously recorded events shall be represented by
    subsequent auditable events, never by editing the original.

-   **AUD-BR-06 --- Critical Operation Logging.** Critical financial,
    inventory, authorization, and administrative operations shall
    generate corresponding audit events as part of the operation\'s
    processing, ideally succeeding or failing together with the business
    record itself.

**12. Notifications --- Business Logic**

**12.1 Event-Driven Delivery Architecture**

A business module never "sends a WhatsApp message" directly --- it emits
a business event (e.g., REPAIR_COMPLETED), and a separate Notification
Engine decides the recipient, channel, and provider from there. This
decoupling matters operationally: a repair can complete successfully
even if WhatsApp is down, because notification delivery is processed
asynchronously and never allowed to roll back the business transaction
that triggered it.

+-----------------------------------------------------------------------+
| Repair Completed -\> Business Event: REPAIR_COMPLETED                 |
|                                                                       |
| -\> Notification Engine -\> recipient + preferences -\>               |
| channel/provider selected                                             |
|                                                                       |
| -\> WhatsApp Provider A: Attempt 1 FAILED -\> Retry -\> Attempt 2     |
| FAILED                                                                |
|                                                                       |
| -\> Fallback: Email Provider B -\> DELIVERED                          |
|                                                                       |
| (Repair status remains Completed regardless of notification outcome)  |
+-----------------------------------------------------------------------+

-   **NOTIF-BR-01 --- Event-Based Notifications.** Business modules
    shall generate notification events for configured business
    occurrences rather than directly managing external notification
    delivery.

-   **NOTIF-BR-02 --- Asynchronous Delivery.** Notification delivery
    shall be processed independently of the successful completion of the
    originating business transaction so that failure of an external
    notification provider does not cause the originating business
    operation to fail.

-   **NOTIF-BR-03 --- Multi-Channel Delivery.** The system shall support
    configurable notification channels, including in-application
    notifications, email, WhatsApp, and optionally SMS or other
    supported channels.

-   **NOTIF-BR-04 --- Provider Abstraction.** External notification
    channels shall support one or more configurable providers, allowing
    providers to be added, removed, prioritized, or replaced without
    modifying the business logic of modules generating notifications.

-   **NOTIF-BR-05 --- Delivery Attempts.** The system shall maintain a
    record of notification delivery attempts, including channel,
    provider, attempt number, timestamp, status, and relevant provider
    response or error information.

**12.2 Retry, Failover & Delivery Status**

Not every failure should be retried the same way: a temporary provider
timeout deserves a retry with increasing delay; an invalid phone number
never will succeed no matter how many times it\'s retried, and should
instead go straight to fallback. Delivery status is tracked as reported
by the provider rather than assumed --- "accepted by the provider" is
not the same claim as "delivered to the customer," and the system
doesn\'t conflate the two.

-   **NOTIF-BR-06 --- Retry Policy.** The system shall support
    configurable retry policies for recoverable notification failures,
    including maximum attempts and increasing retry intervals.

-   **NOTIF-BR-07 --- Failure Classification.** Notification failures
    shall, where supported by the provider, be classified according to
    their nature --- transient, permanent, recipient-related, or
    provider-related --- to determine whether retry or fallback is
    appropriate.

-   **NOTIF-BR-08 --- Provider Failover.** Where multiple providers are
    configured for a notification channel, the system shall support
    provider prioritization and failover when a provider becomes
    unavailable or experiences an applicable service failure.

-   **NOTIF-BR-09 --- Delivery Status.** The system shall support
    asynchronous provider delivery-status updates through provider
    callbacks, webhooks, or equivalent mechanisms where available.

-   **NOTIF-BR-10 --- Delivery Traceability.** Each notification shall
    be uniquely identifiable and traceable from its originating business
    event through its delivery attempts and final delivery status.

-   **NOTIF-BR-11 --- Duplicate Prevention.** The notification system
    shall prevent duplicate delivery resulting from repeated processing
    of the same notification event or retry operation.

-   **NOTIF-BR-12 --- Exhausted Notifications.** Notifications that
    remain undelivered after the configured retry and fallback policies
    have been exhausted shall be marked as failed or exhausted and
    retained for administrative monitoring or manual intervention.

-   **NOTIF-BR-13 --- Business Transaction Independence.** Failure to
    deliver a notification shall not automatically reverse or invalidate
    the business transaction that generated the notification.

**12.3 Notification Categories & Preferences**

A customer disabling WhatsApp notifications generally should never mean
they stop being told their repaired phone is ready --- that\'s why
transactional notifications (payment due, repair ready, refund status)
are distinguished from marketing communications. Customers control which
channel carries a transactional notification, not whether it\'s sent at
all; marketing notifications remain fully opt-in/opt-out, independent of
transactional preferences.

-   **NOTIF-BR-14 --- Notification Categories.** The system shall
    classify notifications into appropriate categories, including
    transactional, operational, security/account, and marketing
    communications.

-   **NOTIF-BR-15 --- Channel Preferences.** Customers shall be able to
    configure their preferred notification channels for notification
    types that permit user-controlled preferences.

-   **NOTIF-BR-16 --- Mandatory Notifications.** Authorized
    administrators shall be able to designate specified notification
    types as mandatory. Mandatory notifications shall not be disabled
    through ordinary customer notification preferences, though the
    customer may still choose which enabled channel carries them.

-   **NOTIF-BR-17 --- Marketing Preferences.** Marketing and promotional
    communications shall support customer opt-in/opt-out preferences
    independently of essential transactional notifications.

-   **NOTIF-BR-18 --- Dashboard Notifications.** Important transactional
    notifications shall be retained within the customer\'s dashboard
    where applicable, independently of the success or failure of
    external notification channels --- the in-app channel is treated as
    an internal, always-available notification record rather than an
    external delivery channel subject to the same failure modes.

-   **NOTIF-BR-19 --- Reminder Scheduling.** The system shall support
    configurable reminder schedules for applicable notifications,
    including payment reminders, collection reminders (per
    REP-BR-15/16), and other time-dependent business notifications.

**13. Expense Tracking & Consolidated Financial Reporting**

Sales, Repairs, Inventory, Commission, and Expenses each remain the
system of record for their own detail data; a separate
Reporting/Financial layer consumes and aggregates across all of them
into one P&L-style view, so the shop owner is never left manually
reconciling four separate reports by hand --- exactly the paper-based
problem AfProsPos exists to replace.

+-----------------------------------------------------------------------+
| Sales \| Repairs \| Inventory \--+                                    |
|                                                                       |
| +\--\> Payments \--+                                                  |
|                                                                       |
| +\--\> Commissions \--+                                               |
|                                                                       |
| +\--\> Expenses \--+                                                  |
|                                                                       |
| v                                                                     |
|                                                                       |
| REPORTING / FINANCIAL (business-wide + per-shop)                      |
+-----------------------------------------------------------------------+

-   **FIN-BR-01 --- Consolidated Reporting.** The system shall provide a
    consolidated reporting layer through which sales, repair, inventory,
    payment, commission, expense, refund, and other relevant operational
    data can be analyzed without requiring manual reconciliation of
    separate module reports.

-   **FIN-BR-02 --- Source Ownership.** Each operational module shall
    remain the authoritative source for the data it generates, while the
    Reporting and Financial module shall consume and aggregate
    authorized data from those sources for reporting purposes.

-   **FIN-BR-03 --- Financial Distinction.** The system shall
    distinguish revenue, payments received, outstanding receivables,
    inventory costs, operating expenses, commissions, refunds, and other
    relevant financial measures rather than treating them as a single
    monetary value.

-   **FIN-BR-04 --- Profitability.** The system shall support
    calculation and presentation of profitability using applicable
    revenue, cost of goods sold, repair costs, commissions, refunds, and
    operating expenses according to configured business rules.

-   **FIN-BR-05 --- Multi-Shop Reporting.** The system shall support
    financial and operational reporting at both business-wide and
    individual-shop levels, with transactions associated with their
    applicable shop or business-level scope.

-   **FIN-BR-06 --- Shared Expenses.** The system shall support expenses
    that apply to an individual shop as well as expenses that apply to
    the business as a whole. Where required, authorized users may
    allocate shared expenses to applicable shops according to configured
    or manually specified allocation rules.

-   **FIN-BR-07 --- Report Traceability.** Financial and operational
    report figures shall be traceable to the underlying transactions and
    records from which they were derived.

-   **FIN-BR-08 --- Historical Integrity.** Changes to operational
    records, commission policies, prices, expenses, refunds, or other
    business configurations shall not silently alter historical
    financial records. Corrections shall be represented through
    appropriate adjustment or reversal records.

**14. Offline & Connectivity --- Business Logic**

AfProsPos does not promise the entire system works offline --- it
promises controlled offline-first operation: cash sales and appropriate
repair-workflow updates keep working during an outage, while operations
that depend on centrally shared, financially risky state (shared
inventory reservations, bank-transfer verification, refunds, inter-shop
transfers) are restricted until connectivity returns.

+-----------------------------------------------------------------------+
| INTERNET LOST                                                         |
|                                                                       |
| \|\-- SAFE OFFLINE (cash sale, repair diagnosis notes, etc.)          |
|                                                                       |
| \| -\> committed to local DB -\> queued in Sync Outbox                |
|                                                                       |
| \`\-- NOT SAFE OFFLINE (shared inventory reservation, bank-transfer   |
| confirm, refund, transfer)                                            |
|                                                                       |
| -\> prevented or queued-but-not-confirmed until connectivity returns  |
|                                                                       |
| Internet returns -\> Sync Outbox -\> Synchronizing -\> Success \|     |
| Conflict (flagged for review)                                         |
+-----------------------------------------------------------------------+

-   **OFF-BR-01 --- Offline Operation.** The system shall support
    controlled offline operation for business processes designated as
    safe for offline execution, allowing eligible transactions to be
    recorded locally when connectivity to the central system is
    unavailable.

-   **OFF-BR-02 --- Offline Policy.** Authorized administrators shall be
    able to configure which operations and payment methods may be
    performed while offline.

-   **OFF-BR-03 --- Local Transaction Integrity.** Eligible offline
    transactions shall be committed atomically to local storage before
    being placed into the synchronization queue, ensuring that
    incomplete transactions are not treated as completed transactions.

-   **OFF-BR-04 --- Synchronization Queue.** Offline transactions and
    applicable business events shall be placed into a persistent
    synchronization queue and automatically synchronized when
    connectivity is restored.

-   **OFF-BR-05 --- Idempotent Synchronization.** Synchronization
    requests shall use stable unique transaction identifiers or
    equivalent idempotency mechanisms to prevent duplicate business
    effects when requests are retried following uncertain network
    responses.

-   **OFF-BR-06 --- Synchronization Status.** The system shall provide
    synchronization states such as pending, synchronizing, synchronized,
    failed, and conflict requiring review.

-   **OFF-BR-07 --- Conflict Detection.** The system shall detect
    conflicts arising when offline activity produces a state that is
    incompatible with the current centralized business state,
    particularly for inventory, reservations, payments, and other
    financially significant resources.

-   **OFF-BR-08 --- Conflict Preservation.** Conflicting transactions
    shall not be silently discarded or overwritten. The original
    transactions shall remain traceable and the conflict shall be
    presented for appropriate resolution according to business policy.

-   **OFF-BR-09 --- Payment Restrictions.** Payment methods requiring
    real-time external verification shall not be treated as confirmed
    while offline unless the relevant payment provider explicitly
    supports an authorized offline transaction mechanism.

-   **OFF-BR-10 --- Inventory Restrictions.** Operations involving
    shared or centrally controlled inventory resources shall be subject
    to additional offline restrictions where stale inventory information
    could result in duplicate allocation, overselling, or incorrect
    stock state.

-   **OFF-BR-11 --- Data Freshness.** Offline-capable interfaces shall
    indicate the current connectivity state and, where applicable, the
    timestamp of the most recent successful synchronization.

-   **OFF-BR-12 --- Reporting During Offline Operation.** Reports and
    dashboards operating from locally cached data shall distinguish
    synchronized information from locally recorded but unsynchronized
    information.

-   **OFF-BR-13 --- Business Transaction Independence.** Temporary loss
    of connectivity shall not invalidate an eligible transaction that
    has already been successfully committed locally.

**15. Data Protection & Privacy --- Business Logic**

Nigeria\'s Data Protection Act gives customers rights to access,
correct, delete, and object to processing of their personal data --- but
erasure is not absolute where the business has another lawful basis,
such as a legal or financial retention obligation. The governing
principle is to delete the person\'s unnecessary personal data, never
the business\'s history: a sale record can be de-identified, but it
isn\'t deleted just because the customer who made it asked to be
forgotten.

+-----------------------------------------------------------------------+
| Customer Data                                                         |
|                                                                       |
| \|\-- Profile Data -\> can be corrected/erased on request             |
|                                                                       |
| \|\-- Transactional Data -\> retained per legal/business retention    |
| policy                                                                |
|                                                                       |
| \| (personal identifiers anonymised; the record itself stays)         |
|                                                                       |
| \`\-- Marketing Data -\> opt-out honored independently of             |
| transactional retention                                               |
+-----------------------------------------------------------------------+

-   **DATA-BR-01 --- Data Subject Requests.** The system shall support
    controlled processing of customer requests relating to access,
    correction, deletion, or restriction of personal data, subject to
    applicable business and legal requirements.

-   **DATA-BR-02 --- Request Management.** Data subject requests shall
    be recorded with their type, date, status, requester, processing
    decision, and relevant administrative actions.

-   **DATA-BR-03 --- Controlled Erasure.** Approved data-erasure
    requests shall remove or anonymise eligible personal data while
    retaining information that the business is required or otherwise
    lawfully permitted to retain.

-   **DATA-BR-04 --- Historical Record Integrity.** Personal-data
    deletion or anonymisation shall not silently alter or invalidate
    historical sales, payments, inventory, repairs, commissions,
    refunds, financial reports, or audit records that must remain
    intact.

-   **DATA-BR-05 --- Data Correction.** The system shall support
    correction of inaccurate or outdated customer information, with
    additional verification or authorization applied to fields
    designated as sensitive or high-impact.

-   **DATA-BR-06 --- Retention.** The system shall support defined
    retention policies for different categories of personal and business
    data. Retention periods shall be determined according to applicable
    legal, regulatory, contractual, and business requirements --- not
    invented by the software.

-   **DATA-BR-07 --- Marketing Objection.** Customers shall be able to
    withdraw from or object to direct marketing communications
    independently of the retention of transactional records.

-   **DATA-BR-08 --- Data Export.** The system shall support authorized
    requests for copies of a customer\'s personal data in an appropriate
    electronic format, subject to applicable restrictions.

-   **DATA-BR-09 --- Privacy Auditability.** Data access, correction,
    deletion, anonymisation, export, and related administrative actions
    shall be logged in the unified audit trail (Section 11) for
    accountability and audit purposes.

*This section is informational context for the business, not legal
advice; the business should confirm its retention periods and erasure
procedures with a qualified adviser under the Nigeria Data Protection
Act.*

**16. Consolidated Business Rule Register**

A single index of every rule resolved across this document, for
traceability during design, development, and QA sign-off. Full rule text
is in the referenced section.

**Repair ↔ Inventory & Repair Management**

  --------------------------------------------------------------------------
  **ID**          **Summary**                                  **Section**
  --------------- -------------------------------------------- -------------
  INV/REP-BR-01   Selecting a part reserves it without         3.3
                  reducing on-hand stock                       

  INV/REP-BR-02   Cannot reserve more than available quantity  3.3

  INV/REP-BR-03   Removing/cancelling releases the reservation 3.3

  INV/REP-BR-04   Installing a part converts reservation to    3.3
                  stock-out                                    

  REP/INV-BR-05   Unreservable part puts repair into Awaiting  3.4
                  Parts                                        

  REP/INV-BR-06   Awaiting Parts triggers a shortage           3.4
                  notification                                 

  INV-BR-07       Low-stock alerts evaluate Available, not     3.4
                  On-hand                                      

  REP-BR-01       Diagnosis required before parts/repair work  3.5

  REP-BR-02       Structured per-component diagnostic          3.5
                  recording                                    

  REP-BR-03       Inaccessible components recorded as Unable   3.5
                  to Test                                      

  REP-BR-04       Diagnosis resolves to                        3.5
                  Repairable/Unrepairable/Further Assessment   

  REP-BR-05       Unrepairable blocks execution and parts      3.5
                  reservation                                  

  REP-BR-06       Parts reserved only after Repairable +       3.5
                  authorization                                

  REP-BR-07       Unrepairable-with-payment triggers           3.5
                  settlement, not tech decision                

  REP-BR-08       Failed repair ≠ unrepairable; needs business 3.5
                  resolution                                   

  REP-BR-09       Ready for Collection assigns a collection    3.8
                  deadline                                     

  REP-BR-10       Missed deadline marks Overdue for Collection 3.8

  REP-BR-11       Configurable abandonment threshold           3.8

  REP-BR-12       Configurable storage-fee policy              3.8

  REP-BR-13       Automatic storage-fee accrual calculation    3.8

  REP-BR-14       Fee/deadline visibility to staff and         3.8
                  customer                                     

  REP-BR-15       Ready-for-collection & reminder              3.8
                  notifications                                

  REP-BR-16       Pre-abandonment notification                 3.8

  REP-BR-17       Abandonment requires administrative          3.8
                  resolution, not auto-disposal                

  REP-BR-18       Full collection/abandonment audit history    3.8

  REP-BR-19       Configurable down-payment deadline           3.6

  REP-BR-20       Missed deadline → Payment Overdue →          3.6
                  Expired/Cancelled                            

  REP-BR-21       Expired unpaid repair returns device via     3.6
                  Collection Lifecycle                         

  REP-BR-22       Completed-with-balance recorded, not         3.7
                  auto-releasable                              

  REP-BR-23       Outstanding balance blocks release by        3.7
                  default                                      

  REP-BR-24       Authorized override permitted and audited    3.7

  REP-BR-25       Collection Lifecycle reused across all       3.8
                  device-return scenarios                      

  REP-BR-26       System distinguishes "Ready for Collection"  3.8
                  vs "Ready for Return"                        
  --------------------------------------------------------------------------

**Sales / POS ↔ Inventory ↔ Payments**

  ---------------------------------------------------------------------------
  **ID**           **Summary**                                  **Section**
  ---------------- -------------------------------------------- -------------
  SALE/INV-BR-01   Checkout creation reserves inventory for a   4.3
                   validity window                              

  SALE/INV-BR-02   Available = On-hand − Reserved               4.3

  SALE/INV-BR-03   Unit-level reservation for serialized        4.3
                   inventory                                    

  SALE/INV-BR-04   Transactional concurrency prevents           4.3
                   double-reservation                           

  SALE/INV-BR-05   Payment confirmation converts reservation to 4.3
                   sale                                         

  SALE/INV-BR-06   Expiry releases reservation, keeps audit     4.3
                   record                                       

  SALE/INV-BR-07   Checkout ↔ unit ↔ payment traceability       4.3
                   maintained                                   

  SALE/PAY-BR-08   Late payment on expired reservation goes to  4.3
                   exception process                            

  PAY-BR-01        Bank-transfer transactions get a distinct    4.5
                   Pending status                               

  PAY-BR-02        Reservation held (not expired) while         4.5
                   transfer confirmation pending                

  PAY-BR-03        Disputed payment routed to human             4.5
                   verification, not auto-resolved              
  ---------------------------------------------------------------------------

**Warranty, Returns, After-Sales & Trade-In**

  -------------------------------------------------------------------------
  **ID**         **Summary**                                  **Section**
  -------------- -------------------------------------------- -------------
  WAR-BR-01      Configurable warranty policy                 5.5
                 (coverage/exclusions/remedies)               

  WAR-BR-02      Warranty linked to sale/repair/customer/unit 5.5

  WAR-BR-03      Eligibility verified before remedy approval  5.5

  WAR-BR-04      Assessment required before remedy (unless    5.5
                 auto-permitted)                              

  WAR-BR-05      Configured exclusions evaluated              5.5

  WAR-BR-06      Policy defines permitted remedies            5.6

  WAR-BR-07      Warranty repair attributed to warranty, not  5.6
                 ₦0 repair                                    

  WAR-BR-08      Configurable coverage extent/limits          5.6
                 calculated                                   

  WAR-BR-09      Refund requires authorization request, not   5.6
                 auto-execution                               

  WAR-BR-10      Replacement tracked; defective unit returned 5.6
                 to inventory                                 

  WAR-BR-11      Repair warranty supported on completed       5.6
                 repairs                                      

  WAR-BR-12      Configurable return/refund window; late      5.9
                 requests denied by default                   

  TRADE-BR-01    Trade-in device assessed and recorded        5.7

  TRADE-BR-02    Approved value becomes transaction credit    5.7

  TRADE-BR-03    Balance = price − trade-in credit            5.7

  TRADE-BR-04    Trade-in recorded as inventory acquisition,  5.7
                 not a discount                               

  TRADE-BR-05    Trade-in value requires separate approval    5.7
                 from assessment                              
  -------------------------------------------------------------------------

**Commission Management**

  -------------------------------------------------------------------------
  **ID**         **Summary**                                  **Section**
  -------------- -------------------------------------------- -------------
  COMM-BR-01     Commissionable amount = net of promotional   6.1
                 discount by default                          

  COMM-BR-02     Commission earned progressively as payments  6.2
                 are confirmed                                

  COMM-BR-03     Reversal/refund triggers a calculated        6.3
                 commission adjustment                        

  COMM-BR-04     Reversals are linked ledger entries, never   6.3
                 overwrite original                           

  COMM-BR-05     Partial refund → proportional partial        6.3
                 commission reversal                          

  COMM-BR-06     Already-paid commission clawback follows     6.3
                 settlement policy                            

  COMM-BR-07     Commission adjustments require authorized    6.3
                 review                                       

  COMM-BR-08     Commission may attribute to multiple staff   6.4
                 on one repair                                

  COMM-BR-09     Multi-staff allocation per policy or manual  6.4
                 authorization                                

  COMM-BR-10     Staff assignment/work history maintained     6.4

  COMM-BR-11     Reassignment never silently changes earned   6.4
                 commission                                   

  COMM-BR-12     Commission record snapshots rate/policy      6.4
                 version at calculation time                  

  COMM-BR-13     Rate/policy changes apply from effective     6.4
                 date, not retroactive                        

  COMM-BR-14     Commission base after discount; other        6.4
                 credits per policy                           
  -------------------------------------------------------------------------

**Referral Program**

  -------------------------------------------------------------------------
  **ID**         **Summary**                                  **Section**
  -------------- -------------------------------------------- -------------
  REF-BR-01      Bonus eligibility based on first qualifying  7.1
                 transaction                                  

  REF-BR-02      Qualifying transaction types configurable    7.1
                 (sale/repair/both)                           

  REF-BR-03      Incomplete transactions never qualify by     7.1
                 default                                      

  REF-BR-04      Bonus calculated and recorded on qualifying  7.1
                 completion                                   

  REF-BR-05      Refund of qualifying transaction → bonus     7.1
                 adjustment per policy                        

  REF-BR-06      One auditable referrer per referred customer 7.1

  REF-BR-07      Referral generates a Reward via the          7.2
                 Marketing reward engine                      

  REF-BR-08      Self-referral prevented                      7.4

  REF-BR-09      Referral relationship changes restricted to  7.4
                 authorized staff                             

  REF-BR-10      Configurable referral/reward limits          7.4

  REF-BR-11      Suspicious referral indicators flagged for   7.4
                 review                                       

  REF-BR-12      Authorized staff approve/reject flagged      7.4
                 referrals                                    

  REF-BR-13      Reward traceable through its full lifecycle  7.4

  REF-BR-14      Bonus held pending return window; late       7.3
                 refund denied, bonus kept                    
  -------------------------------------------------------------------------

**Marketing Campaigns & Rewards**

  -------------------------------------------------------------------------
  **ID**         **Summary**                                  **Section**
  -------------- -------------------------------------------- -------------
  MKT-BR-01      Configurable reward types per campaign       8.1

  MKT-BR-02      Configurable reward conditions (value,       8.1
                 validity, limits)                            

  MKT-BR-03      Store credit maintained as a traceable       8.1
                 ledger                                       

  MKT-BR-04      Optional store-credit-to-cash withdrawal,    8.1
                 conditional/authorized                       

  MKT-BR-05      Configurable discount vouchers               8.1

  MKT-BR-06      Stacking configurable per campaign;          8.2
                 non-stackable by default                     

  MKT-BR-07      Deterministic discount/credit application    8.2
                 order                                        

  MKT-BR-08      Every price adjustment traceable to its      8.2
                 component                                    

  MKT-BR-09      Stacking overrides recorded with user/reason 8.2

  MKT-BR-10      Segments defined by Marketing, not           8.3
                 duplicated as stored data                    

  MKT-BR-11      Segment eligibility evaluated from the       8.3
                 owning module\'s data                        

  MKT-BR-12      Eligibility evaluated dynamically at benefit 8.3
                 time                                         

  MKT-BR-13      Eligibility snapshot recorded when a benefit 8.3
                 is granted                                   

  MKT-BR-14      Campaigns may consume cross-module data,     8.3
                 never own it                                 
  -------------------------------------------------------------------------

**Inventory, Multi-Shop & SKU**

  -------------------------------------------------------------------------
  **ID**         **Summary**                                  **Section**
  -------------- -------------------------------------------- -------------
  INV-BR-08      SKU is unique business-wide, not per-shop    9.1

  INV-BR-09      Central product catalog; shop-level          9.1
                 stock/price records                          

  INV-BR-10      Current-shop is state; transfers are         9.1
                 separately logged history                    

  INV-BR-11      Transfer moves inventory under a SKU; never  9.2
                 creates a new SKU                            

  INV-BR-12      IMEI globally unique, persists through       9.2
                 transfers                                    

  INV-BR-13      Every transfer produces an auditable         9.2
                 movement record                              

  INV-BR-14      Reserved/allocated inventory cannot be       9.2
                 transferred                                  

  INV-BR-15      Available stock (not On-hand) governs all    9.3
                 allocation                                   
  -------------------------------------------------------------------------

**RBAC**

  -------------------------------------------------------------------------
  **ID**         **Summary**                                  **Section**
  -------------- -------------------------------------------- -------------
  RBAC-BR-01     Staff may hold multiple roles simultaneously 10.1

  RBAC-BR-02     Effective permissions are the union across   10.1
                 assigned roles                               

  RBAC-BR-03     No permission granted → no access, by        10.1
                 default                                      

  RBAC-BR-04     Authorized admins manage roles/permissions   10.1

  RBAC-BR-05     Authorization enforced server-side, not only 10.1
                 in the UI                                    

  RBAC-BR-06     New roles/permissions addable without        10.1
                 structural changes                           

  RBAC-BR-07     Role/permission changes never rewrite        10.2
                 historical context                           

  RBAC-BR-08     Deactivation blocks future access, preserves 10.2
                 history                                      

  RBAC-BR-09     Role assignment history maintained           10.2
  -------------------------------------------------------------------------

**Unified Audit Trail**

  -------------------------------------------------------------------------
  **ID**         **Summary**                                  **Section**
  -------------- -------------------------------------------- -------------
  AUD-BR-01      One unified audit trail across all modules   11

  AUD-BR-02      Events categorized by module/activity type   11

  AUD-BR-03      Events record actor, action, entity,         11
                 timestamp, context                           

  AUD-BR-04      Significant changes retain before/after      11
                 values                                       

  AUD-BR-05      Audit records are immutable; corrections are 11
                 new events                                   

  AUD-BR-06      Critical operations auto-generate audit      11
                 events                                       
  -------------------------------------------------------------------------

**Notifications**

  ---------------------------------------------------------------------------
  **ID**         **Summary**                                    **Section**
  -------------- ---------------------------------------------- -------------
  NOTIF-BR-01    Modules emit events; a Notification Engine     12.1
                 handles delivery                               

  NOTIF-BR-02    Delivery is asynchronous; never blocks the     12.1
                 business transaction                           

  NOTIF-BR-03    Configurable multi-channel delivery            12.1
                 (email/WhatsApp/SMS/in-app)                    

  NOTIF-BR-04    Provider abstraction decouples modules from    12.1
                 any one provider                               

  NOTIF-BR-05    Delivery attempts logged with                  12.1
                 channel/provider/status                        

  NOTIF-BR-06    Configurable retry policy for recoverable      12.2
                 failures                                       

  NOTIF-BR-07    Failures classified                            12.2
                 (transient/permanent/recipient/provider)       

  NOTIF-BR-08    Provider failover on service failure           12.2

  NOTIF-BR-09    Asynchronous delivery-status updates via       12.2
                 provider callbacks                             

  NOTIF-BR-10    Every notification traceable from event to     12.2
                 final status                                   

  NOTIF-BR-11    Duplicate delivery prevented on                12.2
                 retry/reprocessing                             

  NOTIF-BR-12    Exhausted notifications marked failed, kept    12.2
                 for review                                     

  NOTIF-BR-13    Notification failure never reverses the        12.2
                 business transaction                           

  NOTIF-BR-14    Notifications classified:                      12.3
                 transactional/operational/security/marketing   

  NOTIF-BR-15    Customers set channel preference for           12.3
                 user-controllable types                        

  NOTIF-BR-16    Mandatory notification types cannot be fully   12.3
                 disabled                                       

  NOTIF-BR-17    Marketing communications independently         12.3
                 opt-in/opt-out                                 

  NOTIF-BR-18    Transactional notifications persist on         12.3
                 dashboard regardless of channel                

  NOTIF-BR-19    Configurable reminder schedules for            12.3
                 time-dependent notices                         
  ---------------------------------------------------------------------------

**Expense Tracking & Consolidated Financial Reporting**

  -------------------------------------------------------------------------
  **ID**         **Summary**                                  **Section**
  -------------- -------------------------------------------- -------------
  FIN-BR-01      One consolidated reporting layer across all  13
                 financial sources                            

  FIN-BR-02      Each module remains the authoritative source 13
                 of its own data                              

  FIN-BR-03      Revenue, payments received, receivables,     13
                 costs kept distinct                          

  FIN-BR-04      Profitability calculated from revenue, COGS, 13
                 commission, expenses                         

  FIN-BR-05      Reporting available business-wide and        13
                 per-shop                                     

  FIN-BR-06      Shared expenses supported, with optional     13
                 allocation to shops                          

  FIN-BR-07      Report figures traceable to underlying       13
                 transactions                                 

  FIN-BR-08      Config/price/rate changes never silently     13
                 rewrite past reports                         
  -------------------------------------------------------------------------

**Offline & Connectivity**

  -------------------------------------------------------------------------
  **ID**         **Summary**                                  **Section**
  -------------- -------------------------------------------- -------------
  OFF-BR-01      Controlled offline operation for             14
                 designated-safe processes                    

  OFF-BR-02      Admins configure which operations/payments   14
                 work offline                                 

  OFF-BR-03      Offline transactions committed atomically    14
                 before queuing                               

  OFF-BR-04      Persistent sync queue, auto-synced on        14
                 reconnect                                    

  OFF-BR-05      Idempotent sync prevents duplicate effects   14
                 on retry                                     

  OFF-BR-06      Explicit sync states                         14
                 (pending/syncing/synced/conflict)            

  OFF-BR-07      Conflicts with server state detected on      14
                 reconnect                                    

  OFF-BR-08      Conflicting transactions preserved, never    14
                 silently overwritten                         

  OFF-BR-09      Real-time-verified payment methods not       14
                 confirmed while offline                      

  OFF-BR-10      Shared/centrally-controlled inventory        14
                 further restricted offline                   

  OFF-BR-11      UI shows connectivity state and last-sync    14
                 timestamp                                    

  OFF-BR-12      Reports distinguish synced vs. locally       14
                 pending data                                 

  OFF-BR-13      Reconnection never invalidates an            14
                 already-committed local transaction          
  -------------------------------------------------------------------------

**Data Protection & Privacy**

  -------------------------------------------------------------------------
  **ID**         **Summary**                                  **Section**
  -------------- -------------------------------------------- -------------
  DATA-BR-01     Controlled handling of                       15
                 access/correction/deletion requests          

  DATA-BR-02     Requests recorded with type, status,         15
                 decision, actions                            

  DATA-BR-03     Erasure removes/anonymises only what isn\'t  15
                 lawfully required                            

  DATA-BR-04     Erasure never invalidates required           15
                 historical financial records                 

  DATA-BR-05     Corrections supported, with extra checks on  15
                 sensitive fields                             

  DATA-BR-06     Retention periods follow legal/business      15
                 policy, not invented                         

  DATA-BR-07     Marketing opt-out independent of             15
                 transactional retention                      

  DATA-BR-08     Authorized data export in a standard         15
                 electronic format                            

  DATA-BR-09     Privacy actions logged in the unified audit  15
                 trail                                        
  -------------------------------------------------------------------------

**17. Recommended Amendments to the BRD**

This analysis surfaced gaps in the BRD\'s original requirement
statements. The following amendments are recommended so the BRD and this
BLD stay consistent:

-   REP-12 (repair status list) should be replaced by the branching
    lifecycle in Section 3.1, rather than a single linear sequence ---
    it did not previously account for Unrepairable, Payment Overdue, or
    Failed outcomes.

-   INV-14 (low-stock alerts) should be clarified to evaluate Available
    quantity (On-hand less Reserved), per INV-BR-07/INV-BR-15, not
    On-hand alone.

-   SALE-02 ("set a warranty if necessary") should reference the new
    Section 5 Warranty, Returns & After-Sales Management module rather
    than remaining a standalone checkout field with no defined claim
    process.

-   A new BRD section, "Warranty, Returns & After-Sales Management,"
    should be added, covering the customer- and admin-facing screens
    implied by Section 5 (claim submission, assessment recording, remedy
    selection, refund/replacement authorization, the return/refund
    window) and by Section 5.7 (trade-in assessment and approval).

-   SALE-03 (checkout creation) should be clarified to state that
    checkout reserves inventory rather than immediately decrementing
    stock, per Section 4.1, and a bank-transfer payment status/dispute
    flow (Section 4.5) should be added alongside it.

-   COMM-01--COMM-05 (commission configuration) should reference the
    commissionable-amount, payment-based-recognition, and
    ledger/reversal rules in Section 6, since the original BRD only
    covered configuring a rate, not how or when it\'s earned or
    reversed.

-   REF-01--REF-05 (referral) should reference the
    qualifying-transaction, reward-generation, and refund-window rules
    in Section 7, and should note that a referral bonus is a Reward
    generated through the Marketing reward engine (Section 8.1) rather
    than a payout the Referral module handles on its own.

-   MKT-01--MKT-04 (marketing) should be expanded to cover the Store
    Credit / Voucher reward instruments, stacking rules, and
    segment-eligibility source-of-truth defined in Section 8.

-   INV-01--INV-09 (product/inventory) should be revised to describe a
    shared business-level product catalog with shop-level stock and
    pricing (Section 9.1), rather than reading as if each shop manages
    an independent catalog.

-   RBAC-01--RBAC-07 should reference the effective-permissions model
    (Section 10.1) and the historical-preservation requirement (Section
    10.2, RBAC-BR-07/08/09).

-   A new BRD Non-Functional Requirement should reference the unified
    Audit Trail (Section 11) explicitly, so RBAC-06\'s "administrative
    actions" log and the existing financial/stock audit requirement are
    described as one system, not two.

-   NOTIF-01--NOTIF-04 should reference the event-driven delivery
    architecture, retry/fallback behavior, and the mandatory-vs-optional
    notification categories defined in Section 12.

-   EXP-04 should be clarified to specify a single consolidated
    reporting module (Section 13) rather than leaving open whether
    reconciliation across sales, repairs, commission, and expenses is
    manual.

-   A new BRD Non-Functional Requirement should describe the
    offline/connectivity behavior in Section 14, since the current NFRs
    are silent on what happens when a POS terminal or dashboard loses
    connectivity mid-transaction.

-   A new BRD Non-Functional Requirement should describe data-subject
    request handling (access, correction, deletion, export) per Section
    15, alongside the existing customer-PII compliance mention.

**18. Resolution Status Summary**

Version 1.0 of this document left every module-crossing question outside
Repair/Inventory, Sales/POS, and Warranty/Returns/Trade-In as an open
item. This version resolves all of them. The table below maps each
original question group to where it was resolved; none remain
outstanding from the original analysis.

  --------------------------------------------------------------------------
  **Question Group (from original    **Status**      **Resolved In**
  analysis)**                                        
  ---------------------------------- --------------- -----------------------
  Repair ↔ Inventory (parts          Resolved        Section 3.3--3.4
  reservation, low stock)                            

  Repair diagnosis, unrepairable,    Resolved        Section 3.5--3.8
  collection & abandonment                           

  Sales/POS ↔ Inventory ↔ Payments   Resolved        Section 4.1--4.3
  (checkout reservation)                             

  Returns / refunds / exchange /     Resolved        Section 5
  warranty (previously missing)                      

  Bank-transfer confirmation status  Resolved        Section 4.5
  & payment disputes                                 

  Commission ↔                       Resolved        Section 6
  Sales/Repairs/Payments/Marketing                   

  Referral ↔ Sales ↔ Marketing       Resolved        Section 7

  Marketing ↔ Commission ↔ Referral  Resolved        Section 8
  (stacking, segments)                               

  Inventory ↔ Multi-Shop ↔ SKU       Resolved        Section 9

  RBAC ↔ Everything (multi-role,     Resolved        Section 10
  historical snapshots)                              

  RBAC-06 admin log vs.              Resolved        Section 11
  financial/stock audit log          (unified)       

  Notifications ↔ Everything         Resolved        Section 12
  (failure, retry, opt-out)                          

  Expense Tracking ↔ Reporting       Resolved        Section 13

  Offline/connectivity               Resolved        Section 14

  Customer data deletion/correction  Resolved        Section 15
  vs. legal retention                                
  --------------------------------------------------------------------------

This does not mean every conceivable edge case has been anticipated ---
as with the original Repair, Sales, and Warranty sections, new questions
will surface once the business begins actually configuring the system
(an exact retry count, a specific return-window duration, a particular
commission-protection default). Those are configuration decisions to
make within the rules above, not business-logic gaps requiring another
analysis pass.

**19. Glossary**

  -----------------------------------------------------------------------
  **Term**           **Definition**
  ------------------ ----------------------------------------------------
  On-hand            Quantity of a product/part physically present in the
                     shop.

  Reserved           Quantity physically present but committed to an open
                     repair job or checkout.

  Available          On-hand minus Reserved; the quantity that can be
                     newly committed.

  Reservation Source The originating transaction (Repair or Checkout)
                     that holds a reservation on stock.

  Serialized         Inventory tracked by individual physical unit (e.g.,
  Inventory          an IMEI-tracked phone).

  Device Disposition Where a physical device currently sits: With
                     Business, Ready for Return/Collection, or Collected.

  Collection         The shared process governing how a device is
  Lifecycle          returned to a customer, and what happens if it
                     isn\'t collected in time.

  Abandonment        The configured point at which an uncollected device
  Threshold          is flagged as requiring administrative resolution.

  Warranty Policy    A configured set of coverage duration, scope,
                     exclusions, and permitted remedies attached to a
                     product or repair.

  Warranty Claim     A customer-initiated report that a fault may be
                     covered by an active warranty, subject to
                     eligibility assessment.

  Repair Warranty    Warranty coverage attached to a completed repair
                     itself, distinct from the product\'s own warranty.

  Return/Refund      The configured period after purchase or collection
  Window             within which a return or refund may be requested.

  Trade-in Credit    The approved assessed value of a customer\'s
                     traded-in device, applied as a credit toward a new
                     purchase.

  Checkout Validity  The configurable window within which a checkout must
  Period             be paid before its inventory reservation expires.

  Payment Dispute    A transaction state where a customer claims payment
                     (typically bank transfer) was made but it has not
                     yet been verified by staff.

  Commissionable     The transaction amount commission is actually
  Amount             calculated on, after applicable promotional
                     discounts (by default).

  Commission Ledger  The append-only record of a commission and every
                     subsequent adjustment/reversal linked to it; nothing
                     is overwritten.

  Reward             The general concept covering any campaign benefit
                     --- referral bonus, loyalty benefit, promotion ---
                     issued as store credit, a voucher, or another
                     configured instrument.

  Store Credit       A traceable customer-value ledger representing value
                     the business owes the customer within the system,
                     usable against future purchases.

  Discount Voucher   A conditional discount entitlement (value, validity,
                     usage limits) that reduces a future transaction\'s
                     price; does not represent money owed.

  Product Catalog    The single business-level definition of a
                     product/SKU\'s brand, model, category, and specs,
                     shared across all shops.

  SKU                A specific sellable product configuration (e.g.,
                     "iPhone 15 / 128GB / Black"), unique across the
                     whole business, not per shop.

  Inventory Item     A physical unit of a SKU --- one IMEI for serialized
                     stock, or a tracked quantity for non-serialized
                     stock.

  Effective          The union of all capabilities granted to a staff
  Permissions        member across every role they currently hold.

  Unified Audit      The single, categorized, append-only log covering
  Trail              administrative, financial, inventory, and
                     operational events across all modules.

  Notification Event The business occurrence (e.g., REPAIR_COMPLETED)
                     that triggers the Notification Engine, decoupled
                     from how or whether delivery ultimately succeeds.

  Idempotency        A property ensuring a repeated request (e.g., a
                     retried sync or notification) produces the same
                     result once, rather than duplicating its effect.

  Consolidated       A single reporting layer aggregating sales, repair,
  Financial          inventory, commission, and expense data into one
  Reporting          P&L-style view, per shop and business-wide.

  Sync Outbox        The local, persistent queue holding transactions
                     recorded while offline, awaiting synchronization
                     once connectivity returns.

  Data Subject       A customer\'s formal request to access, correct,
  Request            delete, or restrict processing of their personal
                     data.

  Anonymisation      Removing or obscuring personal identifiers from a
                     record while retaining the underlying transaction
                     for legally required retention.
  -----------------------------------------------------------------------
