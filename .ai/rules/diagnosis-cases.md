---
paths:
  - 'app/Http/Controllers/Admin/DiagnosisCasesController.php,resources/js/Pages/Admin/DiagnosisCases/**'
---

# Diagnosis Cases

## Diagnosis technicians follow the selected shop
Diagnosis technician options must be active staff with the Technician role and an active grant for the diagnosis case's selected shop. Reload options whenever the intake shop changes, clear stale selections, and enforce the same scope server-side on create and reassignment.
