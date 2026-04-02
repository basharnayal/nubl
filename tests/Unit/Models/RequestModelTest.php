<?php

namespace Tests\Unit\Models;

use App\Models\OrderProof;
use App\Models\OrderRedemption;
use App\Models\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequestModelTest extends TestCase
{
    #[Test]
    public function recipient_can_cancel_only_when_status_is_requested(): void
    {
        $requested = new Request(['status' => 'REQUESTED']);
        $this->assertTrue($requested->isCancellableByRecipient());

        foreach (['APPROVED', 'REDEEMABLE', 'FULFILLED', 'CANCELLED', 'REJECTED'] as $status) {
            $r = new Request(['status' => $status]);
            $this->assertFalse($r->isCancellableByRecipient(), "Expected not cancellable for {$status}");
        }
    }

    #[Test]
    public function needs_provider_fulfillment_proof_when_redeemed_without_proof(): void
    {
        $request = Request::make(['status' => 'REDEEMABLE']);
        $redemption = OrderRedemption::make(['status' => 'REDEEMED']);
        $redemption->setRelation('proof', null);
        $request->setRelation('redemption', $redemption);

        $this->assertTrue($request->needsProviderFulfillmentProof());
    }

    #[Test]
    public function needs_provider_fulfillment_proof_is_false_when_not_redeemed(): void
    {
        $request = Request::make(['status' => 'REDEEMABLE']);
        $redemption = OrderRedemption::make(['status' => 'PENDING']);
        $redemption->setRelation('proof', null);
        $request->setRelation('redemption', $redemption);

        $this->assertFalse($request->needsProviderFulfillmentProof());
    }

    #[Test]
    public function needs_provider_fulfillment_proof_is_false_when_proof_exists(): void
    {
        $request = Request::make(['status' => 'REDEEMABLE']);
        $redemption = OrderRedemption::make(['status' => 'REDEEMED']);
        $redemption->setRelation('proof', OrderProof::make(['id' => 1]));
        $request->setRelation('redemption', $redemption);

        $this->assertFalse($request->needsProviderFulfillmentProof());
    }

    #[Test]
    public function is_reservation_active_reflects_status_list(): void
    {
        $active = new Request(['status' => 'REQUESTED']);
        $this->assertTrue($active->is_reservation_active);

        $inactive = new Request(['status' => 'CANCELLED']);
        $this->assertFalse($inactive->is_reservation_active);
    }
}
