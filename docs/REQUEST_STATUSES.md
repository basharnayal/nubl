# Core Request Statuses

## Overview

Request statuses define the lifecycle of a recipient's order from creation to completion.

## Status Table

| # | Status | Description |
|---|--------|--------------|
| 1 | `REQUESTED` | Recipient created the request |
| 2 | `APPROVED` | Provider adopted (funding_source `PROVIDER_ADOPTION`); City Fund not affected |
| 3 | `REDEEMABLE` | Provider accepted with City Fund (`CITY_FUND`); deducted from city fund |
| 4 | `REJECTED` | Provider rejected the request |
| 5 | `FULFILLED` | Order completed (no transition logic; status only) |

## Flow

```
REQUESTED (recipient creates)
    ├── Adopt   → APPROVED (PROVIDER_ADOPTION)
    ├── Accept  → REDEEMABLE (CITY_FUND)
    └── Reject  → REJECTED
```

## Weekly Allowance (Recipient)

**remaining_limit = 400 - weekly_used**

Where `weekly_used` = sum of `(price_snapshot × quantity)` for requests with status **REQUESTED**, **REDEEMABLE**, or **FULFILLED** (current week). APPROVED and REJECTED do not count.

---

## Missing / TODO

- **`is_provider_donation`** — Indicates if the fulfillment was a provider donation (adoption) or from city fund, to know whether to deduct from city fund or not. May need to be added as an explicit field.

---

## Reference

- **Model:** `App\Models\Request` – defines `Request::STATUSES` constant
- **Controller:** `ProviderRequestController` – handles adopt, approve, reject actions
- **Allowance:** `RecipientAllowanceService::getWeeklyUsed()`, `getRemainingLimit()`
- **Changelog:** `docs/CHANGELOG_REQUEST_AND_WALLET.md` – summary of all request/wallet changes
