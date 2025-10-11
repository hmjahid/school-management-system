<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RefundControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;
    protected $payment;
    protected $refund;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        
        $this->user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create a completed payment
        $this->payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 1000.00,
            'currency' => 'BDT',
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_method' => 'test_gateway',
            'transaction_id' => 'TXN' . uniqid(),
        ]);

        // Create a test refund
        $this->refund = Refund::create([
            'payment_id' => $this->payment->id,
            'user_id' => $this->user->id,
            'processed_by' => $this->admin->id,
            'amount' => 500.00,
            'currency' => 'BDT',
            'status' => 'completed',
            'reason' => 'Test refund',
            'transaction_id' => 'R-' . uniqid(),
        ]);

        // Assign admin role
        $this->admin->assignRole('admin');
    }

    /** @test */
    public function it_lists_refunds()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/refunds');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'payment_id',
                        'amount',
                        'status',
                        'created_at',
                        'user' => ['id', 'name', 'email'],
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    /** @test */
    public function it_creates_a_refund()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/payments/{$this->payment->id}/refunds", [
                'amount' => 300.00,
                'reason' => 'Customer requested refund',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'payment_id',
                    'amount',
                    'status',
                    'reason',
                    'created_at',
                ]
            ]);

        $this->assertDatabaseHas('refunds', [
            'payment_id' => $this->payment->id,
            'amount' => 300.00,
            'reason' => 'Customer requested refund',
        ]);
    }

    /** @test */
    public function it_shows_refund_details()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/refunds/{$this->refund->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->refund->id,
                    'amount' => number_format($this->refund->amount, 2, '.', ''),
                    'status' => 'completed',
                ]
            ]);
    }

    /** @test */
    public function it_processes_a_pending_refund()
    {
        $pendingRefund = Refund::create([
            'payment_id' => $this->payment->id,
            'user_id' => $this->user->id,
            'processed_by' => $this->admin->id,
            'amount' => 200.00,
            'currency' => 'BDT',
            'status' => 'pending',
            'reason' => 'Pending test refund',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/refunds/{$pendingRefund->id}/process", [
                'transaction_id' => 'R-' . uniqid(),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $pendingRefund->id,
                    'status' => 'completed',
                ]
            ]);
    }

    /** @test */
    public function it_cancels_a_pending_refund()
    {
        $pendingRefund = Refund::create([
            'payment_id' => $this->payment->id,
            'user_id' => $this->user->id,
            'processed_by' => $this->admin->id,
            'amount' => 200.00,
            'currency' => 'BDT',
            'status' => 'pending',
            'reason' => 'Pending test refund',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/refunds/{$pendingRefund->id}/cancel", [
                'reason' => 'Customer changed mind',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $pendingRefund->id,
                    'status' => 'cancelled',
                ]
            ]);

        $this->assertDatabaseHas('refunds', [
            'id' => $pendingRefund->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function it_gets_refund_statistics()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/refunds/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_refunded',
                'pending_count',
                'completed_count',
                'failed_count',
                'refunds_by_month' => [],
                'refunds_by_status' => [],
                'refunds_by_payment_method' => [],
            ]);
    }

    /** @test */
    public function it_validates_refund_creation()
    {
        // Test required fields
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/payments/{$this->payment->id}/refunds", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'reason']);

        // Test amount validation
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/payments/{$this->payment->id}/refunds", [
                'amount' => 0,
                'reason' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);

        // Test amount exceeding available balance
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/payments/{$this->payment->id}/refunds", [
                'amount' => 2000.00, // More than the payment amount
                'reason' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function it_prevents_unauthorized_access()
    {
        // Regular user can't view all refunds
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/refunds');
        $response->assertStatus(403);

        // Regular user can view their own refunds
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/refunds/{$this->refund->id}");
        $response->assertStatus(200);

        // Regular user can't create refunds
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/payments/{$this->payment->id}/refunds", [
                'amount' => 100.00,
                'reason' => 'Test',
            ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function it_handles_refund_processing_errors()
    {
        $pendingRefund = Refund::create([
            'payment_id' => $this->payment->id,
            'user_id' => $this->user->id,
            'processed_by' => $this->admin->id,
            'amount' => 200.00,
            'currency' => 'BDT',
            'status' => 'processing', // Already processing
            'reason' => 'Test refund',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/refunds/{$pendingRefund->id}/process");

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Only pending refunds can be processed',
            ]);
    }
}
