---
paths:
  - 'app/Http/Controllers/Admin/SalesController.php,app/Http/Requests/Admin/CreateCheckoutRequest.php,resources/js/Pages/Admin/Sales/**'
---

# Sales

## Identify concurrent checkout sessions
Starting an additional cashier checkout must show the customer/walk-in form before creating the record. Require either a selected customer or a walk-in name so open checkout sessions remain distinguishable; never create a blank concurrent checkout directly from the active cart.
