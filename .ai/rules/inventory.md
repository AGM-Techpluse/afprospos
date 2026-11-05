---
paths:
  - 'domain/Inventory/**'
---

# Inventory

## Device conditions are inventory master data
Treat inventory item conditions as admin-managed entries with kind `condition`, not a hardcoded enum. Product, variant, stock-receipt, and CSV-import paths must validate against the master list and store the entry name exactly; condition renames propagate to inventory items and in-use conditions cannot be deleted.
