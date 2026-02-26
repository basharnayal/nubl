# System Wallet – Donation & Provider Approval Flow

## Overview

The **system wallet** (Ewallet with `owner_type = SYSTEM`) is the city fund. It receives donations from donors and pays out to providers when they approve requests with city fund.

---

## Flows

### 1. Donor adds funds

1. Donor initiates donation → payment gateway (e.g. MyFatoorah)
2. Donor completes payment
3. System verifies payment (e.g. `DonationService::confirmDonation()`)
4. **→ Call `SystemWalletService::addFundsFromDonation()`** to credit the system wallet

### 2. Provider actions on a request

| Action | Effect on city fund | Effect on provider wallet |
|--------|---------------------|---------------------------|
| **Adopt (My Fund)** | No change | No change (provider covers cost themselves) |
| **Approve (City Fund)** | Deducts `reserved_amount` | Credits provider with `reserved_amount` |

When provider clicks **Approve (City Fund)**:
1. Check system wallet has sufficient balance
2. Deduct amount from system wallet
3. Credit provider wallet
4. Create `FundTransaction` records (OUT from system, IN to provider, source: PAYOUT)
5. Update request status to PROVIDER_APPROVED, funding_source to CITY_FUND

---

## SystemWalletService

**Location:** `app/Http/Services/SystemWalletService.php`

| Method | Description |
|--------|-------------|
| `addFundsFromDonation($amount, $donorId, $paymentId?)` | Increments system wallet balance and creates a `FundTransaction` record (source: DONATION, direction: IN) |
| `getSystemWallet()` | Returns the system's default Ewallet |
| `hasSufficientBalance($amount)` | Returns true if system wallet balance ≥ amount |
| `transferToProviderForRequest($request)` | Deducts from system wallet, credits provider wallet, creates audit records |

---

## Integration

In `DonationService::confirmDonation()`, after payment verification and before/after `CityFund::increment()`:

```php
app(SystemWalletService::class)->addFundsFromDonation(
    $donation->amount,
    $donation->user_id,
    $donation->id, // or payment_id when payments table exists
);
```

The call is currently **commented out** in `DonationService`. Uncomment when ready to integrate with the wallet flow.

---

## Data Model

- **Ewallet** (`owner_type = SYSTEM`, `owner_id = null`) – holds pooled donation funds
- **FundTransaction** – audit trail: each donation creates a record with `source = DONATION`, `direction = IN`
