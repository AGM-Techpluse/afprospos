# AfProsPos — Git Branching Strategy

A professional engineering org doesn't pick a branching model because it's "standard" — it picks one that matches how the team actually ships. AfProsPos ships in **numbered, gated phases** (Implementation Plan §2) with **strict CI gates already mandated** (CPNC §7: static analysis, architecture tests, full test suite, frontend lint on every PR). That combination points at a specific, well-justified model — not GitFlow, not pure trunk-based either. Below is the model, why, and exactly what lands where.

---

## 1. The model: trunk-based development with phase integration branches

**Before first production release (Phases 0–10):**

```
main
 └─ phase/1-shared-kernel-identity-rbac
     ├─ feature/1-shared-kernel-value-objects
     ├─ feature/1-staff-customer-authentication
     ├─ feature/1-rbac-role-shop-grants
     └─ feature/1-admin-shell-permission-middleware
 └─ phase/2-ui-design-system
     └─ feature/2-...
```

**After first production release:** `main` becomes production. A lightweight `hotfix/*` lane is added (§5) so a live-store emergency never has to wait for whatever the next phase branch is mid-building.

### Why not GitFlow
GitFlow's `develop`/`release`/`hotfix` branch trio exists to manage **multiple things in flight for release at different times**. AfProsPos pre-launch has exactly one thing in flight: the next phase, in order, each one depending on the last (Phase 3 needs Phase 1's RBAC; Phase 5 needs Phase 4's checkout). A permanent `develop` branch would just be a second `main` with no distinct purpose — pure overhead.

### Why not pure trunk-based (every feature branch straight into `main`)
Pure trunk-based assumes a team large and fast enough that many small PRs land on `main` daily and `main` is always shippable *that day*. AfProsPos's phases are individually large (Phase 1 alone is Shared Kernel + auth + RBAC + shop context) and explicitly gated by exit criteria (Implementation Plan §3) — merging half of Phase 1 into `main` before RBAC exists would leave `main` in a state that fails its own architecture tests. The `phase/*` integration branch gives you trunk-based hygiene *inside* a phase (small, short-lived, CI-gated feature branches) while keeping `main` always representing a **complete, gated phase** rather than a half-finished one.

---

## 2. Branch types and naming

| Branch pattern | Lifetime | Branched from | Merges into | Purpose |
|---|---|---|---|---|
| `main` | Permanent | — | — | Always a complete, passing-CI phase. Production after launch. |
| `phase/<n>-<slug>` | Weeks (one phase) | `main` | `main` | Integration branch for one Implementation Plan phase. |
| `feature/<n>-<slug>` | Days | current `phase/<n>-*` | its `phase/<n>-*` | One module/capability within a phase. |
| `fix/<slug>` | Days | `main` | `main` | Bug found in already-merged `main`, unrelated to the active phase. |
| `hotfix/<slug>` | Hours | `main` (production, post-launch) | `main` **and** the active `phase/*` | Production-breaking issue — see §5. |
| `chore/<slug>` | Days | `main` or active `phase/*` | same | Tooling, CI config, dependency bumps — no business logic. |
| `spike/<slug>` | Days, never merged | anywhere | **nowhere** — thrown away | Throwaway exploration/prototyping. Delete when done; if it turns out useful, redo it properly as a `feature/*`. |

**Naming rule:** lowercase, hyphen-separated, phase number first where applicable — same discipline CPNC already applies to code naming. Examples:

```
phase/1-shared-kernel-identity-rbac
feature/1-rbac-shop-scope-policy
feature/1-staff-deactivation-session-invalidation
feature/3-imei-serialized-reservation
feature/5-paystack-gateway-contract
fix/audit-log-missing-actor-role-snapshot
hotfix/checkout-reservation-not-releasing-on-expiry
chore/upgrade-spatie-permission-v6
```

Bad: `patch-1`, `johns-branch`, `fix-bug`, `phase1` (no slug — a branch name should tell a reviewer what it is without opening it).

---

## 3. What actually gets pushed where, mapped to your phases

This is the concrete answer to "what code goes on which branch" — mapped directly to the Implementation Plan's own phase list, so there's no ambiguity when someone opens a PR.

| Phase branch | What lands on it (module/domain scope) |
|---|---|
| `phase/0-bootstrap` | Laravel install, Breeze/starter kit, `domain/` PSR-4 wiring, base exception taxonomy, CI pipeline config, linting/static analysis setup — no business modules yet |
| `phase/1-shared-kernel-identity-rbac` | `domain/Shared`, `domain/Identity`, `domain/RBAC`, `domain/Shop`, `domain/Audit` (Phase 1 slice), auth middleware, admin/customer route shells |
| `phase/2-ui-design-system` | `resources/js` design tokens, shared layout components, sidebar/nav, the shop-switcher UI (consuming Phase 1's `shopContext` shared prop) |
| `phase/3-inventory-reservation` | `domain/Inventory`, SKU generation, On-hand/Reserved/Available reservation engine, IMEI serialization |
| `phase/4-sales-pos-checkout` | `domain/Sales`, checkout reservation flow, discount/promotion application order |
| `phase/5-payments-gateways` | `domain/Payments`, `PaymentGateway` contract + Paystack/Tranzix/manual-transfer implementations, contract test suite |
| `phase/6-repair-collection` | `domain/Repair`, diagnosis state machine, collection/abandonment lifecycle |
| `phase/7-warranty-returns-tradein` | `domain/Warranty`, `domain/Trade` (or equivalent), return/refund window |
| `phase/8-event-driven-operations` | `domain/Commission`, `domain/Referral`, `domain/Marketing`, full outbox-driven `domain/Audit` expansion, `domain/Notifications` |
| `phase/9-offline-pwa-reporting` | Offline sync outbox, PWA shell, `domain/Reporting`/financial consolidation depth |
| `phase/10-hardening-release` | Load/security testing fixes, final NFR sign-off — no new modules, only hardening of existing ones |

A feature branch's scope should map to **one module or one clearly-bounded slice of a module** — `feature/1-rbac-shop-scope-policy`, not `feature/1-everything`. If a PR touches more than one bounded context's `Domain/` folder, that's usually a sign it should have been two PRs, unless it's a genuine cross-module orchestration change (like `CreateStaffAccountHandler` in this delivery) — in which case say so explicitly in the PR description.

---

## 4. PR and merge rules

1. **No direct pushes to `main` or any `phase/*` branch.** Everything through a pull request, no exceptions — including for the solo owner/developer. A PR against yourself still forces the CI gates to run and gives you a reviewable diff in history.
2. **Every PR must pass, before merge** (CPNC §7.1, already your own stated bar): static analysis (PHPStan/Larastan at a strict level), the full architecture test suite (`tests/Architecture/`), the full Unit + Feature + Contract test suites, and the frontend lint pass.
3. **`feature/*` → `phase/*`: squash merge.** Keeps the phase branch's history as one clean commit per capability, which is what you'll actually want to read back later ("when did shop-scope enforcement land").
4. **`phase/*` → `main`: merge commit, never squash.** You want `git log --graph main` to show each phase as a visible, dated milestone — squashing an entire phase into one commit destroys that.
5. **PR description must reference the BRD/BLD rule IDs it implements or changes** — e.g. "Implements RBAC-05, RBAC-BR-08, RBAC-BR-09." This is what makes a future "why does deactivation force-logout sessions?" question answerable by `git blame` instead of by asking someone to remember.
6. **A PR that would leave `main` failing its own architecture tests is never merged**, even mid-phase — that's the entire reason `phase/*` exists as a buffer. `phase/*` branches are allowed to be temporarily red between feature merges; `main` is not, ever.
7. **Branch protection** (GitHub/GitLab settings, configure once): require PR before merge, require status checks to pass, require branches to be up to date before merging, no force-push, no branch deletion by non-admins for `main`.

---

## 5. Hotfixes (post-launch only)

Once `main` is production and the business is actively selling/repairing on it, a critical bug (e.g., "checkout reservations aren't releasing on expiry, shelves are showing phantom stock") cannot wait for whatever phase is currently mid-flight.

```
main (production, tagged v1.2.0)
 └─ hotfix/checkout-reservation-not-releasing-on-expiry
     → PR into main → deploy → tag v1.2.1
     → cherry-pick the same commit into the active phase/* branch
```

The cherry-pick step is not optional — skipping it means the fix silently disappears the moment the in-flight phase eventually merges and overwrites the file again.

---

## 6. Commit messages

Use **Conventional Commits** (`type(scope): summary`) — cheap to adopt, and it gives you an automatic changelog for free later:

```
feat(rbac): enforce shop-scope check on active-shop switch
fix(identity): deactivated staff session not invalidated under database driver
test(rbac): add concurrency test for duplicate role assignment
refactor(shared): extract Money::isGreaterThan for discount comparisons
chore(ci): add architecture test suite as required status check
docs(bld): record return/refund window amendment
```

Types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `perf`. Scope = module name (`identity`, `rbac`, `shop`, `inventory`, ...) lowercased, matching your `domain/<Module>` folder name.

---

## 7. Tagging and versioning

- Tag `main` at the end of every phase, even pre-launch: `v0.1.0-phase1`, `v0.2.0-phase2`, etc. Cheap, and gives you a concrete rollback point if a later phase's integration reveals a Phase-N assumption was wrong.
- Once live, move to standard SemVer (`v1.0.0`, `v1.1.0`, `v1.1.1` for the hotfix in §5). A MAJOR bump means a breaking API/data change; MINOR is a new phase's features landing; PATCH is a hotfix.

---

## 8. Quick reference

```
main                                    ← always a complete, CI-passing phase / production
 ├─ phase/1-shared-kernel-identity-rbac ← this delivery's scope
 │   ├─ feature/1-shared-kernel-value-objects
 │   ├─ feature/1-staff-customer-authentication
 │   ├─ feature/1-rbac-role-shop-grants
 │   └─ feature/1-admin-shell-permission-middleware
 ├─ fix/some-bug-found-in-main
 └─ hotfix/production-emergency          ← post-launch only, merges to main AND active phase/*
```

One rule to remember above all the others: **`main` never lies about what state the system is actually in.** Everything else in this document exists to protect that one property.
