# AfProsPos — Frontend UI/UX Design System Document

**Document:** Definitive Low-Level Frontend UI/UX Design System  
**Product:** AfProsPos — Phone Sales, Repair & Business Management System  
**Frontend Architecture:** Laravel + Inertia.js + React + Tailwind CSS  
**Design Architecture:** Dual-Theme Design System  
**Source Prototype:** `AfProsPos_Design_Preview (2).html`  
**Status:** BINDING — Development Blueprint / Design-System Source of Truth  
**Companion document:** AfProsPos Codebase Principles & Naming Conventions (CPNC) — governs component placement (`Pages/`, `Components/`, `Features/`) and the Frontend Constraints this document's tokens plug into  
**Date:** September 11, 2026 (Revision 7 — Admin type-scale/component-size bump; §28 tokens.css blueprint resynced with the shipped file)

> This document codifies the visual language and interaction behavior demonstrated in the supplied working HTML/CSS prototype. The prototype is treated as the visual reference implementation. Where the prototype uses a literal value inside a demonstration-only utility, this document converts that value into an explicit design token or semantic variable so production components do not accumulate raw color literals.

---

## How to Read This Document

This is a conformance specification, not a mood board. Every rule uses RFC-2119 severity:

- **MUST / MUST NOT** — a violation is a defect. Blocks merge regardless of who or what produced the code, AI included.
- **SHOULD / SHOULD NOT** — the strong default; deviation requires a written justification in the pull request.

Where this document defines a token, component contract, or state, that definition is authoritative. If a prototype pixel value and a token value in this document ever disagree during implementation, this document wins — the prototype is the *reference*, this document is the *contract*.

## Revision 2 — What Changed and Why

Revision 1 transcribed the prototype faithfully but left several tokens referenced without being defined, and left a few governance gaps that a human reviewer would catch but an AI generating components in isolation would not. This revision is additive and corrective — it does not change the visual identity established in Revision 1. Specifically, this pass:

1. Defines the **Semantic Token Layer** (`--ui-*`) that §1.2 Principle 3 referenced but §2 never formalized (see §2.3).
2. Adds the **missing tokens** that were referenced in prose but absent from the `tokens.css` blueprint: `--a-border-success`, `--c-shell-border` (now also listed in the Customer palette table, not just the blueprint), `--c-focus`, and a concrete Customer elevation/shadow scale (`--c-shadow-*`) to back the "floating surfaces are intentional" principle in §6.1 with actual values.
3. Extends the **Zero Raw Hex Rule** into a **Zero Raw Value Rule** (§2.2) and extends the Tailwind mapping (§16) beyond color to spacing, radius, typography, shadow, and motion — previously only color had a Tailwind config example, leaving every other token category without an enforceable utility-class path.
4. Specifies the **Disabled state** for buttons and inputs (§10.7, §11.6), which §23's State Matrix required every interactive component to define but no component section actually specified.
5. Reconciles the **Components/ directory structure** in §15 with the CPNC/ADD canonical tree (dropped a redundant `DesignSystem/` nesting layer that existed only in this document).
6. Rewrites the **example Button component** (§29) so its class composition actually demonstrates the token-driven Tailwind strategy from §16, instead of introducing untracked ad hoc class names.
7. Ties the **status badge modifier classes** (§9.2) explicitly to the shared status contract (§22), and adds an explicit status→token mapping table.
8. Standardizes "Valid" vs. "Success" terminology (§10.4) to match §22/§23.
9. Adds a new **Offline & Sync Visual Contract** (§14A) — the ADD dedicates a full architecture section to offline/PWA behavior and a `Sync` bounded context, and this design system previously specified zero visual treatment for connectivity, pending-sync, or conflict states.
10. Adds an explicit **Non-Goals** subsection (§1.3) so dark mode, RTL, and print are flagged as out-of-scope-for-now rather than silently undefined.
11. Adds an **AI Pre-Flight Checklist** (Appendix D) and a **Master Token Reference** (Appendix E), mirroring the CPNC's enforcement appendices.

Every fix above is called out inline at its section with a short "**Revision 2 fix:**" marker so the reasoning stays visible rather than being silently absorbed into the prose.

## Revision 3 — What Changed and Why

Revision 3 responds to a concrete production defect: an implementation put a shadow on the staff (Admin) login form. Tracing it back, the design system itself told the implementer to do that — §8.8's Admin Auth Shell said the form sits in a card with "standard borders and shadows," which directly contradicts §6.1 ("the only Admin surface permitted a shadow is the confirmation modal") and Invariant #19 (§33 — "Forms never require decorative elevated cards"). An implementer, human or AI, following §8.8 literally produces exactly the defect that shipped. That is a documentation defect, not an implementation defect, and Revision 3 fixes it at the source rather than only in the one screen where it surfaced.

Revision 3 also closes the second gap this document had: it specified a *system* (tokens, components, states) but never specified how that system composes into the actual screens the product needs — a repair page, a checkout page, a login page. A correct token and a correct component do not guarantee a correct page if nothing states which components a given page uses, in what arrangement, or in what states. This pass adds that missing layer.

Specifically, this revision:

1. **Fixes the Admin Auth Shell elevation contradiction (§8.8)** — the login/auth form no longer carries a shadow; it uses the same border-driven flatness as every other Admin surface, consistent with §6.1 and Invariant #19.
2. **Adds §14C, Page-Level Layout Specifications** — concrete, composable layouts (region diagrams, component manifests, states, responsive behavior) for the specific screens this document previously left to prose-level generality: Admin and Customer authentication, the POS checkout page, repair intake/diagnosis, the repair workflow/detail page, the customer repair tracker, the customer payment page, inventory list/detail, and both dashboards. Every component referenced in §14C already exists in this document's component system (§8–§13) or in the ADD §5A file tree — §14C composes, it does not invent new primitives.
3. **Cross-references ADD §5A** — the new Frontend Page, Component & Feature File Structure section added to the ADD in this same pass — so a page-layout spec here and a file path there stay in lockstep rather than drifting into two documents that disagree about what a page is called.

As with Revision 2, every fix is called out inline with a **Revision 3 fix:** marker.

## Revision 4 — What Changed and Why

Revision 3 fixed the *shadow* on the Admin login form but kept it wrapped in a bordered `admin-surface` panel, reasoning that Admin's flat aesthetic comes from border+surface rather than the absence of a container. In production this still rendered as a visibly boxed card sitting on the page background — the exact "decorative elevated card" Invariant #19 and §10.1's Core Rule prohibit, independent of whether that box carries a shadow. A border-bound card is still a card.

Revision 4 corrects this the rest of the way:

1. **Removes the Admin Auth Shell's bounding panel (§8.8)** — the login form now flows directly onto `admin-bg` with no surface fill, border, or radius, matching §10.1 literally and matching the Customer Auth Shell's existing cardless treatment.
2. **Updates §14C.1's page-level layout diagram** to match — no panel outline around the field stack.

As with prior revisions, the fix is called out inline with a **Revision 4 fix:** marker.

## Revision 5 — What Changed and Why

An implementation of `Pages/Customer/Dashboard/Index.tsx` matched §14C.10's ASCII diagram (greeting, hero card, recent orders, "view all activity") and was, by that narrow measure, spec-compliant — but it looked nothing like the source prototype's `#customer-app` screen (lines 542–650 of `AfProsPos_Design_Preview (2).html`), which was the actual reference this document claims to codify. Tracing the gap: §14C.10 was a simplified summary that silently dropped several things the prototype's single combined screen actually contains — the persistent 236px sidebar (vs. a plain top nav), the master-shell white frame with its 1px border and 30px radius (§8.4 describes this but §14C.10's diagram omits it entirely), and the Edit Profile / Notifications panel the prototype places directly on this screen rather than on a separate page. §14C.10 under-specified the page it was supposed to be authoritative for, and an implementation following it literally reproduced that gap.

This revision corrects §14C.10 to match the prototype's actual composition, and documents one deliberate, permanent departure from the prototype: the prototype hardcodes sample data ("Aisha Bello," "iPhone 13 Pro," a completed Samsung A54 order) because it's a static visual demo. Production `Dashboard/Index.tsx` is data-driven — no backend Repairs/Orders/Notifications data exists yet for the customer portal, so every section that would show that data instead renders a real empty state (§24) sized and styled to look intentional, not broken, until that data starts flowing. That is a data-wiring difference, not a visual one — the same components render either state.

Specifically:

1. **Rewrites §14C.10** to describe the full combined screen: master shell + sidebar/drawer (cross-referencing §8.4/§8.5/§8.6 rather than re-deriving them), hero card (active-repair state and empty state), a quick-actions row surfacing the customer-facing feature set (request a repair, track a repair, find a shop, refer a friend) even before those flows exist, recent-order list items, an Edit Profile panel bound to the customer's real account data (name/phone/email — this is real data, not a mock, since it's the customer's own registered profile), and a Notifications panel/header dropdown.
2. **Clarifies the empty-state contract for data-driven sections**: a section with no backend data yet renders the real §24 empty-state pattern, never a hardcoded sample row standing in for real data.
3. **Notes the header notification bell is interactive** (opens a dropdown, not just a static icon) — the prototype's bell is decorative-only; production makes it real per §14A's existing pattern for surfacing status.

As with prior revisions, every fix is called out inline with a **Revision 5 fix:** marker.

## Revision 6 — What Changed and Why

Two problems surfaced building Revision 5's design: one visual, one a repeat of a mistake already fixed once this session.

**Visual:** the "master shell" fix removed the prototype's demo-only bordered white card correctly, but overcorrected by also flattening the sidebar and main pane into one flush, borderless rectangle. Checking against the prototype's actual desktop CSS again: the sidebar and main pane each keep their own rounding and float as distinct panels with a real gap between them and the viewport edge — that part was never demo chrome, it's the Customer theme's "floating surfaces" principle (§6.1) applied to the shell itself, and removing it along with the frame was wrong. Separately, the sidebar's gradient background was being set with `sm:bg-customer-page-gradient` — a Tailwind responsive-variant prefix applied to a hand-written plain-CSS class, which Tailwind's variant system silently ignores (variants only apply to utilities Tailwind itself generates). The sidebar was stuck on white at every breakpoint as a result. And the mobile drawer's closed position combined a `left: 12px` inset with a `translateX(-100%)` close transform — since the 12% is relative to the element's own width only, not the additional 12px offset, a 12px sliver of the "closed" drawer always remained visible.

**Repeated mistake:** the Edit Profile panel Revision 5 added to the Dashboard was placed inside a shadowed white card — the same "form inside a decorative elevated card" defect Revision 3/4 fixed for the Admin login form (Invariant #19, §10.1's Core Rule), just recreated on the Customer side. The fix isn't to strip the shadow and leave a bare form sitting oddly in a two-column data grid — it's to recognize that profile editing is a big enough concern to warrant its own page, matching the ADD's original file tree (`Pages/Customer/Profile/Show.tsx`) after all.

Specifically, this revision:

1. **Corrects §8.4**: the sidebar and main pane are each independently rounded (`--c-radius-drawer`, 22px), shadowed floating panels (§6.1) sitting on a white page background with a real gap between them — not one flush rectangle. Fixes the responsive-variant bug by applying the gradient background unconditionally (sidebar and main pane always share it, at every breakpoint) rather than trying to vary it with a Tailwind prefix that doesn't apply to a custom class.
2. **Corrects §8.6**: the mobile drawer sits flush against the left/top/bottom viewport edges (`inset-y-0 left-0`, rounded on the trailing edge only) rather than inset with margins — inset positioning combined with a `translateX(-100%)` close transform leaves a residual sliver visible, since the transform's percentage basis is the element's own width only. A flush resting position makes the transform exact.
3. **Reverts part of Revision 5, corrects §14C.10**: Edit Profile is removed from the Dashboard and moves to its own page (`Pages/Customer/Profile/Show.tsx`, unelevated per §10.1 — the same treatment as the Admin/Customer auth forms). The Dashboard's side column instead shows a read-only Account summary card (name/phone/email, real data, no form) linking to the Profile page — not a form, so it can be elevated without contradiction. The hero card's empty state also picks up the same blue gradient treatment as its active-repair state, rather than a separate white/bordered look, for visual consistency regardless of data state.

As with prior revisions, every fix is called out inline with a **Revision 6 fix:** marker.

## Revision 7 — What Changed and Why

Two changes, both scoped to the Admin theme only (Customer is untouched by either):

**Sizing:** a session working through the Admin Inventory screens live (not just reviewing static mockups) found the whole shell reading noticeably small for all-day desktop use — confirmed with a direct 100%-vs-110%-browser-zoom side-by-side comparison, which made the size gap unambiguous rather than a matter of taste. This compounds an earlier, previously undocumented ~8% bump the Admin type scale and component tokens already carried (see the `tokens.css` blueprint's own inline comments below, which referenced "bumped ~8%" without a corresponding revision note here — Revision 7 closes that gap retroactively as well as adding the new bump). A further ~10% is applied on top of the already-bumped values: `--a-text-*` (13/14/15/17/19/23px), and every Admin component token that carries a literal text or padding size (`--a-btn-*`, `--a-table-cell-*`, `--a-field-*`, `--a-nav-item-*`, `--a-sidebar-width*`, `--a-topbar-height`, `--a-drawer-width`, `--a-content-padding-x`). Radii are deliberately untouched — corner-rounding wasn't the source of the "too small" complaint, only text/spacing was, so scaling radii too would be a change nobody asked for.

**Documentation drift:** while making the above change, this section's `tokens.css` blueprint (§28, reproduced below) turned out to already be stale independent of the sizing question — it was missing the Admin typography scale, all Admin/Customer component tokens (Layer 4), the five shared `--ui-*` component tokens (`--ui-modal-width`, `--ui-radius-sheet`, `--ui-loader-size`, `--ui-loader-blur`, `--ui-toast-width`), `--c-focus`/`--c-shadow-soft`/`--c-shadow-strong`, and the entire `[data-theme="admin"]`/`[data-theme="customer"]` semantic resolution block — all of which existed in the shipped `resources/css/tokens.css` (added across Revisions 2–6's fixes) but were never written back into this document's own code sample. The blueprint below is now a verbatim copy of the shipped file, not a frozen Revision-1 snapshot, so the two cannot silently diverge again without the drift being visible in a diff.

Specifically, this revision:

1. Bumps the Admin typography scale and every Admin component token with a literal size by ~10% (values listed above), on top of the pre-existing ~8% bump.
2. Replaces §28's `tokens.css` code block wholesale with the current, actual contents of `resources/css/tokens.css` — closing a real doc/code gap, not just adding the new numbers.

As with prior revisions, the fix is called out inline with a **Revision 7 fix:** marker where it lands on a specific subsection.

---

# 1. Design Principles & Dual-Theme Architecture

## 1.1 Product Design Goal

AfProsPos serves two fundamentally different audiences:

- **Internal operators:** shop owners, accountants, technicians, cashiers, product staff, marketing staff.
- **Customers:** people using a self-service portal to track repairs, view purchases, make payments, and manage their profile.

The design system therefore does **not** attempt to force one visual language across the product.

Instead, AfProsPos has one shared design-system foundation with two intentionally different expression layers.

```text
Shared Foundations
│
├── Accessibility
├── Typography primitives
├── Spacing scale
├── Breakpoints
├── Motion principles
├── Icon system
├── Focus behavior
├── Form semantics
├── Loading behavior
└── Interaction contracts
        │
        ├──────────────────────────┐
        │                          │
        ▼                          ▼
Admin Theme                 Customer Theme
Operations Console          Self-Service Portal
Cloudflare / Dojah-like     Material 3 / iOS-like
dense + serious             friendly + expressive
flat + structured           rounded + floating
compact controls            tactile controls
desktop-first               mobile-first interaction
```

---

## 1.2 Non-Negotiable Design Principles

### Principle 1 — Density follows job-to-be-done

Admin users operate the business. They need:

```text
more information per screen
shorter vertical distances
clear status scanning
stable tables
fast repeat interactions
predictable controls
```

Customer users need:

```text
strong visual hierarchy
low cognitive load
large touch targets
clear next actions
friendly feedback
progressive disclosure
```

### Principle 2 — Flatness is intentional

The product does not use generic "card everything" design.

Admin content areas merge into the page background and rely primarily on:

```text
surface
border
spacing
typography
status color
```

rather than heavy shadows.

Customer surfaces may use floating cards because the consumer portal intentionally uses spatial separation and tactile interaction.

### Principle 3 — The theme is semantic, not a page-specific color scheme

Components must consume semantic variables:

```css
var(--ui-bg)
var(--ui-surface)
var(--ui-text)
var(--ui-accent)
```

or explicit theme-token aliases such as:

```css
var(--a-blue)
var(--c-blue)
```

They must not introduce page-specific raw hex colors.

> **Revision 2 fix:** Revision 1 named the `--ui-*` tokens here but never defined them anywhere in the document — the only tokens actually declared in §3/§28 were the theme-prefixed `--a-*`/`--c-*` ones. §2.3 now formally defines the `--ui-*` semantic layer these tokens belong to, and how it resolves per theme.

### 1.3 Non-Goals (Explicitly Out of Scope for This Version)

Mirroring the ADD's own Goals/Non-Goals structure, the following are **not** addressed by this design system and MUST NOT be invented ad hoc by a developer or AI encountering a gap:

- **Dark mode.** No dark-theme token set exists. Do not add a `prefers-color-scheme: dark` branch or a dark variant of any token without a dedicated design-system revision.
- **RTL layout.** All layout direction is LTR. Do not add `dir="rtl"` handling speculatively.
- **Print stylesheets.** No print-specific rules are defined. Invoice/receipt PDF output is a `pdf`-skill/document-generation concern, not a browser print-CSS concern, and is out of scope here.
- **A third visual theme.** Only Admin and Customer exist. A future "Technician mobile app" or "Kiosk mode" would require its own design-system revision, not an improvised blend of the two existing themes.

If a task appears to require one of the above, treat it as a design-system gap to escalate, not a decision to make silently.

### Principle 4 — Status is redundant

Never communicate status only with color.

Use:

```text
color + text + icon/dot
```

Examples:

```text
● In progress
● Awaiting parts
● Completed
```

### Principle 5 — Primary actions are singular

A screen should normally have one visually dominant primary action.

Secondary actions must have less visual weight.

Destructive actions must never visually compete with a primary business action.

### Principle 6 — Motion communicates state

Motion is functional:

```text
button → loading
drawer → opening
toast → arriving
accordion → expanding
skeleton → refreshing
spinner → blocking
```

Do not add animation solely for decoration.

---

# 2. Design Token Architecture

## 2.1 Token Layers

Production CSS should follow a four-layer token architecture.

```text
Layer 1 — Primitive Tokens
    actual color / size / radius values

Layer 2 — Semantic Tokens
    background / text / action / danger / success

Layer 3 — Theme Tokens
    admin aliases / customer aliases

Layer 4 — Component Tokens
    button / table / input / modal-specific mappings
```

Example:

```css
:root {
  --color-blue-600: #1E56E8;
  --color-blue-050: #EAF0FF;

  --a-blue: var(--color-blue-600);
  --a-blue-soft: var(--color-blue-050);
}
```

Production React components should primarily consume **semantic/theme tokens**, not primitive values.

---

## 2.2 Zero Raw Value Rule (formerly "Zero Raw Hex Rule")

> **Revision 2 fix:** Revision 1 scoped this rule to hex colors only. Every other token category (spacing, radius, typography, shadow, motion) is equally tokenized in §28's `tokens.css` blueprint but had no equivalent prohibition — leaving `className="p-[14px]"` or `className="rounded-[6px]"` technically un-forbidden even though they defeat the same purpose a raw hex color would. The rule now covers every token category this document defines.

The prototype contains literal values for illustrative/demo infrastructure. Production application code must normalize those values into tokens.

Forbidden — color:

```css
background: #1E56E8;
```
```jsx
className="text-[#1E56E8]"
```

Forbidden — spacing, radius, typography, shadow (arbitrary Tailwind values bypassing the token config):

```jsx
className="p-[14px]"        /* ✗ use p-4 (--space-4) via the spacing scale */
className="rounded-[6px]"   /* ✗ use rounded-admin-button */
className="text-[13px]"     /* ✗ use text-admin-base */
```

Preferred, in all cases — a token-backed Tailwind utility configured in `tailwind.config.js` (see §16 for the full mapping across every category):

```jsx
className="bg-admin-blue text-admin-base rounded-admin-button p-4"
```

This rule applies to:

- React components;
- Tailwind utilities (including Tailwind's arbitrary-value syntax `[...]`, which is exactly as forbidden as an inline raw hex — it is the same violation wearing different syntax);
- CSS modules;
- inline styles;
- SVG fills/strokes;
- state classes;
- pseudo-elements.

Exceptions are permitted only inside the central token source itself (`resources/css/tokens.css` and the `tailwind.config.js` theme block that reads from it).

---

## 2.3 The Semantic Token Layer (`--ui-*`)

> **Revision 2 fix:** §1.2 Principle 3 instructed components to consume `var(--ui-bg)`, `var(--ui-surface)`, `var(--ui-text)`, and `var(--ui-accent)` — but Revision 1 never defined this token namespace anywhere; only the theme-prefixed `--a-*`/`--c-*` tokens actually existed. This subsection closes that gap by formalizing Layer 2 (Semantic) from §2.1's four-layer model.

The `--ui-*` namespace exists for the small set of genuinely **theme-agnostic** components — ones that must render correctly inside either an Admin or a Customer surface without knowing which (for example, a shared `Skeleton`, a shared `Toast` primitive before theme-specific styling is layered on, or a cross-cutting `Icon` wrapper). These components consume `--ui-*`, never `--a-*`/`--c-*` directly. A **theme-scoped wrapper** — the top-level Admin or Customer shell — is responsible for resolving `--ui-*` to the correct theme's concrete tokens:

```css
/* tokens.css — semantic layer, resolved per theme scope */
[data-theme="admin"] {
  --ui-bg: var(--a-bg);
  --ui-surface: var(--a-surface);
  --ui-text: var(--a-text);
  --ui-text-secondary: var(--a-text2);
  --ui-border: var(--a-border);
  --ui-accent: var(--a-blue);
  --ui-focus: var(--a-focus);
  --ui-radius-control: var(--a-radius-button);
}

[data-theme="customer"] {
  --ui-bg: var(--c-page-mid);
  --ui-surface: #FFFFFF;
  --ui-text: var(--c-text);
  --ui-text-secondary: var(--c-text2);
  --ui-border: var(--c-input-border);
  --ui-accent: var(--c-blue);
  --ui-focus: var(--c-focus);
  --ui-radius-control: var(--c-radius-pill);
}
```

**Rule:** Any component living in a theme-specific folder (`Components/Admin/`, `Components/Customer/`, or a `Features/<Module>/` component that is only ever rendered inside one theme) MUST consume the theme-prefixed token directly (`--a-blue`, `--c-blue`) — reaching for `--ui-*` there is unnecessary indirection. Only a component explicitly designed to be theme-agnostic (rendered inside either `[data-theme="admin"]` or `[data-theme="customer"]` depending on where it's mounted) consumes `--ui-*`. If you are not sure which applies, the component almost certainly belongs to one theme and should use that theme's direct tokens.

---

# 3. Color Tokens & Palette Specifications

## 3.1 Official Brand Palette

The official identity consists of:

```text
Blue
Yellow
Black
White
```

with semantic:

```text
Red = danger/error
Green = success/confirmation
```

---

## 3.2 Admin Palette

| Token | Hex | RGB | Semantic role |
|---|---|---|---|
| `--a-bg` | `#F5F6F8` | `245, 246, 248` | Application background |
| `--a-surface` | `#FFFFFF` | `255, 255, 255` | Surface / table / form background |
| `--a-border` | `#E4E7EC` | `228, 231, 236` | Default separators |
| `--a-border2` | `#D0D5DD` | `208, 213, 221` | Strong form/control border |
| `--a-text` | `#101828` | `16, 24, 40` | High-emphasis text |
| `--a-text2` | `#667085` | `102, 112, 133` | Secondary text |
| `--a-text3` | `#98A2B3` | `152, 162, 179` | Muted / placeholder / tertiary |
| `--a-blue` | `#1E56E8` | `30, 86, 232` | Primary action |
| `--a-blue-soft` | `#EAF0FF` | `234, 240, 255` | Selection / tint |
| `--a-yellow` | `#F5A623` | `245, 166, 35` | Warning |
| `--a-yellow-soft` | `#FFF6E5` | `255, 246, 229` | Warning background |
| `--a-yellow-text` | `#9A5B00` | `154, 91, 0` | Warning foreground |
| `--a-red` | `#D92D20` | `217, 45, 32` | Danger/error |
| `--a-red-soft` | `#FEF0EE` | `254, 240, 238` | Danger background |
| `--a-green` | `#16A34A` | `22, 163, 74` | Success |
| `--a-green-soft` | `#EAF8EE` | `234, 248, 238` | Success background |

### Admin additional derived tokens

These are tokenized equivalents of values demonstrated by the prototype:

| Token | Value | Role |
|---|---|---|
| `--a-table-head` | `#FAFBFC` | Table header fill |
| `--a-hover` | `#F2F3F6` | Subtle hover |
| `--a-focus` | `#C7D6FC` | Input focus ring |
| `--a-border-warning` | `#F5D999` | Warning component border |
| `--a-border-danger` | `#F6C6C1` | Danger component border |
| `--a-border-success` | `#D5F0DD` | Success component border |
| `--a-overlay` | `rgba(16,20,30,.44)` | Desktop modal / drawer backdrop |
| `--a-shadow-modal` | `0 8px 24px rgba(16,20,30,.18)` | The one Admin surface allowed a shadow — the confirmation modal (§12.2); everything else in Admin stays flat per §6.1 |

> **Revision 2 fix:** `--a-shadow-modal` did not exist in Revision 1. §6.1 requires the Admin confirmation modal to visually separate from the page behind it, but "flat by default" left no token to do that with — this fills the one legitimate Admin elevation case without opening the door to shadows elsewhere in the theme.

---

## 3.3 Customer Palette

| Token | Hex | RGB | Semantic role |
|---|---|---|---|
| `--c-page-start` | `#CFE3FF` | `207, 227, 255` | Gradient start |
| `--c-page-mid` | `#EAF3FF` | `234, 243, 255` | Gradient middle |
| `--c-page-end` | `#FFFFFF` | `255, 255, 255` | Gradient end |
| `--c-text` | `#1B1F27` | `27, 31, 39` | High-emphasis |
| `--c-text2` | `#636B78` | `99, 107, 120` | Secondary |
| `--c-blue` | `#2F6FED` | `47, 111, 237` | Primary action |
| `--c-blue-soft` | `#E7EFFF` | `231, 239, 255` | Tint / selected |
| `--c-yellow` | `#FFC845` | `255, 200, 69` | Accent / warning |
| `--c-yellow-soft` | `#FFF6DF` | `255, 246, 223` | Warning background |
| `--c-red` | `#E24444` | `226, 68, 68` | Error/danger |
| `--c-red-soft` | `#FDECEC` | `253, 236, 236` | Danger background |
| `--c-green` | `#1FA463` | `31, 164, 99` | Success |
| `--c-green-soft` | `#E8F8EF` | `232, 248, 239` | Success background |

### Customer derived tokens

| Token | Value | Role |
|---|---|---|
| `--c-input-border` | `#D7E3F7` | Underline field default |
| `--c-divider` | `#EEF0F3` | Soft structural divider |
| `--c-muted-icon` | `#B7BEC9` | Chevron / tertiary icon |
| `--c-taskbar-border` | `#EEF0F3` | Mobile taskbar separator |
| `--c-overlay` | `rgba(16,20,30,.40)` | Drawer backdrop |
| `--c-focus` | `#B9CFFF` | Keyboard focus ring on Customer controls (see §18.2) |
| `--c-shadow-soft` | `0 2px 10px rgba(16,20,30,.08)` | List item / card / drawer resting elevation |
| `--c-shadow-strong` | `0 10px 30px rgba(16,20,30,.16)` | FAB, modal, toast — surfaces that float above interactive content |
| `--c-gradient-page` | `linear-gradient(180deg, #CFE3FF 0%, #EAF3FF 38%, #FFFFFF 72%)` | Main page background |
| `--c-gradient-hero` | `linear-gradient(135deg, #2F6FED, #1E56E8)` | Hero surface |

> **Revision 2 fix:** `--c-focus` and `--c-shadow-strong` did not exist anywhere in Revision 1 — Accessibility (§18.2) referenced a focus token generically without a Customer-specific value, and Elevation (§6.1) described "floating surfaces are intentional" without ever assigning a shadow value to back that principle up. `--c-shadow-soft` previously said only "prototype-defined soft shadow," which is not implementable; it now has a concrete value.
>
> **Revision 5 fix:** `--c-shell-border` (and `--c-radius-shell` in §6.3) are retired — see §8.4's Revision 5 fix. They backed a "master frame" that turned out to be the source prototype's presentation-tool chrome, not a real production surface.

---

## 3.4 Color Usage Rules

### Admin

Primary blue:

```text
primary CTA
active navigation
focus
positive selection
```

Yellow:

```text
warnings
cautionary operations
attention states
```

Red:

```text
errors
destructive actions
failed states
```

Green:

```text
success
confirmed
completed
positive delta
```

### Customer

Blue should be more visually expressive.

Yellow should appear as a warm accent, especially in:

```text
progress indicators
status dots
hero chips
attention
```

Customer red and green remain semantic and must not be used as decorative gradients outside feedback contexts.

---

## 3.5 Contrast Rule

All text and controls must remain contrast-accessible against their backgrounds.

Do not solve accessibility issues by changing the semantic color of a token locally.

Instead:

```text
create/modify the semantic token
```

and update every mapped component.

---

# 4. Typography & Type Hierarchy

## 4.1 Font Architecture

### Admin

```css
font-family:
  "Inter",
  system-ui,
  sans-serif;
```

Primary use:

```text
all UI
tables
labels
navigation
forms
body content
```

### Customer

Headings / branding:

```css
font-family:
  "Plus Jakarta Sans",
  system-ui,
  sans-serif;
```

Inputs and secondary text:

```css
font-family:
  "Inter",
  system-ui,
  sans-serif;
```

This creates:

```text
friendly display layer
+
highly readable utility layer
```

---

## 4.2 Monospace Stack

Use:

```css
font-family:
  "JetBrains Mono",
  Consolas,
  monospace;
```

for:

- IMEIs
- SKUs
- invoice numbers
- provider references
- transaction identifiers
- technical IDs
- currency figures when precision/scannability benefits from alignment

---

## 4.3 Weight Scale

| Token | Weight | Primary use |
|---|---:|---|
| `--font-regular` | 400 | body |
| `--font-medium` | 500 | controls / secondary emphasis |
| `--font-semibold` | 600 | labels / nav / table states |
| `--font-bold` | 700 | headings / values / CTAs |
| `--font-extrabold` | 800 | customer hero / brand emphasis |

---

## 4.4 Desktop Type Scale

| Token | Size | Line height | Typical use |
|---|---:|---:|---|
| `--text-xs` | 11px | 16px | metadata |
| `--text-sm` | 12px | 17px | secondary text |
| `--text-base` | 13px | 19px | default admin UI |
| `--text-md` | 14px | 20px | section titles |
| `--text-lg` | 16px | 22px | customer title / compact heading |
| `--text-xl` | 19px | 26px | admin page title |
| `--text-2xl` | 22px | 28px | section/portal heading |
| `--text-3xl` | 28px | 34px | customer page heading |
| `--text-4xl` | 34px | 40px | major hero/display |

The prototype's admin page title is approximately `19px/700`, while the customer interface uses approximately `14–16px` compact titles and `16px+` hero emphasis. These values are normalized above into a reusable scale.

---

## 4.5 Mobile Type Scale

| Token | Size | Line height |
|---|---:|---:|
| `--text-xs` | 10px | 14px |
| `--text-sm` | 11px | 16px |
| `--text-base` | 12px | 17px |
| `--text-md` | 13px | 18px |
| `--text-lg` | 15px | 21px |
| `--text-xl` | 18px | 24px |
| `--text-2xl` | 21px | 27px |
| `--text-3xl` | 26px | 32px |
| `--text-4xl` | 32px | 38px |

---

## 4.6 Typography Rules

Admin:

```text
Do not oversize UI text.
Do not use giant dashboard metrics.
Do not use 20+ px table text.
```

Customer:

```text
Use larger visual hierarchy than admin.
Keep body copy short.
Separate display text from utility copy through family rather than excessive size.
```

---

# 5. Spacing Scale, Layout Grids & Breakpoints

## 5.1 Base Spacing Scale

Use a 4px base:

```text
--space-0   0px
--space-1   4px
--space-2   8px
--space-3   12px
--space-4   16px
--space-5   20px
--space-6   24px
--space-8   32px
--space-10  40px
--space-12  48px
--space-16  64px
--space-20  80px
```

The prototype uses dense 6–14px controls in the admin interface and 12–26px breathing room in customer surfaces.

---

## 5.2 Breakpoints

The prototype behavior is strongly driven by a mobile threshold around `640px`.

Define:

```text
sm = 640px
md = 768px
lg = 1024px
xl = 1280px
2xl = 1536px
```

### Critical AfProsPos rule

```text
< 640px
    mobile interaction model

>= 640px
    desktop/tablet compact model
```

Do not merely shrink the desktop interface. Components may change structure.

---

## 5.3 Admin Content Grid

Desktop:

```text
Sidebar 220px
+
Main content flexes
+
Content padding 22px horizontal
```

Prototype baseline:

```text
sidebar = 220px
topbar = 52px
content padding = 20px 22px 40px
```

Collapsed sidebar:

```text
64px
```

Mobile:

```text
sidebar = 230px off-canvas
content padding = 16px
search hidden
shop text compressed
```

---

## 5.4 Admin Form Grid

Maximum content width:

```text
640px
```

Default desktop row:

```css
grid-template-columns: 1fr 1fr;
gap: 14px;
```

Mobile:

```css
grid-template-columns: 1fr;
```

---

## 5.5 Customer Content Grid

Desktop:

```text
main content : side content
1.3fr : 0.9fr
```

with:

```text
gap = 24px
```

Mobile:

```text
one column
```

---

# 6. Elevation, Borders & Radii Specifications

## 6.1 Elevation Philosophy

### Admin

Flat by default:

```text
shadow = 0
```

Hierarchy comes from:

```text
background
surface
border
spacing
```

The prototype's admin stat cards, table shells, inputs, and sidebar are border-driven rather than shadow-driven. The **only** Admin surface permitted a shadow is the confirmation modal, using `--a-shadow-modal` (§3.2). Do not add a shadow to a stat card, table, sidebar, or dropdown to "make it pop" — that violates Principle 2 (§1.2).

### Customer

Floating surfaces are intentional.

Allowed elevated surfaces, and the token each one uses:

| Surface | Shadow token |
|---|---|
| Hero card | `--c-shadow-soft` |
| List item | `--c-shadow-soft` |
| Drawer | `--c-shadow-strong` |
| FAB | `--c-shadow-strong` |
| Taskbar | `--c-shadow-soft` (upward, on the top edge only) |
| Toast | `--c-shadow-strong` |
| Modal | `--c-shadow-strong` |
| Mobile controls (bottom sheet) | `--c-shadow-strong` |

> **Revision 2 fix:** Revision 1 listed these eight surfaces as "allowed" but assigned no shadow token to any of them, and no shadow token had a concrete value to assign in the first place (see §3.3's fix note). The distinction between `--c-shadow-soft` (resting, in-flow elevation) and `--c-shadow-strong` (overlay/floating-above-content elevation) mirrors the same soft/strong pairing already used for Admin borders in §6.4 — reuse of an existing pattern rather than a new one.

---

## 6.2 Admin Radius Scale

```text
--a-radius-input   4px
--a-radius-badge   4px
--a-radius-button  6px
--a-radius-card    8px
--a-radius-modal   14px
```

### Admin rules

- Inputs: 4px
- Buttons: 6px
- Cards/table shells: 8px
- Confirmation modal: 14px

---

## 6.3 Customer Radius Scale

```text
--c-radius-item    14px
--c-radius-card    16px
--c-radius-hero    22px
--c-radius-drawer  22px
--c-radius-pill    100px
```

Customer controls should appear organic and tactile.

---

## 6.4 Border Rules

Admin borders:

```text
default = #E4E7EC token
strong = #D0D5DD token
```

Customer:

```text
surface shell = 1px dark border
fields = 2px underline
soft dividers = tokenized #EEF0F3
```

Do not use multiple competing border strengths within the same component without a reason.

---

# 7. Iconography Standards & Rules

## 7.1 Official Icon System

Use **Bootstrap Icons exclusively**.

Prototype source:

```html
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css"
>
```

Production may use:

```text
WebFont
OR
SVG
```

but the icon glyphs must remain Bootstrap Icons.

---

## 7.2 Strict Anti-Emoji Rule

Emojis are prohibited from all UI surfaces.

Forbidden:

```text
🔔
✅
⚠️
📦
```

Use:

```text
bi-bell
bi-check-circle
bi-exclamation-triangle
bi-box-seam
```

Reasons:

- platform inconsistency;
- uncontrollable visual weight;
- inaccessible semantics;
- theme mismatch;
- consumer-vs-admin inconsistency.

---

## 7.3 Icon Sizing

Admin:

```text
12–16px for utility icons
14–15px navigation icons
```

Customer:

```text
15–19px general actions
17px taskbar icons
19px FAB icon
```

---

## 7.4 Icon Color

Icons must inherit:

```css
currentColor
```

Do not hardcode fill colors inside component SVG markup unless the icon itself is the brand mark.

When an icon is the sole control:

```html
<button aria-label="Open notifications">
  <i class="bi bi-bell"></i>
</button>
```

---

# 8. Layout Shells & Structural Navigation

# 8.1 Admin Shell

## Desktop anatomy

```text
┌─────────────────────────────────────────────────────────────┐
│ Sidebar │ Topbar                                             │
│         ├─────────────────────────────────────────────────────┤
│         │ Content                                             │
│         │                                                      │
│         │ Page Head                                           │
│         │ Stats                                               │
│         │ Tables / Forms                                      │
└─────────┴─────────────────────────────────────────────────────┘
```

### Sidebar

```text
width: 220px
background: surface
right border: 1px
padding: 14px 10px
```

Collapsed:

```text
width: 64px
```

Behavior:

```text
labels hidden
group names hidden
chevron rotates 180deg
icons remain
```

### Navigation item

Prototype baseline:

```text
padding: 7px 8px
radius: 6px
gap: 10px
icon width: 16px
font size: 12.5px
```

Active:

```text
background: blue-soft
color: blue
font-weight: 600
```

Inactive hover:

```text
background: subtle hover
color: high-emphasis
```

---

## 8.2 Admin Mobile Navigation

Sidebar becomes:

```text
position: absolute/fixed
left: 0
top: 0
bottom: 0
width: 230px
```

Initial state:

```text
translateX(-100%)
```

Open:

```text
translateX(0)
```

Backdrop:

```text
rgba(16,20,30,.44)
```

The drawer is flush to the left and does not gain a rounded/floating container because the admin theme must remain operational and serious.

---

## 8.3 Admin Topbar

Exact baseline:

```text
height: 52px
padding: 0 18px
```

Contains:

```text
mobile hamburger
shop switcher
global search
notification bell
avatar
```

Shop switcher example:

```text
[ shop icon ] Ikeja branch [ chevron ]
```

Search:

```text
left icon
flexible width
maximum width ≈ 320px
```

Mobile:

```text
hamburger = visible
search = hidden
shop text = hidden
```

---

# 8.4 Customer Shell

## Desktop

> **Revision 5 fix:** earlier revisions specified a "master frame" here — a white card with a 1px black border and 30px radius wrapping the entire shell, margin/padding around it on all sides. That was transcribed from the source prototype's `#customer-app` styling without noticing it's presentation-tool chrome: the prototype renders every screen inside a fixed-size `.device-frame` used to preview admin/customer and desktop/mobile side by side in one document, and the border/radius/padding was styling *that* preview frame, not a real production requirement. `--c-shell-border` and `--c-radius-shell` are retired — nothing in production consumes them.
>
> **Revision 6 fix:** the Revision 5 fix above overcorrected — it also flattened the sidebar and main pane into one flush, borderless rectangle, which isn't right either. The sidebar and main pane each keep their own rounding and shadow as independent floating panels; only the *outer* bordered white card was ever demo chrome. It also fixed a real bug: the sidebar's gradient background was written as `sm:bg-customer-page-gradient` — applying a Tailwind responsive prefix to a hand-written plain-CSS class, which Tailwind's variant system does not generate a rule for (variants only apply to utilities Tailwind itself produces from `@theme`). The sidebar was rendering white at every breakpoint as a result; the gradient is now applied unconditionally instead of through a variant that silently never matched.

The customer shell is a two-column layout: a persistent sidebar and a main content pane, each an independently rounded, shadowed floating panel (§6.1 — floating surfaces are intentional) sitting on a plain white page background, with a real gap between them:

```text
Sidebar   — fixed/sticky left, full viewport height, width per §8.5, rounded + shadowed
Main pane — fills the remaining width, full viewport height, rounded + shadowed
```

Both panes carry the brand gradient (`--c-gradient-page`) as their background — the same gradient, applied identically to both, at every breakpoint. They read as two distinct surfaces via their shadow and the white gap between them, not via a color difference. The page background behind/between them (the gap, and any outer padding) is plain white.

---

## 8.5 Customer Desktop Navigation

Persistent left navigation:

```text
width ≈ 236px
rounded organic geometry
same family as main content gradient
```

Items:

```text
Home
Repairs
Orders
Shops
Profile
Notifications
Log out
```

Active state:

```text
blue-soft background
blue text
rounded 14px
```

---

## 8.6 Customer Mobile Drawer

Prototype baseline (visual reference only — see the Revision 6 fix below for the production positioning):

```text
top: 12px
left: 12px
bottom: 12px
width: 248px
radius: 22px
shadow: soft/high
```

> **Revision 6 fix:** an implementation combined this 12px inset with a `translateX(-100%)` close transform, reasoning it matched the prototype's `translateX(-130%)`. It doesn't: a percentage transform is relative to the element's own box only, not any additional positional offset, so at `left: 12px` a `translateX(-100%)` close leaves the drawer's trailing 12px permanently visible past the viewport edge — the drawer never fully closes. Production positions the drawer flush against the viewport edges instead (`top/bottom/left: 0`), rounded only on the trailing edge rather than all four corners, so `translateX(-100%)` is exact with no residual sliver. The prototype's inset "floating card" look for the closed drawer is not reproduced in production for this reason.

Drawer movement:

```text
closed: translateX(-100%)
open: translateX(0)
```

Backdrop:

```text
rgba(16,20,30,.40)
```

Unlike the admin drawer, the customer drawer keeps its own rounding and shadow rather than being a flat flush panel — but it sits flush against the three viewport edges it opens from, not inset with margins on all sides.

---

## 8.7 Customer Mobile Bottom Taskbar

```text
height: 62px
position: fixed/sticky to viewport bottom
background: white
```

Five-tab mental model:

```text
Home
Repairs
    +  ← center action
Orders
Profile
```

Center action:

```text
width: 50px
height: 50px
circle
margin-top: -30px
blue fill
shadow
```

This overlaps the taskbar edge and acts as the primary action.

The main page content must reserve sufficient bottom padding:

```text
≈ 86px
```

so the taskbar does not obscure interactive content.

# 8.8 Authentication Shells

Authentication pages (Login, Register, Password Reset) MUST NOT use the full Master Shell (`AdminShell` or `CustomerShell`) because they should not expose internal navigation, sidebars, or taskbars to unauthenticated users.

Instead, they use dedicated minimal shells (`AdminAuthShell` and `CustomerAuthShell`):

## Admin Auth Shell

> **Revision 3 fix:** Revision 1/2 specified "standard borders and **shadows**" here. That directly contradicted §6.1 ("the only Admin surface permitted a shadow is the confirmation modal, via `--a-shadow-modal`") and Invariant #19 (§33 — "Forms never require decorative elevated cards"), and is the traced root cause of a real defect: an implementation followed this section literally and shipped an elevated staff login form. This section is corrected to agree with §6.1/§10.1/Invariant #19 rather than override them.

> **Revision 4 fix:** Revision 3 removed the shadow but kept a bordered `admin-surface` card wrapping the form, reasoning that Admin flatness comes from border+surface rather than "no container." In production this still read as a decorative card around a form — the exact thing Invariant #19 and §10.1's Core Rule ("Forms are **not** wrapped in decorative cards... without an elevated parent") prohibit, border or not. Revision 4 removes the panel entirely: the Admin Auth Shell now follows §10.1 literally, the same as every other Admin form and the same as the Customer Auth Shell below.

- `min-h-screen`, `bg-admin-bg`, centered content vertically and horizontally.
- The form flows directly onto the `admin-bg` page surface — **no bounding panel, no `admin-surface` fill, no border, no `box-shadow`**. Hierarchy comes only from spacing and typography (§10.1, §6.1, Invariant #19).
- Content width follows the standard Admin form constraint (§10.2 — max 640px); a login form's actual field count will render narrower in practice, typically ~360–400px, single column regardless of viewport (a login form never needs the two-column form grid).
- No navigation, no sidebar.
- See §14C.1 for the full page-level layout (logo/heading placement, field order, error/loading states).

## Customer Auth Shell
- `min-h-screen`, `bg-white`, `text-customer-text`.
- Minimal top header displaying only the company logo and name.
- Forms flow directly onto the page surface (no encapsulating cards).
- No bottom taskbar, no drawer navigation.

---

# 9. Tables & Data Display — Desktop Grids vs Mobile Accordion Cards

## 9.1 Admin Desktop Table

Table shell:

```text
surface background
1px border
8px radius
overflow hidden
```

Header:

```text
background: --a-table-head
font-size: 11px
font-weight: 600
secondary text color
```

Cell baseline:

```text
font-size: 12.5px
padding: 10px 14px
```

Row behavior:

```text
hover → subtle background
```

---

## 9.2 Status Badge Anatomy

Admin badge:

```text
inline-flex
indicator dot
label
small rounded capsule
```

Structure — the modifier class names the **status contract category** from §22, not a raw color, so a future palette change never requires touching component markup:

```html
<span class="badge badge-info">
  In progress
</span>
```

> **Revision 2 fix:** Revision 1's example (`class="badge b-blue"`) named the badge after a color rather than a status meaning, with no documented naming convention behind `b-blue` and no link to the status system defined later in §22. `badge-info`/`badge-success`/`badge-warning`/`badge-danger`/`badge-neutral` (one modifier per §22 category) replaces it — see §22 for the full mapping table.

CSS pattern:

```css
.badge::before {
  content: "";
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}
```

The status system (full token mapping in §22) is:

```text
badge-info     (blue)   → active / in progress
badge-success  (green)  → completed / ready
badge-warning  (yellow) → waiting / caution
badge-neutral  (grey)   → neutral / received
badge-danger   (red)    → failed / destructive
```

---

## 9.3 Table Actions

Rightmost actions use:

```text
three-dots icon
```

Example:

```html
<button
  class="row-menu"
  aria-label="More actions"
>
  <i class="bi bi-three-dots"></i>
</button>
```

Do not display long rows of action buttons inside the table.

---

## 9.4 Pagination

Footer:

```text
left = "Showing 1–4 of 128"
right = compact pager
```

Button target:

```text
24px minimum visual control in dense admin context
```

Accessible clickable target should still provide sufficient hit area through padding.

---

## 9.5 Mobile Row-Card Transformation

Breakpoint:

```text
< 640px
```

The desktop table is not horizontally scrolled.

It transforms into:

```text
stacked accordion rows
```

Collapsed:

```text
primary title
status badge
chevron
```

Expanded:

```text
Customer
Technician
Cost
Updated
```

with:

```text
dashed separators
```

followed by:

```text
Edit
Delete
```

---

## 9.6 Mobile Card Interaction

Collapsed row:

```text
tap → open
```

Chevron:

```text
downward when closed
rotated 180deg when open
```

Only one row may be automatically opened at a time if the component is configured as a single-open accordion. Multi-open behavior is permitted only when a task benefits from comparison.

---

# 10. Form Design, Input Anatomy & Multi-Tier Validation

## 10.1 Core Rule

Forms are **not** wrapped in decorative cards.

The form belongs directly to the page surface.

Use:

```text
section heading
helper copy
fields
actions
```

without an elevated parent.

---

# 10.2 Admin Form Anatomy

```text
Label
Input
Validation/helper row
```

Input:

```text
white fill
1px strong border
4–6px radius
```

Focus:

```text
outline: 2px solid --a-focus
border-color: --a-blue
```

Desktop width:

```text
maximum 640px
```

Two columns:

```text
1fr 1fr
```

Mobile:

```text
1fr
```

---

# 10.3 Customer Form Anatomy

Customer inputs use Material-style underline treatment.

```text
transparent fill
no surrounding card
2px bottom border
```

Default:

```text
border-bottom: 2px solid --c-input-border
```

Focused:

```text
border-bottom-color: --c-blue
```

Label uses:

```text
Inter
12px-ish
secondary color
```

Input text:

```text
Inter
13–14px
```

---

# 10.4 Validation State Hierarchy

## Neutral

```text
neutral label
neutral border
no message
```

## Error

```text
red border
red message
error icon
```

Example:

```html
<div class="field error">
  <label>Amount</label>
  <input ... />
  <div class="err-msg">
    <i class="bi bi-exclamation-circle"></i>
    Enter a valid amount
  </div>
</div>
```

## Success (field copy may say "Valid" / "Looks good" — see note)

```text
green border
green confirmation text
check icon
```

Example:

```html
<div class="field success">
  <label>Category</label>
  <input ... />
  <div class="ok-msg">
    <i class="bi bi-check-circle"></i>
    Looks good
  </div>
</div>
```

> **Revision 2 fix:** Revision 1 named this state "Valid" here while §22 (Status Tokens) and §23 (State Matrix) both use "Success" for the identical green/check-icon state. The state name is now standardized on **Success** everywhere in this document; "Valid"/"Looks good" remain perfectly fine as the user-facing *copy* inside a success-state field (per §10.6, copy should stay specific and human), but the CSS modifier and any code/design references to the state itself use `success`, matching `field-error` → `field-success` as parallel modifier names, and matching `badge-success` from §9.2/§22.

---

## 10.5 General Server/Mismatch Banner

Use when the error is:

```text
not attributable to one field
```

Examples:

```text
payment session expired
inventory changed while you were editing
session authorization failed
server-side conflict
```

Placement:

```text
above the affected form/field group
```

Appearance:

```text
soft yellow background
warning icon
border
rounded 7–8px admin radius
```

---

# 10.6 Error Copy Rules

Error messages must say:

```text
what went wrong
+
what the user should do
```

Avoid:

```text
Invalid.
Error.
Something happened.
```

Prefer:

```text
Enter a valid 11-digit phone number.
```

---

# 10.7 Disabled State (Inputs)

> **Revision 2 fix:** §23's State Matrix requires every interactive component to define a `disabled` state, but Revision 1 never specified one for inputs anywhere. This closes that gap with concrete, theme-appropriate values.

Admin:

```text
background: --a-hover (#F2F3F6)
border-color: --a-border
text color: --a-text3
cursor: not-allowed
```

Customer:

```text
border-bottom-color: --c-divider
text color: --c-text2
opacity: .6
cursor: not-allowed
```

Rule: a disabled field MUST still render its label at full opacity/emphasis — only the input control and its current value dim. A user should always be able to read *what* the disabled field is, even when they cannot edit it. Do not disable a field silently; if a field is disabled because of a business rule (e.g. "amount cannot be edited after payment is confirmed"), pair it with a short helper line explaining why, using the neutral helper-text pattern from §10.4.

---

# 11. Button Hierarchy, Color Weighting & Loading Micro-Interactions

## 11.1 Button Tiers

### Admin

```text
Primary       = blue solid
Neutral       = white / outlined
Warning       = soft yellow
Danger        = soft red
Danger solid  = red solid
```

### Customer

```text
Primary       = blue pill
Neutral       = light neutral capsule
Warning       = warm yellow soft capsule
Danger        = soft red capsule
```

---

## 11.2 Admin Button Metrics

Prototype:

```text
radius: 6px
font-size: ~12.5px
font-weight: 600
padding: 7px 13px
```

Buttons should be compact but never visually cramped.

---

## 11.3 Customer Button Metrics

Prototype:

```text
radius: 100px
font-size: 12.5px
font-weight: 700
padding: 11px 18px
```

CTA buttons are pill-shaped.

---

## 11.4 Active State

On click:

```text
transform: scale(.98)
```

Duration:

```text
≈ 120ms
```

Do not use large press animations.

---

## 11.5 Loading Micro-Interaction

Every submit button must immediately:

```text
disable
preserve width/height
replace or augment label
show spinner
```

Prototype behavior:

```html
<span class="spin"></span> Please wait…
```

Equivalent React behavior:

```jsx
<button disabled={isSubmitting}>
  {isSubmitting ? (
    <>
      <span className="spin" aria-hidden="true" />
      Please wait…
    </>
  ) : (
    "Save expense"
  )}
</button>
```

Never allow:

```text
double submit
layout jump
button width collapse
```

---

## 11.6 Disabled State (Buttons)

> **Revision 2 fix:** same gap as §10.7, for buttons — required by §23's State Matrix, never specified in Revision 1.

Both themes:

```text
opacity: .5
cursor: not-allowed
pointer-events: none
no hover/active transform
```

Rule: a disabled primary button MUST keep its tier's color (blue), only dimmed via opacity — do not swap it to a grey "neutral" button style. Swapping colors on disable removes the user's ability to recognize "this is still the primary action, just not available yet" (e.g. "Confirm payment" disabled until the amount field validates) versus "this was never the primary action." This distinction matters most in the checkout and payment-confirmation flows, where the primary CTA is frequently disabled pending validation rather than absent.

---

# 12. Feedback Systems: Confirmation Modals, Dual Toasts & Alert Banners

# 12.1 Confirmation Modal Contract

Native browser dialogs are forbidden.

Never:

```js
window.confirm(...)
```

Use the AfProsPos confirmation component.

Triggered by:

```text
all destructive actions
all warning actions that alter user-visible state
```

---

## 12.2 Desktop Confirmation Modal

```text
width: 380px
radius: 14px
centered
```

Anatomy:

```text
status icon
title
description
actions
```

Danger:

```text
danger icon
red action
```

Warning:

```text
warning icon
yellow action
```

---

## 12.3 Mobile Confirmation Sheet

Mobile transforms from modal to bottom sheet.

```text
width: 100%
bottom: 0
radius: 20px 20px 0 0
```

Contains:

```text
drag handle
status icon
title
body
stacked buttons
```

Actions order:

```text
Cancel
Confirm
```

with buttons full width.

Animation:

```text
translateY(100%) → translateY(0)
```

---

# 12.4 Overlay Behavior

Backdrop:

```text
rgba(16,20,30,.44)
```

or customer-specific softer equivalent.

Interaction:

```text
clicking backdrop may close
```

except when:

```text
the operation is unsafe to interrupt
```

Such states must explicitly opt out.

Keyboard:

```text
Escape → close
```

when the overlay is dismissible.

Focus:

```text
trap inside modal
restore focus to trigger on close
```

---

# 12.5 Admin Toast

Admin toast is deliberately utilitarian.

Position:

```text
top-right
```

Structure:

```text
white card
small icon
title/message
4px colored left border
dismissible
```

Shape:

```text
small rectangular
```

not a giant pill.

Auto-dismiss:

```text
≈ 3.2 seconds
```

as demonstrated by the prototype.

---

# 12.6 Customer Toast

Customer toast is deliberately expressive.

Position:

```text
bottom-center
```

Width:

```text
≈ 88%
max ≈ 340px
```

Shape:

```text
100px pill
```

Visual model:

```text
filled gradient background
overlapping circular icon
bold title
smaller message
```

Entrance:

```text
translateY + scale
cubic-bezier spring
```

The prototype uses a bouncy spring-like:

```text
cubic-bezier(.34,1.56,.64,1)
```

This is a product signature and should be preserved.

---

# 12.7 Alert Banners

Banners are for:

```text
persistent or contextual attention
```

not momentary events.

Use:

```text
yellow-soft
red-soft
green-soft
blue-soft
```

with:

```text
icon
message
optional action
```

A banner should be dismissible only when dismissibility makes sense semantically.

---

# 13. Loading States: Shimmer Skeletons vs Frosted Logo Preloader

## 13.1 Skeleton Loading

Use skeletons when:

```text
layout is known
data is loading/reloading
content should remain spatially stable
```

Prototype:

```css
background:
  linear-gradient(
    90deg,
    light
    25%,
    lighter
    37%,
    light
    63%
  );
background-size: 400% 100%;
animation: shimmer 1.3s infinite;
```

Production tokenization:

```css
--skeleton-base
--skeleton-highlight
```

Skeletons should match real content dimensions.

Bad:

```text
one generic giant rectangle
```

Good:

```text
row title skeleton
status skeleton
value skeleton
```

---

## 13.2 Full-Screen Wait

Use when the interface must prevent interaction during a blocking process.

Prototype behavior:

```text
frosted white overlay
backdrop blur = 6px
rotating site mark
status label
```

Structure:

```html
<div class="spinner-overlay">
  <svg class="spin-mark">...</svg>
  <div class="msg">Loading…</div>
</div>
```

---

## 13.3 Site Mark Animation

```text
width ≈ 52px
rotation = continuous
duration ≈ 1s
linear timing
```

The rotating mark is the AfProsPos brand mark, not a generic circular spinner.

Use generic spinners for button-level waits.

Use `spin-mark` for product-level blocking waits.

---

# 14. Responsive Interaction Patterns

## 14.1 Tables

```text
Desktop ≥ 640
    real table

Mobile < 640
    accordion cards
```

## 14.2 Navigation

```text
Admin desktop:
    persistent sidebar

Admin mobile:
    flush off-canvas drawer

Customer desktop:
    persistent floating-style left navigation

Customer mobile:
    floating off-canvas drawer
    +
    bottom taskbar
```

## 14.3 Forms

```text
Desktop:
  2 columns

Mobile:
  1 column
```

## 14.4 Search

Admin global search is a desktop utility.

At mobile widths:

```text
hide global search from topbar
```

Context-specific searches should appear within page content where required.

---

# 14A. Offline & Sync Visual Contract

> **Revision 2 fix:** the ADD dedicates a full architecture section (§24, Offline/PWA Architecture) to controlled offline operation — a client sync lifecycle of `pending → syncing → synced/conflict`, a defined Safe Offline Class (cash sales, repair diagnosis notes, eligible repair-workflow updates) and Unsafe Offline Class (shared inventory reservation, bank-transfer verification, refunds, inter-shop transfers) — and Revision 1 specified zero visual treatment for any of it. This section is new content, not derived from the HTML prototype (the prototype has no offline demo state), and it deliberately reuses the existing status/badge/banner system rather than inventing new visual language.

## 14A.1 Connectivity Indicator

A small, persistent, non-intrusive indicator lives in the Admin topbar (next to the shop switcher) and the Customer shell header. It is not a banner — it should not compete with page content — and it only escalates to a banner when action is actually needed (§14A.3).

```text
Online:   no indicator shown (silence is the default state)
Offline:  small badge-neutral pill — "Offline" + bi-wifi-off icon
Syncing:  small badge-info pill — "Syncing…" + spinner (button-level spinner, not the full-screen frosted loader from §13.2)
```

## 14A.2 Per-Record Sync Status

Any record created or edited while offline (a Safe Offline Class action) carries a small sync-state affordance on its row/card until confirmed synced, reusing the existing badge component from §9.2/§22:

```text
pending  → badge-neutral, label "Pending sync", icon bi-clock-history
syncing  → badge-info,    label "Syncing",      icon bi-arrow-repeat (spinning)
synced   → no badge — a synced record looks identical to a record that was always online
conflict → badge-danger,  label "Sync conflict", icon bi-exclamation-triangle
```

A `conflict` badge MUST be tappable/clickable and MUST route to a conflict-resolution view — it is never purely decorative, because a silent, unresolved sync conflict is a data-integrity risk.

## 14A.3 Unsafe-Offline Affordance

For an Unsafe Offline Class action (per ADD §24.2 — e.g. attempting a bank-transfer verification or an inter-shop transfer while offline), the control initiating that action MUST be disabled (§11.6 Disabled State) while offline, not merely allowed to fail server-side after the fact. Pair the disabled control with a General Server/Mismatch-style banner (§10.5's yellow-soft pattern) explaining why:

```text
"You're offline. Bank-transfer confirmation requires a connection and will
be available again once you're back online."
```

This prevents the worst version of this UX problem: a cashier tapping "Confirm transfer," believing it worked, and only discovering it silently failed on reconnect.

## 14A.4 Conflict Resolution View

Reuses the Confirmation Modal / Bottom Sheet contract from §12, with `kind="warning"` rather than `kind="danger"` (a conflict is not inherently destructive — it's a decision point):

```text
Title:        "This [repair job / expense / …] changed while you were offline"
Body:         side-by-side or stacked comparison — "Your version" vs. "Current version"
Actions:      [Keep server version]  [Apply my version]
```

Never auto-resolve a conflict silently in either direction — per ADD §24.3, conflicts are surfaced for a human decision, not swallowed by a "last write wins" rule.

---

# 14B. Advanced Layout Patterns & UX Enhancement Playbook

> This section is new supplementary content, added on request, beyond what the prototype demonstrates. Every pattern below is built entirely from tokens, components, and states already defined earlier in this document — nothing here introduces a new color, radius, or type value. Treat these as **recommended patterns to reach for**, not mandatory additions to every screen; apply the same restraint implied by §1.2 Principle 5 (one dominant action) and Principle 2 (flatness is intentional) when deciding whether a pattern earns its place on a given screen.

## 14B.1 Master–Detail Split View (Admin, Desktop ≥ 1024px)

For high-frequency workflows where an operator triages many records and drills into one at a time — the repair queue, the payment dispute queue, the inventory list — a full page navigation on every row click is slower than it needs to be. Above `lg` (1024px), offer a two-pane layout instead of (or as a toggle alongside) the standard full-width table:

```text
┌───────────────────────┬─────────────────────────────────────┐
│ Compact row list       │ Detail panel                         │
│ (≈360px, scrollable)   │ (flexes, scrollable independently)   │
│                        │                                       │
│ ○ Row (selected: blue- │  Full record detail, using the same  │
│   soft background,     │  page-head + stats + content pattern │
│   left blue bar)       │  a full detail page would use        │
│ ○ Row                  │                                       │
│ ○ Row                  │                                       │
└───────────────────────┴─────────────────────────────────────┘
```

Rules:

- The list pane keeps the standard row/table typography (§9.1) — it is a denser version of the same table, not a new component.
- Selecting a row updates the URL (Inertia partial reload of the detail pane's props) so the state is shareable/bookmarkable — this must still be a real server-driven navigation per §17, not client-only state standing in for a page.
- Below `lg`, this pattern collapses to the standard full-page list → full-page detail flow (§9.5's row-card model) — do not attempt a two-pane layout on tablet/mobile widths.
- Use for triage-heavy queues (repairs, disputes, sync conflicts). Do not use for simple reference tables (e.g. a static price list) where there's nothing to triage.

## 14B.2 Pipeline / Kanban View (Admin — Repair & Order Status)

The repair and order lifecycle is already a small, fixed set of sequential statuses (§9.2's status system: in progress, awaiting parts, ready for collection, completed). That shape maps naturally onto an optional Kanban board view, offered as a view-toggle alongside the default table (`≡ Table` / `▦ Board`), not a replacement for it:

```text
┌───────────┬───────────┬────────────────┬───────────┐
│In progress│Awaiting   │Ready for       │Completed  │
│(badge-info)│parts     │collection      │(badge-    │
│           │(badge-    │(badge-success) │success)   │
│  [card]   │warning)   │                │           │
│  [card]   │  [card]   │  [card]        │  [card]   │
│  [card]   │           │                │           │
└───────────┴───────────┴────────────────┴───────────┘
```

- Column header color reuses the exact status badge token from §22 — a technician should never have to learn a second color meaning for "awaiting parts" depending on which view they're in.
- Cards show only the mobile-row-card fields already defined in §9.5 (primary title, status badge, 1–2 key details) — do not invent a new card content spec.
- Drag-between-columns, if implemented, MUST still issue the same Command (§2 of the CPNC — e.g. `TransitionRepairStatusCommand`) as clicking the status change action would; a drag is a trigger for the same server-authoritative transition, never a client-only reorder.
- This view is desktop-only (`≥ lg`). Do not attempt a Kanban board on mobile — offer the row-card list instead.

## 14B.3 Bulk-Selection Contextual Toolbar (Admin Tables)

When one or more table rows are checkbox-selected, replace the table's normal toolbar (search/filter) with a contextual action bar, rather than requiring the operator to act on rows one at a time:

```text
[x] 4 selected     [Assign technician]  [Export]  [Mark ready]     [Clear]
```

- Appears in the same position the filter toolbar normally occupies (§14B.4) — it replaces it, it doesn't stack below it, to avoid vertical layout shift.
- Uses `--a-blue-soft` background to distinguish it from the neutral table-head background (§9.1), signaling "this bar is contextual, not permanent."
- Bulk-destructive actions (e.g. "Delete 4 items") still MUST go through the Confirmation Modal contract (§12.1) — bulk selection never bypasses the confirmation requirement, and the confirmation copy should state the count ("Delete 4 repair jobs?").
- Never offer a bulk action for an Unsafe Offline Class operation (§14A.3) without the same offline-disabled treatment applied per-row.

## 14B.4 Filter & Active-Filter Chips (Admin Tables)

Standardize where filtering lives so every table in the product behaves the same way:

```text
[ Search box ]  [Status ▾]  [Technician ▾]  [Date range ▾]         [+ Add filter]
Active: (Status: Awaiting parts ×)  (Technician: Chidi ×)          [Clear all]
```

- Filter controls sit directly above the table, inside the same content padding as the table itself (§5.3) — not in a separate sidebar or modal, so the current filter state is always visible, not hidden behind a click.
- Each active filter renders as a small dismissible chip (`--a-radius-badge`, 4px, matching §6.2) directly below the filter row — a filter that is "on" but invisible in the UI is a common source of "why is this table empty" support tickets.
- "Clear all" only appears once at least one filter chip is active.

## 14B.5 Slide-Over Quick-Edit Panel (Both Themes)

For a small edit that doesn't warrant leaving the current screen (adjusting a customer's phone number mid-checkout, editing a single expense line), use a right-edge slide-over panel instead of full-page navigation:

```text
Admin:    width 420px, --a-surface background, 1px --a-border left edge, standard §10.2 form anatomy inside
Customer: width 88% (max 380px), --c-radius-drawer left edge, standard §10.3 underline form anatomy inside
```

- Reuses the Drawer motion/backdrop contract from §8.6/§12.4 (translateX entrance, dismissible backdrop, Escape to close, focus trap) — this is structurally a Drawer, not a new overlay primitive.
- On submit, it behaves like any other form (§2 of the CQRS flow in the CPNC): Command → redirect/Inertia partial reload → panel closes and the underlying page reflects the change. It never optimistically closes before the server confirms.
- Reserve this pattern for edits genuinely small enough to complete in under ~4 fields. A multi-section edit (e.g. full repair job re-intake) still belongs on its own page — a slide-over that requires scrolling past several sections has become a page wearing a drawer's clothes.

## 14B.6 Customer Progress Stepper (Customer, Repair/Order Tracking)

The customer portal's core value proposition is tracking a repair or order. A horizontal (desktop) / vertical (mobile) stepper communicates "where am I in this process" far faster than a status badge alone:

```text
Desktop:
  (●)────(●)────(○)────(○)
 Received  Diag-   In      Ready
           nosed   progress

Mobile (vertical):
  ● Received         — 3 Sep, 9:02am
  │
  ● Diagnosed        — 3 Sep, 11:40am
  │
  ○ In progress
  │
  ○ Ready for collection
```

- Completed steps: filled circle, `--c-blue`. Current step: filled circle with a soft pulse/ring using `--c-blue-soft`, per §19's motion-communicates-state principle — not a static highlight. Future steps: `--c-muted-icon` outline only.
- Timestamps next to completed steps (mobile view above) reuse the monospace stack (§4.2) if a precise time is shown, since precision/scannability is exactly what that stack exists for.
- This is a read-only status display, not a form — do not make steps clickable/navigable unless a step genuinely has drill-down detail worth a tap (e.g. tapping "Diagnosed" to read the technician's diagnosis note).
- Pair with the Offline/Sync affordances from §14A when the underlying status was set via an offline-created update still in `pending`/`syncing` state — show the step as reached, with the small sync badge from §14A.2 next to its timestamp rather than withholding the step until sync completes.

## 14B.7 Activity Timeline (Both Themes — Audit / History Views)

Any record with a meaningful history (a repair job's status changes, an audit log entry, a payment's state transitions) benefits from a vertical timeline rather than a raw chronological table:

```text
● Payment confirmed                    Admin: Chinedu · 2 Sep, 4:12pm
│  ₦45,000 via Paystack
│
● Payment initiated                    Customer · 2 Sep, 4:10pm
│  ₦45,000 via card
```

- Each entry: status-colored dot (reusing §22's mapping — a completion-type event uses `badge-success`'s color, a failure uses `badge-danger`'s, a neutral log entry uses `badge-neutral`'s), a business-fact description in the entry title (matching the Event-naming spirit from the CPNC — "Payment confirmed," not "Status changed to confirmed"), and an actor + timestamp on the same line, right-aligned on desktop.
- This is the natural UI surface for the domain Events defined in the CPNC (§3.3) — each timeline entry corresponds 1:1 to a past-tense domain event (`PaymentConfirmed`, `RepairCompleted`) rather than to a raw audit-log column diff. If a timeline entry can't be phrased as a clean business-fact sentence, that's a signal the underlying event naming needs revisiting, not that the timeline should show raw field diffs.
- Long timelines (10+ entries) should paginate or "Show more" rather than render unbounded — an audit trail for a shop with years of history should not become a scroll-forever page.

## 14B.8 Dashboard Widget Grid (Admin)

Formalizes stat-card + chart dashboard layouts so every dashboard in the product (shop overview, sales dashboard, commission dashboard) shares one grid rather than each page inventing its own:

```text
12-column grid, --space-4 (16px) gutter

Stat card   = spans 3 columns  (4 per row on desktop, 2 per row on tablet, 1 per row on mobile)
Chart card  = spans 6 or 12 columns
List/table card = spans 12 columns, placed below stat cards and charts
```

- Stat cards are flat/border-driven per Admin's elevation rule (§6.1) — a dashboard is not an exception to "flat by default."
- A stat card's primary number uses `--text-2xl`/`--font-bold` (§4.4/§4.3); its label uses `--text-sm`/`--a-text2` — do not let the number and label compete for the same visual weight.
- Charts consume the same status/semantic color tokens as everything else (a "completed repairs" trend line uses `--a-green`, not an arbitrary chart-library default palette) — a chart's colors are still subject to the Zero Raw Value Rule (§2.2).

---

# 14C. Page-Level Layout Specifications

> **Revision 3 addition.** Every earlier section defines the *system* — tokens, components, states, responsive rules. This section defines how that system composes into specific, named product screens. Nothing below introduces a new color, radius, spacing, or motion value; every layout is built entirely from components and tokens already defined in §2–§14B. File paths reference the ADD §5A Frontend Page, Component & Feature File Structure — the two documents describe the same pages and must be read together. Each spec covers: purpose, layout regions, the component/feature manifest, states (§23), and responsive behavior (§14).

## 14C.1 Admin Staff Login

**File:** `Pages/Admin/Auth/Login.tsx` · **Shell:** `AdminAuthShell` (§8.8)

**Purpose:** authenticate staff before any shop-scoped data is exposed. Nothing on this page is shop-scoped — there is no shop switcher, no navigation, no notification bell.

```text
Desktop (≥ 640px) and Mobile — identical single-column layout, centered:

┌──────────────────────────────────────┐
│                                        │  bg-admin-bg (--a-bg), min-h-screen
│              [ AfProsPos logo ]        │
│                                        │
│              Sign in                   │  --text-xl, bold — no panel, no border,
│              (--text-xl, bold)         │  no fill, no shadow (see §8.8 Revision 4 fix)
│                                        │  content max-width ~380px
│              Email                     │
│              [___________]            │
│                                        │
│              Password                  │
│              [___________]            │
│                                        │
│              [ ] Remember me           │
│                                        │
│              [   Sign in    ]         │  AdminButton, tone=primary, full width
│                                        │
│              Forgot password?          │  text link, --a-blue
│                                        │
└──────────────────────────────────────┘
```

**Component manifest:** `AdminAuthShell`, `AdminInput` × 2, `AdminButton` (primary, full-width), `FormField`/`FormErrorMessage` (§10.4), a plain text `Link` for "Forgot password?". No `AdminSidebar`, `AdminTopbar`, or `ConnectivityIndicator` — those belong to the authenticated shell only.

**States:**
- **Default** — empty fields, no validation shown.
- **Field error** — per-field red border + message, §10.4 pattern (e.g. "Enter your email address").
- **Submit error (general)** — §10.5 General Server/Mismatch banner above the form, e.g. "Incorrect email or password." Do not reveal whether the email exists (standard auth-enumeration hygiene) — the copy is identical whether the email or the password was wrong.
- **Loading** — the Sign in button enters its §11.5 loading micro-interaction (`disabled`, spinner, "Please wait…") immediately on submit; the rest of the form is not disabled (a user should still be able to see what they typed).
- **Deactivated account** — a staff member deactivated per ADD §1 must not reach the dashboard even with correct credentials; render the same general submit-error banner rather than a distinct "deactivated" message that would leak account-status information to an attacker.

**Responsive:** this page does not transform at 640px — it is single-column at every width by design, since a login form never needs the two-column grid (§10.2) or a data-table transformation.

## 14C.2 Customer Login / Register

**Files:** `Pages/Customer/Auth/Login.tsx`, `Pages/Customer/Auth/Register.tsx` · **Shell:** `CustomerAuthShell` (§8.8)

```text
┌──────────────────────────────────────┐
│  [ logo ]  AfProsPos                   │  minimal top header, bg-white
│                                        │
│                                        │
│     Welcome back                       │  --font-customer-display, --text-3xl
│     Track your repairs and orders      │  --c-text2, --text-md
│                                        │
│     Phone number or email              │  underline field (§10.3)
│     ──────────────────────             │
│                                        │
│     Password                           │
│     ──────────────────────             │
│                                        │
│     (  Sign in  )                      │  CustomerButton, pill, full width
│                                        │
│     New here? Create an account →      │  link to Register.tsx
│                                        │
└──────────────────────────────────────┘
```

**Component manifest:** `CustomerAuthShell`, `CustomerInput` (underline model, §10.3), `CustomerButton` (primary, pill). No `CustomerDrawer`, `CustomerTaskbar`, or `CustomerFab` — unauthenticated users get no navigation chrome per §8.8.

**Register.tsx** repeats the same shell with the additional fields the BRD requires for customer identity (name, phone, email, password + confirmation) in the same underline form anatomy, still single column, still no card. Both pages sit directly on `bg-white`, per §8.8 — never `--c-gradient-page`; the gradient page background is reserved for the authenticated Customer shell (§8.4) so a returning customer visually recognizes "I am now inside my account" the moment they land on the dashboard after login.

**States:** identical pattern to §14C.1 (field error, general submit-error banner, loading button) but using Customer error copy tone (§21 — friendly, specific) and the Customer field-error visual (§10.4's red underline, not a red box border).

## 14C.3 Admin POS Checkout (Sales)

**File:** `Pages/Admin/Sales/Checkout.tsx` · **Shell:** `AdminShell` · **Feature components:** `Features/Sales/{POSCart,ProductSearch,CheckoutTotals,CheckoutExpiryTimer}.tsx`

**Purpose:** the highest-frequency, highest-stakes Admin screen — a cashier builds a cart against live, reserved inventory (ADD §15) under a real expiry clock. This page justifies the heaviest use of interactive React state in the product (ADD §25.2) precisely because it is the one screen that is not simple CRUD.

```text
Desktop (≥ 1024px) — two-column working layout:

┌─────────────────────────────────────────────────────────────────┐
│ AdminTopbar (shop switcher fixed to the shop this checkout        │
│              belongs to — cannot be changed mid-checkout)         │
├─────────────────┬───────────────────────────────────────────────┤
│ ProductSearch     │  POSCart                                       │
│ (left, ~40%)      │  (right, ~60%)                                 │
│                   │                                                 │
│ [ search box   ]  │  Customer: [ walk-in ▾ ] or [ lookup ]          │
│                   │  ──────────────────────────────────────         │
│ ○ Product result   │  1× iPhone 12 (IMEI 3591...)     ₦185,000     │
│   [+ Add]          │  2× Screen protector               ₦4,000     │
│ ○ Product result   │  ──────────────────────────────────────         │
│   [+ Add]          │  Subtotal                        ₦189,000     │
│                   │  Discount                          -₦5,000     │
│  (serialized       │  ──────────────────────────────────────         │
│   products open    │  Total                           ₦184,000     │
│   SerializedUnit-   │                                                 │
│   Picker on add)   │  Payment method: [ Cash ▾ ]                     │
│                   │  CheckoutExpiryTimer: 14:32 remaining           │
│                   │                                                 │
│                   │  [        Complete sale        ]  primary CTA  │
└─────────────────┴───────────────────────────────────────────────┘
```

- `CheckoutExpiryTimer` is a live countdown reflecting the server-issued checkout expiry (ADD §15/§16) — it is a *display* of server truth, not a client-owned timer; on expiry it disables the cart and shows the §10.5 general banner ("This checkout expired — start a new one"), it does not silently keep accepting input past the server's deadline.
- Adding a serialized (IMEI-tracked) product opens `SerializedUnitPicker` (ADD §5A.4) as a modal/slide-over (§14B.5) rather than navigating away from the cart — leaving this page loses cart-building context.
- The primary CTA is singular per Principle 5 (§1.2) — "Complete sale" is the one dominant action; "Cancel checkout" is a Neutral-tier button, visually subordinate, placed away from the primary CTA so a mis-tap cannot cancel an in-progress sale.
- `CheckoutTotals` renders money via the monospace stack (§4.2) for scannability, consistent with §4.2's currency-figure guidance.

**States:**
- **Empty cart** — `AdminEmptyState` ("Search or scan a product to begin") in the cart pane; "Complete sale" is disabled (§11.6) until at least one line item exists.
- **Insufficient stock** — attempting to add more than `Available` (ADD §15.4) shows an inline error on that product's search result, not a page-level banner — the failure is local to one product, not the whole cart.
- **Reservation conflict** (another cashier took the last unit between search and add) — §10.5 general banner: "That item was just reserved by another sale — showing updated availability," and the product list re-queries. This must read the server's rejection, never assume success and roll back client-side.
- **Submitting** — "Complete sale" enters the §11.5 loading state; the entire cart becomes read-only (`pointer-events: none` per §11.6) to prevent a double-submit racing the same reservation the button is committing.
- **Offline** — per §14A.3, "Complete sale" is disabled with the offline explanation banner if payment method requires online verification (card/in-app); a configured offline-safe cash sale (ADD §24.1) remains available and queues per §14A.2.

**Responsive (< 1024px):** the two-column layout collapses to a single column — `ProductSearch` becomes a full-width search-then-results flow, and adding a product transitions to the `POSCart` view (a tab/step switch, not a page navigation) rather than trying to show both panes at once. This is a `< lg` collapse in addition to the general `< 640px` mobile model, because a POS cart genuinely needs more room than a typical table before its two-pane layout still works.

## 14C.4 Admin Repair Intake & Diagnosis

**Files:** `Pages/Admin/Repairs/Create.tsx` (intake), `Pages/Admin/Repairs/Diagnosis.tsx` · **Feature:** `Features/Repairs/DiagnosisChecklist.tsx`

**Intake (`Create.tsx`)** uses the standard Admin form anatomy (§10.2) directly on the page surface — no card, two-column desktop grid, one column mobile:

```text
Section: Customer
  [ Customer lookup / walk-in ]   [ Phone number ]

Section: Device
  [ Device type ▾ ]               [ Brand ▾ ]
  [ Model ]                       [ IMEI / serial ]
  [ Reported issue (textarea, full width) ]

  [        Create repair job        ]   primary CTA
```

**Diagnosis (`Diagnosis.tsx`)** is not a form in the CRUD sense — it is a structured checklist matching the domain model in ADD §17.2 (per-component Working/Faulty/Not Tested/Unable to Test), rendered by `DiagnosisChecklist`:

```text
┌───────────────────────────────────────────────┐
│ Repair #RJ-00412 — iPhone 12, IMEI 3591...       │  AdminPageHead
│ badge-info "Diagnosing"                          │
├───────────────────────────────────────────────┤
│ Component        Working  Faulty  Not tested  Unable │
│ Screen              ○       ●        ○          ○    │
│ Battery             ○       ○        ●          ○    │
│ Face ID             ○       ○        ○          ●    │
│ Camera               ●       ○        ○          ○    │
│                                                   │
│ Diagnosis notes                                    │
│ [_______________________________________________]  │
│                                                   │
│ Outcome:  ( Repairable )  ( Unrepairable )  ( Further assessment ) │
│                                                   │
│ Estimated cost: [ ₦______ ]   (shown to customer)  │
│                                                   │
│ [   Save diagnosis   ]                             │
└───────────────────────────────────────────────┘
```

- Each component row is a segmented control, not four separate checkboxes — exactly one state per component is selectable at a time, matching the domain model's per-component enum (ADD §17.2) rather than allowing an invalid multi-select state to exist in the UI at all.
- "Outcome" is a required single choice before "Save diagnosis" is enabled — this is not optional/inferred from the component grid, because ADD §17.2 treats it as an explicit final decision, not a derived value.
- This page does **not** let the technician reserve parts — per ADD §17.3, parts reservation is a separate action gated on repairability + authorization, and appears only after diagnosis is saved and the job reaches the appropriate state (see §14C.5).

**States:** `Skeleton` while the repair job loads; a saved-but-unsynced diagnosis while offline shows the §14A.2 sync badge on the page head rather than blocking the technician from continuing to work.

## 14C.5 Admin Repair Workflow / Detail Page

**File:** `Pages/Admin/Repairs/Show.tsx` · **Feature:** `Features/Repairs/{PartsReservationPicker,RepairStatusStepper}.tsx`

This is the technician's and the accountant's single source of truth for one repair job across its whole lifecycle (ADD §17.1: Received → Diagnosing → Awaiting Authorization → In Progress → Completed/Failed → Ready for Collection → Collected). It is intentionally one page, not a page per state, because the value of this screen is seeing the whole job at once.

```text
┌───────────────────────────────────────────────────────┐
│ Repair #RJ-00412 — iPhone 12          badge-info "In progress" │
│ Customer: Amaka O. · Technician: Chidi                    │
├───────────────────────────────────────────────────────┤
│ RepairStatusStepper (§14B.6 pattern, Admin-styled)         │
│ Received ● ─ Diagnosed ● ─ Authorized ● ─ In progress ○ ─ Ready │
├───────────────────────────────────────────────────────┤
│ Financial status         │  Parts                          │
│ Down payment: ₦20,000     │  PartsReservationPicker           │
│ Balance due: ₦45,000      │   ✓ Screen assembly (reserved)   │
│ badge-warning "Balance     │   ✓ Battery (reserved)           │
│  due before release"       │                                 │
├───────────────────────────────────────────────────────┤
│ ActivityTimeline (§14B.7) — one entry per domain event:      │
│  ● Repair diagnosed         Chidi · 3 Sep, 11:40am           │
│  ● Down payment confirmed   Customer · 3 Sep, 11:52am        │
│  ● Parts reserved           Chidi · 3 Sep, 12:05pm           │
├───────────────────────────────────────────────────────┤
│ [ Mark completed ]  [ Mark failed ]  [ Release device ]       │
└───────────────────────────────────────────────────────┘
```

- Action buttons at the bottom are contextual to the current state — e.g. "Release device" renders `disabled` (§11.6) with an inline helper explaining *why* ("Outstanding balance of ₦45,000") whenever `hasOutstandingBalance()` is true, per ADD §17.1/CPNC boolean-method naming, rather than being hidden — hiding it would make the release rule invisible instead of just unavailable, and staff would not learn a device is release-blocked until they went looking for a button that isn't there.
- "Release device" with an outstanding balance is never silently allowed from this page — an authorized override (ADD §17.1) is a separate, explicitly audited action reached through its own Confirmation Modal (§12.1) with `kind="warning"`, requiring a typed reason, never a same-click bypass of the disabled state.
- The Parts panel reuses `PartsReservationPicker`, which itself calls the same Inventory reservation contract as the POS cart's `SerializedUnitPicker` (ADD §15) — this page does not reimplement reservation UI from scratch.

**States:** `Skeleton` on initial load (matching the region layout, per §13.1 — not one giant rectangle); the Parts panel shows a job-blocking-shortage banner (§10.5-style, yellow-soft) distinct from the general low-stock alert, per the Implementation Plan's Phase 6 exit criteria, when a required part is unavailable.

## 14C.6 Customer Repair Tracker

**File:** `Pages/Customer/Repairs/Show.tsx` · **Feature:** reuses `RepairStatusStepper` in its Customer variant (§14B.6)

```text
┌──────────────────────────────────────┐
│ CustomerShell (drawer/taskbar per device) │
├──────────────────────────────────────┤
│  iPhone 12 repair                       │  --text-3xl, --font-customer-display
│  Reported: Cracked screen                │
│                                        │
│  ● Received         3 Sep, 9:02am        │  vertical stepper, mobile
│  │                                     │
│  ● Diagnosed        3 Sep, 11:40am       │
│  │  "Screen and battery need replacing"  │  technician-visible diagnosis note
│  │                                     │
│  ◉ In progress                          │  current step, pulsing ring (§14B.6)
│  │                                     │
│  ○ Ready for collection                  │
│                                        │
│  ┌─────────────────────────────┐      │  CustomerHeroCard, shadow: --c-shadow-soft
│  │ Balance due: ₦45,000          │      │
│  │ (  Pay now  )                 │      │  primary pill CTA → Pages/Customer/Payments/Show.tsx
│  └─────────────────────────────┘      │
└──────────────────────────────────────┘
```

- This page is read-only status display (§14B.6) — the customer cannot edit diagnosis or status here; the one interactive element is the "Pay now" CTA, which is the single dominant action per Principle 5, and it is the only place this page asks the customer to do anything.
- The balance-due card is a `CustomerHeroCard` (§8.4/§14B), the one intentional elevated Customer surface for this page — this is correct per the Customer theme's floating-surface philosophy (§6.1) and is not the same rule as Admin (§8.8's fix applies to Admin only; Customer forms and hero cards are allowed shadows by design).
- If diagnosis is still pending, the "Diagnosed" step renders as the current (pulsing) step with no note yet, rather than being hidden — a customer should always see where their repair currently stands, never a stepper that jumps steps.

## 14C.7 Customer Payment Page

**File:** `Pages/Customer/Payments/Show.tsx` · **Feature:** `Features/Payments/{PaymentMethodSwitcher,PaymentStatusBadge,BankTransferProofUpload}.tsx`

```text
┌──────────────────────────────────────┐
│  Pay for repair #RJ-00412                │
│  Amount due: ₦45,000                     │  --text-2xl, monospace amount
│                                        │
│  How would you like to pay?              │
│  ( Card / in-app )  ( Bank transfer )  ( POS terminal — in store ) │  PaymentMethodSwitcher
│                                        │
│  [ selected method's fields/instructions ]│
│                                        │
│  PaymentStatusBadge: "Waiting for payment" │  reflects ADD §45's state model exactly
│                                        │
│  (   Pay ₦45,000   )                      │  primary pill CTA
└──────────────────────────────────────┘
```

- `PaymentStatusBadge` renders literally the backend `PaymentStatus` values from ADD §45 — `pending` → "Waiting for payment," `payment_pending_confirmation` → "Transfer received — awaiting confirmation," `confirmed` → "Payment confirmed," `disputed` → "Payment under review," `exception` → "Payment requires assistance." This page must never display "Paid" for `payment_pending_confirmation`, per ADD §45's explicit prohibition.
- Selecting "Bank transfer" reveals `BankTransferProofUpload` and switches the primary CTA to "I've made the transfer" rather than "Pay ₦45,000" — submitting does not mark the payment paid; it transitions it to `payment_pending_confirmation` per ADD §12, and the status badge updates to reflect that, not to "confirmed."
- A card/in-app payment redirect (Paystack et al.) leaves this page entirely for the provider's hosted flow and returns to this same page afterward; per ADD §14.2 and §25.3, the returned page must re-fetch and display server-confirmed status — it must never render "confirmed" purely because the browser came back from a "success" redirect URL.
- This page is the canonical location for the frontend rule in ADD §45 — if a future page needs to show payment status, it links here rather than re-implementing a status display.

## 14C.8 Admin Inventory — List & Detail

**Files:** `Pages/Admin/Inventory/Products/{Index,Show}.tsx`

`Index.tsx` is the standard Admin responsive data pattern (§9) with no deviation — `AdminFilterBar` (§14B.4) above `AdminTable`, transforming to row-cards below 640px (§9.5). Above `lg`, offer the Master–Detail Split (§14B.1) as a view toggle, since triaging a large catalog benefits from it exactly as the pattern describes.

`Show.tsx` (product detail):

```text
┌───────────────────────────────────────────────┐
│ iPhone 12 128GB Black             badge-success "In stock" │
├───────────────────────────────────────────────┤
│ AdminStatCard row: On hand │ Reserved │ Available │ Low-stock threshold │
├───────────────────────────────────────────────┤
│ SKU: SKU-IP12-128-BLK        (monospace)          │
│ Cost price: ₦150,000 · Markup: 22% · Selling: ₦183,000 │
├───────────────────────────────────────────────┤
│ Serialized units (if applicable)                    │
│  IMEI 3591...  status: reserved (checkout #4821)      │
│  IMEI 3592...  status: available                     │
├───────────────────────────────────────────────┤
│ ActivityTimeline — stock movements (received, sold, adjusted, transferred) │
└───────────────────────────────────────────────┘
```

`Available` is always rendered as the computed value the server returns (`on_hand - reserved`, ADD §15.4) — this page never computes it client-side from `on_hand` and `reserved` independently, so a display bug here can never disagree with the value the reservation engine actually enforces.

## 14C.9 Admin Dashboard

**File:** `Pages/Admin/Dashboard/Index.tsx`

Uses the Dashboard Widget Grid exactly as specified in §14B.8 — stat cards (Open repairs, Today's sales, Pending disputes, Low-stock items) spanning 3 columns each, one or two chart cards below, a "Needs attention" list/table card spanning full width surfacing anything requiring action across modules (disputed payments, awaiting-parts jobs, sync conflicts) rather than requiring the owner to check five separate pages. No new widget shape is introduced here beyond §14B.8's grid — this page is a composition, not a new pattern.

## 14C.10 Customer Dashboard / Home

**File:** `Pages/Customer/Dashboard/Index.tsx` · **Shell:** `CustomerShell` (§8.4/§8.5/§8.6)

> **Revision 5 fix:** this section previously diagrammed only the greeting/hero/orders column and omitted the shell (sidebar), a profile panel, and the notification surfaces the source prototype's `#customer-app` screen actually combines onto this page. See the Revision 5 changelog above for the full trace.
>
> **Revision 6 fix:** Revision 5 placed an *editable* Edit Profile form directly on this page inside a shadowed white card — recreating, on the Customer side, the exact "form inside a decorative elevated card" defect Revision 3/4 fixed for the Admin login form (Invariant #19, §10.1). The fix is not to strip the shadow and leave a bare form awkwardly sitting in a two-column data grid — it's to give profile editing its own page (`Pages/Customer/Profile/Show.tsx`, unelevated per §10.1, same treatment as the auth forms), matching the ADD's file tree. This page's side column instead shows a read-only **Account** summary card (name/phone/email, real data, no form) linking to the Profile page — since it isn't a form, it can be elevated without contradiction.

```text
Desktop (>= 640px) — sidebar and main pane are each independently rounded,
shadowed floating panels (§8.4) on a plain white page background, not one
flush rectangle. Both carry the page gradient:

┌─────────────┬──────────────────────────────────────────────┐
│ AfProsPos    │  [avatar] Hi, Amaka            [notif. bell ▾]│  §8.5 sidebar +
│              │                                                │  topbar
│ ● Home       │  ┌ Main column (1.3fr) ─────┐ ┌ Side (0.9fr)─┐│  §5.5 grid ratio
│ ○ Repairs    │  │ CustomerHeroCard          │ │ Account      ││
│ ○ Orders     │  │ (active-repair state, OR  │ │  name/phone/ ││
│ ○ Shops      │  │  §24 empty state if none, │ │  email       ││
│ ● Profile    │  │  same blue treatment      │ │  (Manage     ││
│              │  │  either way)               │ │   profile →)││
│              │  │ Quick actions (4-across):  │ │              ││
│              │  │  Request a repair          │ │ Notifications││
│              │  │  Track a repair            │ │  (§24 empty  ││
│              │  │  Find a shop                │ │   state until││
│              │  │  Refer a friend             │ │   real data) ││
│              │  │ Order history              │ └──────────────┘│
│              │  │  CustomerListItem × N, or   │                 │
│              │  │  §24 empty state if none   │                 │
│              │  └────────────────────────────┘                │
│ ⏻ Log out    │  [FAB: request a repair]                       │
└─────────────┴──────────────────────────────────────────────┘

Mobile (< 640px): sidebar becomes the off-canvas drawer (§8.6); the two
columns above collapse to one (§5.5); the FAB is replaced by the taskbar's
center action (§8.7).
```

**Component manifest:** `CustomerShell`, `CustomerHeroCard`, `CustomerListItem` × N, `CustomerEmptyState` (order history, notifications), an Account summary card (read-only — name/phone/email, a "Manage profile" link, no form), a header notification-bell dropdown, `CustomerFab`. `CustomerInput`/`CustomerButton` are used on the separate Profile page (§14C.10a below), not here.

**Data contract:** this page is data-driven — nothing on it is a hardcoded sample value standing in for real data, including the prototype's own hardcoded "Aisha Bello" / "iPhone 13 Pro" / sample order, none of which are product requirements (§34). Every section backed by data that doesn't exist yet renders the real §24 empty state rather than a fabricated row:

- **Hero card** — summarizes at most the customer's single most-relevant open item (an in-progress repair, an unpaid balance) — per Principle 5, this page does not compete for attention with multiple hero cards. With no active repair, it renders the §24 empty state inviting the customer to book one, not omitted outright, since the page should never look broken/incomplete — and keeps the same blue gradient treatment as the active-repair state rather than switching to a white/bordered look, so the hero card reads consistently regardless of data state.
- **Account summary** is the one card with real data available today — the customer's own registered name/phone/email — displayed read-only with a link to the Profile page where it's actually edited.
- **Order history** and **Notifications** (both the side-panel section and the header dropdown) render `CustomerEmptyState` until Sales/Orders and Notifications data exists for the customer portal.
- **Quick actions** (request a repair, track a repair, find a shop, refer a friend) surface the customer-facing feature set the sidebar's disabled nav items point at, so the dashboard previews the product's shape even before those flows are built — each renders inert/"Soon" rather than linking to a page that doesn't exist yet, consistent with the disabled-nav-item pattern already used elsewhere (e.g. AdminShell's "Shops (Soon)").
- **Notification bell** — interactive (opens a dropdown), not decorative-only as in the static prototype; reuses the empty-state pattern above until real notifications exist.

## 14C.10a Customer Profile

**File:** `Pages/Customer/Profile/Show.tsx` · **Shell:** `CustomerShell` (§8.4/§8.5/§8.6)

The customer's own name/phone/email, editable directly on the page — no card, no border, no shadow (§10.1's Core Rule, same as every other form in the product). A page heading, a one-line description, then the field stack (`CustomerInput` × 3) and `CustomerButton` Save/Cancel. Linked from the sidebar's "Profile" item and from the Dashboard's Account summary card's "Manage profile" link.

---

# 15. Component Architecture

Recommended React component hierarchy:

```text
resources/js/
├── Components/
│   ├── Admin/
│   │   ├── AdminButton.tsx
│   │   ├── AdminInput.tsx
│   │   ├── AdminBadge.tsx
│   │   ├── AdminTable.tsx
│   │   ├── AdminRowCard.tsx
│   │   ├── AdminSidebar.tsx
│   │   └── AdminTopbar.tsx
│   ├── Customer/
│   │   ├── CustomerButton.tsx
│   │   ├── CustomerInput.tsx
│   │   ├── CustomerListItem.tsx
│   │   ├── CustomerDrawer.tsx
│   │   ├── CustomerTaskbar.tsx
│   │   └── CustomerHeroCard.tsx
│   ├── Feedback/
│   │   ├── ConfirmDialog.tsx
│   │   ├── Toast.tsx
│   │   ├── AlertBanner.tsx
│   │   ├── Skeleton.tsx
│   │   └── BlockingLoader.tsx
│   ├── Icons/
│   ├── Forms/
│   ├── Tables/
│   └── Layout/
└── Features/
    └── <Module>/        (domain-specific: POSCart, SerializedUnitPicker, etc. — see CPNC §5.2)
```

> **Revision 2 fix:** Revision 1 nested everything under an extra `Components/DesignSystem/` layer. That wrapper was redundant — the CPNC's Frontend Constraints (§5.2) already define `Components/` as, by definition, the location for centralized, reusable design-system elements, and it doesn't nest a `DesignSystem/` layer inside `Components/`. The two documents described the same tree with one extra folder of disagreement; this revision drops the wrapper so both documents now describe the identical structure, and adds `Features/` here for completeness since design-system components and domain-specific Feature components are the two halves of the same picture.

The exact framework-level path can vary, but design-system components must be centralized and reused — see the CPNC's §1 (Zero Dead Code & File Reuse Directive) for the reuse-before-invent discovery process that applies equally to a new `Components/` file as it does to a new backend class.

---

# 16. Tailwind CSS Mapping Strategy

Tailwind must consume the token layer rather than duplicating visual values.

Example:

```js
// tailwind.config.js
export default {
  theme: {
    extend: {
      colors: {
        admin: {
          bg: "var(--a-bg)",
          surface: "var(--a-surface)",
          border: "var(--a-border)",
          border2: "var(--a-border2)",
          text: "var(--a-text)",
          text2: "var(--a-text2)",
          text3: "var(--a-text3)",
          blue: "var(--a-blue)",
          "blue-soft": "var(--a-blue-soft)",
          yellow: "var(--a-yellow)",
          "yellow-soft": "var(--a-yellow-soft)",
          red: "var(--a-red)",
          "red-soft": "var(--a-red-soft)",
          green: "var(--a-green)",
          "green-soft": "var(--a-green-soft)",
        },
        customer: {
          blue: "var(--c-blue)",
          "blue-soft": "var(--c-blue-soft)",
          yellow: "var(--c-yellow)",
          "yellow-soft": "var(--c-yellow-soft)",
          red: "var(--c-red)",
          "red-soft": "var(--c-red-soft)",
          green: "var(--c-green)",
          "green-soft": "var(--c-green-soft)",
          text: "var(--c-text)",
          text2: "var(--c-text2)",
        },
      },
    },
  },
};
```

This allows:

```jsx
className="bg-admin-surface border-admin-border"
```

without introducing hex values into components.

---

# 17. Inertia.js + React Interaction Contract

The UI design system assumes server-driven page navigation through Inertia.js and local React state for highly interactive controls.

## Use Inertia for

```text
page navigation
form submissions
server validation
redirects
flash messages
authorization-driven page data
```

## Use React local state for

```text
accordion open state
drawer open state
modal visibility
toast animation
button loading microstate
tab state
client-only controls
```

Example:

```tsx
const [isOpen, setIsOpen] = useState(false);
const [submitting, setSubmitting] = useState(false);
```

The design-system component should not embed domain rules.

---

# 18. Accessibility Requirements

## 18.1 Keyboard

Every interactive control must be keyboard reachable.

Required:

```text
Tab
Shift+Tab
Enter
Space
Escape
Arrow keys where appropriate
```

---

## 18.2 Focus

Never remove focus visibility globally.

Bad:

```css
outline: none;
```

Preferred:

```css
outline: 2px solid var(--focus-token);
outline-offset: 2px;
```

---

## 18.3 Modal Focus

Confirmation dialogs require:

```text
focus trap
initial focus
Escape handling
focus restoration
```

---

## 18.4 Reduced Motion

Where users request reduced motion:

```css
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms;
    animation-iteration-count: 1;
    transition-duration: 0.01ms;
    scroll-behavior: auto;
  }
}
```

The semantic state transition must remain understandable without animation.

---

# 19. Motion System

## 19.1 Timing Tokens

```css
--motion-fast: 120ms;
--motion-normal: 180ms;
--motion-slow: 220ms;
--motion-toast: 300ms;
```

The exact prototype behaviors include:

```text
button press ≈ 120ms
admin drawer ≈ 220ms
customer drawer ≈ 220ms
toast entrance ≈ 180–300ms
skeleton cycle ≈ 1.3s
blocking logo spin ≈ 1.0s per rotation
```

---

## 19.2 Easing

Default:

```css
cubic-bezier(.2,.8,.2,1)
```

Spring-like customer toast:

```css
cubic-bezier(.34,1.56,.64,1)
```

Use spring easing sparingly.

---

# 20. Do's and Don'ts

## 20.1 Admin Do

```text
Do use compact typography.
Do use borders to establish hierarchy.
Do use flat surfaces.
Do provide fast scanning.
Do use stable tables on desktop.
Do transform tables into row-cards on mobile.
Do keep primary actions visually dominant.
Do use status badges with indicator dots.
Do use shop context prominently.
```

## 20.2 Admin Don't

```text
Do not turn every panel into an elevated card.
Do not use oversized typography.
Do not use rounded 100px buttons.
Do not use gradients as the default surface treatment.
Do not hide critical status in icon-only controls.
Do not use emojis.
Do not use browser confirm dialogs.
```

---

## 20.3 Customer Do

```text
Do use expressive hierarchy.
Do use rounded surfaces.
Do use the blue gradient environment.
Do make CTAs pill-shaped.
Do use floating action affordances.
Do use bottom task navigation on mobile.
Do make the most important action obvious.
Do preserve generous visual breathing room.
```

## 20.4 Customer Don't

```text
Do not copy the dense admin table style.
Do not use rectangular admin buttons.
Do not flatten every surface.
Do not use a persistent desktop taskbar.
Do not make the FAB compete with destructive actions.
Do not introduce random colors outside semantic tokens.
Do not use emojis.
```

---

# 21. Content and Interaction Writing

The visual system is only effective when text is equally structured.

## Labels

Prefer:

```text
Expense title
Amount
Phone number
Preferred branch
```

over:

```text
Please enter expense title
Your amount goes here
```

## Buttons

Prefer verbs:

```text
Save expense
New repair job
View invoice
Reorder
Delete shop
```

## Destructive confirmation

Pattern:

```text
Are you sure?
[Consequence]
[Cancel] [Destructive action]
```

The consequence should describe what changes and whether it can be undone.

---

# 22. Design Tokens for Status

A shared status contract avoids inconsistent semantics across modules.

```text
INFO / ACTIVE
  blue
  icon: bi-info-circle / relevant contextual icon

SUCCESS
  green
  icon: bi-check-circle / bi-check-lg

WARNING
  yellow
  icon: bi-exclamation-triangle

DANGER
  red
  icon: bi-x-circle / bi-trash3

NEUTRAL
  grey
  icon: optional
```

Status names should be business-state words rather than visual-state words.

Good:

```text
In progress
Awaiting parts
Ready for collection
Completed
```

Bad:

```text
Blue
Yellow
Green
```

---

# 23. Design-System State Matrix

Each interactive component should define at least:

```text
default
hover
active
focus
disabled
loading
error
success
```

For overlays:

```text
closed
opening
open
closing
```

For async content:

```text
idle
loading
loaded
empty
error
refreshing
```

---

# 24. Empty States

The prototype primarily demonstrates populated data but the production system requires a deterministic empty-state pattern.

## Admin

Use:

```text
neutral icon
short title
one-line explanation
primary action if applicable
```

Keep it compact.

## Customer

Use:

```text
friendly illustration/icon
short explanation
single obvious action
```

Do not introduce emojis. Use Bootstrap Icons or an approved brand illustration.

---

# 25. Error States

## Admin page error

Prefer:

```text
page-level alert/banner
retry action
```

## Customer page error

Prefer:

```text
friendly inline feedback
retry action
```

Errors must not destroy the current layout.

---

# 26. Density Rules by Theme

| Attribute | Admin | Customer |
|---|---|---|
| Default UI density | High | Medium |
| Base radius | 4–8px | 14–22px |
| Primary button | 6px | 100px |
| Main surfaces | Flat | Floating |
| Shadows | Minimal | Frequent but soft |
| Typography | Compact | Expressive |
| Navigation | Sidebar | Sidebar + mobile taskbar |
| Mobile table | Row cards | Lists/cards |
| Forms | Boxed | Underline |
| Toast | Top-right | Bottom-center |
| Modal mobile | Bottom sheet | Bottom sheet |
| Background | Solid | Gradient |

---

# 27. Prototype-to-Production Mapping

The supplied prototype demonstrates the following production contracts:

```text
Admin:
  220px sidebar
  64px collapsed rail
  52px topbar
  dense tables
  mobile row-card transformation
  compact 6px buttons
  flat forms
  top-right toasts
  380px desktop confirmation modal

Customer:
  white master shell
  dark 1px shell outline
  30px shell radius
  blue gradient page
  22px hero/drawer radius
  100px pills
  floating FAB
  62px mobile taskbar
  50px center action
  bottom-center pill toast
  bouncy toast entrance
```

These are not generic theme suggestions. They are the design contracts demonstrated by the prototype.

---

# 28. CSS Token File Blueprint (`tokens.css`)

The following is the recommended production token organization.

```css
/* =========================================================
   AfProsPos Design System Tokens
   tokens.css
   ========================================================= */

:root {
  /* ---------- Primitive colors ---------- */
  --color-white: #FFFFFF;
  --color-black: #101828;

  --color-blue-600: #1E56E8;
  --color-blue-050: #EAF0FF;

  --color-yellow-500: #F5A623;
  --color-yellow-050: #FFF6E5;
  --color-yellow-800: #9A5B00;

  --color-red-600: #D92D20;
  --color-red-050: #FEF0EE;

  --color-green-600: #16A34A;
  --color-green-050: #EAF8EE;

  /* ---------- Neutral ---------- */
  --neutral-025: #FAFBFC;
  --neutral-050: #F5F6F8;
  --neutral-100: #F2F3F6;
  --neutral-200: #E4E7EC;
  --neutral-300: #D0D5DD;
  --neutral-400: #98A2B3;
  --neutral-600: #667085;
  --neutral-900: #101828;

  /* ---------- Typography ---------- */
  --font-admin:
    "Inter",
    system-ui,
    sans-serif;

  --font-customer-display:
    "Plus Jakarta Sans",
    system-ui,
    sans-serif;

  --font-customer-ui:
    "Inter",
    system-ui,
    sans-serif;

  --font-mono:
    "JetBrains Mono",
    Consolas,
    monospace;

  --font-regular: 400;
  --font-medium: 500;
  --font-semibold: 600;
  --font-bold: 700;
  --font-extrabold: 800;

  /* ---------- Type ---------- */
  --text-xs: 11px;
  --text-sm: 12px;
  --text-base: 13px;
  --text-md: 14px;
  --text-lg: 16px;
  --text-xl: 19px;
  --text-2xl: 22px;
  --text-3xl: 28px;
  --text-4xl: 34px;

  /* ---------- Line height ---------- */
  --leading-xs: 16px;
  --leading-sm: 17px;
  --leading-base: 19px;
  --leading-md: 20px;
  --leading-lg: 22px;
  --leading-xl: 26px;
  --leading-2xl: 28px;
  --leading-3xl: 34px;
  --leading-4xl: 40px;

  /* ---------- Spacing ---------- */
  --space-0: 0;
  --space-1: 4px;
  --space-2: 8px;
  --space-3: 12px;
  --space-4: 16px;
  --space-5: 20px;
  --space-6: 24px;
  --space-8: 32px;
  --space-10: 40px;
  --space-12: 48px;
  --space-16: 64px;
  --space-20: 80px;

  /* ---------- Motion ---------- */
  --motion-fast: 120ms;
  --motion-normal: 180ms;
  --motion-slow: 220ms;
  --motion-toast: 300ms;

  --motion-ease-standard: cubic-bezier(.2, .8, .2, 1);
  --motion-ease-spring: cubic-bezier(.34, 1.56, .64, 1);

  /* ---------- Shared (theme-agnostic) component tokens ---------- */
  /* §12.2 desktop confirmation modal width; the doc gives one universal
     value, not a per-theme one, so this lives outside the [data-theme] blocks. */
  --ui-modal-width: 380px;
  /* §12.3 mobile confirmation sheet radius (distinct from --a-radius-modal/
     --c-radius-drawer, which are for other surfaces). */
  --ui-radius-sheet: 20px;
  /* §13.2/§13.3 full-screen blocking wait: site-mark size and backdrop blur. */
  --ui-loader-size: 52px;
  --ui-loader-blur: 6px;
  /* §12.6 Customer toast max-width ("max ≈ 340px") */
  --ui-toast-width: 340px;

  /* ---------- Admin semantic tokens ---------- */
  --a-bg: #F5F6F8;
  --a-surface: #FFFFFF;
  --a-border: #E4E7EC;
  --a-border2: #D0D5DD;
  --a-text: #101828;
  --a-text2: #667085;
  --a-text3: #98A2B3;

  --a-blue: #1E56E8;
  --a-blue-soft: #EAF0FF;

  --a-yellow: #F5A623;
  --a-yellow-soft: #FFF6E5;
  --a-yellow-text: #9A5B00;

  --a-red: #D92D20;
  --a-red-soft: #FEF0EE;

  --a-green: #16A34A;
  --a-green-soft: #EAF8EE;

  --a-table-head: #FAFBFC;
  --a-hover: #F2F3F6;
  --a-focus: #C7D6FC;
  --a-border-warning: #F5D999;
  --a-border-danger: #F6C6C1;
  --a-border-success: #D5F0DD;
  --a-overlay: rgba(16, 20, 30, .44);
  --a-shadow-modal: 0 8px 24px rgba(16,20,30,.18);

  --a-radius-input: 4px;
  --a-radius-button: 6px;
  --a-radius-badge: 4px;
  --a-radius-card: 8px;
  --a-radius-modal: 14px;

  /* ---------- Customer semantic tokens ---------- */
  --c-page-start: #CFE3FF;
  --c-page-mid: #EAF3FF;
  --c-page-end: #FFFFFF;

  --c-text: #1B1F27;
  --c-text2: #636B78;

  --c-blue: #2F6FED;
  --c-blue-soft: #E7EFFF;

  --c-yellow: #FFC845;
  --c-yellow-soft: #FFF6DF;
  --c-yellow-text: #9A5B00;

  --c-red: #E24444;
  --c-red-soft: #FDECEC;

  --c-green: #1FA463;
  --c-green-soft: #E8F8EF;

  --c-input-border: #D7E3F7;
  --c-divider: #EEF0F3;
  --c-muted-icon: #B7BEC9;
  --c-overlay: rgba(16, 20, 30, .40);
  --c-focus: #B9CFFF;

  --c-shadow-soft: 0 2px 10px rgba(16,20,30,.08);
  --c-shadow-strong: 0 10px 30px rgba(16,20,30,.16);

  --c-radius-item: 14px;
  --c-radius-card: 16px;
  --c-radius-hero: 22px;
  --c-radius-drawer: 22px;
  --c-radius-pill: 100px;

  --c-gradient-page:
    linear-gradient(
      180deg,
      #CFE3FF 0%,
      #EAF3FF 38%,
      #FFFFFF 72%
    );

  --c-gradient-hero:
    linear-gradient(
      135deg,
      #2F6FED,
      #1E56E8
    );

  /* ---------- Admin typography scale (Layer 4) ----------
     The prototype's literal sizes (§ throughout) read as too small for
     all-day desktop use once built and used for real — bumped ~8% over
     both the shared base scale and the prototype's own component
     baselines per direct user feedback, then bumped a further ~10% on
     top of that (Revision 7) after a side-by-side 100%-vs-110%-browser-
     zoom comparison confirmed the larger size reads better. Admin-only:
     the shared --text-xs/sm/base/... tokens (and Customer, which
     aliases them) are untouched. */
  --a-text-xs: 13px;
  --a-text-sm: 14px;
  --a-text-base: 15px;
  --a-text-md: 17px;
  --a-text-lg: 19px;
  --a-text-xl: 23px;

  /* ---------- Admin component tokens (Layer 4) ---------- */
  --a-sidebar-width: 242px;
  --a-sidebar-width-collapsed: 70px;
  --a-topbar-height: 57px;
  --a-drawer-width: 253px;
  --a-content-padding-x: 24px;
  --a-btn-text-size: 15px;
  --a-btn-padding-x: 14px;
  --a-btn-padding-y: 8px;
  --a-table-cell-text-size: 15px;
  --a-table-cell-padding-y: 11px;
  --a-table-cell-padding-x: 15px;
  /* prototype ".field input,.field select,.field textarea" baseline, bumped ~8% then a further ~10% (Revision 7, see typography scale note above) */
  --a-field-text-size: 15px;
  --a-field-padding-x: 11px;
  --a-field-padding-y: 9px;
  /* §8.1 "Navigation item" prototype baseline, bumped ~8% then a further ~10% (Revision 7) */
  --a-nav-item-padding-y: 8px;
  --a-nav-item-padding-x: 9px;
  --a-nav-item-gap: 11px;
  --a-nav-item-icon-width: 18px;
  --a-nav-item-text-size: 15px;

  /* ---------- Customer component tokens (Layer 4) ---------- */
  --c-header-height-desktop: 72px;
  --c-header-height-mobile: 60px;
  --c-taskbar-height: 62px;
  --c-content-bottom-padding: 86px;
  /* §8.5/§8.6: persistent 236px sidebar on desktop, 248px off-canvas
     drawer card on mobile. */
  --c-sidebar-width-desktop: 236px;
  --c-drawer-width-mobile: 248px;
  --c-btn-text-size: 12.5px;
  --c-btn-padding-x: 18px;
  --c-btn-padding-y: 11px;
  --c-caption-size: 13px;
  --c-field-text-size: 14px;
}

/* ---------- Semantic UI layer per theme ---------- */
[data-theme="admin"] {
  --ui-bg: var(--a-bg);
  --ui-surface: var(--a-surface);
  --ui-text: var(--a-text);
  --ui-text-secondary: var(--a-text2);
  --ui-border: var(--a-border);
  --ui-accent: var(--a-blue);
  --ui-focus: var(--a-focus);
  --ui-radius-control: var(--a-radius-button);

  --ui-info: var(--a-blue);
  --ui-info-soft: var(--a-blue-soft);
  --ui-success: var(--a-green);
  --ui-success-soft: var(--a-green-soft);
  --ui-warning: var(--a-yellow);
  --ui-warning-soft: var(--a-yellow-soft);
  --ui-warning-text: var(--a-yellow-text);
  --ui-danger: var(--a-red);
  --ui-danger-soft: var(--a-red-soft);

  --ui-overlay: var(--a-overlay);
  --ui-shadow-modal: var(--a-shadow-modal);

  --ui-skeleton-base: var(--a-hover);
  --ui-skeleton-highlight: var(--a-surface);

  /* Admin-only type-scale bump so every bare text-xs/sm/base/md/lg/xl
     utility used anywhere inside the Admin shell picks it up
     automatically, not just the explicit text-admin-* utilities — CSS
     custom properties resolve per the DOM cascade, so overriding the
     shared tokens here (Customer is untouched) reaches every Tailwind
     utility built on top of them without hunting down every call site.
     See the --a-text-* definitions above for the numbers. */
  --text-xs: var(--a-text-xs);
  --text-sm: var(--a-text-sm);
  --text-base: var(--a-text-base);
  --text-md: var(--a-text-md);
  --text-lg: var(--a-text-lg);
  --text-xl: var(--a-text-xl);
}

[data-theme="customer"] {
  --ui-bg: var(--c-page-mid);
  --ui-surface: #FFFFFF;
  --ui-text: var(--c-text);
  --ui-text-secondary: var(--c-text2);
  --ui-border: var(--c-input-border);
  --ui-accent: var(--c-blue);
  --ui-focus: var(--c-focus);
  --ui-radius-control: var(--c-radius-pill);

  --ui-info: var(--c-blue);
  --ui-info-soft: var(--c-blue-soft);
  --ui-success: var(--c-green);
  --ui-success-soft: var(--c-green-soft);
  --ui-warning: var(--c-yellow);
  --ui-warning-soft: var(--c-yellow-soft);
  --ui-warning-text: var(--c-yellow-text);
  --ui-danger: var(--c-red);
  --ui-danger-soft: var(--c-red-soft);

  --ui-overlay: var(--c-overlay);
  --ui-shadow-modal: var(--c-shadow-strong);

  --ui-skeleton-base: var(--c-divider);
  --ui-skeleton-highlight: #FFFFFF;
}

/* ---------- Mobile typography aliases ---------- */
@media (max-width: 639px) {
  :root {
    --text-xs: 10px;
    --text-sm: 11px;
    --text-base: 12px;
    --text-md: 13px;
    --text-lg: 15px;
    --text-xl: 18px;
    --text-2xl: 21px;
    --text-3xl: 26px;
    --text-4xl: 32px;
  }
}

/* ---------- Reduced motion ---------- */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

---

# 29. Example Production Button Component

```tsx
import type { ButtonHTMLAttributes, ReactNode } from "react";

type Props = ButtonHTMLAttributes<HTMLButtonElement> & {
  loading?: boolean;
  children: ReactNode;
  theme?: "admin" | "customer";
  tone?: "primary" | "neutral" | "warning" | "danger";
};

export function AppButton({
  loading = false,
  children,
  theme = "admin",
  tone = "primary",
  disabled,
  ...props
}: Props) {
  const classes = [
    theme === "admin" ? "btn" : "btn-c",
    theme === "admin" ? `btn-${tone}` : `btn-c-${tone}`,
  ].join(" ");

  return (
    <button
      {...props}
      disabled={disabled || loading}
      className={classes}
      aria-busy={loading}
    >
      {loading ? (
        <>
          <span className="spin" aria-hidden="true" />
          <span>Please wait…</span>
        </>
      ) : (
        children
      )}
    </button>
  );
}
```

The component owns presentation state only. It does not know whether a payment is valid, a repair can be cancelled, or an inventory reservation exists.

---

# 30. Example Responsive Admin Table Contract

```tsx
<ResponsiveDataTable
  breakpoint="640px"
  columns={[
    { key: "device", label: "Device" },
    { key: "customer", label: "Customer" },
    { key: "status", label: "Status" },
    { key: "technician", label: "Technician" },
    { key: "cost", label: "Cost" },
    { key: "updated", label: "Updated" },
  ]}
  mobilePrimaryField="device"
  mobileStatusField="status"
  mobileDetails={[
    "customer",
    "technician",
    "cost",
    "updated",
  ]}
/>
```

This ensures the responsive transformation is encoded once instead of hand-built independently on every page.

---

# 31. Example Confirmation Flow

```tsx
const [confirming, setConfirming] = useState(false);

<ConfirmDialog
  open={confirming}
  kind="danger"
  title="Delete this repair job?"
  body="This action cannot be undone."
  onCancel={() => setConfirming(false)}
  onConfirm={handleDelete}
/>
```

The same component should automatically switch from:

```text
desktop centered modal
```

to:

```text
mobile bottom sheet
```

at the responsive breakpoint.

---

# 32. Design QA Checklist

Every production screen should pass:

## Visual

```text
[ ] Correct theme tokens
[ ] No raw hex outside token files
[ ] Correct typography family
[ ] Correct radius scale
[ ] Correct density
[ ] Correct status colors
[ ] No unintended shadows
```

## Interaction

```text
[ ] Buttons have hover/active/focus/disabled states
[ ] Submit buttons disable immediately
[ ] Async actions expose progress
[ ] Destructive operations use confirmation
[ ] Drawer/modal focus is managed
[ ] Toast feedback is shown where appropriate
```

## Responsive

```text
[ ] Works below 640px
[ ] Admin table becomes row cards
[ ] Admin sidebar becomes drawer
[ ] Customer drawer works
[ ] Customer taskbar appears
[ ] Customer FAB becomes center taskbar action
[ ] Forms stack to one column
```

## Accessibility

```text
[ ] Keyboard reachable
[ ] Focus visible
[ ] Icon-only controls have aria-label
[ ] Status does not rely on color alone
[ ] Reduced motion supported
[ ] Modal focus trap implemented
```

---

# 33. Final Design-System Invariants

The following are non-negotiable.

```text
1. AfProsPos has two intentionally different interface identities.

2. Admin is compact, flat, structured, operational, and border-driven.

3. Customer is rounded, expressive, floating, and consumer-oriented.

4. The official palette is blue, yellow, black, and white.

5. Red and green are strictly semantic.

6. All production colors originate from design tokens.

7. No raw hex values are permitted in application components.

8. Admin uses Inter as the primary UI family.

9. Customer uses Plus Jakarta Sans for headings/branding and Inter for utility text.

10. JetBrains Mono/Consolas is used for technical identifiers and precision values.

11. Bootstrap Icons are the only UI icon system.

12. Emojis are prohibited.

13. Admin desktop sidebar width is 220px.

14. Admin collapsed sidebar width is 64px.

15. Admin topbar height is 52px.

16. Customer desktop shell uses a white framed container with a dark 1px border and 30px radius.

17. Customer mobile uses a 62px bottom taskbar.

18. The customer primary mobile action is a 50px circular center action overlapping the taskbar.

19. Forms never require decorative elevated cards.

20. Admin desktop forms use a maximum width of 640px.

21. Admin desktop forms default to two columns.

22. Customer inputs use the underline model.

23. Admin buttons use compact 6px radii.

24. Customer buttons use pill-shaped 100px radii.

25. Native browser confirm dialogs are prohibited.

26. Destructive and warning actions require the design-system confirmation modal/sheet.

27. Admin toasts are top-right white cards with colored left borders.

28. Customer toasts are bottom-centered filled capsules with spring entrance.

29. Skeletons preserve content dimensions.

30. Full-screen blocking waits use the frosted overlay + rotating site mark.

31. Tables become accordion row-cards below 640px.

32. Status badges contain both a label and an indicator dot.

33. Loading buttons preserve their dimensions.

34. Motion is functional and must respect reduced-motion preferences.

35. Component styling belongs in centralized design-system components rather than repeated page-specific CSS.
```

---

# 34. Implementation Source Note

This document is based on the supplied working AfProsPos HTML/CSS prototype, including its concrete theme variables, typography choices, Bootstrap Icon usage, responsive breakpoints, shell dimensions, table-to-card behavior, form styling, button states, modal behavior, toast treatments, skeleton animation, full-screen loader, and customer/admin-specific navigation models.

The prototype itself contains a small amount of demo infrastructure (theme/viewport toggles and hard-coded preview data). Those demonstration mechanics are not product requirements. The visual and interaction behaviors they expose are the source of truth that this document turns into reusable production tokens and component contracts.

The design system should therefore be implemented as:

```text
tokens.css
   ↓
Tailwind token mappings
   ↓
Reusable React design-system primitives
   ↓
Theme-specific compositions
   ↓
Inertia page layouts
   ↓
Domain-specific screens
```

The goal is to prevent individual pages from inventing their own spacing, radii, colors, feedback patterns, or responsive behavior.