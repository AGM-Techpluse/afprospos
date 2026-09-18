**BUSINESS REQUIREMENTS DOCUMENT**

**AfProsPos**

*Phone Sales, Repair & Business Management System*

Document Version: 1.0

Date: August 28, 2026

Status: Draft for Review

**Document Control**

**Revision History**

  --------------------------------------------------------------------------
  **Version**   **Date**        **Author**             **Description**
  ------------- --------------- ---------------------- ---------------------
  1.0           Aug 28, 2026    Product/Business       Initial draft
                                Analysis Team          business requirements
                                                       document

  --------------------------------------------------------------------------

**Distribution List**

-   Shop Owner / Business Founder

-   Product & Engineering Team

-   UI/UX Design Team

-   QA Team

**Table of Contents**

**1. Introduction 4**

> 1.1 Purpose of the Document 4
>
> 1.2 Project Overview 4
>
> 1.3 Business Objectives 4
>
> 1.4 Scope 4

**2. Stakeholders & User Roles 5**

**3. System Overview 6**

> 3.1 Customer Dashboard 6
>
> 3.2 Admin Dashboard 6

**4. Functional Requirements 7**

> 4.1 User Management & Role-Based Access Control (RBAC) 7
>
> 4.2 Repair Management Module 7
>
> 4.3 Sales & Point-of-Sale (POS) Module 8
>
> 4.4 Commission Management 8
>
> 4.5 Payments, Checkout, Invoicing & Receipts 8
>
> 4.6 Referral System 9
>
> 4.7 Product & Inventory Management 9
>
> 4.8 Multi-Shop Management 10
>
> 4.9 Customer-Facing Features 10
>
> 4.10 Marketing & Promotions 11
>
> 4.11 Notifications 11
>
> 4.12 Business Expense Tracking 11

**5. RBAC Permission Matrix (Reference) 12**

**6. Non-Functional Requirements 13**

**7. Assumptions & Constraints 14**

**8. Glossary of Terms 15**

**1. Introduction**

**1.1 Purpose of the Document**

This Business Requirements Document (BRD) defines the business needs,
functional scope, user roles, and operational rules for AfProsPos --- a
web-based Phone Sales, Repair & Business Management System. It is
intended to guide product design, engineering, and QA teams in building
a system that digitizes phone repair operations, retail/accessory sales,
inventory, and multi-shop business management under a single platform.

**1.2 Project Overview**

AfProsPos is a Point-of-Sale (POS) and business management platform
purpose-built for phone sales and repair businesses. It replaces manual,
paper-based, or fragmented tracking of repairs, sales, inventory, and
staff performance with a unified web system consisting of two primary
interfaces:

-   A Customer Dashboard --- where customers register, track repairs,
    view purchase history, make payments, and browse shops.

-   An Admin Dashboard --- a role-based operations console used by shop
    owners and staff (technicians, accountants, cashiers, product
    managers, and marketing staff) to run day-to-day business operations
    across one or more shop locations.

**1.3 Business Objectives**

-   **OBJ-01** Digitize and standardize the full phone repair lifecycle,
    from intake to collection, with transparent pricing and status
    tracking for customers.

-   **OBJ-02** Provide a unified POS for phone, accessory, and device
    sales with commission tracking, warranties, and multiple payment
    methods.

-   **OBJ-03** Enable accurate, real-time inventory control with SKU
    standardization and IMEI-level tracking of individual devices.

-   **OBJ-04** Support business growth across multiple shop locations
    under centralized oversight, while preserving shop-level autonomy.

-   **OBJ-05** Increase customer retention and acquisition through a
    referral program and targeted marketing/discount campaigns.

-   **OBJ-06** Improve financial visibility through commission
    reporting, expense tracking, and consolidated sales/repair
    reporting.

-   **OBJ-07** Keep customers and staff informed in real time via
    WhatsApp, email, and in-app notifications.

**1.4 Scope**

**1.4.1 In Scope**

-   Customer registration, authentication, and customer dashboard

-   Admin dashboard with Role-Based Access Control (RBAC)

-   Repair intake, tracking, pricing, and checkout workflow

-   Sales / POS workflow for phones, accessories, and other devices

-   Commission configuration and reporting

-   Referral tracking and bonus calculation

-   Product and inventory management, including SKU generation and IMEI
    tracking

-   Multi-shop management and shop-level reporting

-   Marketing campaigns, flyers, and discount management

-   Notifications (WhatsApp, email, in-app)

-   Business expense tracking

-   Invoicing, receipts, and payment processing (POS terminal, bank
    transfer, cash, and in-app payment)

**1.4.2 Out of Scope (for this phase)**

-   Native mobile applications (iOS/Android) --- the system is web-based
    for this phase

-   Direct hardware/payment gateway vendor selection (to be defined in
    the technical design phase)

-   Third-party accounting system integration (e.g., QuickBooks) beyond
    internal expense/commission tracking

-   Cross-border currency support beyond the shop\'s operating currency,
    unless specified later

**2. Stakeholders & User Roles**

AfProsPos is used by two categories of users: internal staff/admins
(accessing the Admin Dashboard under RBAC) and customers (accessing the
Customer Dashboard). The table below summarizes each role.

  ----------------------------------------------------------------------------
  **Role**            **Dashboard**   **Primary Responsibilities**
  ------------------- --------------- ----------------------------------------
  Shop Owner (Super   Admin           Overall system control. Adds/removes
  Admin)                              employees and admins, manages shops,
                                      sales, repairs, and inventory. Full
                                      visibility across all shops.

  Accountant          Admin           Financial reporting and management:
                                      sales revenue, commissions, expenses,
                                      and repair income across shop(s).

  Technician          Admin           Creates and manages repair jobs: device
                                      type, parts, repair time, labour charge,
                                      down-payment requirement, collection
                                      date.

  Cashier / Sales     Admin           Creates sales transactions (phones,
  Staff                               accessories, devices), sets warranty,
                                      generates checkout for customer or
                                      walk-in payment.

  Product Staff /     Admin           Manages product catalog and inventory:
  Manager                             adds devices/products (bulk CSV or
                                      single entry), SKU generation, IMEI,
                                      pricing, stock control.

  Marketing / Sales   Admin           Uploads marketing flyers, sets up
  Staff                               discount campaigns, defines customer
                                      eligibility, tracks promotional history.

  Customer            Customer        Registers, browses shops/products,
                                      requests/tracks repairs, makes
                                      purchases, views history, pays invoices,
                                      leaves notes.
  ----------------------------------------------------------------------------

*Note: A single staff account may be assigned more than one role where
the business permits (e.g., a shop owner acting as an accountant in a
small shop). Role assignment and permission granularity are governed by
the RBAC module described in Section 4.1.*

**3. System Overview**

**3.1 Customer Dashboard**

A self-service portal where a registered customer can:

-   View and track active repair jobs, including status, cost breakdown,
    assigned technician, and estimated/actual collection date

-   Make payments toward repairs (including down payments requested by a
    technician) or purchases

-   View purchase and repair history

-   View and edit their profile and contact information

-   Browse participating shops and their contact details

-   Search for products/devices across shop(s)

-   Leave notes (e.g., on a repair job or an order)

-   Receive notifications on order, repair, and payment status

**3.2 Admin Dashboard**

A role-based operations console, scoped by shop and permission level,
that surfaces only the modules relevant to a given staff member\'s role.
The Shop Owner has unrestricted access across all shops; other roles see
a subset of modules according to their assigned permissions (see Section
4.1).

**4. Functional Requirements**

Each requirement is tagged with a unique ID (module prefix + number) for
traceability during design, development, and QA sign-off.

**4.1 User Management & Role-Based Access Control (RBAC)**

-   **RBAC-01** The system shall support distinct roles: Shop Owner,
    Accountant, Technician, Cashier/Sales Staff, Product Staff/Manager,
    Marketing Staff, and Customer.

-   **RBAC-02** The Shop Owner shall be able to create, edit, and
    deactivate employee/admin accounts, and assign one or more roles to
    each.

-   **RBAC-03** The Shop Owner shall be able to un-employ
    (deactivate/offboard) staff, subject to their own permission level,
    from the multi-shop management view.

-   **RBAC-04** Each role shall have a configurable set of permissions
    (view, create, edit, delete, approve) scoped per module (repairs,
    sales, inventory, reports, marketing, expenses).

-   **RBAC-05** The system shall support permission scoping per shop, so
    a staff member can be granted access to one, several, or all shop
    locations.

-   **RBAC-06** The system shall log administrative actions (role
    changes, deactivations, permission edits) for audit purposes.

-   **RBAC-07** Customers shall be restricted to the Customer Dashboard
    and shall not have access to any admin module.

**4.2 Repair Management Module**

Covers the full lifecycle of a repair job from intake to collection.

**4.2.1 Repair Creation (Technician)**

-   **REP-01** A technician shall be able to create a new repair job by
    selecting the device type (make/model).

-   **REP-02** A technician shall be able to select the part(s) required
    for the repair from the inventory catalog.

-   **REP-03** A technician shall be able to set an estimated repair
    time/duration for the job.

-   **REP-04** A technician shall be able to set the labour charge for
    the repair, separate from parts cost.

-   **REP-05** The system shall calculate total repair cost as the sum
    of parts cost and labour charge.

-   **REP-06** A technician shall be able to specify whether a down
    payment is required before work begins, and the down payment amount
    or percentage.

-   **REP-07** A technician shall be able to record device collection
    details and set an expected collection date.

-   **REP-08** The system shall assign (or allow assignment of) a
    technician to the repair job so the customer can see who is working
    on their device.

**4.2.2 Repair Tracking (Customer)**

-   **REP-09** A customer shall be able to track repair status and
    elapsed/remaining repair time from their dashboard.

-   **REP-10** A customer shall be able to view repair details: cost
    breakdown (parts + labour), assigned technician (where applicable),
    and collection date.

-   **REP-11** A customer shall be able to proceed to checkout to pay a
    down payment or the full repair cost using any supported payment
    method.

-   **REP-12** The system shall update repair status automatically as it
    moves through defined stages (e.g., Received, Diagnosing, Awaiting
    Down Payment, In Progress, Awaiting Parts, Completed, Ready for
    Collection, Collected).

-   **REP-13** The system shall notify the customer at each significant
    status change.

**4.3 Sales & Point-of-Sale (POS) Module**

-   **SALE-01** A cashier/sales staff member shall be able to create a
    sale for a phone, accessory, or other device from the product
    catalog.

-   **SALE-02** A cashier shall be able to set a warranty period/terms
    on a sale where applicable.

-   **SALE-03** A cashier shall be able to generate a checkout for the
    transaction, and make it payable either in the customer\'s dashboard
    (for registered customers) or directly at the point of sale for
    walk-in customers.

-   **SALE-04** The system shall support applying discounts or active
    promotional campaigns at checkout (see Section 4.10).

-   **SALE-05** The system shall generate an invoice for every sale.

-   **SALE-06** The system shall support receipt printing at the point
    of sale.

-   **SALE-07** The system shall record the sale against the correct
    shop location and update inventory in real time.

**4.4 Commission Management**

-   **COMM-01** The Shop Owner/Admin shall be able to configure staff
    commission rates for: phone sales, repairs, and accessory sales,
    independently.

-   **COMM-02** The Admin shall be able to disable commission entirely,
    per shop, per role, or system-wide.

-   **COMM-03** The system shall calculate commission earned per staff
    member automatically based on completed sales/repairs and the
    applicable rate.

-   **COMM-04** The system shall generate commission reports, filterable
    by staff member, role, shop, and date range.

-   **COMM-05** Commission reports shall be visible to the Accountant
    and Shop Owner roles.

**4.5 Payments, Checkout, Invoicing & Receipts**

-   **PAY-01** The system shall support the following payment methods:
    POS terminal (card) payment, bank transfer, cash, and
    in-app/dashboard payment.

-   **PAY-02** The system shall generate a digital invoice for every
    sale and repair transaction.

-   **PAY-03** The system shall support receipt printing
    (thermal/standard printer) at checkout.

-   **PAY-04** The system shall support partial payments (e.g., down
    payment followed by balance payment) and track outstanding balances
    per transaction.

-   **PAY-05** The system shall record the payment method and reference
    (e.g., transfer reference, terminal transaction ID) against every
    transaction for reconciliation.

  -----------------------------------------------------------------------
  **Payment Method**   **Applicable To**    **Notes**
  -------------------- -------------------- -----------------------------
  Cash                 In-store sales &     Recorded by
                       repairs              cashier/technician at time of
                                            payment

  POS Terminal (Card)  In-store sales &     Requires terminal transaction
                       repairs              reference for reconciliation

  Bank Transfer        In-store & remote    Requires proof/reference; may
                       payments             require admin confirmation

  In-App / Dashboard   Customer-initiated   Available to registered
  Payment              payments             customers paying from their
                                            dashboard
  -----------------------------------------------------------------------

**4.6 Referral System**

-   **REF-01** The system shall allow a customer to refer new customers
    using a unique referral code or link.

-   **REF-02** The system shall track referrals: who referred whom, and
    the referral\'s conversion status (e.g., signed up, first purchase
    completed).

-   **REF-03** The system shall calculate referral bonuses automatically
    based on configurable rules (e.g., flat bonus, percentage of
    referred customer\'s first purchase).

-   **REF-04** The system shall maintain a referral history viewable by
    the customer (their own referrals) and by admins (all referrals).

-   **REF-05** The system shall generate referral reports for admins,
    including total bonuses paid/owed and top referrers.

**4.7 Product & Inventory Management**

**4.7.1 Product Creation & Import**

-   **INV-01** Product staff shall be able to import products in bulk
    via CSV file upload.

-   **INV-02** Product staff shall be able to add a single
    product/device manually, one at a time.

-   **INV-03** Product staff shall be able to create and assign a
    product category for each device/product added.

-   **INV-04** Product staff shall be able to record the device\'s IMEI
    where applicable (individual phone units).

-   **INV-05** Product staff shall be able to set device condition
    (e.g., New, Used -- Grade A/B/C, Refurbished).

-   **INV-06** Product staff shall be able to record device
    specifications: color, storage capacity, battery health/capacity,
    and other relevant specs by category.

-   **INV-07** Product staff shall be able to record the device brand.

-   **INV-08** Product staff shall be able to record the quantity/amount
    of a device or product in stock.

**4.7.2 Pricing Fields --- Naming Convention**

The request specified two price-related fields: the amount the shop pays
to acquire the item, and the amount the shop sells it for (calculated by
adding a margin/percentage). Using \"cost\" and \"price\"
interchangeably risks confusion during development. The recommended
standard terms --- used consistently throughout this document and
recommended for the data model --- are:

+-----------------------------------------------------------------------+
| **Recommended Field Naming**                                          |
|                                                                       |
| **Cost Price** --- the amount the shop paid to acquire the            |
| device/product (purchase cost).                                       |
|                                                                       |
| **Markup / Margin %** --- the percentage the shop adds on top of Cost |
| Price.                                                                |
|                                                                       |
| **Selling Price** --- the final customer-facing price (Cost Price +   |
| Markup), auto-calculated but editable.                                |
+-----------------------------------------------------------------------+

-   **INV-09** The system shall store Cost Price, Markup %, and Selling
    Price for every product/device, auto-calculating Selling Price from
    Cost Price and Markup %, with the ability for staff to manually
    override the Selling Price.

**4.7.3 Inventory Control**

-   **INV-10** The system shall support inventory transfer of
    products/devices between shops, with a transfer log (from-shop,
    to-shop, quantity, initiated-by, received-by, timestamp).

-   **INV-11** The system shall support stock-in (receiving new stock)
    and stock-out (sale, damage, transfer) transactions.

-   **INV-12** The system shall support manual stock adjustments with a
    required reason/note (e.g., damage, loss, correction).

-   **INV-13** The system shall provide inventory search across
    products, filterable by category, brand, condition, shop, and stock
    level.

-   **INV-14** The system shall trigger low-stock alerts when a
    product\'s quantity falls below a configurable threshold, notifying
    the relevant product staff/admin.

**4.7.4 SKU Generation**

-   **SKU-01** The system shall auto-generate a unique SKU for every
    product/device added to inventory.

-   **SKU-02** The SKU shall be composed of an alphanumeric prefix
    configurable per shop (shop\'s preferred code) combined with a
    segment representing the product\'s category, plus a unique
    sequential or random identifier.

-   **SKU-03** Example SKU structure: \[Shop Code\]-\[Category
    Code\]-\[Unique Sequence\], e.g., LGS-PHN-000482 for a phone at a
    shop coded \"LGS\".

-   **SKU-04** The system shall guarantee SKU uniqueness system-wide (or
    per-shop, per configuration) and shall not allow duplicate SKUs.

**4.8 Multi-Shop Management**

-   **SHOP-01** The Shop Owner/Admin shall be able to view a list of all
    shops with their details and locations.

-   **SHOP-02** The Shop Owner/Admin shall be able to create new shops,
    capturing name, location/address, contact details, and shop code
    (used in SKU generation).

-   **SHOP-03** An admin with sufficient permission shall be able to log
    in to / switch context into a specific shop\'s operational view.

-   **SHOP-04** Each shop\'s view shall surface: orders, sales reports,
    repair reports, product/device stock levels, and employee list.

-   **SHOP-05** An admin with sufficient permission shall be able to
    un-employ (deactivate) staff at a specific shop.

-   **SHOP-06** The system shall support cross-shop reporting for the
    Shop Owner, aggregating sales, repairs, and inventory across all
    locations.

**4.9 Customer-Facing Features**

-   **CUST-01** A customer shall be able to self-register and manage
    their account (profile, contact information).

-   **CUST-02** A customer shall be able to view their full purchase
    history and repair history.

-   **CUST-03** A customer shall be able to browse participating shops
    and view each shop\'s contact information and location.

-   **CUST-04** A customer shall be able to search for products/devices
    across available shops.

-   **CUST-05** A customer shall be able to leave notes (e.g., on a
    repair job or a product enquiry).

**4.10 Marketing & Promotions**

-   **MKT-01** Marketing/sales staff shall be able to upload
    flyers/creatives for marketing campaigns.

-   **MKT-02** Marketing/sales staff shall be able to set up discount
    campaigns, including discount type (flat/percentage), validity
    period, and applicable products/categories.

-   **MKT-03** Marketing/sales staff shall be able to define customer
    eligibility rules for a campaign (e.g., all customers, first-time
    customers, referred customers, specific segments).

-   **MKT-04** The system shall maintain a promotional history log,
    including past campaigns, redemption counts, and performance.

**4.11 Notifications**

-   **NOTIF-01** The system shall send notifications for key events: new
    sale, repair status change, payment received/due, and other
    significant admin-defined actions.

-   **NOTIF-02** The system shall support notification delivery via
    WhatsApp, email, and in-app notifications.

-   **NOTIF-03** Both customers and relevant admins/staff shall receive
    notifications appropriate to their role and involvement in the
    triggering event.

-   **NOTIF-04** Users shall be able to configure notification
    preferences per channel, where applicable.

**4.12 Business Expense Tracking**

-   **EXP-01** The Accountant/Admin shall be able to record business
    expenses, categorized (e.g., rent, utilities, salaries, supplies).

-   **EXP-02** The system shall support recording expenses per shop, for
    shop-level and consolidated financial reporting.

-   **EXP-03** The system shall generate expense reports filterable by
    category, shop, and date range.

-   **EXP-04** Expense data shall feed into overall business financial
    reporting alongside sales, repair, and commission data.

**5. RBAC Permission Matrix (Reference)**

High-level module access by role. Y = full access, P = partial/scoped
access (subject to granular permission settings), --- = no access. Final
permission granularity is configurable by the Shop Owner as per Section
4.1.

  ----------------------------------------------------------------------------------------------------------
  **Module**             **Shop    **Accountant**   **Technician**   **Cashier**   **Product   **Marketing
                         Owner**                                                   Staff**     Staff**
  ---------------------- --------- ---------------- ---------------- ------------- ----------- -------------
  Employee/Role          Y         ---              ---              ---           ---         ---
  Management                                                                                   

  Shop Management        Y         P                ---              ---           ---         ---

  Repairs                Y         P                Y                P             ---         ---

  Sales / POS            Y         P                ---              Y             P           ---

  Commission Config &    Y         Y                ---              ---           ---         ---
  Reports                                                                                      

  Inventory / Products / Y         P                P                P             Y           ---
  SKU                                                                                          

  Referral Program       Y         P                ---              P             ---         P

  Marketing & Discounts  Y         ---              ---              ---           ---         Y

  Expense Tracking       Y         Y                ---              ---           ---         ---

  Reports                Y         Y                P                P             P           P
  (Sales/Repair/Stock)                                                                         
  ----------------------------------------------------------------------------------------------------------

**6. Non-Functional Requirements**

  -----------------------------------------------------------------------
  **Category**     **Requirement**
  ---------------- ------------------------------------------------------
  Security         Role-based access control, encrypted storage of
                   sensitive data (payment references, IMEI, customer
                   PII), secure authentication (password policies,
                   optional 2FA for admin accounts).

  Performance      Core POS/checkout actions (sale creation, payment
                   confirmation) should complete within a few seconds
                   under normal load.

  Scalability      Architecture should support adding new shops, staff,
                   and product categories without redesign.

  Availability     System should target high uptime given its use for
                   daily, in-person retail and repair operations.

  Usability        Admin dashboard should be usable by non-technical
                   retail/repair staff with minimal training; customer
                   dashboard should be simple and mobile-friendly.

  Auditability     All financial transactions, stock movements, and
                   permission changes should be logged with user,
                   timestamp, and action for audit purposes.

  Data Integrity   SKU and IMEI uniqueness must be enforced; inventory
                   counts must reconcile across stock-in, stock-out,
                   transfers, and adjustments.

  Compliance       Handling of customer personal data (contact info,
                   purchase history) should follow applicable data
                   protection practices.
  -----------------------------------------------------------------------

**7. Assumptions & Constraints**

**7.1 Assumptions**

-   Each shop operates as a distinct location with its own inventory,
    staff, and shop code, under one overarching business account.

-   Customers may interact with more than one shop under the same
    business using a single customer account.

-   Commission and discount rules are configured per business but may be
    overridden per shop where the business allows it.

-   WhatsApp and email notification delivery will rely on third-party
    provider integration (provider to be selected in technical design).

**7.2 Constraints**

-   Final payment gateway/terminal provider selection is pending and may
    affect the payment integration design.

-   SKU format (Section 4.7.4) is a recommended structure and may be
    refined during technical design in consultation with the business.

**8. Glossary of Terms**

  -----------------------------------------------------------------------
  **Term**         **Definition**
  ---------------- ------------------------------------------------------
  BRD              Business Requirements Document

  RBAC             Role-Based Access Control --- restricting system
                   access based on a user\'s assigned role

  POS              Point of Sale --- the point at which a sales
                   transaction is completed

  SKU              Stock Keeping Unit --- a unique code identifying a
                   specific product/device

  IMEI             International Mobile Equipment Identity --- a unique
                   number identifying a mobile device

  Cost Price       The amount the shop paid to acquire a product or
                   device

  Selling Price    The customer-facing price after markup is applied to
                   the Cost Price

  Down Payment     A partial payment made by a customer before repair
                   work begins

  Walk-in Customer A customer purchasing or requesting a repair in
                   person, without a registered account
  -----------------------------------------------------------------------
