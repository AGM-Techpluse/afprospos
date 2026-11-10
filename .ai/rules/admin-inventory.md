---
paths:
  - 'domain/Inventory/**,resources/js/Pages/Admin/Inventory/**'
---

# Admin Inventory

## Keep SKU rows distinct and label variants
Inventory catalog tables keep one row per sellable SKU because stock, price, and serialization belong to the SKU. Group sibling rows by product and identify them with their attribute label plus Variant X of Y; do not collapse sibling SKUs into one row.
