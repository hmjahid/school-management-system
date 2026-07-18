<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentStatusAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_creator_can_view_payment_status_page(): void
    {
        $spatieRole = Role::firstOrCreate(['name' => 'parent', 'guard_name' => 'web']);
        $roleRow = \App\Models\Role::firstOrCreate(['name' => 'parent', 'guard_name' => 'web']);

        $u1 = User::create(['name' => 'P1', 'email' => 'p1@example.com', 'password' => 'password', 'role_id' => $roleRow->id]);
        $u2 = User::create(['name' => 'P2', 'email' => 'p2@example.com', 'password' => 'password', 'role_id' => $roleRow->id]);
        $u1->assignRole($spatieRole);
        $u2->assignRole($spatieRole);

        $payment = Payment::create([
            'paymentable_type' => Payment::PURPOSE_TUITION,
            'paymentable_id' => 1,
            'amount' => 10,
            'paid_amount' => 0,
            'due_amount' => 10,
            'discount_amount' => 0,
            'fine_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 10,
            'payment_method' => 'bkash',
            'payment_status' => Payment::STATUS_PENDING,
            'payment_details' => [],
            'metadata' => [],
            'created_by' => $u1->id,
            'updated_by' => $u1->id,
        ]);

        $this->actingAs($u2)->get(route('site.payments.status', $payment))->assertStatus(403);
        $this->actingAs($u1)->get(route('site.payments.status', $payment))->assertOk();
    }
}
