<?php

namespace Tests\Feature\Provider;

use App\Models\OrderProof;
use App\Models\OrderRedemption;
use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Notifications\RequestStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderProofUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $provider;

    private User $recipient;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

        $this->provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->provider->assignRole('provider');

        $this->recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->recipient->assignRole('recipient');
    }

    #[Test]
    public function provider_can_upload_fulfillment_proof_and_mark_provider_adoption_fulfilled(): void
    {
        Notification::fake();

        [$request, $redemption] = $this->createRedeemedRequest('PROVIDER_ADOPTION');

        $this->actingAs($this->provider)
            ->post(route('provider.proof.store', $redemption), [
                'proof_file' => UploadedFile::fake()->image('handoff.jpg'),
            ])
            ->assertRedirect(route('provider.requests.index'));

        $proof = OrderProof::where('order_redemption_id', $redemption->id)->first();

        $this->assertNotNull($proof);
        $this->assertTrue($proof->is_provider_donation);
        $this->assertNotNull($proof->fulfilled_at);
        Storage::disk('local')->assertExists($proof->proof_url);

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status' => 'FULFILLED',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'request.fulfillment_proof_uploaded',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'notification.sent',
        ]);

        Notification::assertSentTo(
            $this->recipient,
            RequestStatusChangedNotification::class,
            fn (RequestStatusChangedNotification $notification) => $notification->status === 'FULFILLED'
                && $notification->request->id === $request->id
        );
    }

    #[Test]
    public function provider_cannot_upload_proof_for_another_provider_redemption(): void
    {
        [$request, $redemption] = $this->createRedeemedRequest('CITY_FUND');

        $otherProvider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $otherProvider->assignRole('provider');

        $this->actingAs($otherProvider)
            ->post(route('provider.proof.store', $redemption), [
                'proof_file' => UploadedFile::fake()->image('not-yours.jpg'),
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('order_proofs', [
            'order_redemption_id' => $redemption->id,
        ]);
        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status' => 'REDEEMABLE',
        ]);
    }

    #[Test]
    public function duplicate_proof_upload_deletes_newly_uploaded_file(): void
    {
        [$request, $redemption] = $this->createRedeemedRequest('CITY_FUND');

        Storage::disk('local')->put("private/proofs/{$redemption->id}/existing.jpg", 'existing-proof');
        OrderProof::create([
            'order_redemption_id' => $redemption->id,
            'proof_url' => "private/proofs/{$redemption->id}/existing.jpg",
            'is_provider_donation' => false,
            'fulfilled_at' => now(),
        ]);

        $this->actingAs($this->provider)
            ->post(route('provider.proof.store', $redemption), [
                'proof_file' => UploadedFile::fake()->image('duplicate.jpg'),
            ])
            ->assertRedirect(route('provider.requests.index'));

        Storage::disk('local')->assertExists("private/proofs/{$redemption->id}/existing.jpg");
        $this->assertSame(
            ["private/proofs/{$redemption->id}/existing.jpg"],
            Storage::disk('local')->allFiles("private/proofs/{$redemption->id}")
        );
        $this->assertSame(1, OrderProof::where('order_redemption_id', $redemption->id)->count());
        $this->assertSame('REDEEMABLE', $request->fresh()->status);
    }

    /**
     * @return array{RequestModel, OrderRedemption}
     */
    private function createRedeemedRequest(string $fundingSource): array
    {
        $menuItem = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Meal',
            'price' => 25.00,
            'category' => 'Meals',
            'is_active' => true,
        ]);

        $request = RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 25.00,
            'status' => 'REDEEMABLE',
            'funding_source' => $fundingSource,
        ]);

        $request->items()->create([
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 25.00,
        ]);

        $redemption = OrderRedemption::create([
            'request_id' => $request->id,
            'provider_id' => $this->provider->id,
            'token_code' => hash('sha256', 'token-'.$request->id),
            'short_code_hash' => hash('sha256', 'short-'.$request->id),
            'token_ciphertext' => 'encrypted-token',
            'short_code_ciphertext' => 'encrypted-short',
            'ttl_minutes' => 180,
            'redeem_expires_at' => now()->addHour(),
            'status' => 'REDEEMED',
        ]);

        return [$request, $redemption];
    }
}
