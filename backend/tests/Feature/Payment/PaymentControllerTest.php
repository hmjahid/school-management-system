<?php

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;
    protected $payment;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->seed(RolePermissionSeeder::class);
        
        // Create test users
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
        
        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('user');
        
        // Seed payment gateways
        $this->seed(\Database\Seeders\PaymentGatewaySeeder::class);
        
        // Create a test payment
        $this->payment = Payment::factory()->create([
            'created_by' => $this->regularUser->id,
            'updated_by' => $this->regularUser->id,
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_endpoints()
    {
        $response = $this->getJson('/api/payments');
        $response->assertStatus(401);
        
        $response = $this->getJson('/api/payments/' . $this->payment->id);
        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_list_own_payments()
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/payments');
            
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'invoice_number',
                        'amount',
                        'payment_status',
                        'created_at',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    /** @test */
    public function admin_can_view_any_payment()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/payments/' . $this->payment->id);
            
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->payment->id,
                    'invoice_number' => $this->payment->invoice_number,
                ]
            ]);
    }

    /** @test */
    public function user_can_initiate_payment()
    {
        $gateway = PaymentGateway::where('code', 'bkash')->first();
        
        $response = $this->actingAs($this->regularUser)
            ->postJson('/api/payments/initiate', [
                'gateway' => 'bkash',
                'amount' => 1000,
                'currency' => 'BDT',
                'paymentable_type' => 'tuition',
                'paymentable_id' => 1,
                'description' => 'Test payment',
            ]);
            
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Payment initiated successfully',
            ]);
    }

    /** @test */
    public function it_validates_payment_initiation()
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson('/api/payments/initiate', [
                // Missing required fields
            ]);
            
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'gateway', 'amount', 'currency', 'paymentable_type', 'paymentable_id'
            ]);
    }

    /** @test */
    public function user_can_check_payment_status()
    {
        $response = $this->getJson('/api/payments/status/' . $this->payment->invoice_number);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->payment->id,
                    'invoice_number' => $this->payment->invoice_number,
                ]
            ]);
    }

    /** @test */
    public function admin_can_update_payment_status()
    {
        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/payments/{$this->payment->id}/status", [
                'status' => 'completed',
                'notes' => 'Payment verified manually',
            ]);
            
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'payment_status' => 'completed',
                ]
            ]);
            
        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'payment_status' => 'completed',
        ]);
    }

    /** @test */
    public function admin_can_record_offline_payment()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/payments/record-offline', [
                'gateway' => 'cash',
                'amount' => 1500,
                'currency' => 'BDT',
                'paymentable_type' => 'tuition',
                'paymentable_id' => 1,
                'payment_date' => now()->format('Y-m-d'),
                'description' => 'Offline cash payment',
            ]);
            
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Offline payment recorded successfully',
            ]);
            
        $this->assertDatabaseHas('payments', [
            'payment_method' => 'cash',
            'amount' => 1500,
            'payment_status' => 'completed',
        ]);
    }

    /** @test */
    public function admin_can_export_payments()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/payments/export?format=csv');
            
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
