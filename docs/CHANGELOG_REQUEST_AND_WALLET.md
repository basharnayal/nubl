# Changelog: Request Statuses, Allowance & Wallet

Summary of changes to request lifecycle, recipient allowance, and wallet/balance handling.

---

## 1. Request Status Changes

| Before | After | Description |
|--------|-------|-------------|
| `PROVIDER_APPROVED` | `APPROVED` | Status when provider adopts (pays from own pocket) |
| `PENDING` | `REQUESTED` | Initial status when recipient creates a request |
| `ADOPTED` | `APPROVED` | Merged into APPROVED; provider adopted = APPROVED + PROVIDER_ADOPTION |
| `PROVIDER_REJECTED` | `REJECTED` | Provider rejection status |

---

## 2. Core Request Flow

```
REQUESTED (recipient creates)
    ├── Adopt   → APPROVED (PROVIDER_ADOPTION, City Fund not affected)
    ├── Accept  → REDEEMABLE (CITY_FUND, deducted from city fund)
    └── Reject  → REJECTED
```

**Statuses:** REQUESTED, APPROVED, REDEEMABLE, REJECTED, FULFILLED  
See `docs/REQUEST_STATUSES.md`.

---

## 3. Recipient Weekly Allowance

**Formula:** `remaining_limit = 400 - weekly_used`

**weekly_used** = sum of `(price_snapshot × quantity)` for requests with status **REQUESTED**, **REDEEMABLE**, or **FULFILLED** (current week).

**Do NOT count:** APPROVED (provider adopted), REJECTED.

See `docs/RECIPIENT_WEEKLY_ALLOWANCE.md`, `RecipientAllowanceService`.

---

## 4. Wallet Balance (fund_transactions)

**Balance formula (all wallets):**  
`sum(amount) where direction='IN' minus sum(amount) where direction='OUT'`

**Implementation:**
- `FundTransactionObserver` — updates `ewallets.balance` on each new `FundTransaction` (IN → increment, OUT → decrement)
- `Ewallet::syncBalance()` — recalculates balance from transactions and updates the column
- `php artisan wallets:sync-balances` — syncs all wallet balances (for fixing existing data)
- `EwalletSeeder` — calls `syncBalance()` after creating initial FundTransaction
- Applies to **system (city fund)** and **provider** wallets

See `docs/SYSTEM_WALLET_DONATION_FLOW.md`.

---

## 5. AllowanceTestDataSeeder

Seeds:
- **REDEEMABLE:** Family meal package (85 SAR)
- **FULFILLED:** Rice & Chicken Combo (35 SAR), Vegetable Soup (15 SAR)
- **APPROVED:** Daily support order (25 SAR) — provider adopted, does NOT count

**weekly_used:** 85 + 35 + 15 = 135 SAR → **remaining = 265 SAR**

---

## 6. Provider UI

- "Adopt Request (My Fund)" → APPROVED
- "Accept (City Fund)" → REDEEMABLE (was "Approve (City Fund)")

---

## 7. Missing / TODO

| Item | Description |
|------|--------------|
| **`is_provider_donation`** | Indicates if the fulfillment was a provider donation (adoption) or from city fund. Used to know whether to deduct from city fund or not. Currently inferred from `funding_source` (`PROVIDER_ADOPTION` vs `CITY_FUND`); an explicit column may be needed for clarity. |
