<?php

namespace Tests\Feature\Disputes;

use App\Enums\PricingType;
use App\Enums\ServiceStatus;
use App\Enums\TransactionState;
use App\Models\Category;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DisputeEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_party_can_upload_evidence_when_opening_a_dispute(): void
    {
        Storage::fake('local');

        [$order, $buyer] = $this->order();

        $this->actingAs($buyer)
            ->post(route('orders.dispute', $order), [
                'reason' => 'Delivery issue',
                'message' => 'The delivered files were incomplete.',
                'evidence' => UploadedFile::fake()->create(
                    'contract.pdf',
                    120,
                    'application/pdf',
                ),
            ])
            ->assertRedirect();

        $dispute = Dispute::query()->sole();

        $this->assertNotNull($dispute->evidence_path);
        $this->assertSame('contract.pdf', $dispute->evidence_original_name);
        Storage::disk('local')->assertExists($dispute->evidence_path);

        $this->actingAs($buyer)
            ->get(route('disputes.evidence', $dispute))
            ->assertOk()
            ->assertDownload('contract.pdf');
    }

    public function test_only_the_parties_and_admins_can_download_evidence(): void
    {
        Storage::fake('local');

        [$order, $buyer] = $this->order();

        $this->actingAs($buyer)->post(route('orders.dispute', $order), [
            'reason' => 'Scope issue',
            'message' => 'The scope was not delivered.',
            'evidence' => UploadedFile::fake()->create(
                'brief.pdf',
                50,
                'application/pdf',
            ),
        ]);

        $dispute = Dispute::query()->sole();

        $outsider = User::factory()->buyer()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($outsider)
            ->get(route('disputes.evidence', $dispute))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('disputes.evidence', $dispute))
            ->assertOk();
    }

    public function test_invalid_evidence_files_are_rejected(): void
    {
        Storage::fake('local');

        [$order, $buyer] = $this->order();

        $this->actingAs($buyer)
            ->post(route('orders.dispute', $order), [
                'reason' => 'Delivery issue',
                'message' => 'This message is long enough to pass validation.',
                'evidence' => UploadedFile::fake()->create(
                    'malware.exe',
                    10,
                    'application/x-msdownload',
                ),
            ])
            ->assertSessionHasErrors('evidence');

        $this->assertDatabaseCount('disputes', 0);
    }

    /**
     * @return array{0: Order, 1: User, 2: User}
     */
    private function order(): array
    {
        $category = Category::query()->create([
            'name' => 'Design',
            'slug' => 'design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $buyer = User::factory()->buyer()->create();
        $seller = User::factory()->seller()->create();

        $service = Service::query()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Landing page',
            'slug' => 'landing-page-'.uniqid(),
            'description' => 'A landing page.',
            'deliverables' => ['Page'],
            'pricing_type' => PricingType::Fixed,
            'price_minor' => 50000,
            'currency' => 'USD',
            'delivery_days' => 7,
            'revisions' => 2,
            'negotiation_enabled' => true,
            'status' => ServiceStatus::Published,
            'published_at' => now(),
        ]);

        $transaction = Transaction::factory()->create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'service_id' => $service->id,
            'state' => TransactionState::Paid,
        ]);

        $order = Order::query()->create([
            'transaction_id' => $transaction->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount_minor' => 50000,
            'currency' => 'USD',
            'status' => 'in_progress',
            'paid_at' => now(),
            'started_at' => now(),
        ]);

        return [$order, $buyer, $seller];
    }
}
